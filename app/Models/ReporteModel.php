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
                h.numero AS habitacion,
                h.tipo AS tipo_hab,
                COUNT(s.id) AS num_estadias,
                SUM(s.precio_noche * s.noches) AS venta_teorica,
                (SELECT SUM(monto) FROM rooming_pagos WHERE stay_id IN (SELECT id FROM rooming_stays WHERE habitacion_id = h.id AND MONTH(fecha_registro) = :mes AND YEAR(fecha_registro) = :anio)) AS cobrado_total,
                (SELECT SUM(monto) FROM rooming_pagos WHERE stay_id IN (SELECT id FROM rooming_stays WHERE habitacion_id = h.id AND MONTH(fecha_registro) = :mes AND YEAR(fecha_registro) = :anio) AND metodo = 'EFECTIVO') AS cobrado_efectivo,
                (SELECT SUM(monto) FROM rooming_pagos WHERE stay_id IN (SELECT id FROM rooming_stays WHERE habitacion_id = h.id AND MONTH(fecha_registro) = :mes AND YEAR(fecha_registro) = :anio) AND metodo LIKE 'POS%') AS cobrado_pos,
                (SELECT SUM(monto) FROM rooming_pagos WHERE stay_id IN (SELECT id FROM rooming_stays WHERE habitacion_id = h.id AND MONTH(fecha_registro) = :mes AND YEAR(fecha_registro) = :anio) AND metodo = 'YAPE O PLIN') AS cobrado_yape
            FROM habitaciones h
            LEFT JOIN rooming_stays s ON h.id = s.habitacion_id 
                AND MONTH(s.fecha_registro) = :mes AND YEAR(s.fecha_registro) = :anio
            GROUP BY h.id, h.numero
            ORDER BY h.piso, h.numero
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':mes' => $mes, ':anio' => $anio]);
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
            WHERE MONTH(y.fecha) = :mes AND YEAR(y.fecha) = :anio AND y.estado = 'cerrado'
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
        // 1. Ingresos Rooming (Lodging)
        $sqlIng = "SELECT SUM(monto) FROM rooming_pagos WHERE MONTH(fecha_pago) = :mes AND YEAR(fecha_pago) = :anio";
        $stmt = $this->pdo->prepare($sqlIng);
        $stmt->execute([':mes' => $mes, ':anio' => $anio]);
        $ingHosting = (float)$stmt->fetchColumn();

        // 2. Otros Ingresos (Venta productos, early checkin, etc en Flujo)
        $sqlOtros = "SELECT SUM(monto) FROM flujo_caja_movimientos WHERE tipo='Ingreso' AND categoria NOT IN ('HABITACIÓN', 'YAPE O PLIN') AND flujo_id IN (SELECT id FROM flujo_caja WHERE MONTH(fecha) = :mes AND YEAR(fecha) = :anio)";
        $stmt = $this->pdo->prepare($sqlOtros);
        $stmt->execute([':mes' => $mes, ':anio' => $anio]);
        $otrosIng = (float)$stmt->fetchColumn();

        // 3. Egresos Operativos (Flujo)
        $sqlEgr = "SELECT SUM(monto) FROM flujo_caja_movimientos WHERE tipo='Egreso' AND flujo_id IN (SELECT id FROM flujo_caja WHERE MONTH(fecha) = :mes AND YEAR(fecha) = :anio)";
        $stmt = $this->pdo->prepare($sqlEgr);
        $stmt->execute([':mes' => $mes, ':anio' => $anio]);
        $egresosOp = (float)$stmt->fetchColumn();

        // 4. Gastos Yape (Reporte Alex)
        $sqlYape = "SELECT SUM(total_gastado) FROM gastos_yape WHERE MONTH(fecha) = :mes AND YEAR(fecha) = :anio AND estado='cerrado'";
        $stmt = $this->pdo->prepare($sqlYape);
        $stmt->execute([':mes' => $mes, ':anio' => $anio]);
        $gastosYape = (float)$stmt->fetchColumn();

        return [
            'ingresos_hospedaje' => $ingHosting,
            'otros_ingresos' => $otrosIng,
            'egresos_operativos' => $egresosOp,
            'gastos_yape' => $gastosYape,
            'utilidad_neta' => ($ingHosting + $otrosIng) - ($egresosOp + $gastosYape)
        ];
    }
}
