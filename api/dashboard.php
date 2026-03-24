<?php
/**
 * api/dashboard.php
 * Entry point for all dashboard-related data requests.
 */
require_once '../config/db.php';
require_once '../auth/session.php';
require_once '../app/Controllers/DashboardController.php';

header('Content-Type: application/json; charset=utf-8');

$rol = $_SESSION['auth_rol'] ?? 'cajera';
$controller = new DashboardController($pdo);

if ($rol === 'admin') {
    echo json_encode($controller->getAdminData());
} else {
    echo json_encode($controller->getCajeraData());
}
