<?php
/**
 * api/inventario.php
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../app/Controllers/InventarioController.php';

// Temporarily define InventarioController if it doesn't exist yet or just use the model
// Let's create the controller first
$action = $_GET['action'] ?? '';
$controller = new InventarioController($pdo);

switch ($action) {
    case 'listar':
        $res = $controller->listar();
        json_response($res['ok'], $res['data']);
        break;
    case 'crear':
        $input = json_decode(file_get_contents('php://input'), true);
        $res = $controller->crear($input);
        json_response($res['ok'], null, $res['ok'] ? 200 : 422, $res['msg']);
        break;
    case 'actualizar':
        $id = (int)($_GET['id'] ?? 0);
        $input = json_decode(file_get_contents('php://input'), true);
        $res = $controller->actualizar($id, $input);
        json_response($res['ok'], null, $res['ok'] ? 200 : 422, $res['msg']);
        break;
    case 'recargar':
        $id = (int)($_GET['id'] ?? 0);
        $input = json_decode(file_get_contents('php://input'), true);
        $res = $controller->recargar($id, (int)($input['cantidad'] ?? 0));
        json_response($res['ok'], null, $res['ok'] ? 200 : 422, $res['msg']);
        break;
    case 'eliminar':
        $id = (int)($_GET['id'] ?? 0);
        $res = $controller->eliminar($id);
        json_response($res['ok'], null, $res['ok'] ? 200 : 422, $res['msg']);
        break;
    case 'alertas':
        $res = $controller->alertas();
        json_response($res['ok'], $res['data']);
        break;
    case 'consumo_interno':
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $input['usuario_id'] = $_SESSION['auth_id'] ?? 1;
        $res = $controller->consumoInterno($input);
        json_response($res['ok'], null, $res['ok'] ? 200 : 422, $res['msg']);
        break;
    case 'historial':
        $filtros = [
            'producto_id'  => $_GET['producto_id'] ?? '',
            'tipo'         => $_GET['tipo'] ?? '',
            'fecha_desde'  => $_GET['fecha_desde'] ?? '',
            'fecha_hasta'  => $_GET['fecha_hasta'] ?? '',
        ];
        $res = $controller->historial($filtros);
        json_response($res['ok'], $res['data']);
        break;
    default:
        json_response(false, null, 400, 'Acción inválida');
}
