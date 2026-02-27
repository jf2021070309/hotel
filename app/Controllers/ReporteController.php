<?php
// ============================================================
// app/Controllers/ReporteController.php
// ============================================================
class ReporteController {
    private mysqli $db;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    /** Cuadre diario: totales de pagos y gastos de una fecha */
    public function cuadreDiario(string $fecha): void {
        // Pagos del día
        $stmt = $this->db->prepare("
            SELECT
                IFNULL(SUM(CASE WHEN metodo='efectivo' THEN monto ELSE 0 END), 0) efectivo,
                IFNULL(SUM(CASE WHEN metodo='tarjeta'  THEN monto ELSE 0 END), 0) tarjeta,
                IFNULL(SUM(monto), 0) total_ingresos
            FROM pagos WHERE fecha = ?
        ");
        $stmt->bind_param('s', $fecha);
        $stmt->execute();
        $pagos = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Gastos del día
        $stmt = $this->db->prepare("SELECT IFNULL(SUM(monto),0) total FROM gastos WHERE fecha = ?");
        $stmt->bind_param('s', $fecha);
        $stmt->execute();
        $stmt->bind_result($total_gastos);
        $stmt->fetch();
        $stmt->close();

        // Habitaciones ocupadas ese día
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM registros
            WHERE fecha_ingreso <= ? AND (fecha_salida >= ? OR fecha_salida IS NULL)
              AND estado = 'activo'
        ");
        $stmt->bind_param('ss', $fecha, $fecha);
        $stmt->execute();
        $stmt->bind_result($hab_ocupadas);
        $stmt->fetch();
        $stmt->close();

        // Detalle pagos
        $stmt = $this->db->prepare("
            SELECT p.monto, p.metodo, h.numero hab_num, c.nombre cliente
            FROM pagos p
            JOIN registros r ON r.id = p.registro_id
            JOIN habitaciones h ON h.id = r.habitacion_id
            JOIN clientes c ON c.id = r.cliente_id
            WHERE p.fecha = ? ORDER BY p.id DESC
        ");
        $stmt->bind_param('s', $fecha);
        $stmt->execute();
        $detalle_pagos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Detalle gastos
        $stmt = $this->db->prepare("SELECT descripcion, monto FROM gastos WHERE fecha = ? ORDER BY id DESC");
        $stmt->bind_param('s', $fecha);
        $stmt->execute();
        $detalle_gastos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $ganancia_neta = (float)$pagos['total_ingresos'] - (float)$total_gastos;

        json_response(true, [
            'fecha'          => $fecha,
            'efectivo'       => (float)$pagos['efectivo'],
            'tarjeta'        => (float)$pagos['tarjeta'],
            'total_ingresos' => (float)$pagos['total_ingresos'],
            'total_gastos'   => (float)$total_gastos,
            'ganancia_neta'  => $ganancia_neta,
            'hab_ocupadas'   => (int)$hab_ocupadas,
            'detalle_pagos'  => $detalle_pagos,
            'detalle_gastos' => $detalle_gastos,
        ]);
    }

    /** Reporte mensual */
    public function mensual(int $year, int $month): void {
        $inicio = sprintf('%04d-%02d-01', $year, $month);
        $fin    = date('Y-m-t', strtotime($inicio));

        // Ingresos del mes
        $stmt = $this->db->prepare("SELECT IFNULL(SUM(monto),0) FROM pagos WHERE fecha BETWEEN ? AND ?");
        $stmt->bind_param('ss', $inicio, $fin);
        $stmt->execute();
        $stmt->bind_result($total_ingresos);
        $stmt->fetch();
        $stmt->close();

        // Gastos del mes
        $stmt = $this->db->prepare("SELECT IFNULL(SUM(monto),0) FROM gastos WHERE fecha BETWEEN ? AND ?");
        $stmt->bind_param('ss', $inicio, $fin);
        $stmt->execute();
        $stmt->bind_result($total_gastos);
        $stmt->fetch();
        $stmt->close();

        // Registros del mes
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM registros WHERE fecha_ingreso BETWEEN ? AND ?");
        $stmt->bind_param('ss', $inicio, $fin);
        $stmt->execute();
        $stmt->bind_result($total_registros);
        $stmt->fetch();
        $stmt->close();

        // Desglose por método
        $stmt = $this->db->prepare("
            SELECT
                IFNULL(SUM(CASE WHEN metodo='efectivo' THEN monto ELSE 0 END),0) efectivo,
                IFNULL(SUM(CASE WHEN metodo='tarjeta'  THEN monto ELSE 0 END),0) tarjeta
            FROM pagos WHERE fecha BETWEEN ? AND ?
        ");
        $stmt->bind_param('ss', $inicio, $fin);
        $stmt->execute();
        $metodos = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Desglose diario (ingresos)
        $stmt = $this->db->prepare("
            SELECT DATE(fecha) dia, SUM(monto) total
            FROM pagos WHERE fecha BETWEEN ? AND ?
            GROUP BY DATE(fecha) ORDER BY dia
        ");
        $stmt->bind_param('ss', $inicio, $fin);
        $stmt->execute();
        $ing_dias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Desglose diario (gastos)
        $stmt = $this->db->prepare("
            SELECT DATE(fecha) dia, SUM(monto) total
            FROM gastos WHERE fecha BETWEEN ? AND ?
            GROUP BY DATE(fecha) ORDER BY dia
        ");
        $stmt->bind_param('ss', $inicio, $fin);
        $stmt->execute();
        $gas_dias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        json_response(true, [
            'year'             => $year,
            'month'            => $month,
            'total_ingresos'   => (float)$total_ingresos,
            'total_gastos'     => (float)$total_gastos,
            'ganancia_mes'     => (float)$total_ingresos - (float)$total_gastos,
            'total_registros'  => (int)$total_registros,
            'efectivo'         => (float)$metodos['efectivo'],
            'tarjeta'          => (float)$metodos['tarjeta'],
            'ingresos_por_dia' => $ing_dias,
            'gastos_por_dia'   => $gas_dias,
        ]);
    }

    /** Stats dashboard */
    public function dashboard(): void {
        // Stats habitaciones
        $row = $this->db->query("
            SELECT COUNT(*) total,
                   SUM(estado='libre')   libres,
                   SUM(estado='ocupado') ocupadas
            FROM habitaciones
        ")->fetch_assoc();

        // Ingresos del día
        $r = $this->db->query("SELECT IFNULL(SUM(monto),0) FROM pagos WHERE fecha = CURDATE()");
        $ingresos = (float)$r->fetch_row()[0];

        // Gastos del día
        $r = $this->db->query("SELECT IFNULL(SUM(monto),0) FROM gastos WHERE fecha = CURDATE()");
        $gastos = (float)$r->fetch_row()[0];

        // Habitaciones con huésped activo
        $sql = "SELECT h.id, h.numero, h.tipo, h.piso, h.estado, h.precio_base,
                       c.nombre cliente, r.precio precio_actual,
                       r.id reg_id, r.fecha_ingreso
                FROM habitaciones h
                LEFT JOIN registros r ON r.habitacion_id = h.id AND r.estado = 'activo'
                LEFT JOIN clientes  c ON c.id = r.cliente_id
                ORDER BY h.piso, h.numero";
        $habitaciones = $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);

        json_response(true, [
            'stats' => [
                'total'        => (int)$row['total'],
                'libres'       => (int)$row['libres'],
                'ocupadas'     => (int)$row['ocupadas'],
                'ingresos_dia' => $ingresos,
                'gastos_dia'   => $gastos,
                'ganancia_dia' => $ingresos - $gastos,
            ],
            'habitaciones' => $habitaciones,
        ]);
    }

    /** Datos para gráficos: ocupación actual + top habitaciones por ingresos */
    public function graficos(int $year, int $month): void {
        $inicio = sprintf('%04d-%02d-01', $year, $month);
        $fin    = date('Y-m-t', strtotime($inicio));

        // Estado actual de habitaciones
        $row = $this->db->query("
            SELECT COUNT(*) total,
                   SUM(estado='libre')   libres,
                   SUM(estado='ocupado') ocupadas
            FROM habitaciones
        ")->fetch_assoc();

        // Top 6 habitaciones por ingresos en el mes
        $stmt = $this->db->prepare("
            SELECT CONCAT('Hab. ', h.numero) habitacion,
                   IFNULL(SUM(p.monto), 0) total
            FROM habitaciones h
            LEFT JOIN registros r ON r.habitacion_id = h.id
            LEFT JOIN pagos p ON p.registro_id = r.id
                               AND p.fecha BETWEEN ? AND ?
            GROUP BY h.id, h.numero
            ORDER BY total DESC
            LIMIT 6
        ");
        $stmt->bind_param('ss', $inicio, $fin);
        $stmt->execute();
        $top_hab = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Check-ins por día del mes
        $stmt = $this->db->prepare("
            SELECT DATE(fecha_ingreso) dia, COUNT(*) total
            FROM registros
            WHERE fecha_ingreso BETWEEN ? AND ?
            GROUP BY DATE(fecha_ingreso) ORDER BY dia
        ");
        $stmt->bind_param('ss', $inicio, $fin);
        $stmt->execute();
        $checkins_dia = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        json_response(true, [
            'hab_total'    => (int)$row['total'],
            'hab_libres'   => (int)$row['libres'],
            'hab_ocupadas' => (int)$row['ocupadas'],
            'top_hab'      => $top_hab,
            'checkins_dia' => $checkins_dia,
        ]);
    }
}
