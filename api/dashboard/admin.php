<?php
/**
 * api/dashboard/admin.php
 */
require_once '../../config/db.php';
require_once '../../auth/session.php';
require_once '../../auth/middleware.php';
require_once '../../app/Controllers/DashboardController.php';

header('Content-Type: application/json; charset=utf-8');

// Required permission
if (!tienePermiso('admin')) {
    echo json_encode(['ok' => false, 'msg' => 'Acceso denegado. Se requiere rol de administrador.']);
    exit;
}

$controller = new DashboardController($pdo);
$response = $controller->getAdminData();

echo json_encode($response);
