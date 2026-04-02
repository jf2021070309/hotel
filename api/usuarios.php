<?php
/**
 * api/usuarios.php - Puerta de enlace única para el módulo de usuarios
 */
require_once '../config/db.php';
require_once '../auth/session.php';
require_once '../auth/middleware.php';
require_once '../app/Controllers/UsuarioController.php';

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

    default:
        json_response(false, null, 400, "Acción no válida");
        break;
}
