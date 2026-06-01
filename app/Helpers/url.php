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
        $projectRoot = realpath(dirname(__DIR__, 2));

        if ($docRoot && $projectRoot) {
            $docRoot = str_replace('\\', '/', $docRoot);
            $projectRoot = str_replace('\\', '/', $projectRoot);

            if (stripos($projectRoot, $docRoot) === 0) {
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
            'rooming/v2.php'            => 'rooming/v2',
            'reservas/index.php'        => 'reservas',
            'flujo/index.php'           => 'flujo',
            'flujo/v2.php'              => 'flujo/v2',
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
            'limpieza/v2.php'           => 'limpieza/v2',
            'clientes/index.php'        => 'clientes',
            'clientes/v2.php'           => 'clientes/v2',
            'clientes/frecuentes.php'   => 'clientes-frecuentes',
            'admin/usuarios.php'        => 'admin/usuarios',
            'admin/medios_pago.php'     => 'admin/medios-pago',
            'admin/auditoria.php'       => 'admin/auditoria',
            'app/Views/reportes/mendoza.php' => 'reportes/mendoza',
            'logout.php'                => 'logout.php',
        ];
    }
}

if (!function_exists('view_base_href_for_request')) {
    /**
     * Calcula el base href correcto para la vista actual.
     * Funciona en localhost (/hotel/modulo) y en producción (/modulo).
     *
     * Si la vista fue incluida desde un subdirectorio (app/Views/modulo/archivo.php),
     * devuelve la URL absoluta de ese directorio para que los scripts relativos
     * (como "index.js") puedan resolverse correctamente en el navegador.
     */
    function view_base_href_for_request(): ?string {
        $projectBase = project_base_url(); // e.g. "/hotel/" o "/"
        $requestUri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        // Quitar el prefijo del proyecto para obtener la ruta relativa
        if (stripos($requestUri, $projectBase) === 0) {
            $relative = substr($requestUri, strlen($projectBase));
        } else {
            $relative = ltrim($requestUri, '/');
        }
        $relative = trim((string)$relative, '/');

        // Si accedemos a la raíz (dashboard) no necesitamos base href
        if ($relative === '' || $relative === 'index.php') {
            return null;
        }

        // Buscar en el mapa de rutas limpias cuál archivo físico sirve esta URL
        $map = clean_route_map();
        foreach ($map as $physicalPath => $cleanPath) {
            if (trim($cleanPath, '/') === $relative) {
                // Calculamos el directorio del archivo físico
                $dir = dirname($physicalPath);
                $dir = ltrim(str_replace('\\', '/', $dir), './');
                if ($dir === '' || $dir === '.') {
                    return null;
                }
                // Si es un path en app/Views/... usarlo directamente
                if (strpos($physicalPath, 'app/Views/') !== 0) {
                    $dir = 'app/Views/' . $dir;
                }
                return $projectBase . $dir . '/';
            }
        }

        // Fallback: si la URL ya parece un path físico (acceso directo sin clean URL)
        if (strpos($relative, 'app/Views/') === 0) {
            $dir = trim(dirname($relative), './');
            return $projectBase . $dir . '/';
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
