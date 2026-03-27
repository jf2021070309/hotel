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
                    WHEN HOUR(a.created_at) >= 6 AND HOUR(a.created_at) < 14 THEN 'MAÑANA' 
                    ELSE 'TARDE' 
                END AS turno,
                a.moneda,
                SUM(CASE WHEN a.tipo_pago = 'EFECTIVO' THEN a.monto ELSE 0 END) AS cobrado_efectivo,
                SUM(CASE WHEN a.tipo_pago LIKE 'POS%' THEN a.monto ELSE 0 END) AS cobrado_pos,
                SUM(CASE WHEN a.tipo_pago IN ('YAPE', 'PLIN', 'YAPE O PLIN') THEN a.monto ELSE 0 END) AS cobrado_yape,
                SUM(CASE WHEN a.tipo_pago IN ('TRANSFERENCIA', 'DEPOSITO', 'TRANSF', 'DEPOS/TRANS.') THEN a.monto ELSE 0 END) AS cobrado_transf,
                SUM(a.monto) AS total_fila,
                CONCAT(s.tipo_comprobante, ' ', IFNULL(s.num_comprobante, '')) AS comprobante
            FROM anticipos a
            JOIN rooming_stays s ON a.stay_id = s.id
            JOIN habitaciones h ON s.habitacion_id = h.id
            WHERE MONTH(a.fecha) = :mes AND YEAR(a.fecha) = :anio
              AND s.estado != 'anulado'
            GROUP BY a.stay_id, a.fecha, turno, a.moneda
            ORDER BY a.fecha DESC, turno DESC, h.piso, h.numero
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':mes' => $mes, ':anio' => $anio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Resumen por Moneda y Método (Mendoza Footer)
     */
    public function getResumenDesglosado(int $mes, int $anio): array {
        $sql = "
            SELECT 
                moneda,
                tipo_pago,
                SUM(monto) AS total
            FROM anticipos a
            JOIN rooming_stays s ON a.stay_id = s.id
            WHERE MONTH(a.fecha) = :mes AND YEAR(a.fecha) = :anio
              AND s.estado != 'anulado'
            GROUP BY moneda, tipo_pago
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':mes' => $mes, ':anio' => $anio]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $res = [
            'POS' => ['PEN' => 0, 'USD' => 0, 'CLP' => 0],
            'EFECTIVO' => ['PEN' => 0, 'USD' => 0, 'CLP' => 0],
            'YAPE' => 0,
            'TRANSFERENCIA' => 0
        ];

        foreach ($rows as $r) {
            $m = $r['moneda'];
            $t = $r['tipo_pago'];
            $val = (float)$r['total'];

            if (strpos($t, 'POS') !== false) $res['POS'][$m] += $val;
            elseif ($t === 'EFECTIVO') $res['EFECTIVO'][$m] += $val;
            elseif (in_array($t, ['YAPE', 'PLIN', 'YAPE O PLIN'])) $res['YAPE'] += $val;
            elseif (in_array($t, ['TRANSFERENCIA', 'DEPOSITO', 'TRANSF', 'DEPOS/TRANS.'])) $res['TRANSFERENCIA'] += $val;
        }
        return $res;
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
            'utilidad_neta' => ($ingHosting + $otrosIng) - ($egresosOp + $gastosCch + $gastosYape)
        ];
    }
}
