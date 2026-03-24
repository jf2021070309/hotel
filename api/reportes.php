<?php
/**
 * api/reportes.php
 */
require_once '../config/db.php';
require_once '../auth/session.php';
require_once '../app/Controllers/ReporteController.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'resumen';
$controller = new ReporteController($pdo);

switch ($action) {
    case 'mendoza':
        echo json_encode($controller->mendoza());
        break;
    
    case 'alex':
        echo json_encode($controller->alex());
        break;

    case 'resumen':
        echo json_encode($controller->resumenPL());
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => 'Acción no reconocida']);
        break;
}
