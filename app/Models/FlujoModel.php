<?php
/**
 * app/Models/FlujoModel.php
 * 
 * Modelo encargado de la persistencia de datos del Flujo de Caja y sus movimientos.
 * Maneja la lógica compleja de estados de turno, cálculos multidivisa y 
 * disparadores de sincronización con el módulo de Caja Chica.
 */
class FlujoModel {
    /** @var PDO Conexión a la base de datos. */
    private PDO $pdo;

    /**
     * Constructor del modelo.
     * 
     * @param PDO $pdo Conexión a la base de datos.
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene las categorías financieras activas asignadas al módulo de Flujo.
     * 
     * @return array Arreglo de categorías (id, tipo, nombre).
     */
    public function getCategorias(): array {
        $stmt = $this->pdo->query("SELECT id, tipo, nombre FROM finanzas_categorias WHERE modulo='Flujo' AND activo=1 ORDER BY tipo, orden, nombre");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recupera una lista de turnos de caja con totales consolidados y saldo de efectivo en sobre.
     * Realiza conversiones de moneda en tiempo real basadas en el tipo de cambio del día o fallbacks.
     * 
     * @param array $filtros Filtros de 'mes', 'anio' y 'estado'.
     * @return array Lista de turnos con métricas de ingresos, egresos y efectivo.
     */
    public function listar(array $filtros): array {
        $where = ["1=1"];
        $params = [];

        if (!empty($filtros['mes']) && !empty($filtros['anio'])) {
            $where[] = "MONTH(f.fecha) = :mes AND YEAR(f.fecha) = :anio";
            $params[':mes']  = $filtros['mes'];
            $params[':anio'] = $filtros['anio'];
        }

        if (!empty($filtros['estado']) && $filtros['estado'] !== 'todos') {
            $where[] = "f.estado = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        $sqlWhere = implode(" AND ", $where);

        $sql = "
            SELECT 
                f.id, f.fecha, f.turno, f.estado, f.nota_entrega,
                u.nombre AS operador,
                COALESCE(SUM(CASE WHEN m.tipo='Ingreso' THEN 
                    (CASE WHEN m.moneda='USD' THEN m.monto * COALESCE((SELECT factor FROM tipos_cambio WHERE fecha=f.fecha AND moneda_origen='USD' LIMIT 1), 3.7)
                          WHEN m.moneda='CLP' THEN m.monto * COALESCE((SELECT factor FROM tipos_cambio WHERE fecha=f.fecha AND moneda_origen='CLP' LIMIT 1), 0.0039)
                          ELSE m.monto END) ELSE 0 END), 0) AS total_ingresos,
                COALESCE(SUM(CASE WHEN m.tipo='Egreso' THEN 
                    (CASE WHEN m.moneda='USD' THEN m.monto * COALESCE((SELECT factor FROM tipos_cambio WHERE fecha=f.fecha AND moneda_origen='USD' LIMIT 1), 3.7)
                          WHEN m.moneda='CLP' THEN m.monto * COALESCE((SELECT factor FROM tipos_cambio WHERE fecha=f.fecha AND moneda_origen='CLP' LIMIT 1), 0.0039)
                          ELSE m.monto END) ELSE 0 END), 0) AS total_egresos,
                COALESCE(SUM(CASE WHEN m.medio_pago='EFECTIVO' AND m.tipo='Ingreso' THEN m.monto ELSE 0 END), 0) 
              - COALESCE(SUM(CASE WHEN m.medio_pago='EFECTIVO' AND m.tipo='Egreso'  THEN m.monto ELSE 0 END), 0) AS efectivo_sobre
            FROM flujo_caja f
            LEFT JOIN usuarios u ON f.usuario_id = u.id
            LEFT JOIN flujo_caja_movimientos m ON f.id = m.flujo_id
            WHERE $sqlWhere
            GROUP BY f.id
            ORDER BY f.fecha DESC, f.turno ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene la información detallada de un flujo de caja, incluyendo todos sus movimientos.
     * 
     * @param int $id ID del flujo.
     * @return array|null Datos del flujo y sus movimientos (ingresos/egresos) o null si no existe.
     */
    public function getDetalle(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT f.*, u.nombre AS operador FROM flujo_caja f LEFT JOIN usuarios u ON f.usuario_id = u.id WHERE f.id = ?");
        $stmt->execute([$id]);
        $flujo = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$flujo) return null;

        $stmtMovs = $this->pdo->prepare("SELECT * FROM flujo_caja_movimientos WHERE flujo_id = ? ORDER BY id ASC");
        $stmtMovs->execute([$id]);
        $movs = $stmtMovs->fetchAll(PDO::FETCH_ASSOC);

        $flujo['ingresos'] = array_values(array_filter($movs, fn($m) => $m['tipo'] === 'Ingreso'));
        $flujo['egresos']  = array_values(array_filter($movs, fn($m) => $m['tipo'] === 'Egreso'));

        // Extraer TC del día
        $stmtTC = $this->pdo->prepare("SELECT moneda_origen, factor FROM tipos_cambio WHERE fecha = ?");
        $stmtTC->execute([$flujo['fecha']]);
        $tcData = $stmtTC->fetchAll(PDO::FETCH_ASSOC);
        $tc = ['USD' => 3.7, 'CLP' => 0.0039]; // Fallbacks
        foreach($tcData as $row) { $tc[$row['moneda_origen']] = (float)$row['factor']; }
        $flujo['tc'] = $tc;

        return $flujo;
    }

    /**
     * Verifica si ya existe un registro de flujo para una fecha y turno específicos.
     * 
     * @param string $fecha Fecha del turno.
     * @param string $turno Nombre/identificador del turno (ej. Mañana, Tarde).
     * @param int $excludeId ID a excluir en la búsqueda (usado al editar).
     * @return bool True si ya existe, false si está libre.
     */
    public function checkExisteTurno(string $fecha, string $turno, int $excludeId = 0): bool {
        // Bloquear si ya existe CUALQUIER turno para esa fecha/turno.
        // La clave única de la BD no permite duplicados sin importar el estado.
        $stmt = $this->pdo->prepare("SELECT id FROM flujo_caja WHERE fecha = ? AND turno = ? AND id != ?");
        $stmt->execute([$fecha, $turno, $excludeId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Obtiene el ID de cualquier flujo existente para una fecha y turno (sin importar estado).
     */
    public function getIdExistente(string $fecha, string $turno): ?int {
        $stmt = $this->pdo->prepare("SELECT id FROM flujo_caja WHERE fecha = ? AND turno = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$fecha, $turno]);
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    }

    /**
     * Guarda el encabezado del turno y sincroniza sus movimientos.
     * Incluye lógica de integración automática: movimientos de "RECEPCIÓN C.CH." generan ingresos en Caja Chica.
     * 
     * @param array $data Datos generales (id, fecha, turno, etc).
     * @param array $ingresos Lista de movimientos de ingreso.
     * @param array $egresos Lista de movimientos de egreso.
     * @return int ID del flujo guardado.
     * @throws Exception Si ocurre un error en la transacción.
     */
    public function guardar(array $data, array $ingresos, array $egresos): int {
        $id = (int)($data['id'] ?? 0);
        
        $this->pdo->beginTransaction();
        try {
            if ($id > 0) {
                // Update
                $stmt = $this->pdo->prepare("UPDATE flujo_caja SET nota_entrega = ? WHERE id = ?");
                $stmt->execute([$data['nota_entrega'], $id]);
            } else {
                // Insert
                $stmt = $this->pdo->prepare("INSERT INTO flujo_caja (fecha, turno, estado, nota_entrega, usuario_id) VALUES (?, ?, 'borrador', ?, ?)");
                $stmt->execute([$data['fecha'], $data['turno'], $data['nota_entrega'], $data['usuario_id']]);
                $id = (int)$this->pdo->lastInsertId();
            }

            // Clear old movements and insert fresh
            $this->pdo->prepare("DELETE FROM flujo_caja_movimientos WHERE flujo_id = ?")->execute([$id]);

            $stmtMov = $this->pdo->prepare("INSERT INTO flujo_caja_movimientos (flujo_id, categoria_id, categoria, tipo, moneda, monto, medio_pago, observacion, sobre_fecha, sobre_turno) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $movs = array_merge($ingresos, $egresos);
            foreach ($movs as $mov) {
                // Ignore empty rows
                if (empty($mov['categoria']) || empty($mov['monto']) || $mov['monto'] <= 0) continue;
                
                $catId = !empty($mov['categoria_id']) ? $mov['categoria_id'] : null;
                $sFecha = !empty($mov['sobre_fecha']) ? $mov['sobre_fecha'] : null;
                $sTurno = !empty($mov['sobre_turno']) ? $mov['sobre_turno'] : null;

                $stmtMov->execute([
                    $id, 
                    $catId, 
                    $mov['categoria'], 
                    $mov['tipo'], 
                    $mov['moneda'], 
                    $mov['monto'], 
                    $mov['medio_pago'], 
                    $mov['observacion'] ?? '',
                    $sFecha,
                    $sTurno
                ]);

                $movId = (int)$this->pdo->lastInsertId();

                // SINCRONIZACIÓN CAJA CHICA: RECEPCIÓN C.CH. (EGRESO DEL Turno -> INGRESO DE CAJA CHICA)
                if ($mov['tipo'] === 'Egreso' && $mov['categoria'] === 'RECEPCIÓN C.CH.') {
                    // Buscar ciclo activo de Caja Chica
                    $stmtCC = $this->pdo->prepare("SELECT id FROM caja_chica WHERE estado = 'abierta' ORDER BY id DESC LIMIT 1");
                    $stmtCC->execute();
                    $cajaId = $stmtCC->fetchColumn();

                    if ($cajaId) {
                        // Primero borrar duplicados previos de este mismo movimiento del flujo
                        $this->pdo->prepare("DELETE FROM caja_chica_movimientos WHERE flujo_movimiento_id = ?")->execute([$movId]);
                        
                        // Insertar ingreso en Caja Chica
                        $stmtCCMov = $this->pdo->prepare("
                            INSERT INTO caja_chica_movimientos 
                            (caja_id, tipo, monto, rubro, documento, fecha, observacion, usuario_id, flujo_movimiento_id) 
                            VALUES (?, 'ingreso', ?, 'REPOSICIÓN DESDE FLUJO', 'FLUJO-#$id', CURDATE(), ?, ?, ?)
                        ");
                        $stmtCCMov->execute([
                            $cajaId, 
                            $mov['monto'], 
                            $mov['observacion'] ?? '', 
                            $data['usuario_id'],
                            $movId
                        ]);
                    }
                }
            }

            $this->pdo->commit();
            return $id;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Actualiza el estado de un turno (borrador, cerrado, depositado).
     * 
     * @param int $id ID del flujo.
     * @param string $estado Nuevo estado.
     * @return bool True si se actualizó correctamente.
     */
    public function cambiarEstado(int $id, string $estado): bool {
        $stmt = $this->pdo->prepare("UPDATE flujo_caja SET estado = ? WHERE id = ?");
        return $stmt->execute([$estado, $id]);
    }

    /**
     * Genera un resumen financiero consolidado por moneda para una fecha dada.
     * Incluye solo turnos que no están en estado 'borrador'.
     * 
     * @param string $fecha Fecha del arqueo.
     * @return array Desglose de totales por turno y moneda.
     */
    public function getResumenDia(string $fecha): array {
        $sql = "
            SELECT 
                f.turno,
                COALESCE(SUM(CASE WHEN m.tipo='Ingreso' THEN 
                    (CASE WHEN m.moneda='USD' THEN m.monto * COALESCE((SELECT factor FROM tipos_cambio WHERE fecha=f.fecha AND moneda_origen='USD' LIMIT 1), 3.7)
                          WHEN m.moneda='CLP' THEN m.monto * COALESCE((SELECT factor FROM tipos_cambio WHERE fecha=f.fecha AND moneda_origen='CLP' LIMIT 1), 0.0039)
                          ELSE m.monto END) ELSE 0 END), 0) AS total_ingresos,
                COALESCE(SUM(CASE WHEN m.tipo='Egreso' THEN 
                    (CASE WHEN m.moneda='USD' THEN m.monto * COALESCE((SELECT factor FROM tipos_cambio WHERE fecha=f.fecha AND moneda_origen='USD' LIMIT 1), 3.7)
                          WHEN m.moneda='CLP' THEN m.monto * COALESCE((SELECT factor FROM tipos_cambio WHERE fecha=f.fecha AND moneda_origen='CLP' LIMIT 1), 0.0039)
                          ELSE m.monto END) ELSE 0 END), 0) AS total_egresos,
                COALESCE(SUM(CASE WHEN m.medio_pago='EFECTIVO' AND m.tipo='Ingreso' AND m.moneda='PEN' THEN m.monto ELSE 0 END), 0) 
              - COALESCE(SUM(CASE WHEN m.medio_pago='EFECTIVO' AND m.tipo='Egreso'  AND m.moneda='PEN' THEN m.monto ELSE 0 END), 0) AS efectivo_pen,
                COALESCE(SUM(CASE WHEN m.medio_pago='EFECTIVO' AND m.tipo='Ingreso' AND m.moneda='USD' THEN m.monto ELSE 0 END), 0) 
              - COALESCE(SUM(CASE WHEN m.medio_pago='EFECTIVO' AND m.tipo='Egreso'  AND m.moneda='USD' THEN m.monto ELSE 0 END), 0) AS efectivo_usd,
                COALESCE(SUM(CASE WHEN m.medio_pago='EFECTIVO' AND m.tipo='Ingreso' AND m.moneda='CLP' THEN m.monto ELSE 0 END), 0) 
              - COALESCE(SUM(CASE WHEN m.medio_pago='EFECTIVO' AND m.tipo='Egreso'  AND m.moneda='CLP' THEN m.monto ELSE 0 END), 0) AS efectivo_clp
            FROM flujo_caja f
            LEFT JOIN flujo_caja_movimientos m ON f.id = m.flujo_id
            WHERE f.fecha = :fecha AND f.estado != 'borrador'
            GROUP BY f.turno
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':fecha' => $fecha]);
        $turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resumen = [
            'fecha' => $fecha,
            'turnos' => $turnos,
            'total_dia_ingresos' => 0,
            'total_dia_egresos'  => 0,
            'efectivo_pen' => 0,
            'efectivo_usd' => 0,
            'efectivo_clp' => 0
        ];

        foreach ($turnos as $t) {
            $resumen['total_dia_ingresos'] += $t['total_ingresos'];
            $resumen['total_dia_egresos']  += $t['total_egresos'];
            $resumen['efectivo_pen'] += $t['efectivo_pen'];
            $resumen['efectivo_usd'] += $t['efectivo_usd'];
            $resumen['efectivo_clp'] += $t['efectivo_clp'];
        }

        return $resumen;
    }
    /**
     * Busca si existe un turno en estado 'borrador' (activo) que coincida 
     * con la fecha y hora actual para el auto-redireccionamiento.
     * @return int|null ID del flujo o null si no hay ninguno abierto.
     */
    public function getTurnoActivo(): ?int {
        $fecha = date('Y-m-d');
        $hora  = (int)date('H');
        
        // MAÑANA: 6 am a 2 pm (06:00 - 13:59)
        // TARDE: 2 pm a 10 pm (14:00 - 21:59). 
        // Si es fuera de rango, buscamos el de TARDE o el último de hoy.
        $turnoActual = ($hora >= 6 && $hora < 14) ? 'MAÑANA' : 'TARDE';
        
        // 1. Intentar buscar el turno exacto de hoy que corresponda a la hora
        $stmt = $this->pdo->prepare("SELECT id FROM flujo_caja WHERE fecha = ? AND turno = ? AND estado = 'borrador' LIMIT 1");
        $stmt->execute([$fecha, $turnoActual]);
        $id = $stmt->fetchColumn();
        
        // 2. Fallback: Si no hay exacto para la hora, cualquier borrador de HOY (por si se pasó de hora)
        if (!$id) {
            $stmt = $this->pdo->prepare("SELECT id FROM flujo_caja WHERE fecha = ? AND estado = 'borrador' ORDER BY id DESC LIMIT 1");
            $stmt->execute([$fecha]);
            $id = $stmt->fetchColumn();
        }
        
        return $id ? (int)$id : null;
    }

    /**
     * Obtiene el reporte consolidado diario solicitado por el señor Alex.
     * Muestra el efectivo recaudado en cada sobre (Mañana/Tarde) restando los 
     * egresos que fueron extraídos específicamente de esos sobres.
     * 
     * @param string $fecha Fecha del reporte.
     * @return array Estructura de datos para la "Tabla Verde".
     */
    public function getReporteAlexDiario(string $fecha): array {
        // 1. Ingresos por turno (Efectivo)
        $sqlIng = "
            SELECT 
                f.turno, 
                m.moneda, 
                SUM(m.monto) as total_ingreso
            FROM flujo_caja f
            JOIN flujo_caja_movimientos m ON f.id = m.flujo_id
            WHERE f.fecha = ? AND m.tipo = 'Ingreso' AND m.medio_pago = 'EFECTIVO'
            GROUP BY f.turno, m.moneda
        ";
        $stmtIng = $this->pdo->prepare($sqlIng);
        $stmtIng->execute([$fecha]);
        $ingresos = $stmtIng->fetchAll(PDO::FETCH_ASSOC);

        // 2. Egresos asociados a estos sobres (sin importar en qué turno se registraron)
        $sqlEgr = "
            SELECT 
                sobre_turno as turno, 
                moneda, 
                SUM(monto) as total_egreso,
                GROUP_CONCAT(CONCAT(categoria, ' (', monto, ')') SEPARATOR ', ') as detalle_egresos
            FROM flujo_caja_movimientos
            WHERE sobre_fecha = ? AND tipo = 'Egreso' AND medio_pago = 'EFECTIVO'
            GROUP BY sobre_turno, moneda
        ";
        $stmtEgr = $this->pdo->prepare($sqlEgr);
        $stmtEgr->execute([$fecha]);
        $egresos = $stmtEgr->fetchAll(PDO::FETCH_ASSOC);

        // Estructurar el resultado
        $reporte = [
            'MAÑANA' => ['PEN' => 0, 'USD' => 0, 'CLP' => 0, 'egresos_detalle' => ''],
            'TARDE'  => ['PEN' => 0, 'USD' => 0, 'CLP' => 0, 'egresos_detalle' => ''],
            'fecha'  => $fecha
        ];

        foreach ($ingresos as $i) {
            $reporte[$i['turno']][$i['moneda']] += (float)$i['total_ingreso'];
        }

        foreach ($egresos as $e) {
            if (isset($reporte[$e['turno']])) {
                $reporte[$e['turno']][$e['moneda']] -= (float)$e['total_egreso'];
                $reporte[$e['turno']]['egresos_detalle'] = $e['detalle_egresos'];
            }
        }

        return $reporte;
    }
}
