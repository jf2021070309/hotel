<?php
/**
 * Dashboard Shell & Role Router
 * 
 * Punto de entrada principal tras el login.
 * Redirige al login si no hay sesión activa.
 */

// Resolver la raíz del proyecto
$_projectRoot = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2) . DIRECTORY_SEPARATOR;

// Primero cargar sesión (no necesita DB)
require_once $_projectRoot . 'app/Middleware/session.php';

// Si no hay sesión, redirigir al login SIN cargar la DB
if (!isset($_SESSION['auth_id'])) {
    require_once $_projectRoot . 'app/Helpers/url.php';
    header("Location: " . project_base_url() . "login");
    exit;
}

// Solo cargar DB y middleware si hay sesión activa
require_once $_projectRoot . 'config/db.php';
require_once $_projectRoot . 'app/Middleware/auth.php';

$rol = $_SESSION['auth_rol'] ?? 'cajera';

if ($rol === 'admin') {
    require_once $_projectRoot . 'app/Views/dashboard/admin.php';
} else {
    require_once $_projectRoot . 'app/Views/dashboard/cajera.php';
}
