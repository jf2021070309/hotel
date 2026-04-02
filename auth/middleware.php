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
 * Función helper para proteger rutas por rol y módulo.
 * Si no tiene permiso por rol NI por módulo individual, deniega acceso.
 */
function protegerPorRol(string $rol_minimo, string $modulo = ''): void {
    global $base;
    
    // 1. Acceso por jerarquía de rol
    $accessByRole = tienePermiso($rol_minimo);
    
    // 2. Acceso por permiso granular de módulo (si se provee)
    $accessByModule = ($modulo !== '') ? tieneAccesoModulo($modulo) : false;
    
    // Admin siempre tiene acceso (ya manejado en tienePermiso/tieneAccesoModulo)
    
    if (!$accessByRole && !$accessByModule) {
        // Detectar si es una petición API
        $isApi = (
            strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false || 
            (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
        );
        
        if ($isApi) {
            // Asegurar que json_response esté disponible (está en config/db.php)
            if (!function_exists('json_response')) {
                // Intentar cargar db.php relativo a este archivo
                $dbPath = __DIR__ . '/../config/db.php';
                if (file_exists($dbPath)) require_once $dbPath;
            }
            
            if (function_exists('json_response')) {
                json_response(false, null, 403, 'Acceso Denegado: No tienes permisos suficientes para este módulo.');
            } else {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['ok' => false, 'msg' => 'Acceso Denegado (API)']);
                exit;
            }
        } else {
            // Redirigir a página de error 403 para vistas normales
            header('Location: ' . route('errors/403.php', $base ?? ''));
            exit;
        }
    }
}
