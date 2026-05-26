<?php
/**
 * LogoutController — Cerrar sesión de forma segura con auditoría
 */
$_projectRoot = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2) . DIRECTORY_SEPARATOR;

require_once $_projectRoot . 'config/db.php';
require_once $_projectRoot . 'app/Middleware/session.php';
require_once $_projectRoot . 'app/Models/AuditoriaModel.php';
require_once $_projectRoot . 'app/Helpers/url.php';

if (estaAutenticado()) {
    cerrarSesion();
}

header('Location: ' . project_base_url() . 'login');
exit;
