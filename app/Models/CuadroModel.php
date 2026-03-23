<?php
/**
 * app/Models/CuadroModel.php
 */
class CuadroModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Returns all rooms with their stays for the given month.
     * Single optimized JOIN — no N+1 queries.
     */
    public function getDatosMes(int $mes, int $anio): array {
        $primerDia  = sprintf('%04d-%02d-01', $anio, $mes);
        $ultimoDia  = date('Y-m-t', strtotime($primerDia));
        $diasEnMes  = (int)date('t', strtotime($primerDia));

        // 1. All rooms
        $stmtHab = $this->pdo->query(
            "SELECT id, numero, tipo, estado,
                    CAST(SUBSTRING(numero, 1, 1) AS UNSIGNED) AS piso
             FROM habitaciones
             ORDER BY piso ASC, numero ASC"
        );
        $habitacionesRaw = $stmtHab->fetchAll(PDO::FETCH_ASSOC);

        // 2. All stays overlapping the month (single query)
        $stmt = $this->pdo->prepare(
            "SELECT
                 s.id,
                 s.habitacion_id,
                 s.fecha_registro,
                 s.fecha_checkout,
                 s.noches,
                 s.pax_total,
                 s.estado_pago,
                 s.total_pago,
                 s.total_cobrado,
                 s.moneda_pago,
                 s.medio_reserva  AS canal,
                 s.estado,
                 s.metodo_pago,
                 s.observaciones,
                 p.nombre_completo AS titular
             FROM rooming_stays s
             LEFT JOIN rooming_pax p ON p.stay_id = s.id AND p.es_titular = 1
             WHERE s.estado IN ('activo','late_checkout')
               AND s.fecha_registro <= :ultimo
               AND s.fecha_checkout  > :primero"
        );
        $stmt->execute([':ultimo' => $ultimoDia, ':primero' => $primerDia]);
        $staysRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Index by room id
        $staysByRoom = [];
        foreach ($staysRaw as $s) {
            $diaIni = (int)date('j', strtotime(max($s['fecha_registro'], $primerDia)));
            $diaFin = (int)date('j', strtotime(min($s['fecha_checkout'],  $ultimoDia)));
            $cols   = max(1, $diaFin - $diaIni);

            $staysByRoom[$s['habitacion_id']][] = [
                'id'            => (int)$s['id'],
                'dia_inicio'    => $diaIni,
                'dia_fin'       => $diaFin,
                'cols'          => $cols,
                'fecha_inicio'  => $s['fecha_registro'],
                'fecha_fin'     => $s['fecha_checkout'],
                'noches'        => (int)$s['noches'],
                'titular'       => $s['titular'] ?? '---',
                'pax'           => (int)$s['pax_total'],
                'estado_pago'   => $s['estado_pago'],
                'total_pago'    => (float)$s['total_pago'],
                'total_cobrado' => (float)$s['total_cobrado'],
                'moneda_pago'   => $s['moneda_pago'],
                'canal'         => $s['canal'],
                'estado'        => $s['estado'],
                'metodo_pago'   => $s['metodo_pago'],
                'observaciones' => $s['observaciones'],
            ];
        }

        // 4. Build result
        $habitaciones = [];
        foreach ($habitacionesRaw as $h) {
            $habitaciones[] = [
                'id'     => (int)$h['id'],
                'numero' => $h['numero'],
                'tipo'   => $h['tipo'],
                'estado' => $h['estado'],
                'piso'   => (int)$h['piso'],
                'stays'  => $staysByRoom[$h['id']] ?? [],
            ];
        }

        return ['habitaciones' => $habitaciones, 'dias_en_mes' => $diasEnMes];
    }

    /**
     * Today's summary panel.
     */
    public function getResumenDia(string $fecha): array {
        $stmt = $this->pdo->prepare(
            "SELECT
                 COUNT(DISTINCT s.id)               AS ocupadas,
                 (SELECT COUNT(*) FROM habitaciones) AS total,
                 COALESCE(SUM(s.pax_total), 0)       AS pax_total,
                 COALESCE(SUM(s.total_cobrado), 0)   AS ingresos_hoy,
                 SUM(s.estado_pago != 'pagado')      AS pendientes,
                 SUM(s.estado_pago = 'pendiente')    AS cnt_pendiente,
                 SUM(s.estado_pago = 'adelanto')     AS cnt_adelanto,
                 SUM(s.estado_pago = 'parcial')      AS cnt_parcial,
                 SUM(s.estado_pago = 'pagado')       AS cnt_pagado
             FROM rooming_stays s
             WHERE s.estado IN ('activo','late_checkout')
               AND s.fecha_registro <= :fecha
               AND s.fecha_checkout  > :fecha2"
        );
        $stmt->execute([':fecha' => $fecha, ':fecha2' => $fecha]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'ocupadas'      => (int)($row['ocupadas']      ?? 0),
            'total'         => (int)($row['total']         ?? 0),
            'pax_total'     => (int)($row['pax_total']     ?? 0),
            'ingresos_hoy'  => (float)($row['ingresos_hoy'] ?? 0),
            'pendientes'    => (int)($row['pendientes']    ?? 0),
            'cnt_pendiente' => (int)($row['cnt_pendiente'] ?? 0),
            'cnt_adelanto'  => (int)($row['cnt_adelanto']  ?? 0),
            'cnt_parcial'   => (int)($row['cnt_parcial']   ?? 0),
            'cnt_pagado'    => (int)($row['cnt_pagado']    ?? 0),
        ];
    }

    /**
     * Register a quick payment and recalculate estado_pago.
     */
    public function pagoRapido(int $stay_id, float $monto, string $moneda, string $metodo, float $tc, int $uid): array {
        $monto_pen = $moneda === 'PEN' ? $monto : round($monto * $tc, 2);

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO anticipos (stay_id, monto, moneda, monto_pen, tc_aplicado, tipo_pago, recibo, fecha, usuario_id)
                 VALUES (?, ?, ?, ?, ?, ?, '', NOW(), ?)"
            );
            $stmt->execute([$stay_id, $monto, $moneda, $monto_pen, $tc, $metodo, $uid]);

            // Recalculate
            $stmt = $this->pdo->prepare("SELECT SUM(monto_pen) FROM anticipos WHERE stay_id = ?");
            $stmt->execute([$stay_id]);
            $totalCobrado = (float)$stmt->fetchColumn();

            $stmt = $this->pdo->prepare("SELECT total_pago FROM rooming_stays WHERE id = ?");
            $stmt->execute([$stay_id]);
            $totalPago = (float)$stmt->fetchColumn();

            $estadoPago = 'pendiente';
            if ($totalCobrado >= $totalPago)               $estadoPago = 'pagado';
            elseif ($totalCobrado >= $totalPago * 0.5)     $estadoPago = 'parcial';
            elseif ($totalCobrado > 0)                     $estadoPago = 'adelanto';

            $stmt = $this->pdo->prepare(
                "UPDATE rooming_stays SET total_cobrado = ?, estado_pago = ? WHERE id = ?"
            );
            $stmt->execute([$totalCobrado, $estadoPago, $stay_id]);

            $this->pdo->commit();
            return ['ok' => true, 'total_cobrado' => $totalCobrado, 'estado_pago' => $estadoPago];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Mark a stay as late_checkout.
     */
    public function lateCheckout(int $id): bool {
        $stmt = $this->pdo->prepare(
            "UPDATE rooming_stays SET estado = 'late_checkout' WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }
}
