<?php
/**
 * api/cuadro/datos.php
 * GET ?mes=MM&anio=YYYY
 * Returns all rooms with their stays for the requested month + daily summary.
 * Single optimized JOIN — no N+1 queries.
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../auth/session.php';
requerirSesion();

$mes  = (int)($_GET['mes']  ?? date('n'));
$anio = (int)($_GET['anio'] ?? date('Y'));

// Validate range
if ($mes < 1 || $mes > 12) $mes = (int)date('n');
if ($anio < 2020 || $anio > 2100) $anio = (int)date('Y');

$primerDia = sprintf('%04d-%02d-01', $anio, $mes);
$ultimoDia = date('Y-m-t', strtotime($primerDia));
$diasEnMes = (int)date('t', strtotime($primerDia));
$hoy       = date('Y-m-d');

try {
    // ── 1. Load all rooms ────────────────────────────────────────────────────
    $stmtHab = $pdo->query(
        "SELECT id, numero, tipo, estado, piso
         FROM habitaciones
         ORDER BY piso ASC, numero ASC"
    );
    $habitacionesRaw = $stmtHab->fetchAll(PDO::FETCH_ASSOC);

    // ── 2. Load all stays that overlap the requested month (single query) ──
    $stmtStays = $pdo->prepare(
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
             s.observaciones,
             s.metodo_pago,
             p.nombre_completo AS titular
         FROM rooming_stays s
         LEFT JOIN rooming_pax p ON p.stay_id = s.id AND p.es_titular = 1
         WHERE s.estado IN ('activo', 'late_checkout')
           AND s.fecha_registro <= :ultimo
           AND s.fecha_checkout  > :primero"
    );
    $stmtStays->execute([':ultimo' => $ultimoDia, ':primero' => $primerDia]);
    $staysRaw = $stmtStays->fetchAll(PDO::FETCH_ASSOC);

    // ── 3. Index stays by room id ────────────────────────────────────────────
    $staysByRoom = [];
    foreach ($staysRaw as $s) {
        $staysByRoom[$s['habitacion_id']][] = [
            'id'            => (int)$s['id'],
            'dia_inicio'    => (int)date('j', strtotime(max($s['fecha_registro'], $primerDia))),
            'dia_fin'       => (int)date('j', strtotime(min($s['fecha_checkout'], $ultimoDia))),
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

    // ── 4. Build final rooms array ───────────────────────────────────────────
    $habitaciones = [];
    foreach ($habitacionesRaw as $h) {
        $habitaciones[] = [
            'id'     => (int)$h['id'],
            'numero' => $h['numero'],
            'tipo'   => $h['tipo'],
            'estado' => $h['estado'],
            'piso'   => (int)($h['piso'] ?? substr($h['numero'], 0, 1)),
            'stays'  => $staysByRoom[$h['id']] ?? [],
        ];
    }

    // ── 5. Daily summary (today) ─────────────────────────────────────────────
    $stmtResumen = $pdo->prepare(
        "SELECT
             COUNT(DISTINCT s.id)              AS ocupadas,
             (SELECT COUNT(*) FROM habitaciones) AS total,
             COALESCE(SUM(s.pax_total), 0)      AS pax_total,
             COALESCE(SUM(s.total_cobrado), 0)  AS ingresos_hoy,
             SUM(s.estado_pago != 'pagado')     AS pendientes,
             SUM(s.estado_pago = 'pendiente')   AS cnt_pendiente,
             SUM(s.estado_pago = 'adelanto')    AS cnt_adelanto,
             SUM(s.estado_pago = 'parcial')     AS cnt_parcial,
             SUM(s.estado_pago = 'pagado')      AS cnt_pagado
         FROM rooming_stays s
         WHERE s.estado IN ('activo', 'late_checkout')
           AND s.fecha_registro <= :hoy
           AND s.fecha_checkout  > :hoy2"
    );
    $stmtResumen->execute([':hoy' => $hoy, ':hoy2' => $hoy]);
    $resumen = $stmtResumen->fetch(PDO::FETCH_ASSOC);

    jsonResponse(true, 'ok', [
        'habitaciones' => $habitaciones,
        'dias_en_mes'  => $diasEnMes,
        'mes'          => $mes,
        'anio'         => $anio,
        'hoy'          => (int)date('j'),
        'resumen'      => [
            'ocupadas'      => (int)$resumen['ocupadas'],
            'total'         => (int)$resumen['total'],
            'pax_total'     => (int)$resumen['pax_total'],
            'ingresos_hoy'  => (float)$resumen['ingresos_hoy'],
            'pendientes'    => (int)$resumen['pendientes'],
            'cnt_pendiente' => (int)$resumen['cnt_pendiente'],
            'cnt_adelanto'  => (int)$resumen['cnt_adelanto'],
            'cnt_parcial'   => (int)$resumen['cnt_parcial'],
            'cnt_pagado'    => (int)$resumen['cnt_pagado'],
        ],
    ]);

} catch (Exception $e) {
    jsonResponse(false, 'Error al cargar datos: ' . $e->getMessage());
}
