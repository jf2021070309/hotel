<?php
/**
 * api/tipos_cambio.php
 */
require_once '../config/db.php';
require_once '../auth/session.php';
require_once '../auth/middleware.php';
require_once '../app/Models/TiposCambioModel.php';

// tipos_cambio es recurso de soporte: accesible si tiene rol cajera o módulo rooming
protegerPorRol('cajera', 'rooming');
$model = new TiposCambioModel($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $usd = $model->getToday('USD') ?? 3.75; // Valores por defecto si no hay
    $clp = $model->getToday('CLP') ?? 0.0038;
    json_response(true, ['USD' => $usd, 'CLP' => $clp]);
}
