<?php
/**
 * ajax/clientes_v2.php
 * API Endpoint para Clientes V2.
 */
require_once __DIR__ . '/../ajax/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
require_once BASE_PATH . 'config/db.php';
require_once BASE_PATH . 'app/Middleware/auth.php';

// Validar inicio de sesión
protegerPorRol('cajera', 'clientes');

require_once BASE_PATH . 'app/Controllers/ClientesV2Controller.php';
require_once BASE_PATH . 'app/Helpers/DocumentLookupService.php';

$controller = new ClientesV2Controller($pdo);
$lookupService = new DocumentLookupService();

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($action) {
    case 'listar':
        json_response(true, $controller->listar());
        break;

    case 'guardar':
        $rows = $input['rows'] ?? [];
        json_response(true, $controller->guardar($rows));
        break;

    case 'eliminar':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            json_response(false, null, 400, "ID inválido");
        }
        $res = $controller->eliminar($id);
        json_response($res['ok'], null, $res['ok'] ? 200 : 400, $res['msg']);
        break;

    case 'lookup_dni':
        $dni = $_GET['dni'] ?? '';
        $data = $lookupService->consultarDni($dni);
        if ($data) {
            json_response(true, $data);
        } else {
            json_response(false, null, 404, "DNI no encontrado o error en consulta");
        }
        break;

    case 'lookup_ruc':
        $ruc = $_GET['ruc'] ?? '';
        $data = $lookupService->consultarRuc($ruc);
        if ($data) {
            json_response(true, $data);
        } else {
            json_response(false, null, 404, "RUC no encontrado o error en consulta");
        }
        break;

    default:
        json_response(false, null, 400, "Acción no válida");
        break;
}
