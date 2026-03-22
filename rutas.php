<?php
// ============================================================
// rutas.php — Gestión centralizada de rutas de vistas
// ============================================================

if (!defined('BASE_VIEWS')) {
    define('BASE_VIEWS', 'app/Views/');
}

/**
 * Retorna la URL correcta para un módulo y página,
 * asegurando que apunte a app/Views/ si es necesario.
 * 
 * @param string $path El path relativo (ej: 'habitaciones/index.php')
 * @param string $base El prefijo de nivel (ej: '../')
 * @return string La URL completa
 */
function route(string $path, string $base = ''): string {
    // Si es el index raíz o ya tiene la ruta completa, no le agregamos el prefijo de vistas
    if ($path === 'index.php' || strpos($path, 'app/Views/') === 0 || $path === 'logout.php') {
        return $base . $path;
    }
    
    return $base . BASE_VIEWS . $path;
}
