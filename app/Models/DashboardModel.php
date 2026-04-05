<?php
/**
 * Modelo del Dashboard.
 *
 * Realiza consultas complejas y agregaciones para generar KPIs financieros,
 * estados de ocupación y proyecciones de ingresos.
 *
 * @package App\Models
 */
class DashboardModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Compila todas las métricas administrativas para una fecha dada.
     * Calcula ocupación, PAX, ingresos multidivisa (PEN, USD, CLP), egresos,
     * cobros pendientes y datos para el gráfico comparativo mensual.
     *
     * @param string $fecha Fecha de referencia (Y-m-d)
     * @return array Mapa de métricas agrupadas por KPI, desgloses y listas.
     */
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

        // 3A. Ingresos reales de Rooming (anticipos): no se hace conversión para reflejar caja física separada
        $stmtAnticipos = $this->pdo->prepare("SELECT monto, tipo_pago, moneda FROM anticipos WHERE fecha = ?");
        $stmtAnticipos->execute([$fecha]);
        $pagosRooming = $stmtAnticipos->fetchAll(PDO::FETCH_ASSOC);

        // 3B. Ingresos extras y Egresos del Flujo de Caja
        $stmtFlujo = $this->pdo->prepare("
            SELECT m.tipo, m.categoria, m.moneda, m.monto, m.medio_pago, m.observacion
            FROM flujo_caja f 
            JOIN flujo_caja_movimientos m ON f.id = m.flujo_id 
            WHERE f.fecha = ? AND m.monto > 0 AND f.estado != 'borrador_eliminado'
        ");
        $stmtFlujo->execute([$fecha]);
        $movimientos = $stmtFlujo->fetchAll(PDO::FETCH_ASSOC);

        $ingresos_desglose = [];
        $egresos_desglose = [];
        
        $ingresos_hoy = ['PEN' => 0, 'USD' => 0, 'CLP' => 0];
        $egresos_hoy = ['PEN' => 0, 'USD' => 0, 'CLP' => 0];

        // Sumar pagos de Rooming directos primero
        foreach ($pagosRooming as $p) {
            $moneda = $p['moneda'] ?: 'PEN';
            $monto = (float)$p['monto'];
            $medio = strtoupper($p['tipo_pago']);
            
            // Auto-corrector por si la data antigua se guardó como PEN
            if (strpos($medio, 'PESOS') !== false) $moneda = 'CLP';
            if (strpos($medio, 'DOLARES') !== false || strpos($medio, 'USD') !== false) $moneda = 'USD';

            $ingresos_hoy[$moneda] = ($ingresos_hoy[$moneda] ?? 0) + $monto;

            $cat = ($medio === 'EFECTIVO' || $medio === 'EFECTIV') ? 'Efectivo '.$moneda : $medio;
            if (!isset($ingresos_desglose[$cat])) $ingresos_desglose[$cat] = ['moneda' => $moneda, 'monto' => 0];
            $ingresos_desglose[$cat]['monto'] += $monto;
        }

        // Sumar Flujo de Caja
        foreach ($movimientos as $m) {
            $moneda = $m['moneda'] ?: 'PEN';
            $monto = (float)$m['monto'];
            $medio = strtoupper($m['medio_pago']);

            if ($m['tipo'] === 'Ingreso') {
                if (strtolower(trim($m['categoria'])) === 'alojamiento / pago extra') continue;
                if (strpos($m['observacion'] ?? '', 'PAGO Stay #') !== false) continue; // Evitar doblaje

                // Auto-corrector
                if (strpos($medio, 'PESOS') !== false || strpos(strtoupper($m['categoria']), 'PESOS') !== false) $moneda = 'CLP';
                if (strpos($medio, 'DOLARES') !== false || strpos(strtoupper($m['categoria']), 'DOLARES') !== false) $moneda = 'USD';

                $ingresos_hoy[$moneda] = ($ingresos_hoy[$moneda] ?? 0) + $monto;
                
                $cat = ($medio === 'EFECTIVO' || $medio === 'EFECTIV') ? 'Efectivo '.$moneda : $medio;
                if (!isset($ingresos_desglose[$cat])) $ingresos_desglose[$cat] = ['moneda' => $moneda, 'monto' => 0];
                $ingresos_desglose[$cat]['monto'] += $monto;
            } else {
                if (strpos($medio, 'PESOS') !== false || strpos(strtoupper($m['categoria']), 'PESOS') !== false) $moneda = 'CLP';
                if (strpos($medio, 'DOLARES') !== false || strpos(strtoupper($m['categoria']), 'DOLARES') !== false) $moneda = 'USD';

                $egresos_hoy[$moneda] = ($egresos_hoy[$moneda] ?? 0) + $monto;
                $cat = empty($m['categoria']) ? 'Otros' : $m['categoria'];
                if (!isset($egresos_desglose[$cat])) $egresos_desglose[$cat] = ['moneda' => $moneda, 'monto' => 0];
                $egresos_desglose[$cat]['monto'] += $monto;
            }
        }

        // Format for Vue
        $ing_arr = []; foreach($ingresos_desglose as $k => $v) if ($v['monto'] > 0) $ing_arr[] = ['categoria' => $k, 'moneda' => $v['moneda'], 'monto' => $v['monto']];
        $egr_arr = []; foreach($egresos_desglose as $k => $v) if ($v['monto'] > 0) $egr_arr[] = ['categoria' => $k, 'moneda' => $v['moneda'], 'monto' => $v['monto']];

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
                s.estado_pago AS estado,
                s.moneda_pago
            FROM rooming_stays s
            JOIN habitaciones h ON s.habitacion_id = h.id
            WHERE s.estado_pago != 'pagado' AND s.estado IN ('activo', 'late_checkout')
            ORDER BY debe DESC;
        ";
        $cobros_pendientes = $this->pdo->query($sqlCobros)->fetchAll(PDO::FETCH_ASSOC);

        $pendientes_hoy = ['PEN' => 0, 'USD' => 0, 'CLP' => 0];
        foreach ($cobros_pendientes as $c) {
            $moneda = $c['moneda_pago'] ?: 'PEN';
            $monto = (float)$c['debe'];
            $pendientes_hoy[$moneda] = ($pendientes_hoy[$moneda] ?? 0) + $monto;
        }

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

        $neto_hoy = [
            'PEN' => round(($ingresos_hoy['PEN'] ?? 0) - ($egresos_hoy['PEN'] ?? 0), 2),
            'USD' => round(($ingresos_hoy['USD'] ?? 0) - ($egresos_hoy['USD'] ?? 0), 2),
            'CLP' => round(($ingresos_hoy['CLP'] ?? 0) - ($egresos_hoy['CLP'] ?? 0), 2)
        ];

        // 7. Gráfico Mes (Ingresos reales combinados vs Egresos)
        $mesActual = date('Y-m', strtotime($fecha));
        $stmtGrafico = $this->pdo->prepare("
            SELECT dia, SUM(ingresos) as ingresos, SUM(egresos) as egresos FROM (
               -- Origen 1: Pagos cobrados directamente de Rooming (anticipos)
               SELECT fecha as dia, SUM(monto_pen) as ingresos, 0 as egresos 
               FROM anticipos WHERE DATE_FORMAT(fecha, '%Y-%m') = ? GROUP BY fecha
               UNION ALL
               -- Origen 2: Flujo extras y Egresos (Ignorando Rooming duplicado)
               SELECT f.fecha as dia, 
                 SUM(CASE WHEN m.tipo='Ingreso' AND m.moneda='PEN' AND m.categoria != 'Alojamiento / Pago extra' THEN m.monto ELSE 0 END) as ingresos,
                 SUM(CASE WHEN m.tipo='Egreso' AND m.moneda='PEN' THEN m.monto ELSE 0 END) as egresos
               FROM flujo_caja f
               JOIN flujo_caja_movimientos m ON f.id = m.flujo_id
               WHERE DATE_FORMAT(f.fecha, '%Y-%m') = ?
               GROUP BY f.fecha
            ) t GROUP BY dia ORDER BY dia ASC
        ");
        $stmtGrafico->execute([$mesActual, $mesActual]);
        $grafico_mes = $stmtGrafico->fetchAll(PDO::FETCH_ASSOC);

        return [
            'kpi' => [
                'ocupacion' => ['ocupadas' => (int)$ocupacion['ocupadas'], 'total' => (int)$ocupacion['total']],
                'pax_hoy' => $pax_hoy,
                'ingresos_hoy' => $ingresos_hoy,
                'pendientes_hoy' => $pendientes_hoy,
                'egresos_hoy' => $egresos_hoy,
                'neto_hoy' => $neto_hoy
            ],
            'ingresos_desglose' => $ing_arr,
            'egresos_desglose' => $egr_arr,
            'habitaciones' => $habs,
            'cobros_pendientes' => $cobros_pendientes,
            'sobres' => $sobres,
            'grafico_mes' => $grafico_mes
        ];
    }

    /**
     * Compila los datos operativos específicos para el personal de recepción/caja.
     * Prioriza deudas urgentes, movimientos del turno actual y estados de habitaciones.
     *
     * @param string $fecha Fecha actual
     * @param int $usuarioId ID del usuario logueado
     * @param string $turno Nombre del turno (MAÑANA/TARDE/NOCHE)
     * @return array Datos operativos incluyendo check-ins, check-outs y estado del turno.
     */
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
            'id' => $flujoRow ? $flujoRow['id'] : null,
            'ingresos' => 0,
            'egresos' => 0,
            'efectivo_sobre' => 0,
            'estado' => $flujoRow ? $flujoRow['estado'] : 'inexistente'
        ];

        if ($flujoRow) {
            $flujoId = $flujoRow['id'];
            $stmtMovs = $this->pdo->prepare("
                SELECT 
                   COALESCE(SUM(CASE WHEN tipo='Ingreso' AND moneda='PEN' THEN monto ELSE 0 END), 0) AS ing_pen,
                   COALESCE(SUM(CASE WHEN tipo='Egreso' AND moneda='PEN' THEN monto ELSE 0 END), 0) AS egr_pen,
                   COALESCE(SUM(CASE WHEN tipo='Ingreso' AND moneda='USD' THEN monto ELSE 0 END), 0) AS ing_usd,
                   COALESCE(SUM(CASE WHEN tipo='Ingreso' AND moneda='CLP' THEN monto ELSE 0 END), 0) AS ing_clp,
                   COALESCE(SUM(CASE WHEN medio_pago='EFECTIVO' AND moneda='PEN' AND tipo='Ingreso' THEN monto ELSE 0 END), 0) -
                   COALESCE(SUM(CASE WHEN medio_pago='EFECTIVO' AND moneda='PEN' AND tipo='Egreso'  THEN monto ELSE 0 END), 0) AS efec
                FROM flujo_caja_movimientos
                WHERE flujo_id = ?
            ");
            $stmtMovs->execute([$flujoId]);
            $movData = $stmtMovs->fetch(PDO::FETCH_ASSOC);

            if ($movData) {
                $mi_turno['ingresos'] = (float)$movData['ing_pen']; // Soles base
                $mi_turno['ingresos_usd'] = (float)$movData['ing_usd'];
                $mi_turno['ingresos_clp'] = (float)$movData['ing_clp'];
                $mi_turno['egresos']  = (float)$movData['egr_pen']; // Solo SOLES
                $mi_turno['efectivo_sobre'] = (float)$movData['efec'];
            }
        }

        // 5. KPIs globales (Ocupación, PAX, Ingresos, Egresos) para las tarjetas superiores
        $stmtOcup = $this->pdo->query("SELECT COUNT(id) as total, SUM(CASE WHEN estado IN ('ocupado','ocupada') THEN 1 ELSE 0 END) as ocupadas FROM habitaciones");
        $ocupacion = $stmtOcup->fetch(PDO::FETCH_ASSOC);

        $stmtPax = $this->pdo->prepare("SELECT SUM(pax_total) FROM rooming_stays WHERE estado IN ('activo','late_checkout') AND fecha_registro <= ? AND fecha_checkout > ?");
        $stmtPax->execute([$fecha, $fecha]);
        $pax_hoy = (int)$stmtPax->fetchColumn();

        $stmtAnticipos = $this->pdo->prepare("SELECT monto, tipo_pago, moneda FROM anticipos WHERE fecha = ?");
        $stmtAnticipos->execute([$fecha]);
        $pagosRooming = $stmtAnticipos->fetchAll(PDO::FETCH_ASSOC);

        $stmtFlujo = $this->pdo->prepare("SELECT m.tipo, m.categoria, m.moneda, m.monto, m.medio_pago, m.observacion FROM flujo_caja f JOIN flujo_caja_movimientos m ON f.id = m.flujo_id WHERE f.fecha = ? AND m.monto > 0 AND f.estado != 'borrador_eliminado'");
        $stmtFlujo->execute([$fecha]);
        $movimientos = $stmtFlujo->fetchAll(PDO::FETCH_ASSOC);

        $ingresos_hoy = ['PEN' => 0, 'USD' => 0, 'CLP' => 0];
        $egresos_hoy = ['PEN' => 0, 'USD' => 0, 'CLP' => 0];

        foreach ($pagosRooming as $p) {
            $moneda = $p['moneda'] ?: 'PEN';
            $monto = (float)$p['monto'];
            $medio = strtoupper($p['tipo_pago']);
            if (strpos($medio, 'PESOS') !== false) $moneda = 'CLP';
            if (strpos($medio, 'DOLARES') !== false || strpos($medio, 'USD') !== false) $moneda = 'USD';
            $ingresos_hoy[$moneda] = ($ingresos_hoy[$moneda] ?? 0) + $monto;
        }

        foreach ($movimientos as $m) {
            $moneda = $m['moneda'] ?: 'PEN';
            $monto = (float)$m['monto'];
            $medio = strtoupper($m['medio_pago']);
            if ($m['tipo'] === 'Ingreso') {
                if (strtolower(trim($m['categoria'])) === 'alojamiento / pago extra') continue;
                if (strpos($m['observacion'] ?? '', 'PAGO Stay #') !== false) continue; // Evitar contar 2 veces si helper sobribió categoría

                if (strpos($medio, 'PESOS') !== false || strpos(strtoupper($m['categoria']), 'PESOS') !== false) $moneda = 'CLP';
                if (strpos($medio, 'DOLARES') !== false || strpos(strtoupper($m['categoria']), 'DOLARES') !== false) $moneda = 'USD';
                $ingresos_hoy[$moneda] = ($ingresos_hoy[$moneda] ?? 0) + $monto;
            } else {
                if (strpos($medio, 'PESOS') !== false || strpos(strtoupper($m['categoria']), 'PESOS') !== false) $moneda = 'CLP';
                if (strpos($medio, 'DOLARES') !== false || strpos(strtoupper($m['categoria']), 'DOLARES') !== false) $moneda = 'USD';
                $egresos_hoy[$moneda] = ($egresos_hoy[$moneda] ?? 0) + $monto;
            }
        }

        $sqlCobros = "SELECT (s.total_pago - COALESCE(s.total_cobrado, 0)) AS debe, s.moneda_pago FROM rooming_stays s WHERE s.estado_pago != 'pagado' AND s.estado IN ('activo', 'late_checkout')";
        $cobros_pendientes_arr = $this->pdo->query($sqlCobros)->fetchAll(PDO::FETCH_ASSOC);
        $pendientes_hoy = ['PEN' => 0, 'USD' => 0, 'CLP' => 0];
        foreach ($cobros_pendientes_arr as $c) {
            $moneda = $c['moneda_pago'] ?: 'PEN';
            $pendientes_hoy[$moneda] = ($pendientes_hoy[$moneda] ?? 0) + (float)$c['debe'];
        }

        $kpi = [
            'ocupacion' => ['ocupadas' => (int)$ocupacion['ocupadas'], 'total' => (int)$ocupacion['total']],
            'pax_hoy' => $pax_hoy,
            'ingresos_hoy' => $ingresos_hoy,
            'pendientes_hoy' => $pendientes_hoy,
            'egresos_hoy' => $egresos_hoy
        ];

        return [
            'urgentes' => $urgentes,
            'checkouts_hoy' => $checkouts_hoy,
            'checkins_esperados' => $checkins_esperados,
            'mi_turno' => $mi_turno,
            'kpi' => $kpi
        ];
    }
}
