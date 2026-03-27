<?php
/**
 * api/consumos.php
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../app/Controllers/ConsumoController.php';

$action = $_GET['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$controller = new ConsumoController($pdo);

switch ($action) {
    case 'registrar':
        $res = $controller->registrar($input);
        json_response($res['ok'], null, $res['ok'] ? 200 : 422, $res['msg']);
        break;

    case 'listar':
        $id = (int)($_GET['stay_id'] ?? 0);
        $res = $controller->listarPorStay($id);
        json_response($res['ok'], $res['data']);
        break;

    default:
        json_response(false, null, 400, 'Acción inválida');
}
