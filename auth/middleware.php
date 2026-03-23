<?php
/**
 * auth/middleware.php
 * Debe incluirse en toda página protegida.
 */
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../rutas.php'; // Para redirecciones consistentes

if (!estaAutenticado()) {
    // Si no está logueado, redirigir al login
    header('Location: ' . route('auth/login.php', $base ?? '')); 
    exit;
}

/**
 * Función helper para proteger rutas por rol.
 * Si no tiene permiso, redirige a una página 403.
 */
function protegerPorRol(string $rol_minimo): void {
    global $base;
    if (!tienePermiso($rol_minimo)) {
        header('Location: ' . route('errors/403.php', $base ?? ''));
        exit;
    }
}
