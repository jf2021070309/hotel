<?php
/**
 * api/usuarios/cambiar_pass.php
 */
require_once '../../config/db.php';
require_once '../../app/Models/UsuarioModel.php';
require_once '../../app/Models/AuditoriaModel.php';
require_once '../../auth/session.php';
require_once '../../auth/middleware.php';

protegerPorRol('admin');

$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);
$newPass = trim($input['password'] ?? '');

if (!$id || empty($newPass)) json_response(false, null, 400, "Datos incompletos");

$usuario_model = new UsuarioModel($pdo);
$audit_model   = new AuditoriaModel($pdo);

if ($usuario_model->updatePassword($id, $newPass)) {
    $currentUser = obtenerUsuarioActual();
    $audit_model->registrar($currentUser['id'], $currentUser['nombre'], 'PASSWORD_CAMBIADO', 'USUARIOS', "Cambiada contraseña de usuario ID: " . $id);
    json_response(true, null, 200, "Contraseña actualizada");
} else {
    json_response(false, null, 500, "Error al cambiar contraseña");
}
