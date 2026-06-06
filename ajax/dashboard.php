<?php
/**
 * Endpoints del Módulo de Dashboard.
 *
 * Punto de entrada único para las solicitudes de datos de los paneles
 * de administración y operación (cajera).
 *
 * @package API\Dashboard
 */
require_once BASE_PATH . 'config/db.php';
require_once BASE_PATH . 'app/Middleware/session.php';
require_once BASE_PATH . 'app/Middleware/auth.php';
require_once BASE_PATH . 'app/Controllers/DashboardController.php';

protegerPorRol('cajera', 'dashboard');

header('Content-Type: application/json; charset=utf-8');

$rol = $_SESSION['auth_rol'] ?? 'cajera';
$controller = new DashboardController($pdo);

if ($rol === 'admin') {
    echo json_encode($controller->getAdminData());
} else {
    echo json_encode($controller->getCajeraData());
}
