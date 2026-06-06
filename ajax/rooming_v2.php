<?php
/**
 * ajax/rooming_v2.php
 * API Endpoint para Rooming V2.
 */
header('Content-Type: application/json; charset=utf-8');
require_once BASE_PATH . 'config/db.php';
require_once BASE_PATH . 'app/Middleware/auth.php';

// Validar inicio de sesión
protegerPorRol('cajera', 'rooming');

require_once BASE_PATH . 'app/Controllers/RoomingV2Controller.php';
$controller = new RoomingV2Controller($pdo);

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($action) {
    case 'listar':
        $mes  = (int)($_GET['mes']  ?? date('n'));
        $anio = (int)($_GET['anio'] ?? date('Y'));
        json_response(true, $controller->listar($mes, $anio));
        break;

    case 'guardar':
        $rows = $input['rows'] ?? [];
        $res  = $controller->guardar($rows);
        json_response($res['ok'], null, $res['ok'] ? 200 : 500, $res['msg']);
        break;

    case 'eliminar':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            json_response(false, null, 400, "ID inválido");
        }
        $res = $controller->eliminar($id);
        json_response($res['ok'], null, $res['ok'] ? 200 : 400, $res['msg']);
        break;

    default:
        json_response(false, null, 400, "Acción no válida");
        break;
}
