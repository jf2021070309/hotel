<?php
/**
 * router.php — Router para el servidor built-in de PHP (php -S)
 *
 * Usado exclusivamente en producción (Railway, Docker con php -S).
 * Replica lo que hace el .htaccess en Apache con mod_rewrite.
 *
 * Uso: php -S 0.0.0.0:${PORT} -t /app router.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

// 1. Si el archivo o carpeta existe realmente, servirlo directamente (assets, CSS, JS, etc.)
$filePath = __DIR__ . $uri;
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    return false; // php -S sirve el archivo estatic directamente
}

// 2. Mapa de rutas limpias → archivo PHP real
$routeMap = [
    '/'                        => 'index.php',
    ''                         => 'index.php',
    '/habitaciones'            => 'app/Views/habitaciones/index.php',
    '/rooming'                 => 'app/Views/rooming/index.php',
    '/reservas'                => 'app/Views/reservas/index.php',
    '/flujo'                   => 'app/Views/flujo/index.php',
    '/flujo/form'              => 'app/Views/flujo/form.php',
    '/flujo/dia'               => 'app/Views/flujo/dia.php',
    '/flujo/reporte-sobre'     => 'app/Views/flujo/reporte_sobre.php',
    '/flujo/reporte-alex-diario' => 'app/Views/flujo/reporte_alex_diario.php',
    '/caja-chica'              => 'app/Views/caja_chica/index.php',
    '/sobres'                  => 'app/Views/sobres/index.php',
    '/calculadora'             => 'app/Views/calculadora/index.php',
    '/yape'                    => 'app/Views/yape/index.php',
    '/inventario'              => 'app/Views/inventario/index.php',
    '/desayunos'               => 'app/Views/desayunos/index.php',
    '/limpieza'                => 'app/Views/limpieza/index.php',
    '/clientes'                => 'app/Views/clientes/index.php',
    '/clientes-frecuentes'     => 'app/Views/clientes/frecuentes.php',
    '/admin/usuarios'          => 'app/Views/admin/usuarios.php',
    '/admin/medios-pago'       => 'app/Views/admin/medios_pago.php',
    '/admin/auditoria'         => 'app/Views/admin/auditoria.php',
    '/reportes/mendoza'        => 'app/Views/reportes/mendoza.php',
    '/login'                   => 'login.php',
    '/login.php'               => 'login.php',
    '/logout.php'              => 'logout.php',
];

// Normalizar URI (quitar trailing slash excepto en raíz)
$cleanUri = rtrim($uri, '/') ?: '/';

// Buscar la ruta exacta primero
if (isset($routeMap[$cleanUri])) {
    $target = __DIR__ . '/' . $routeMap[$cleanUri];
    if (file_exists($target)) {
        $previousCwd = getcwd();
        $targetDir = dirname($target);
        if ($previousCwd !== false && $previousCwd !== $targetDir) {
            chdir($targetDir);
        }
        require $target;
        if ($previousCwd !== false && $previousCwd !== $targetDir) {
            chdir($previousCwd);
        }
        exit;
    }
}

// Fallback: si empieza con /api/, /app/, /auth/, /config/, /assets/ → servir directamente
$passThroughPrefixes = ['/api/', '/app/', '/auth/', '/config/', '/assets/', '/includes/'];
foreach ($passThroughPrefixes as $prefix) {
    if (strpos($uri, $prefix) === 0) {
        $target = __DIR__ . $uri;
        if (file_exists($target)) {
            $previousCwd = getcwd();
            $targetDir = dirname($target);
            if ($previousCwd !== false && $previousCwd !== $targetDir) {
                chdir($targetDir);
            }
            require $target;
            if ($previousCwd !== false && $previousCwd !== $targetDir) {
                chdir($previousCwd);
            }
            exit;
        }
    }
}

// Fallback final: dashboard
require __DIR__ . '/index.php';
