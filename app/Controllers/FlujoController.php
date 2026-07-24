<?php
/**
 * app/Controllers/FlujoController.php
 * 
 * Controlador encargado de gestionar el Flujo de Caja diario del hotel.
 * Orquesta la apertura, cierre y auditoría de los turnos de caja, 
 * además de la gestión multidivisa y sincronización con otros módulos financieros.
 */
class FlujoController {
    /** @var PDO Conexión a la base de datos. */
    private PDO $pdo;
    private FlujoModel $model;
    private AuditoriaModel $audit;

    /**
     * Constructor del controlador.
     * 
     * @param PDO $pdo Conexión a la base de datos.
     */
    public function __construct(PDO $pdo) {
        require_once __DIR__ . '/../Models/FlujoModel.php';
        require_once __DIR__ . '/../Models/AuditoriaModel.php';
        
        $this->pdo = $pdo;
        $this->model = new FlujoModel($pdo);
        $this->audit = new AuditoriaModel($pdo);
    }

    /**
     * Obtiene las categorías configuradas para el flujo de caja.
     * 
     * @return array Lista de categorías activas (id, tipo, nombre).
     */
    public function categorias(): array {
        return $this->model->getCategorias();
    }

    /**
     * Lista los turnos de caja filtrados por mes, año y estado.
     * 
     * @param array $filtros Arreglo con 'mes', 'anio' y 'estado'.
     * @return array Lista de turnos con totales calculados en moneda base.
     */
    public function listar(array $filtros): array {
        $params = [
            'mes'    => $filtros['mes'] ?? date('n'),
            'anio'   => $filtros['anio'] ?? date('Y'),
            'estado' => $filtros['estado'] ?? 'todos'
        ];
        return $this->model->listar($params);
    }

    /**
     * Obtiene el detalle completo de un turno específico, incluyendo sus movimientos de ingreso y egreso.
     * 
     * @param int $id ID del flujo de caja.
     * @return array Resultado con 'ok' y 'data' (detalle + movimientos).
     */
    public function detalle(int $id): array {
        if ($id <= 0) return ['ok' => false, 'msg' => 'ID de flujo inválido'];
        
        $detalle = $this->model->getDetalle($id);
        if (!$detalle) return ['ok' => false, 'msg' => 'Flujo no encontrado o no existe'];

        return ['ok' => true, 'data' => $detalle];
    }

    /**
     * Guarda (crea o actualiza) un turno de caja y sus movimientos asociados.
     * Realiza validaciones de permisos y estados (borrador vs cerrado).
     * 
     * @param array $input Datos del encabezado del flujo y arreglos de 'ingresos' y 'egresos'.
     * @return array Resultado de la operación con el ID generado o mensaje de error.
     */
    public function guardar(array $input): array {
        $id    = (int)($input['id'] ?? 0);
        $fecha = $input['fecha'] ?? date('Y-m-d');
        $turno = $input['turno'] ?? '';

        if (empty($fecha) || empty($turno)) {
            return ['ok' => false, 'msg' => 'Fecha y Turno son requeridos'];
        }

        // Si ya existe un turno para esa fecha/turno (abierto o cerrado), redirigir directamente
        if ($id === 0 && $this->model->checkExisteTurno($fecha, $turno, 0)) {
            $existente = $this->model->getIdExistente($fecha, $turno);
            return ['ok' => true, 'msg' => 'El turno ya existe para esta fecha. Redirigiendo al registro...', 'data' => ['id' => $existente, 'existente' => true]];
        }

        // Validación de Horario Abierto: Si se intenta crear un turno nuevo, verificar si existe algún otro turno en borrador
        if ($id === 0) {
            $stmtAbierto = $this->pdo->prepare("SELECT id, turno, fecha FROM flujo_caja WHERE estado = 'borrador' ORDER BY id DESC LIMIT 1");
            $stmtAbierto->execute();
            $abierto = $stmtAbierto->fetch(PDO::FETCH_ASSOC);

            if ($abierto) {
                $turnoAbierto = mb_strtolower($abierto['turno']);
                $turnoIntento = mb_strtolower($turno);
                $fechaAbierto = date('d/m/Y', strtotime($abierto['fecha']));

                if ($turnoAbierto === $turnoIntento) {
                    $msg = "Existe un turno $turnoAbierto ($fechaAbierto) aún abierto. Para abrir el turno de hoy debes cerrar el anterior.";
                } else {
                    $msg = "Para abrir turno $turnoIntento debes cerrar turno $turnoAbierto";
                }

                return [
                    'ok' => false,
                    'msg' => $msg,
                    'data' => [
                        'turno_abierto' => true,
                        'abierto_id' => (int)$abierto['id'],
                        'abierto_turno' => $abierto['turno'],
                        'abierto_fecha' => $abierto['fecha'],
                        'intento_turno' => $turno
                    ]
                ];
            }
        }

        // Si es edición, evaluar si está cerrado/depositado
        if ($id > 0) {
            $actual = $this->model->getDetalle($id);
            if ($actual && $actual['estado'] !== 'borrador') {
                return ['ok' => false, 'msg' => 'No puedes editar un turno cerrado o depositado. Reabre el turno antes de corregirlo.'];
            }
        }

        $data = [
            'id'           => $id,
            'fecha'        => $fecha,
            'turno'        => $turno,
            'nota_entrega' => $input['nota_entrega'] ?? '',
            'usuario_id'   => $_SESSION['auth_id']
        ];

        try {
            $newId = $this->model->guardar($data, $input['ingresos'] ?? [], $input['egresos'] ?? []);
            
            $accion = ($id === 0) ? 'FLUJO_CREADO' : 'FLUJO_ACTUALIZADO';
            $msgAudit = ($id === 0) 
                ? "Abrió un nuevo turno de Caja: $fecha ($turno)" 
                : "Actualizó movimientos del turno de Caja: $fecha ($turno)";
            
            $this->registrarAuditoriaSeguro($accion, 'FINANZAS', $msgAudit);

            return ['ok' => true, 'msg' => 'Turno guardado correctamente', 'data' => ['id' => $newId]];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => 'Error al guardar el flujo: ' . $e->getMessage()];
        }
    }

    /**
     * Cierra oficialmente un turno de caja, bloqueando ediciones posteriores para cajeras.
     * 
     * @param int $id ID del flujo de caja.
     * @return array Mensaje de éxito o error.
     */
    public function cerrar(int $id): array {
        if ($id <= 0) return ['ok' => false, 'msg' => 'ID inválido'];
        
        $actual = $this->model->getDetalle($id);
        if (!$actual) return ['ok' => false, 'msg' => 'Flujo no encontrado'];
        if ($actual['estado'] !== 'borrador') return ['ok' => false, 'msg' => 'El flujo ya no está en borrador'];

        if ($this->model->cambiarEstado($id, 'cerrado')) {
            $this->registrarAuditoriaSeguro('FLUJO_CERRADO', 'FINANZAS', "Flujo ID $id cerrado.");
            return ['ok' => true, 'msg' => 'Turno cerrado correctamente'];
        }
        return ['ok' => false, 'msg' => 'No se pudo cerrar el turno'];
    }

    /**
     * Marca un flujo cerrado como "depositado", indicando que el efectivo ya fue entregado a administración/banco.
     * 
     * @param int $id ID del flujo de caja.
     * @return array Mensaje de éxito o error.
     */
    public function depositar(int $id): array {
        if ($id <= 0) return ['ok' => false, 'msg' => 'ID inválido'];
        
        $actual = $this->model->getDetalle($id);
        if (!$actual) return ['ok' => false, 'msg' => 'Flujo no encontrado'];
        if ($actual['estado'] !== 'cerrado') return ['ok' => false, 'msg' => 'Solo flujos cerrados se pueden depositar'];

        if ($this->model->cambiarEstado($id, 'depositado')) {
            $this->registrarAuditoriaSeguro('FLUJO_DEPOSITADO', 'FINANZAS', "Flujo ID $id marcado depositado.");
            return ['ok' => true, 'msg' => 'Dinero del turno depositado correctamente'];
        }
        return ['ok' => false, 'msg' => 'No se pudo depositar el turno'];
    }

    /**
     * Reabre un turno cerrado a estado "borrador" para permitir ediciones. Solo permitido para Administradores.
     * 
     * @param int $id ID del flujo de caja.
     * @return array Mensaje de éxito o error.
     */
    public function reabrir(int $id): array {
        if ($id <= 0) return ['ok' => false, 'msg' => 'ID de flujo inválido'];
        
        // Solo Admin/Supervisor pueden reabrir
        if (!in_array($_SESSION['auth_rol'] ?? '', ['admin', 'supervisor'])) {
            return ['ok' => false, 'msg' => 'No tienes permisos para reabrir turnos'];
        }

        $actual = $this->model->getDetalle($id);
        if (!$actual) return ['ok' => false, 'msg' => 'Flujo no encontrado'];
        if ($actual['estado'] === 'depositado') {
            return ['ok' => false, 'msg' => 'No se puede reabrir un flujo depositado. Registra un ajuste administrativo.'];
        }
        if ($actual['estado'] !== 'cerrado') {
            return ['ok' => false, 'msg' => 'Solo se pueden reabrir turnos cerrados'];
        }

        if ($this->model->cambiarEstado($id, 'borrador')) {
            $this->registrarAuditoriaSeguro('FLUJO_REABIERTO', 'FINANZAS', "Flujo ID $id reabierto a borrador.");
            return ['ok' => true, 'msg' => 'Turno reabierto correctamente (ahora es editable)'];
        }
        return ['ok' => false, 'msg' => 'No se pudo reabrir el turno'];
    }

    /**
     * La auditoría nunca debe romper la operación principal del flujo.
     */
    private function registrarAuditoriaSeguro(string $accion, string $modulo, string $detalle): void {
        try {
            $this->audit->registrar(
                $_SESSION['auth_id'] ?? null,
                $accion,
                $modulo,
                $detalle
            );
        } catch (Throwable $e) {
            error_log('Auditoria FlujoController: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene un resumen consolidado financiero de todos los turnos cerrados en una fecha específica.
     * 
     * @param string $fecha Formato YYYY-MM-DD.
     * @return array Resumen con totales por moneda y desglose de turnos.
     */
    public function resumenDia(string $fecha): array {
        return $this->model->getResumenDia($fecha);
    }

    /**
     * Guarda la nota de entrega de manera manual desde el grid v2
     */
    public function guardarNota(): array {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        $flujoId = (int)($data['flujo_id'] ?? 0);
        $nota = trim($data['nota_entrega'] ?? '');
        
        if ($flujoId <= 0) {
            return ['success' => false, 'message' => 'ID inválido'];
        }
        
        $stmt = $this->pdo->prepare("UPDATE flujo_caja SET nota_entrega = ? WHERE id = ?");
        if ($stmt->execute([$nota, $flujoId])) {
            return ['success' => true, 'message' => 'Nota guardada'];
        }
        return ['success' => false, 'message' => 'Error al guardar la nota'];
    }

    /**
     * Obtiene el resumen consolidado mensual específico para la liquidación de sobres de Alex.
     * 
     * @param int $mes Mes (1-12).
     * @param int $anio Año (YYYY).
     * @return array Resumen diario consolidado del mes.
     */
    public function resumenAlexMensual(int $mes, int $anio): array {
        return $this->model->getReporteAlexMensual($mes, $anio);
    }

    /**
     * Obtiene el resumen consolidado específico para la liquidación de sobres de Alex.
     * 
     * @param string $fecha Formato YYYY-MM-DD.
     * @return array Resumen con totales por turno y desglose de egresos.
     */
    public function resumenAlex(string $fecha): array {
        return $this->model->getReporteAlexDiario($fecha);
    }

    /**
     * Verifica si hay un turno de caja abierto y correcto.
     * Horario abierto:
     *  1. El usuario tiene una caja abierta (borrador) -> ok:true sin importar la hora del reloj.
     *  2. Otra caja en el sistema sigue en borrador    -> ok:false, hay que cerrarla primero.
     *  3. Sin ningún flujo abierto en el sistema       -> ok:false, hay que abrir un nuevo turno.
     * 
     * @return array {ok, flujo_id, turno_actual, turno_flujo?, turno_pendiente?, msg}
     */
    public function verificarApertura(): array {
        require_once __DIR__ . '/../Helpers/FinanzasHelper.php';
        
        $turnoActual = FinanzasHelper::getTurnoActual();
        $usuarioId   = $_SESSION['auth_id'];

        // 1. Buscar si el usuario tiene una caja abierta (borrador)
        $stmtExacto = $this->pdo->prepare("
            SELECT id, turno FROM flujo_caja
            WHERE usuario_id = ? AND estado = 'borrador'
            ORDER BY id DESC LIMIT 1
        ");
        $stmtExacto->execute([$usuarioId]);
        $flujoExacto = $stmtExacto->fetch(PDO::FETCH_ASSOC);

        if ($flujoExacto) {
            // ✔ Caja abierta
            return [
                'ok'           => true,
                'flujo_id'     => (int)$flujoExacto['id'],
                'turno_flujo'  => $flujoExacto['turno'],
                'turno_actual' => $flujoExacto['turno'],
                'msg'          => 'Flujo de caja abierto.'
            ];
        }

        // 2. Si el usuario actual no tiene caja, buscar si en general hay otra caja en borrador en el sistema
        $stmtOtro = $this->pdo->prepare("
            SELECT id, turno FROM flujo_caja
            WHERE estado = 'borrador'
            ORDER BY id DESC LIMIT 1
        ");
        $stmtOtro->execute();
        $flujoOtroTurno = $stmtOtro->fetch(PDO::FETCH_ASSOC);

        if ($flujoOtroTurno) {
            return [
                'ok'              => false,
                'flujo_id'        => null,
                'turno_flujo'     => $flujoOtroTurno['turno'],
                'turno_actual'    => $turnoActual,
                'turno_pendiente' => $flujoOtroTurno['turno'],
                'msg'             => "Existe un flujo de caja de {$flujoOtroTurno['turno']} aún abierto en el sistema. Debe cerrarse antes de abrir un nuevo turno."
            ];
        }

        // 3. No hay ninguna caja abierta en el sistema
        return [
            'ok'           => false,
            'flujo_id'     => null,
            'turno_actual' => $turnoActual,
            'msg'          => "No hay flujo de caja abierto. Abre un nuevo turno para continuar."
        ];
    }

    /**
     * Obtiene el flujo del mes completo mapeado por días y turnos.
     * 
     * @param array $params
     * @return array
     */
    public function flujoMesGrid(array $params): array {
        $mes = (int)($params['mes'] ?? date('n'));
        $anio = (int)($params['anio'] ?? date('Y'));
        
        return $this->model->getFlujoMesGrid($mes, $anio);
    }

    /**
     * Obtiene datos para el modal de consumo rápido
     */
    public function datosConsumoRapido(): array {
        $sql = "SELECT s.id, h.numero as hab_numero, 
                COALESCE((SELECT c.nombre_razon_social FROM rooming_pax rp JOIN clientes c ON rp.cliente_id = c.id WHERE rp.stay_id = s.id AND rp.es_titular_acompanante = 1 LIMIT 1), 'Sin Titular') as huesped_principal
                FROM rooming_stays s 
                JOIN habitaciones h ON s.habitacion_id = h.id
                WHERE s.estado IN ('activo', 'late_checkout') 
                ORDER BY h.numero ASC";
        $stmtStays = $this->pdo->query($sql);
        $stays = $stmtStays->fetchAll(PDO::FETCH_ASSOC);

        $prods = [
            ['id' => 's_san_mateo', 'nombre' => 'SAN MATEO', 'precio_venta' => 5],
            ['id' => 's_inca_kola', 'nombre' => 'INCA KOLA', 'precio_venta' => 7],
            ['id' => 's_coca_cola', 'nombre' => 'COCA COLA', 'precio_venta' => 7],
            ['id' => 's_cerveza', 'nombre' => 'CERVEZA', 'precio_venta' => 10],
            ['id' => 's_powerade', 'nombre' => 'POWERADE', 'precio_venta' => 7],
        ];

        return ['ok' => true, 'data' => ['stays' => $stays, 'productos' => $prods]];
    }

    /**
     * Guarda el consumo rápido desde el Flujo de Caja
     */
    public function guardarConsumoRapido(array $input): array {
        $flujoId = (int)($input['flujo_id'] ?? 0);
        $stayId = (int)($input['stay_id'] ?? 0);
        $precio = (float)($input['precio'] ?? 0);
        $tipo = $input['tipo'] ?? ''; // BEBIDA o DESAYUNO
        $columna = $input['columna'] ?? 'pen_ef'; 
        $destino = $input['destino'] ?? 'independiente';
        
        $invId = null;
        $obs = 'Consumo';

        if ($flujoId <= 0 || $stayId <= 0 || $precio <= 0) {
            return ['ok' => false, 'msg' => 'Datos incompletos'];
        }

        // Obtener hab de stay
        $stmtS = $this->pdo->prepare("SELECT h.numero FROM rooming_stays s JOIN habitaciones h ON s.habitacion_id = h.id WHERE s.id = ?");
        $stmtS->execute([$stayId]);
        $hab = $stmtS->fetchColumn();

        // Determinar Medio de Pago Completo
        $medioPago = 'SOLES EFECTIVO';
        if ($columna === 'depo') { $medioPago = 'TRANSFERENCIA'; }
        if ($columna === 'yape') { $medioPago = 'YAPE'; }
        if ($columna === 'pos_usd') { $medioPago = 'POS DOLARES'; }
        if ($columna === 'pos_pen') { $medioPago = 'POS SOLES'; }
        if ($columna === 'pesos') { $medioPago = 'PESOS EFECTIVO'; }
        if ($columna === 'usd_ef') { $medioPago = 'DOLARES EFECTIVO'; }
        if ($columna === 'pen_ef') { $medioPago = 'SOLES EFECTIVO'; }
        if ($columna === 'cuenta_hab') { $medioPago = 'CUENTA HABITACION'; }

        $this->pdo->beginTransaction();
        try {
            $obs = ($tipo === 'DESAYUNO') ? 'Desayuno Buffet' : 'Bebida Refri';
            $prodId = ($tipo === 'BEBIDA') ? $input['producto_id'] : null;
            $invId = 0;

            if ($tipo === 'BEBIDA' && $prodId) {
                $staticProds = [
                    's_san_mateo' => 'SAN MATEO',
                    's_inca_kola' => 'INCA KOLA',
                    's_coca_cola' => 'COCA COLA',
                    's_cerveza' => 'CERVEZA',
                    's_powerade' => 'POWERADE',
                ];
                if (isset($staticProds[$prodId])) {
                    $obs = $staticProds[$prodId];
                    // Obtener o crear un producto genérico en inventario_productos
                    $stmtGen = $this->pdo->prepare("SELECT id FROM inventario_productos WHERE nombre = ? LIMIT 1");
                    $stmtGen->execute([$obs]);
                    $genId = $stmtGen->fetchColumn();
                    if (!$genId) {
                        $stmtInsGen = $this->pdo->prepare("INSERT INTO inventario_productos (nombre, categoria, refrigeradora, precio_venta, stock_actual, activo) VALUES (?, 'Bebidas', 1, 0, 1000, 1)");
                        $stmtInsGen->execute([$obs]);
                        $genId = $this->pdo->lastInsertId();
                    }
                    $invId = $genId;
                } else {
                    $invId = (int)$prodId;
                    $stmtP = $this->pdo->prepare("SELECT nombre FROM inventario_productos WHERE id = ?");
                    $stmtP->execute([$invId]);
                    $obs = $stmtP->fetchColumn() ?: 'Bebida';
                    
                    $stmtInv = $this->pdo->prepare("UPDATE inventario_productos SET stock_actual = stock_actual - 1 WHERE id = ?");
                    $stmtInv->execute([$invId]);
                }
            }
            
            if ($destino === 'cuenta_hab') {
                $obsAdicional = " - Se adiciona {$precio} del {$obs}";
                
                $stmtCheck = $this->pdo->prepare("SELECT total_pago, pagos_json, observaciones FROM rooming_stays WHERE id = ?");
                $stmtCheck->execute([$stayId]);
                $stay = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                
                $nuevoTotal = (float)$stay['total_pago'] + $precio;
                $nuevaObs = $stay['observaciones'] ? $stay['observaciones'] . $obsAdicional : $obsAdicional;
                $pagosJson = $stay['pagos_json'];
                
                if ($pagosJson) {
                    $pagosArr = json_decode($pagosJson, true);
                    if (is_array($pagosArr) && count($pagosArr) > 0) {
                        $pagosArr[0]['pago_total'] = (float)$pagosArr[0]['pago_total'] + $precio;
                        $pagosJson = json_encode($pagosArr, JSON_UNESCAPED_UNICODE);
                    }
                }
                
                $stmtStay = $this->pdo->prepare("UPDATE rooming_stays SET total_pago = ?, observaciones = ?, pagos_json = ? WHERE id = ?");
                $stmtStay->execute([$nuevoTotal, $nuevaObs, $pagosJson, $stayId]);

                // Insertar en rooming_consumos para que aparezca el detalle en Reporte Mendoza
                $pagado = 0;
                $stmtC = $this->pdo->prepare("INSERT INTO rooming_consumos (stay_id, producto_id, cantidad, precio_unitario, total, metodo_pago, pagado, usuario_id) VALUES (?, ?, 1, ?, ?, 'CUENTA HABITACION', ?, ?)");
                $stmtC->execute([$stayId, $invId, $precio, $precio, $pagado, $_SESSION['auth_id'] ?? 1]);
                
                // Actualizar el movimiento de ingreso de hospedaje en la caja para que el Flujo de Caja refleje el nuevo total
                $stmtUpdateMov = $this->pdo->prepare("UPDATE flujo_caja_movimientos SET monto = monto + ? WHERE stay_id = ? ORDER BY id ASC LIMIT 1");
                $stmtUpdateMov->execute([$precio, $stayId]);
            } else {
                $pagado = 1;
                $stmtC = $this->pdo->prepare("INSERT INTO rooming_consumos (stay_id, producto_id, cantidad, precio_unitario, total, metodo_pago, pagado, usuario_id) VALUES (?, ?, 1, ?, ?, ?, ?, ?)");
                $stmtC->execute([$stayId, $invId, $precio, $precio, $medioPago, $pagado, $_SESSION['auth_id'] ?? 1]);

                $catIdMap = [
                    'depo' => 1,
                    'yape' => 2,
                    'pos_usd' => 3,
                    'pos_pen' => 4,
                    'pesos' => 5,
                    'usd_ef' => 6,
                    'pen_ef' => 7
                ];
                $catId = $catIdMap[$columna] ?? 7;

                $obsFlujo = "$obs - Registro #$stayId (Hab #$hab)";

                $moneda = (in_array($columna, ['pos_usd', 'usd_ef'])) ? 'USD' : (($columna === 'pesos') ? 'CLP' : 'PEN');

                $medioFinalMap = [
                    'depo' => 'TRANSFERENCIA',
                    'yape' => 'YAPE_CAJA',
                    'pos_usd' => 'POS_DOLARES',
                    'pos_pen' => 'POS_SOLES',
                    'pesos' => 'EFECTIVO',
                    'usd_ef' => 'EFECTIVO',
                    'pen_ef' => 'EFECTIVO'
                ];
                $medioFinal = $medioFinalMap[$columna] ?? 'EFECTIVO';

                $stmtF = $this->pdo->prepare("INSERT INTO flujo_caja_movimientos (flujo_id, categoria_id, tipo, moneda, monto, medio_pago, observacion) VALUES (?, ?, 'Ingreso', ?, ?, ?, ?)");
                $stmtF->execute([$flujoId, $catId, $moneda, $precio, $medioFinal, $obsFlujo]);
            }

            $this->pdo->commit();
            return ['ok' => true, 'msg' => ($destino === 'cuenta_hab') ? 'Consumo cargado a la factura de la habitación' : 'Consumo guardado y sumado a caja de forma independiente'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['ok' => false, 'msg' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Guarda cambios en egresos en lote desde la grid V2
     */
    public function guardarEgresosLote(array $input): array {
        $turnos = $input['turnos'] ?? [];
        if (empty($turnos)) return ['ok' => true];

        $catIds = [
            'mercado' => 9, 'movilidad' => 10, 'cafeteria' => 11, 'lavanderia' => 12,
            'utiles' => 13, 'recepcion' => 14, 'repuestos' => 15, 'personal' => 16, 'otros_eg' => 17
        ];
        
        $stmtCats = $this->pdo->query("SELECT id, nombre FROM finanzas_categorias WHERE tipo='Egreso'");
        if ($stmtCats) {
            $dbCats = $stmtCats->fetchAll(PDO::FETCH_ASSOC);
            foreach($dbCats as $c) {
                $n = strtoupper($c['nombre']);
                if (strpos($n, 'MERCADO') !== false) $catIds['mercado'] = $c['id'];
                else if (strpos($n, 'MOVIL') !== false) $catIds['movilidad'] = $c['id'];
                else if (strpos($n, 'CAFE') !== false || strpos($n, 'VEA') !== false || strpos($n, 'GENOV') !== false) $catIds['cafeteria'] = $c['id'];
                else if (strpos($n, 'LAVAN') !== false) $catIds['lavanderia'] = $c['id'];
                else if (strpos($n, 'ESCRIT') !== false || strpos($n, 'UTIL') !== false) $catIds['utiles'] = $c['id'];
                else if (strpos($n, 'RECEP') !== false || strpos($n, 'CHICA') !== false) $catIds['recepcion'] = $c['id'];
                else if (strpos($n, 'REPUEST') !== false || strpos($n, 'SERV') !== false) $catIds['repuestos'] = $c['id'];
                else if (strpos($n, 'PERSO') !== false || strpos($n, 'PAGO') !== false) $catIds['personal'] = $c['id'];
                else if (strpos($n, 'OTROS') !== false) $catIds['otros_eg'] = $c['id'];
            }
        }

        $this->pdo->beginTransaction();
        try {
            $stmtDel = $this->pdo->prepare("DELETE FROM flujo_caja_movimientos WHERE flujo_id = ? AND tipo = 'Egreso'");
            $stmtIns = $this->pdo->prepare("INSERT INTO flujo_caja_movimientos (flujo_id, categoria_id, tipo, moneda, monto, medio_pago, observacion) VALUES (?, ?, 'Egreso', 'PEN', ?, 'EFECTIVO', ?)");

            foreach ($turnos as $t) {
                $flujoId = (int)($t['flujo_id'] ?? 0);
                if ($flujoId <= 0) continue;
                
                // Borrar egresos previos
                $stmtDel->execute([$flujoId]);

                // Insertar los nuevos (consolidando por columna)
                $campos = ['mercado', 'movilidad', 'cafeteria', 'lavanderia', 'utiles', 'recepcion', 'repuestos', 'personal', 'otros_eg'];
                foreach ($campos as $campo) {
                    $monto = (float)($t[$campo] ?? 0);
                    $obs = $t[$campo . '_obs'] ?? 'Actualización Rápida Grid';
                    if (empty(trim($obs))) $obs = 'Actualización Rápida Grid';
                    if ($monto > 0) {
                        $cId = $catIds[$campo] ?? null;
                        $stmtIns->execute([$flujoId, $cId, $monto, $obs]);
                    }
                }
            }

            $this->pdo->commit();
            return ['ok' => true];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['ok' => false, 'msg' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Guarda las notas de entrega editadas en la vista de sobres.
     */
    public function guardarNotasSobres(array $input): array {
        $turnos = $input['turnos'] ?? [];
        return $this->model->guardarNotasSobres($turnos);
    }
}

