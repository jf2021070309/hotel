<?php
// public/tools/check_desayunos.php
// Uso: abrir en el navegador o curl: ?document_num=76032957 o ?stay_id=123 o ?hab=202&fecha=2026-05-31
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/db.php';

$out = ['ok' => false, 'queries' => [], 'results' => []];

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $document = $_GET['document_num'] ?? null;
    $stay_id = isset($_GET['stay_id']) ? (int)$_GET['stay_id'] : null;
    $hab = $_GET['hab'] ?? null;
    $fecha = $_GET['fecha'] ?? date('Y-m-d');

    if ($document) {
        // Buscar cliente
        $q = 'SELECT * FROM clientes WHERE documento_num = ? LIMIT 1';
        $out['queries'][] = $q;
        $stmt = $pdo->prepare($q);
        $stmt->execute([$document]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        $out['results']['cliente'] = $cliente ?: null;

        if ($cliente) {
            $cid = (int)$cliente['id'];
            $q2 = 'SELECT rp.*, c.nombre_razon_social FROM rooming_pax rp JOIN clientes c ON c.id = rp.cliente_id WHERE rp.cliente_id = ?';
            $out['queries'][] = $q2;
            $stmt = $pdo->prepare($q2);
            $stmt->execute([$cid]);
            $out['results']['rooming_pax'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    if ($stay_id) {
        $q = 'SELECT id, fecha_registro, fecha_checkin_real, fecha_checkout, estado, checkin_realizado, pax_total, habitacion_id FROM rooming_stays WHERE id = ?';
        $out['queries'][] = $q;
        $stmt = $pdo->prepare($q);
        $stmt->execute([$stay_id]);
        $out['results']['stay'] = $stmt->fetch(PDO::FETCH_ASSOC);

        $q2 = 'SELECT rp.*, c.nombre_razon_social, c.documento_num FROM rooming_pax rp JOIN clientes c ON c.id = rp.cliente_id WHERE rp.stay_id = ?';
        $out['queries'][] = $q2;
        $stmt = $pdo->prepare($q2);
        $stmt->execute([$stay_id]);
        $out['results']['pax'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($hab) {
        // Buscar stays por habitación y fecha
        $q = "SELECT s.id, s.fecha_registro, s.fecha_checkout, s.estado, s.checkin_realizado, s.pax_total, h.numero as hab_num FROM rooming_stays s JOIN habitaciones h ON h.id = s.habitacion_id WHERE h.numero = ? AND DATE(s.fecha_registro) <= ? AND DATE(s.fecha_checkout) >= ? ORDER BY s.id DESC";
        $out['queries'][] = $q;
        $stmt = $pdo->prepare($q);
        $stmt->execute([$hab, $fecha, $fecha]);
        $out['results']['stays_por_hab'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Revisar si ya existe un registro en `desayunos` para la fecha
    $q = 'SELECT * FROM desayunos WHERE fecha = ? LIMIT 1';
    $out['queries'][] = $q;
    $stmt = $pdo->prepare($q);
    $stmt->execute([$fecha]);
    $out['results']['desayunos_cab'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($out['results']['desayunos_cab']) {
        $did = (int)$out['results']['desayunos_cab']['id'];
        $q2 = 'SELECT dd.*, h.numero as habitacion FROM desayunos_detalle dd JOIN rooming_stays rs ON rs.id = dd.stay_id JOIN habitaciones h ON h.id = rs.habitacion_id WHERE dd.desayuno_id = ? ORDER BY h.numero ASC';
        $out['queries'][] = $q2;
        $stmt = $pdo->prepare($q2);
        $stmt->execute([$did]);
        $out['results']['desayunos_detalle'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ejecutar la misma consulta que usa Desayunos para la fecha
    $q = "SELECT s.id as checkin_id, h.numero as habitacion, h.id as habitacion_id, s.id as stay_id,
 (SELECT c.nombre_razon_social FROM rooming_pax rp JOIN clientes c ON rp.cliente_id = c.id WHERE rp.stay_id = s.id AND rp.es_titular_acompanante = 1 LIMIT 1) as titular,
 s.pax_total as pax
 FROM rooming_stays s
 JOIN habitaciones h ON s.habitacion_id = h.id
 WHERE s.estado IN ('activo','late_checkout','finalizado')
   AND DATE(s.fecha_registro) <= :f1
   AND DATE(s.fecha_checkout) >= :f2";
    $out['queries'][] = $q;
    $stmt = $pdo->prepare($q);
    $stmt->execute([':f1' => $fecha, ':f2' => $fecha]);
    $out['results']['desayunos_query'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $out['ok'] = true;
} catch (Exception $e) {
    $out['error'] = $e->getMessage();
}

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// Nota: Este script solo lee y retorna datos. No ejecuta actualizaciones.
?>
