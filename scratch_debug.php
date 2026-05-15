<?php
require_once 'config/db.php';
require_once 'app/Models/ReporteModel.php';

$model = new ReporteModel($pdo);
try {
    $res = $model->getResumenDesglosado(5, 2026);
    echo "SUCCESS: " . json_encode($res);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
} catch (Error $e) {
    echo "FATAL ERROR: " . $e->getMessage();
}
