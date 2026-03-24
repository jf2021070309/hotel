<?php
/**
 * api/dashboard/cajera.php
 */
require_once '../../config/db.php';
require_once '../../auth/session.php';
require_once '../../auth/middleware.php';
require_once '../../app/Controllers/DashboardController.php';

header('Content-Type: application/json; charset=utf-8');

// Requiere permiso de recepcionista o cajera (cualquiera logueado que no sea admin puro también puede verlo por default si se le asigna)
// En el sistema actual, asumo que 'cajera' abarca recepción
if (!tienePermiso('cajera') && !tienePermiso('admin')) {
    echo json_encode(['ok' => false, 'msg' => 'Acceso denegado.']);
    exit;
}

$controller = new DashboardController($pdo);
$response = $controller->getCajeraData();

echo json_encode($response);
