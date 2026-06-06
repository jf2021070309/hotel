<?php
/**
 * api/yape.php — Thin router for Gastos Yape
 */
require_once BASE_PATH . 'config/db.php';
require_once BASE_PATH . 'app/Middleware/session.php';
require_once BASE_PATH . 'app/Middleware/auth.php';
require_once BASE_PATH . 'app/Controllers/YapeController.php';

protegerPorRol('cajera', 'yape');

ini_set('display_errors', '0');
set_error_handler(function(int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    error_log("Yape API PHP warning: $message in $file:$line");
    return true;
});
set_exception_handler(function(Throwable $e): void {
    json_response(false, null, 500, 'Error interno Yape: ' . $e->getMessage());
});

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

$controller = new YapeController($pdo);

switch ($action) {
    case 'listar':
        if ($method !== 'GET') json_response(false, null, 405, 'Método no permitido');
        $res = $controller->listar();
        json_response($res['ok'], $res['data'] ?? null, 200, $res['msg'] ?? '');
        break;

    case 'detalle':
        if ($method !== 'GET') json_response(false, null, 405, 'Método no permitido');
        $id = (int)($_GET['id'] ?? 0);
        $res = $controller->detalle($id);
        json_response($res['ok'], $res['data'] ?? null, $res['ok'] ? 200 : 404, $res['msg'] ?? '');
        break;

    case 'guardar':
        if ($method !== 'POST') json_response(false, null, 405, 'Método no permitido');
        $res = $controller->guardar($input);
        json_response($res['ok'], $res['data'] ?? null, $res['ok'] ? 200 : 422, $res['msg']);
        break;

    case 'crear_dia':
        if ($method !== 'POST') json_response(false, null, 405, 'Método no permitido');
        $res = $controller->crearDia($input);
        json_response($res['ok'], $res['data'] ?? null, $res['ok'] ? 200 : 422, $res['msg']);
        break;

    case 'cerrar':
        if ($method !== 'POST') json_response(false, null, 405, 'Método no permitido');
        $res = $controller->cerrar($input);
        json_response($res['ok'], null, $res['ok'] ? 200 : 422, $res['msg']);
        break;

    default:
        json_response(false, null, 400, 'Acción no válida');
        break;
}
