<?php
/**
 * index.php — Dashboard Shell & Role Router
 */
require_once 'config/db.php';
require_once 'auth/session.php';
require_once 'auth/middleware.php';

// Redirigir si no hay sesión
if (!isset($_SESSION['auth_id'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['auth_rol'] ?? 'cajera';

if ($rol === 'admin') {
    require_once 'app/Views/dashboard/admin.php';
} else {
    // Para cajeras, recepcionistas o cualquier otro rol operativo
    require_once 'app/Views/dashboard/cajera.php';
}
