<?php
/**
 * api/usuarios/editar.php
 */
require_once '../../config/db.php';
require_once '../../app/Models/UsuarioModel.php';
require_once '../../app/Models/AuditoriaModel.php';
require_once '../../auth/session.php';
require_once '../../auth/middleware.php';

protegerPorRol('admin');

$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);

if (!$id) json_response(false, null, 400, "ID de usuario inválido");

// Regla: No se puede cambiar el rol del admin id=1
if ($id === 1 && $input['rol'] !== 'admin') {
    json_response(false, null, 403, "No se puede cambiar el rol del administrador principal");
}

// Regla: No se puede desactivar el propio usuario logueado
$currentUser = obtenerUsuarioActual();
if ($id === $currentUser['id'] && $input['estado'] == 0) {
    json_response(false, null, 403, "No puedes desactivar tu propio usuario");
}

$usuario_model = new UsuarioModel($pdo);
$audit_model   = new AuditoriaModel($pdo);

// Verificar si el nombre de usuario ya existe en otro registro
if (!empty($input['usuario'])) {
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? AND id != ?");
    $stmt->execute([$input['usuario'], $id]);
    if ($stmt->fetch()) {
        json_response(false, null, 409, "El nombre de usuario '{$input['usuario']}' ya está en uso");
    }
}

if ($usuario_model->update($id, $input)) {
    $audit_model->registrar($currentUser['id'], $currentUser['nombre'], 'USUARIO_EDITADO', 'USUARIOS', "Editado usuario con ID: " . $id);
    json_response(true, null, 200, "Usuario actualizado correctamente");
} else {
    json_response(false, null, 500, "Error al actualizar usuario");
}
