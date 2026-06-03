<?php
/**
 * public/index.php — Front Controller
 * 
 * Todas las peticiones web pasan por este archivo.
 * Resuelve la ruta y carga la vista o API correspondiente.
 */

// Raíz del proyecto
if (!defined('BASE_PATH')) {
    $basePath = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
    define('BASE_PATH', $basePath . DIRECTORY_SEPARATOR);
}

// Cargar helpers de URL (contiene el mapa único de rutas)
require_once BASE_PATH . 'app/Helpers/url.php';

// Obtener la URI limpia
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$projectBase = project_base_url();

// Quitar el prefijo del proyecto
if (stripos($uri, $projectBase) === 0) {
    $uri = substr($uri, strlen($projectBase));
}
$uri = trim($uri, '/');

// ─── MAPA DE RUTAS: URL limpia → archivo físico ───────────
// Invertimos clean_route_map() (que mapea archivo→URL) para obtener URL→archivo
$cleanMap = clean_route_map();
$webRoutes = [];
foreach ($cleanMap as $file => $cleanUrl) {
    $cleanUrl = trim($cleanUrl, '/');
    // Los archivos del mapa apuntan a Views, agregamos el prefijo
    if (strpos($file, 'app/Views/') === 0) {
        $webRoutes[$cleanUrl] = $file;
    } elseif ($file === 'index.php') {
        // Ignorar para que el fallback lo redirija a reservas
    } elseif ($file === 'logout.php') {
        $webRoutes['logout.php'] = 'app/Controllers/LogoutController.php';
        $webRoutes['logout'] = 'app/Controllers/LogoutController.php';
    } else {
        $webRoutes[$cleanUrl] = 'app/Views/' . $file;
    }
}

// Rutas adicionales no cubiertas por clean_route_map()
$webRoutes['login'] = 'app/Views/auth/login.php';
$webRoutes['login.php'] = 'app/Views/auth/login.php';
$webRoutes['reportes/alex'] = 'app/Views/reportes/alex.php';

// ─── RESOLVER RUTA ─────────────────────────────────────────

// Redireccionar raíz a reservas explícitamente para mantener estado activo del menú
if ($uri === '' || $uri === 'index.php') {
    header('Location: ' . project_base_url() . 'reservas');
    exit;
}

// Redireccionar V1 a V2 automáticamente
if ($uri === 'rooming' || $uri === 'rooming/index.php') {
    header('Location: ' . project_base_url() . 'rooming/v2');
    exit;
}
if ($uri === 'flujo' || $uri === 'flujo/index.php') {
    header('Location: ' . project_base_url() . 'flujo/v2');
    exit;
}

// 1. Buscar ruta exacta en el mapa
if (isset($webRoutes[$uri])) {
    $target = BASE_PATH . $webRoutes[$uri];
    if (file_exists($target)) {
        require $target;
        exit;
    }
}

// 2. Servir endpoints AJAX internos
if (strpos($uri, 'ajax/') === 0 || strpos($uri, 'api/') === 0) {
    // Normalizar: api/ → ajax/
    $ajaxUri = preg_replace('/^api\//', 'ajax/', $uri);
    $target = BASE_PATH . $ajaxUri;
    if (file_exists($target)) {
        require $target;
        exit;
    }
}

// 3. Servir assets estáticos
if (strpos($uri, 'assets/') === 0 || strpos($uri, 'public/assets/') === 0) {
    return false;
}

// 4. Fallback: archivos directos (app/, includes/, etc.)
$directPrefixes = ['app/', 'includes/', 'config/'];
foreach ($directPrefixes as $prefix) {
    if (strpos($uri, $prefix) === 0) {
        $target = BASE_PATH . $uri;
        if (file_exists($target)) {
            require $target;
            exit;
        }
    }
}

// 5. Si nada coincide → cuadro de reservas
header('Location: ' . project_base_url() . 'reservas');
exit;
