<?php
/**
 * api/limpieza.php
 * Router for Cleaning module actions.
 */
require_once '../config/db.php';
require_once '../auth/session.php';
require_once '../app/Controllers/LimpiezaController.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'hoy';
$controller = new LimpiezaController($pdo);

switch ($action) {
    case 'hoy':
        echo json_encode($controller->getHoy());
        break;

    case 'generar':
        echo json_encode($controller->generar());
        break;

    case 'actualizar':
        echo json_encode($controller->actualizar());
        break;

    case 'observacion':
        echo json_encode($controller->agregarObservacion());
        break;

    case 'listar':
        echo json_encode($controller->listarHistorial());
        break;

    case 'detalle':
        echo json_encode($controller->getDetalleDia());
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => 'Acción no reconocida']);
        break;
}
