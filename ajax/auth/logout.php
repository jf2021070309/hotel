<?php
/**
 * api/auth/logout.php
 */
require_once __DIR__ . '/../../ajax/bootstrap.php';
require_once BASE_PATH . 'config/db.php';
require_once BASE_PATH . 'app/Middleware/session.php';
require_once BASE_PATH . 'app/Models/AuditoriaModel.php';

if (estaAutenticado()) {
    $user = obtenerUsuarioActual();
    $audit_model = new AuditoriaModel($pdo);
    $audit_model->registrar($user['id'], 'LOGOUT', 'AUTH');
    cerrarSesion();
}

json_response(true, null, 200, "Sesión cerrada");
