<?php
/**
 * logout.php — Cerrar sesión de forma segura con auditoría
 */
require_once 'config/db.php';
require_once 'auth/session.php';
require_once 'app/Models/AuditoriaModel.php';

if (estaAutenticado()) {
    cerrarSesion();
}

header('Location: app/Views/auth/login.php');
exit;
