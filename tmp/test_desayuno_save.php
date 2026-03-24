<?php
if(function_exists('opcache_reset')) opcache_reset();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Controllers/DesayunoController.php';

$controller = new DesayunoController($pdo);

// 1. Get today's calculated data
$hoy = $controller->getHoy();
if (!$hoy['ok']) {
    die("Error getting today's data: " . $hoy['msg']);
}

$data = $hoy['data'];
if ($data['ya_existe']) {
    echo "Record already exists for today. Deleting it to test fresh insert...\n";
    $pdo->query("DELETE FROM desayunos_detalle WHERE desayuno_id = {$data['id']}");
    $pdo->query("DELETE FROM desayunos WHERE id = {$data['id']}");
    
    // Get fresh again
    $hoy = $controller->getHoy();
    $data = $hoy['data'];
}

echo "Initial Pax: {$data['pax_calculado']}\n";

// 2. Modify data (e.g., toggle one room off)
if (count($data['detalles']) > 0) {
    $data['detalles'][0]['incluye_desayuno'] = false;
    $data['observacion'] = "Prueba automatizada de guardado post-fix SQL";
    
    // Recalculate adjusted pax
    $totalFinal = 0;
    foreach ($data['detalles'] as $d) {
        if ($d['incluye_desayuno']) {
            $totalFinal += (int)$d['pax'];
        }
    }
    $data['pax_ajustado'] = $totalFinal;
    $data['usuario_id'] = 1; // Explicitly set for test
    
    echo "Adjusted Pax: {$totalFinal}\n";
    
    // 3. Save explicitly via model to get trace
    try {
        $model = new DesayunoModel($pdo);
        $resId = $model->guardar($data, $data['detalles']);
        echo "Save result: OK, ID: $resId\n";
    } catch (Exception $e) {
        echo "Exception caught:\n" . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    }

        $stmt = $pdo->query("SELECT * FROM desayunos WHERE id = {$resId}");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "\nDB Record:\n";
        print_r($row);
} else {
    echo "No occupied rooms to test with.\n";
}
