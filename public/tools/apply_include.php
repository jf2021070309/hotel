<?php
// public/tools/apply_include.php
// CLI usage: php public/tools/apply_include.php <desayuno_id> <stay_id> <incluye>
// Example: php public/tools/apply_include.php 1 1 1
if (PHP_SAPI !== 'cli') {
    echo json_encode(['ok' => false, 'msg' => 'Run from CLI only.']);
    exit;
}
require_once __DIR__ . '/../../config/db.php';

$argv0 = $argv;
if (count($argv) < 4) {
    echo json_encode(['ok' => false, 'msg' => 'Usage: php apply_include.php <desayuno_id> <stay_id> <incluye>']);
    exit(1);
}

$desayuno_id = (int)$argv[1];
$stay_id = (int)$argv[2];
$incluye = (int)$argv[3] ? 1 : 0;

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('UPDATE desayunos_detalle SET incluye_desayuno = ? WHERE desayuno_id = ? AND stay_id = ?');
    $stmt->execute([$incluye, $desayuno_id, $stay_id]);

    // Recalcular pax_ajustado
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(pax * incluye_desayuno), 0) FROM desayunos_detalle WHERE desayuno_id = ?');
    $stmt->execute([$desayuno_id]);
    $pax_ajustado = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('UPDATE desayunos SET pax_ajustado = ? WHERE id = ?');
    $stmt->execute([$pax_ajustado, $desayuno_id]);

    $pdo->commit();

    echo json_encode(['ok' => true, 'desayuno_id' => $desayuno_id, 'stay_id' => $stay_id, 'incluye' => $incluye, 'pax_ajustado' => $pax_ajustado]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    exit(1);
}
