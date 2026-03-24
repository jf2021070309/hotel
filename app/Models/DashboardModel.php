<?php
/**
 * app/Models/DashboardModel.php
 */
class DashboardModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAdminData(string $fecha): array {
        // 1. KPI Ocupación
        $stmtOcup = $this->pdo->query("SELECT COUNT(id) as total, SUM(CASE WHEN estado IN ('ocupado','ocupada') THEN 1 ELSE 0 END) as ocupadas FROM habitaciones");
        $ocupacion = $stmtOcup->fetch(PDO::FETCH_ASSOC);

        // 2. KPI PAX Hoy
        $stmtPax = $this->pdo->prepare("SELECT SUM(pax_total) FROM rooming_stays WHERE estado IN ('activo','late_checkout') AND fecha_registro <= ? AND fecha_checkout > ?");
        $stmtPax->execute([$fecha, $fecha]);
        $pax_hoy = (int)$stmtPax->fetchColumn();

        // Extraer TC del día para cálculos
        $stmtTC = $this->pdo->prepare("SELECT moneda_origen, factor FROM tipos_cambio WHERE fecha = ?");
        $stmtTC->execute([$fecha]);
        $tcData = $stmtTC->fetchAll(PDO::FETCH_ASSOC);
        $tc = ['USD' => 3.7, 'CLP' => 0.0039]; 
        foreach($tcData as $row) { $tc[$row['moneda_origen']] = (float)$row['factor']; }

        // 3. Desglose Ingresos y Egresos (Turnos cerrados u hoy abierto)
        $stmtFlujo = $this->pdo->prepare("
            SELECT m.tipo, m.categoria, m.moneda, m.monto, m.medio_pago
            FROM flujo_caja f 
            JOIN flujo_caja_movimientos m ON f.id = m.flujo_id 
            WHERE f.fecha = ? AND m.monto > 0 AND f.estado != 'borrador_eliminado'
        ");
        $stmtFlujo->execute([$fecha]);
        $movimientos = $stmtFlujo->fetchAll(PDO::FETCH_ASSOC);

        $ingresos_desglose = [];
        $egresos_desglose = [];
        $ingresos_hoy = 0;
        $egresos_hoy = 0;

        foreach ($movimientos as $m) {
            $montoSoles = (float)$m['monto'];
            if ($m['moneda'] === 'USD') $montoSoles *= $tc['USD'];
            if ($m['moneda'] === 'CLP') $montoSoles *= $tc['CLP'];

            if ($m['tipo'] === 'Ingreso') {
                $ingresos_hoy += $montoSoles;
                // Group by payment category or method
                $cat = $m['medio_pago'] === 'EFECTIVO' ? 'Efectivo '.$m['moneda'] : $m['medio_pago'];
                if (!isset($ingresos_desglose[$cat])) $ingresos_desglose[$cat] = 0;
                $ingresos_desglose[$cat] += $montoSoles;
            } else {
                $egresos_hoy += $montoSoles;
                $cat = empty($m['categoria']) ? 'Otros' : $m['categoria'];
                if (!isset($egresos_desglose[$cat])) $egresos_desglose[$cat] = 0;
                $egresos_desglose[$cat] += $montoSoles;
            }
        }

        // Format for Vue
        $ing_arr = []; foreach($ingresos_desglose as $k => $v) $ing_arr[] = ['categoria' => $k, 'monto' => $v];
        $egr_arr = []; foreach($egresos_desglose as $k => $v) $egr_arr[] = ['categoria' => $k, 'monto' => $v];

        // 4. Estado de Habitaciones
        $stmtHab = $this->pdo->query("SELECT estado, COUNT(id) as c FROM habitaciones GROUP BY estado");
        $habs = ['libres'=>0, 'ocupadas'=>0, 'limpieza'=>0, 'mantenimiento'=>0, 'late_checkout'=>0];
        foreach($stmtHab->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $e = strtolower($row['estado']);
            if ($e === 'libre') $habs['libres'] = (int)$row['c'];
            elseif ($e === 'ocupado' || $e === 'ocupada') $habs['ocupadas'] = (int)$row['c'];
            elseif (isset($habs[$e])) $habs[$e] = (int)$row['c'];
        }

        // 5. Cobros Pendientes
        $sqlCobros = "
            SELECT 
                h.numero AS hab, 
                COALESCE((SELECT nombre_completo FROM rooming_pax p WHERE p.stay_id = s.id AND es_titular=1 LIMIT 1), 'Huésped no asignado') AS huesped, 
                (s.total_pago - COALESCE(s.total_cobrado, 0)) AS debe, 
                s.estado_pago AS estado
            FROM rooming_stays s
            JOIN habitaciones h ON s.habitacion_id = h.id
            WHERE s.estado_pago != 'pagado' AND s.estado IN ('activo', 'late_checkout')
            ORDER BY debe DESC;
        ";
        $cobros_pendientes = $this->pdo->query($sqlCobros)->fetchAll(PDO::FETCH_ASSOC);

        // 6. Sobres del día
        $stmtSobres = $this->pdo->prepare("
            SELECT turno, estado,
            COALESCE(SUM(CASE WHEN m.medio_pago='EFECTIVO' AND m.tipo='Ingreso' AND m.moneda='PEN' THEN m.monto ELSE 0 END), 0) -
            COALESCE(SUM(CASE WHEN m.medio_pago='EFECTIVO' AND m.tipo='Egreso' AND m.moneda='PEN'  THEN m.monto ELSE 0 END), 0) AS monto
            FROM flujo_caja f
            LEFT JOIN flujo_caja_movimientos m ON f.id = m.flujo_id
            WHERE f.fecha = ?
            GROUP BY f.id, f.turno, f.estado
        ");
        $stmtSobres->execute([$fecha]);
        $sobresData = $stmtSobres->fetchAll(PDO::FETCH_ASSOC);
        $sobres = ['manana' => ['monto'=>0, 'estado'=>'N/A'], 'tarde' => ['monto'=>0, 'estado'=>'N/A']];
        foreach ($sobresData as $s) {
            $t = strtolower(str_replace('Ñ', 'n', $s['turno']));
            if (isset($sobres[$t])) {
                $sobres[$t] = ['monto' => (float)$s['monto'], 'estado' => $s['estado']];
            }
        }

        // 7. Gráfico Mes (Solo Ingresos vs Egresos en SOLES)
        $mesActual = date('Y-m', strtotime($fecha));
        $stmtGrafico = $this->pdo->prepare("
            SELECT f.fecha as dia, 
                   SUM(CASE WHEN m.tipo='Ingreso' AND m.moneda='PEN' THEN m.monto ELSE 0 END) as ingresos,
                   SUM(CASE WHEN m.tipo='Egreso' AND m.moneda='PEN' THEN m.monto ELSE 0 END) as egresos
            FROM flujo_caja f
            JOIN flujo_caja_movimientos m ON f.id = m.flujo_id
            WHERE DATE_FORMAT(f.fecha, '%Y-%m') = ?
            GROUP BY f.fecha
            ORDER BY f.fecha ASC
        ");
        $stmtGrafico->execute([$mesActual]);
        $grafico_mes = $stmtGrafico->fetchAll(PDO::FETCH_ASSOC);

        return [
            'kpi' => [
                'ocupacion' => ['ocupadas' => (int)$ocupacion['ocupadas'], 'total' => (int)$ocupacion['total']],
                'pax_hoy' => $pax_hoy,
                'ingresos_hoy' => round($ingresos_hoy, 2),
                'egresos_hoy' => round($egresos_hoy, 2),
                'neto_hoy' => round($ingresos_hoy - $egresos_hoy, 2)
            ],
            'ingresos_desglose' => $ing_arr,
            'egresos_desglose' => $egr_arr,
            'habitaciones' => $habs,
            'cobros_pendientes' => $cobros_pendientes,
            'sobres' => $sobres,
            'grafico_mes' => $grafico_mes
        ];
    }

    public function getCajeraData(string $fecha, int $usuarioId, string $turno): array {
        
        // 1. Urgentes (Top deuds for the shift to collect)
        $sqlUrgentes = "
            SELECT 
                h.numero AS hab, 
                COALESCE((SELECT nombre_completo FROM rooming_pax p WHERE p.stay_id = s.id AND es_titular=1 LIMIT 1), 'Desconocido') AS huesped, 
                (s.total_pago - COALESCE(s.total_cobrado, 0)) AS debe
            FROM rooming_stays s
            JOIN habitaciones h ON s.habitacion_id = h.id
            WHERE s.estado_pago != 'pagado' AND s.estado IN ('activo', 'late_checkout')
            ORDER BY debe DESC LIMIT 5
        ";
        $urgentes = $this->pdo->query($sqlUrgentes)->fetchAll(PDO::FETCH_ASSOC);

        // 2. Checkouts de hoy
        $stmtCheckouts = $this->pdo->prepare("
            SELECT 
                h.numero AS hab, 
                COALESCE((SELECT nombre_completo FROM rooming_pax p WHERE p.stay_id = s.id AND es_titular=1 LIMIT 1), 'Desconocido') AS huesped, 
                (s.total_pago - COALESCE(s.total_cobrado, 0)) AS saldo,
                s.estado_pago
            FROM rooming_stays s
            JOIN habitaciones h ON s.habitacion_id = h.id
            WHERE s.fecha_checkout = ? AND s.estado IN ('activo', 'late_checkout')
        ");
        $stmtCheckouts->execute([$fecha]);
        $checkouts_hoy = $stmtCheckouts->fetchAll(PDO::FETCH_ASSOC);

        // 3. Checkins esperados (if there is a reserved state, else empty for now as requested graceful degradation)
        $stmtCheckins = $this->pdo->prepare("
            SELECT 
                h.numero AS hab, 
                s.medio_reserva AS canal, 
                s.pax_total AS pax,
                COALESCE(s.hora_checkin, '14:00:00') as hora_estimada
            FROM rooming_stays s
            JOIN habitaciones h ON s.habitacion_id = h.id
            WHERE s.fecha_registro = ? AND s.estado = 'reserva'
        ");
        $stmtCheckins->execute([$fecha]);
        $checkins_esperados = $stmtCheckins->fetchAll(PDO::FETCH_ASSOC);

        // 4. Mi Turno (Current flow)
        $stmtF = $this->pdo->prepare("
            SELECT id, estado FROM flujo_caja 
            WHERE fecha = ? AND turno = ? AND usuario_id = ? 
            ORDER BY id DESC LIMIT 1
        ");
        $stmtF->execute([$fecha, $turno, $usuarioId]);
        $flujoRow = $stmtF->fetch(PDO::FETCH_ASSOC);

        $mi_turno = [
            'ingresos' => 0,
            'egresos' => 0,
            'efectivo_sobre' => 0,
            'estado' => $flujoRow ? $flujoRow['estado'] : 'inexistente'
        ];

        if ($flujoRow) {
            $flujoId = $flujoRow['id'];
            $stmtMovs = $this->pdo->prepare("
                SELECT 
                   COALESCE(SUM(CASE WHEN tipo='Ingreso' THEN monto ELSE 0 END), 0) AS ing,
                   COALESCE(SUM(CASE WHEN tipo='Egreso' THEN monto ELSE 0 END), 0) AS egr,
                   COALESCE(SUM(CASE WHEN medio_pago='EFECTIVO' AND moneda='PEN' AND tipo='Ingreso' THEN monto ELSE 0 END), 0) -
                   COALESCE(SUM(CASE WHEN medio_pago='EFECTIVO' AND moneda='PEN' AND tipo='Egreso'  THEN monto ELSE 0 END), 0) AS efec
                FROM flujo_caja_movimientos
                WHERE flujo_id = ?
            ");
            $stmtMovs->execute([$flujoId]);
            $movData = $stmtMovs->fetch(PDO::FETCH_ASSOC);

            if ($movData) {
                $mi_turno['ingresos'] = (float)$movData['ing'];
                $mi_turno['egresos']  = (float)$movData['egr'];
                $mi_turno['efectivo_sobre'] = (float)$movData['efec'];
            }
        }

        return [
            'urgentes' => $urgentes,
            'checkouts_hoy' => $checkouts_hoy,
            'checkins_esperados' => $checkins_esperados,
            'mi_turno' => $mi_turno
        ];
    }
}
