<?php
/**
 * Endpoints del Módulo de Usuarios.
 *
 * Archivo de enrutamiento principal (API) para el manejo de peticiones
 * relacionadas a usuarios. Protege rutas y delega la ejecución al Controlador.
 *
 * @package API\Usuarios
 */
require_once BASE_PATH . 'config/db.php';
require_once BASE_PATH . 'app/Middleware/session.php';
require_once BASE_PATH . 'app/Middleware/auth.php';
require_once BASE_PATH . 'app/Controllers/UsuarioController.php';
require_once BASE_PATH . 'app/Helpers/DocumentLookupService.php';

// Detectar acción y método
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true);

$controller = new UsuarioController($pdo);

switch ($action) {
    case 'listar':
        protegerPorRol('cajera', 'gestion_usuarios');
        json_response(true, $controller->index());
        break;
    
    case 'personal_limpieza':
        $stmt = $pdo->prepare("SELECT id, nombre FROM usuarios WHERE rol = 'limpieza' AND estado = 1 ORDER BY nombre");
        $stmt->execute();
        json_response(true, $stmt->fetchAll());
        break;

    case 'crear':
        if ($method !== 'POST') json_response(false, null, 405, "Método no permitido");
        protegerPorRol('cajera', 'gestion_usuarios');
        $res = $controller->create($input);
        json_response($res['ok'], null, $res['code'] ?? 200, $res['msg']);
        break;

    case 'editar':
        if ($method !== 'POST') json_response(false, null, 405, "Método no permitido");
        protegerPorRol('cajera', 'gestion_usuarios');
        $id = (int)($input['id'] ?? 0);
        $res = $controller->update($id, $input);
        json_response($res['ok'], null, $res['code'] ?? 200, $res['msg']);
        break;

    case 'cambiar_pass':
        if ($method !== 'POST') json_response(false, null, 405, "Método no permitido");
        protegerPorRol('cajera', 'gestion_usuarios');
        $id   = (int)($input['id'] ?? 0);
        $pass = $input['password'] ?? '';
        $res  = $controller->updatePassword($id, $pass);
        json_response($res['ok'], null, $res['code'] ?? 200, $res['msg']);
        break;

    case 'consultar_dni':
        $controller->consultarDni();
        break;

    case 'consultar_ruc':
        $controller->consultarRuc();
        break;

    default:
        json_response(false, null, 400, "Acción no válida");
        break;
}
