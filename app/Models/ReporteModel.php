<?php
/**
 * app/Models/ReporteModel.php
 */
class ReporteModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }


    /**
     * Reporte Sr. Mendoza: Venta Detallada por Habitación
     * Incluye todos los pagos (Efectivo, POS, Yape) asociados a estadías del mes.
     */
    public function getVentaHospedaje(int $mes, int $anio): array {
        $sql = "
            SELECT 
                a.id AS pago_id,
                a.stay_id,
                h.numero AS habitacion,
                h.tipo AS tipo_hab,
                s.pax_total AS pax,
                s.fecha_registro AS check_in,
                s.fecha_checkout AS check_out,
                s.noches,
                s.medio_reserva AS canal,
                a.fecha AS pago_fecha,
                CASE 
                    -- Sincronizado con FinanzasHelper::getTurnoActual()
                    WHEN HOUR(a.created_at) >= 6 AND HOUR(a.created_at) < 14 THEN 'MAÑANA' 
                    ELSE 'TARDE' 
                END AS turno,
                a.moneda,
                a.monto,
                a.tipo_pago,
                CASE 
                    WHEN a.tipo_pago IN ('TRANSFERENCIA', 'DEPOSITO', 'TRANSF', 'DEPOS/TRANS.') THEN 'TRANSFER.'
                    WHEN a.tipo_pago IN ('YAPE', 'PLIN', 'YAPE O PLIN') THEN 'YAPE/PLIN'
                    WHEN a.tipo_pago LIKE '%POS%' AND a.moneda = 'USD' THEN 'POS $'
                    WHEN a.tipo_pago LIKE '%POS%' AND a.moneda = 'PEN' THEN 'POS S/'
                    WHEN a.tipo_pago LIKE '%POS%' AND a.moneda = 'CLP' THEN 'POS P$'
                    WHEN a.tipo_pago LIKE '%EFECTIVO%' AND a.moneda = 'CLP' THEN 'EFEC P$'
                    WHEN a.tipo_pago LIKE '%EFECTIVO%' AND a.moneda = 'USD' THEN 'EFEC $'
                    WHEN a.tipo_pago LIKE '%EFECTIVO%' AND a.moneda = 'PEN' THEN 'EFEC S/'
                    ELSE a.tipo_pago
                END AS medio_label,
                a.monto AS total_fila,
                CONCAT(s.tipo_comprobante, ' ', IFNULL(s.num_comprobante, '')) AS comprobante
            FROM anticipos a
            JOIN rooming_stays s ON a.stay_id = s.id
            JOIN habitaciones h ON s.habitacion_id = h.id
            WHERE MONTH(a.fecha) = :mes AND YEAR(a.fecha) = :anio
              AND s.estado != 'anulado'
            ORDER BY a.fecha DESC, turno DESC, h.piso, h.numero
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':mes' => $mes, ':anio' => $anio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Resumen por Moneda y Método (Mendoza Footer)
     * Consolidado de Hospedaje (anticipos) + Consumos Extras (rooming_consumos)
     */
    public function getResumenDesglosado(int $mes, int $anio): array {
        $res = [
            'POS' => ['PEN' => 0, 'USD' => 0, 'CLP' => 0],
            'EFECTIVO' => ['PEN' => 0, 'USD' => 0, 'CLP' => 0],
            'YAPE' => 0,
            'TRANSFERENCIA' => 0
        ];

        // 1. Procesar Anticipos (Hospedaje + Pagos de consumos vinculados)
        $sqlAnticipos = "
            SELECT a.moneda, a.tipo_pago, SUM(a.monto) AS total
            FROM anticipos a
            JOIN rooming_stays s ON a.stay_id = s.id
            WHERE MONTH(a.fecha) = :mes AND YEAR(a.fecha) = :anio
              AND s.estado != 'anulado'
            GROUP BY a.moneda, a.tipo_pago
        ";
        $stmtA = $this->pdo->prepare($sqlAnticipos);
        $stmtA->execute([':mes' => $mes, ':anio' => $anio]);
        
        foreach ($stmtA->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $m = $r['moneda'];
            $t = strtoupper($r['tipo_pago'] ?? '');
            $val = (float)$r['total'];

            if (strpos($t, 'YAPE') !== false || strpos($t, 'PLIN') !== false) {
                $res['YAPE'] += $val;
            } elseif (strpos($t, 'TRANS') !== false || strpos($t, 'DEPO') !== false || strpos($t, 'BANCO') !== false) {
                $res['TRANSFERENCIA'] += $val;
            } elseif (strpos($t, 'EFECTIVO') !== false) {
                $res['EFECTIVO'][$m] = ($res['EFECTIVO'][$m] ?? 0) + $val;
            } elseif (strpos($t, 'POS') !== false) {
                $res['POS'][$m] = ($res['POS'][$m] ?? 0) + $val;
            }
        }

        // 2. Ingresos por Consumos Extras (Solo Ventas Directas que NO tienen stay_id)
        $sqlConsumos = "SELECT c.total, c.metodo_pago, 'PEN' as moneda 
                        FROM rooming_consumos c 
                        WHERE MONTH(c.created_at) = :mes AND YEAR(c.created_at) = :anio 
                          AND c.metodo_pago IS NOT NULL AND c.metodo_pago != ''
                          AND (c.stay_id IS NULL OR c.stay_id = 0)"; 
        
        $stmtC = $this->pdo->prepare($sqlConsumos);
        $stmtC->execute([':mes' => $mes, ':anio' => $anio]);
        
        foreach ($stmtC->fetchAll(PDO::FETCH_ASSOC) as $c) {
            $t = strtoupper($c['metodo_pago'] ?? '');
            $val = (float)$c['total'];

            if (strpos($t, 'YAPE') !== false || strpos($t, 'PLIN') !== false) {
                $res['YAPE'] += $val;
            } elseif (strpos($t, 'TRANS') !== false || strpos($t, 'DEPO') !== false || strpos($t, 'BANCO') !== false) {
                $res['TRANSFERENCIA'] += $val;
            } elseif (strpos($t, 'EFECTIVO') !== false) {
                $res['EFECTIVO']['PEN'] += $val;
            } elseif (strpos($t, 'POS') !== false) {
                $res['POS']['PEN'] += $val;
            }
        }

        return $res;
    }

    /**
     * Mendoza: Detalle de consumos realizados en el mes
     */
    public function getConsumosDetail(int $mes, int $anio): array {
        $sql = "
            SELECT 
                c.id,
                c.stay_id,
                h.numero AS habitacion,
                c.nombre_producto AS producto,
                c.cantidad,
                c.total,
                c.metodo_pago,
                DATE(c.created_at) AS fecha,
                CASE 
                    WHEN HOUR(c.created_at) >= 6 AND HOUR(c.created_at) < 14 THEN 'MAÑANA' 
                    ELSE 'TARDE' 
                END AS turno
            FROM rooming_consumos c
            LEFT JOIN rooming_stays s ON c.stay_id = s.id
            LEFT JOIN habitaciones h ON s.habitacion_id = h.id
            WHERE MONTH(c.created_at) = :mes AND YEAR(c.created_at) = :anio
            ORDER BY c.created_at DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['mes' => $mes, 'anio' => $anio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Reporte Alex: Gastos Yape (Sin Hospedaje)
     */
    public function getGastosYape(int $mes, int $anio): array {
        $sql = "
            SELECT 
                y.fecha,
                y.turno,
                d.rubro,
                d.monto,
                d.observacion,
                d.documento,
                u.nombre AS operador
            FROM gastos_yape y
            JOIN gastos_yape_detalle d ON y.id = d.gasto_yape_id
            LEFT JOIN usuarios u ON y.usuario_id = u.id
            WHERE MONTH(y.fecha) = :mes AND YEAR(y.fecha) = :anio
            ORDER BY y.fecha DESC, y.turno ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':mes' => $mes, ':anio' => $anio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Resumen Mensual Consolidado (P&L)
     */
    public function getResumenP_L(int $mes, int $anio): array {
        // 1. Ingresos Rooming (Lodging) desde ANTICIPOS
        $sqlIng = "SELECT SUM(monto_pen) FROM anticipos WHERE MONTH(fecha) = :mes AND YEAR(fecha) = :anio";
        $stmt = $this->pdo->prepare($sqlIng);
        $stmt->execute([':mes' => $mes, ':anio' => $anio]);
        $ingHosting = (float)$stmt->fetchColumn();

        // 2. Otros Ingresos (Venta productos, early checkin, etc en Flujo)
        $sqlOtros = "SELECT SUM(monto) FROM flujo_caja_movimientos WHERE tipo='Ingreso' AND categoria NOT IN ('HABITACIÓN', 'YAPE O PLIN') AND flujo_id IN (SELECT id FROM flujo_caja WHERE MONTH(fecha) = :mes AND YEAR(fecha) = :anio)";
        $stmt = $this->pdo->prepare($sqlOtros);
        $stmt->execute([':mes' => $mes, ':anio' => $anio]);
        $otrosIng = (float)$stmt->fetchColumn();

        // 3. Egresos Operativos (Flujo) - EXCLUIMOS reposición de Caja Chica para no duplicar si sumamos sus gastos reales
        $sqlEgr = "SELECT SUM(monto) FROM flujo_caja_movimientos 
                   WHERE tipo='Egreso' 
                   AND categoria NOT IN ('RECEPCIÓN C.CH.', 'REPOSICIÓN C.CH.')
                   AND flujo_id IN (SELECT id FROM flujo_caja WHERE MONTH(fecha) = :mes AND YEAR(fecha) = :anio)";
        $stmt = $this->pdo->prepare($sqlEgr);
        $stmt->execute(['mes' => $mes, 'anio' => $anio]);
        $egresosOp = (float)$stmt->fetchColumn();

        // 4. Caja Chica (Gastos Reales)
        $sqlCch = "SELECT SUM(monto) FROM caja_chica_movimientos 
                   WHERE tipo='egreso' AND (anulado=0 OR anulado IS NULL)
                   AND MONTH(fecha) = :mes AND YEAR(fecha) = :anio";
        $stmt = $this->pdo->prepare($sqlCch);
        $stmt->execute(['mes' => $mes, 'anio' => $anio]);
        $gastosCch = (float)$stmt->fetchColumn();

        // 5. Gastos Yape (Reporte Alex)
        $sqlYape = "SELECT SUM(d.monto) 
                    FROM gastos_yape_detalle d
                    JOIN gastos_yape y ON d.gasto_yape_id = y.id
                    WHERE MONTH(y.fecha) = :mes AND YEAR(y.fecha) = :anio";
        $stmt = $this->pdo->prepare($sqlYape);
        $stmt->execute(['mes' => $mes, 'anio' => $anio]);
        $gastosYape = (float)$stmt->fetchColumn();

        return [
            'ingresos_hospedaje' => $ingHosting,
            'otros_ingresos' => $otrosIng,
            'egresos_operativos' => $egresosOp,
            'gastos_caja_chica' => $gastosCch,
            'gastos_yape' => $gastosYape,
            'total_ingresos' => ($ingHosting + $otrosIng),
            'total_gastos' => ($egresosOp + $gastosCch + $gastosYape),
            'ganancia_mes' => ($ingHosting + $otrosIng) - ($egresosOp + $gastosCch + $gastosYape),
            'utilidad_neta' => ($ingHosting + $otrosIng) - ($egresosOp + $gastosCch + $gastosYape)
        ];
    }

    /**
     * Datos completos para el Reporte Mensual (con desglose diario)
     */
    public function getMensualData(int $mes, int $anio): array {
        $resumen = $this->getResumenP_L($mes, $anio);
        
        // Ingresos por día (Anticipos + Otros Ingresos de Flujo)
        $sqlIng = "
            SELECT dia, SUM(monto) as total FROM (
                SELECT fecha as dia, SUM(monto_pen) as monto FROM anticipos 
                WHERE MONTH(fecha) = :mes AND YEAR(fecha) = :anio GROUP BY fecha
                UNION ALL
                SELECT f.fecha as dia, SUM(m.monto) as monto 
                FROM flujo_caja f JOIN flujo_caja_movimientos m ON f.id = m.flujo_id
                WHERE m.tipo='Ingreso' AND m.categoria NOT IN ('HABITACIÓN', 'YAPE O PLIN')
                  AND MONTH(f.fecha) = :mes AND YEAR(f.fecha) = :anio
                GROUP BY f.fecha
            ) t GROUP BY dia
        ";
        $stmt = $this->pdo->prepare($sqlIng);
        $stmt->execute(['mes' => $mes, 'anio' => $anio]);
        $ingresos_por_dia = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Gastos por día (Flujo Egresos + Caja Chica + Yape)
        $sqlGas = "
            SELECT dia, SUM(monto) as total FROM (
                SELECT f.fecha as dia, SUM(m.monto) as monto 
                FROM flujo_caja f JOIN flujo_caja_movimientos m ON f.id = m.flujo_id
                WHERE m.tipo='Egreso' AND m.categoria NOT IN ('RECEPCIÓN C.CH.', 'REPOSICIÓN C.CH.')
                  AND MONTH(f.fecha) = :mes AND YEAR(f.fecha) = :anio
                GROUP BY f.fecha
                UNION ALL
                SELECT fecha as dia, SUM(monto) as monto FROM caja_chica_movimientos
                WHERE tipo='egreso' AND (anulado=0 OR anulado IS NULL)
                  AND MONTH(fecha) = :mes AND YEAR(fecha) = :anio
                GROUP BY fecha
                UNION ALL
                SELECT y.fecha as dia, SUM(d.monto) as monto FROM gastos_yape_detalle d
                JOIN gastos_yape y ON d.gasto_yape_id = y.id
                WHERE MONTH(y.fecha) = :mes AND YEAR(y.fecha) = :anio
                GROUP BY y.fecha
            ) t GROUP BY dia
        ";
        $stmt = $this->pdo->prepare($sqlGas);
        $stmt->execute(['mes' => $mes, 'anio' => $anio]);
        $gastos_por_dia = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Totales de medios de pago para el resumen
        $stmtMedios = $this->pdo->prepare("
            SELECT 
                SUM(CASE WHEN tipo_pago LIKE '%POS%' THEN monto_pen ELSE 0 END) as tarjeta,
                SUM(CASE WHEN tipo_pago LIKE '%EFECTIVO%' THEN monto_pen ELSE 0 END) as efectivo
            FROM anticipos WHERE MONTH(fecha) = :mes AND YEAR(fecha) = :anio
        ");
        $stmtMedios->execute(['mes' => $mes, 'anio' => $anio]);
        $medios = $stmtMedios->fetch(PDO::FETCH_ASSOC);

        return array_merge($resumen, [
            'ingresos_por_dia' => $ingresos_por_dia,
            'gastos_por_dia' => $gastos_por_dia,
            'efectivo' => (float)$medios['efectivo'],
            'tarjeta' => (float)$medios['tarjeta'],
            'total_registros' => count($ingresos_por_dia)
        ]);
    }

    /**
     * Reporte de Cuadre Diario
     */
    public function getCuadreDiario(string $fecha): array {
        // 1. Ingresos y Gastos Totales
        $stmtTotal = $this->pdo->prepare("
            SELECT 
                SUM(CASE WHEN tipo='Ingreso' THEN monto ELSE 0 END) as ing,
                SUM(CASE WHEN tipo='Egreso' THEN monto ELSE 0 END) as egr
            FROM flujo_caja_movimientos m
            JOIN flujo_caja f ON m.flujo_id = f.id
            WHERE f.fecha = ? AND f.estado != 'borrador_eliminado'
        ");
        $stmtTotal->execute([$fecha]);
        $totals = $stmtTotal->fetch(PDO::FETCH_ASSOC);

        // 2. Detalle de Pagos (Hospedaje)
        $stmtPagos = $this->pdo->prepare("
            SELECT h.numero as hab_num, 
                   COALESCE((SELECT nombre_completo FROM rooming_pax p WHERE p.stay_id = s.id AND es_titular=1 LIMIT 1), 'Huésped') as cliente,
                   a.tipo_pago as metodo, a.monto
            FROM anticipos a
            JOIN rooming_stays s ON a.stay_id = s.id
            JOIN habitaciones h ON s.habitacion_id = h.id
            WHERE a.fecha = ? AND s.estado != 'anulado'
        ");
        $stmtPagos->execute([$fecha]);
        $pagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);

        // 3. Detalle de Gastos
        $stmtGastos = $this->pdo->prepare("
            SELECT m.observacion as descripcion, m.monto
            FROM flujo_caja_movimientos m
            JOIN flujo_caja f ON m.flujo_id = f.id
            WHERE f.fecha = ? AND m.tipo = 'Egreso' AND f.estado != 'borrador_eliminado'
        ");
        $stmtGastos->execute([$fecha]);
        $gastos = $stmtGastos->fetchAll(PDO::FETCH_ASSOC);

        // 4. Medios de pago desglosados
        $stmtMedios = $this->pdo->prepare("
            SELECT 
                SUM(CASE WHEN tipo_pago LIKE '%POS%' THEN monto ELSE 0 END) as tarjeta,
                SUM(CASE WHEN tipo_pago LIKE '%EFECTIVO%' THEN monto ELSE 0 END) as efectivo
            FROM anticipos WHERE fecha = ?
        ");
        $stmtMedios->execute([$fecha]);
        $medios = $stmtMedios->fetch(PDO::FETCH_ASSOC);

        // 5. Ocupación
        $stmtOcup = $this->pdo->prepare("SELECT COUNT(id) FROM rooming_stays WHERE fecha_registro <= ? AND fecha_checkout > ? AND estado != 'anulado'");
        $stmtOcup->execute([$fecha, $fecha]);
        $ocupadas = (int)$stmtOcup->fetchColumn();

        return [
            'total_ingresos' => (float)$totals['ing'],
            'total_gastos' => (float)$totals['egr'],
            'ganancia_neta' => (float)$totals['ing'] - (float)$totals['egr'],
            'efectivo' => (float)$medios['efectivo'],
            'tarjeta' => (float)$medios['tarjeta'],
            'hab_ocupadas' => $ocupadas,
            'detalle_pagos' => $pagos,
            'detalle_gastos' => $gastos
        ];
    }

    /**
     * Datos para Gráficos Estadísticos
     */
    public function getGraficosData(int $mes, int $anio): array {
        // 1. Ocupación Actual
        $stmtTotal = $this->pdo->query("SELECT COUNT(id) FROM habitaciones WHERE estado != 'eliminada'");
        $totalHab = (int)$stmtTotal->fetchColumn();

        $stmtOcup = $this->pdo->query("SELECT COUNT(id) FROM habitaciones WHERE estado = 'ocupada'");
        $ocupadas = (int)$stmtOcup->fetchColumn();

        // 2. Top Habitaciones por Ingresos en el mes
        $stmtTop = $this->pdo->prepare("
            SELECT h.numero as habitacion, SUM(a.monto_pen) as total
            FROM anticipos a
            JOIN rooming_stays s ON a.stay_id = s.id
            JOIN habitaciones h ON s.habitacion_id = h.id
            WHERE MONTH(a.fecha) = :mes AND YEAR(a.fecha) = :anio
            GROUP BY h.id ORDER BY total DESC LIMIT 6
        ");
        $stmtTop->execute(['mes' => $mes, 'anio' => $anio]);
        $topHab = $stmtTop->fetchAll(PDO::FETCH_ASSOC);

        return [
            'hab_libres' => $totalHab - $ocupadas,
            'hab_ocupadas' => $ocupadas,
            'top_hab' => $topHab
        ];
    }
}
