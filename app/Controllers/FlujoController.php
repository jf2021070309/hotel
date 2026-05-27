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
}
