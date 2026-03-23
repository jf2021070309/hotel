<?php
/**
 * api/usuarios/crear.php
 */
require_once '../../config/db.php';
require_once '../../app/Models/UsuarioModel.php';
require_once '../../app/Models/AuditoriaModel.php';
require_once '../../auth/session.php';
require_once '../../auth/middleware.php';

protegerPorRol('admin');

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['usuario']) || empty($input['password']) || empty($input['nombre'])) {
    json_response(false, null, 400, "Todos los campos son obligatorios");
}

$usuario_model = new UsuarioModel($pdo);
$audit_model   = new AuditoriaModel($pdo);

try {
    $id = $usuario_model->create($input);
    $currentUser = obtenerUsuarioActual();
    $audit_model->registrar($currentUser['id'], $currentUser['nombre'], 'USUARIO_CREADO', 'USUARIOS', "Creado usuario: " . $input['usuario']);
    json_response(true, ['id' => $id], 201, "Usuario creado exitosamente");
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        json_response(false, null, 409, "El nombre de usuario ya existe");
    }
    json_response(false, null, 500, "Error al crear usuario");
}
