<?php
// ============================================================
// rutas.php — Gestión centralizada de rutas de vistas
// ============================================================

if (!defined('BASE_VIEWS')) {
    define('BASE_VIEWS', 'app/Views/');
}

if (!function_exists('project_base_url')) {
    /**
     * Retorna la base pública del proyecto, p.ej. "/hotel/".
     */
    function project_base_url(): string {
        $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
        $projectRoot = realpath(__DIR__);

        if ($docRoot && $projectRoot) {
            $docRoot = str_replace('\\', '/', $docRoot);
            $projectRoot = str_replace('\\', '/', $projectRoot);

            if (strpos($projectRoot, $docRoot) === 0) {
                $relative = trim(substr($projectRoot, strlen($docRoot)), '/');
                return '/' . ($relative !== '' ? $relative . '/' : '');
            }
        }

        return '/';
    }
}

if (!function_exists('clean_route_map')) {
    /**
     * Mapa de URLs limpias del sistema.
     */
    function clean_route_map(): array {
        return [
            'index.php'                 => '',
            'habitaciones/index.php'    => 'habitaciones',
            'rooming/index.php'         => 'rooming',
            'reservas/index.php'        => 'reservas',
            'flujo/index.php'           => 'flujo',
            'flujo/form.php'            => 'flujo/form',
            'flujo/dia.php'             => 'flujo/dia',
            'flujo/reporte_sobre.php'   => 'flujo/reporte-sobre',
            'flujo/reporte_alex_diario.php' => 'flujo/reporte-alex-diario',
            'caja_chica/index.php'      => 'caja-chica',
            'sobres/index.php'          => 'sobres',
            'calculadora/index.php'     => 'calculadora',
            'yape/index.php'            => 'yape',
            'inventario/index.php'      => 'inventario',
            'desayunos/index.php'       => 'desayunos',
            'limpieza/index.php'        => 'limpieza',
            'clientes/index.php'        => 'clientes',
            'admin/usuarios.php'        => 'admin/usuarios',
            'admin/medios_pago.php'     => 'admin/medios-pago',
            'admin/auditoria.php'       => 'admin/auditoria',
            'app/Views/reportes/mendoza.php' => 'reportes/mendoza',
            'app/Views/reportes/alex.php'    => 'reportes/alex',
            'logout.php'                => 'logout.php',
        ];
    }
}

if (!function_exists('view_base_href_for_request')) {
    /**
     * Calcula el base href correcto para la vista actual cuando se accede
     * mediante URL limpia o mediante la ruta física de app/Views.
     */
    function view_base_href_for_request(): ?string {
        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $projectBase = project_base_url();

        if (strpos($requestPath, $projectBase) === 0) {
            $requestPath = substr($requestPath, strlen($projectBase));
        }
        $requestPath = trim((string)$requestPath, '/');

        if ($requestPath === '') {
            return null;
        }

        if (strpos($requestPath, 'app/Views/') === 0) {
            $dir = trim(dirname($requestPath), '.\\/');
            return $projectBase . ($dir !== '' ? $dir . '/' : '');
        }

        foreach (clean_route_map() as $target => $clean) {
            if (trim($clean, '/') !== $requestPath) {
                continue;
            }

            $target = ltrim($target, '/');
            if ($target === 'index.php' || $target === 'logout.php') {
                return null;
            }

            if (strpos($target, 'app/Views/') === 0) {
                $dir = trim(dirname($target), '.\\/');
            } else {
                $dir = trim(BASE_VIEWS . dirname($target), '.\\/');
            }

            return $projectBase . ($dir !== '' ? $dir . '/' : '');
        }

        return null;
    }
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
    $path = ltrim($path, '/');
    $map = clean_route_map();
    $projectBase = project_base_url();

    if (isset($map[$path])) {
        $clean = $map[$path];
        return $projectBase . $clean;
    }

    // Si es el index raíz o ya tiene la ruta completa, no le agregamos el prefijo de vistas
    if ($path === 'index.php' || strpos($path, 'app/Views/') === 0 || $path === 'logout.php') {
        return $projectBase . $path;
    }

    return $projectBase . BASE_VIEWS . $path;
}
