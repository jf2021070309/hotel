<?php
/**
 * api/tipos_cambio.php
 */
require_once '../config/db.php';
require_once '../auth/session.php';
require_once '../auth/middleware.php';
require_once '../app/Models/TiposCambioModel.php';

protegerPorRol('cajera');
$model = new TiposCambioModel($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $usd = $model->getToday('USD') ?? 3.75; // Valores por defecto si no hay
    $clp = $model->getToday('CLP') ?? 0.0038;
    json_response(true, ['USD' => $usd, 'CLP' => $clp]);
}
