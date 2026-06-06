<?php
/**
 * api/flujo.php — Thin router for Flujo de Caja
 */
require_once __DIR__ . '/../ajax/bootstrap.php';
require_once BASE_PATH . 'config/db.php';
require_once BASE_PATH . 'app/Middleware/session.php';
require_once BASE_PATH . 'app/Middleware/auth.php';
require_once BASE_PATH . 'app/Controllers/FlujoController.php';

protegerPorRol('cajera', 'flujo');
 // Cajeras, Supervisores, Admin

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

$controller = new FlujoController($pdo);

switch ($action) {
    case 'listar':
        if ($method !== 'GET') json_response(false, null, 405, 'Método no permitido');
        json_response(true, $controller->listar($_GET));
        break;

    case 'detalle':
        try {
            if ($method !== 'GET') json_response(false, null, 405, 'Método no permitido');
            $id = (int)($_GET['id'] ?? 0);
            $res = $controller->detalle($id);
            json_response($res['ok'], $res['data'] ?? null, $res['ok'] ? 200 : 404, $res['msg'] ?? '');
        } catch (Exception $e) {
            json_response(false, null, 500, 'Error interno: ' . $e->getMessage());
        }
        break;

    case 'guardar':
        if ($method !== 'POST') json_response(false, null, 405, 'Método no permitido');
        $res = $controller->guardar($input);
        // Si la validacion detecta un turno abierto, enviamos status 200 para capturarlo facilmente en axios
        $status = ($res['ok'] || !empty($res['data']['turno_abierto'])) ? 200 : 422;
        json_response($res['ok'], $res['data'] ?? null, $status, $res['msg'] ?? '');
        break;

    case 'cerrar':
        if ($method !== 'POST') json_response(false, null, 405, 'Método no permitido');
        // Cajeras pueden cerrar su propio turno. Solo admin puede reabrir.
        $id = (int)($input['id'] ?? 0);
        $res = $controller->cerrar($id);
        json_response($res['ok'], null, $res['ok'] ? 200 : 422, $res['msg']);
        break;

    case 'depositar':
        if ($method !== 'POST') json_response(false, null, 405, 'Método no permitido');
        protegerPorRol('admin'); // Only admin/supervisor can deposit
        $id = (int)($input['id'] ?? 0);
        $res = $controller->depositar($id);
        json_response($res['ok'], null, $res['ok'] ? 200 : 422, $res['msg']);
        break;

    case 'reabrir':
        if ($method !== 'POST') json_response(false, null, 405, 'Método no permitido');
        protegerPorRol('admin'); 
        $id = (int)($input['id'] ?? 0);
        $res = $controller->reabrir($id);
        json_response($res['ok'], null, $res['ok'] ? 200 : 422, $res['msg']);
        break;

    case 'resumen_dia':
        if ($method !== 'GET') json_response(false, null, 405, 'Método no permitido');
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        json_response(true, $controller->resumenDia($fecha));
        break;

    case 'resumen_alex':
        if ($method !== 'GET') json_response(false, null, 405, 'Método no permitido');
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        json_response(true, $controller->resumenAlex($fecha));
        break;

    case 'resumen_alex_mensual':
        if ($method !== 'GET') json_response(false, null, 405, 'Método no permitido');
        $mes = (int)($_GET['mes'] ?? date('n'));
        $anio = (int)($_GET['anio'] ?? date('Y'));
        json_response(true, $controller->resumenAlexMensual($mes, $anio));
        break;

    case 'categorias':
        if ($method !== 'GET') json_response(false, null, 405, 'Método no permitido');
        json_response(true, $controller->categorias());
        break;

    case 'mensual_grid':
        if ($method !== 'GET') json_response(false, null, 405, 'Método no permitido');
        json_response(true, $controller->flujoMesGrid($_GET));
        break;

    case 'verificar_apertura':
        if ($method !== 'GET') json_response(false, null, 405, 'Método no permitido');
        $res = $controller->verificarApertura();
        json_response($res['ok'], $res, 200, $res['msg'] ?? '');
        break;

    default:
        json_response(false, null, 400, 'Acción no válida');
        break;
}
