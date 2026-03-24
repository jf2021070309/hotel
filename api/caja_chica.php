<?php
/**
 * api/caja_chica.php — Thin router for Caja Chica
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../auth/middleware.php';
require_once __DIR__ . '/../app/Controllers/CajaChicaController.php';

// Caja Chica es accesible probablemente por cajeras/supervisores igual
protegerPorRol('cajera'); 

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

$controller = new CajaChicaController($pdo);

switch ($action) {
    case 'ciclo_activo':
        if ($method !== 'GET') json_response(false, null, 405, 'Método no permitido');
        json_response(true, $controller->cicloActivo());
        break;

    case 'listar':
        if ($method !== 'GET') json_response(false, null, 405, 'Método no permitido');
        json_response(true, $controller->listar());
        break;

    case 'abrir':
        if ($method !== 'POST') json_response(false, null, 405, 'Método no permitido');
        $res = $controller->abrir($input);
        json_response($res['ok'], $res['data'] ?? null, $res['ok'] ? 200 : 422, $res['msg']);
        break;

    case 'gasto':
        if ($method !== 'POST') json_response(false, null, 405, 'Método no permitido');
        $res = $controller->registrarGasto($input);
        json_response($res['ok'], null, $res['ok'] ? 200 : 422, $res['msg']);
        break;

    case 'anular':
        if ($method !== 'POST') json_response(false, null, 405, 'Método no permitido');
        $res = $controller->anularGasto($input);
        json_response($res['ok'], null, $res['ok'] ? 200 : 422, $res['msg']);
        break;

    case 'cerrar':
        if ($method !== 'POST') json_response(false, null, 405, 'Método no permitido');
        $res = $controller->cerrar($input);
        json_response($res['ok'], null, $res['ok'] ? 200 : 422, $res['msg']);
        break;

    case 'categorias':
        if ($method !== 'GET') json_response(false, null, 405, 'Método no permitido');
        json_response(true, $controller->categorias());
        break;

    default:
        json_response(false, null, 400, 'Acción no válida');
        break;
}
