<?php
/**
 * api/auth/logout.php
 */
require_once '../../config/db.php';
require_once '../../auth/session.php';
require_once '../../app/Models/AuditoriaModel.php';

if (estaAutenticado()) {
    $user = obtenerUsuarioActual();
    $audit_model = new AuditoriaModel($pdo);
    $audit_model->registrar($user['id'], $user['nombre'], 'LOGOUT', 'AUTH');
    cerrarSesion();
}

json_response(true, null, 200, "Sesión cerrada");
