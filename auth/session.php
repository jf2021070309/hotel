<?php
/**
 * auth/session.php
 * Manejo centralizado de la sesión del usuario.
 */

if (session_status() === PHP_SESSION_NONE) {
    // Sesión de 8 horas (duración de un turno típico)
    ini_set('session.gc_maxlifetime', 28800);
    session_set_cookie_params(28800);
    session_start();
}

/**
 * Inicia la sesión del usuario guardando sus datos básicos.
 */
function iniciarSesion(array $usuario): void {
    session_regenerate_id(true);
    $_SESSION['auth_id']      = $usuario['id'];
    $_SESSION['auth_nombre']  = $usuario['nombre'];
    $_SESSION['auth_rol']     = $usuario['rol'];
    $_SESSION['auth_usuario'] = $usuario['usuario'];
    $_SESSION['last_activity'] = time();
}

/**
 * Cierra la sesión activa.
 */
function cerrarSesion(): void {
    session_unset();
    session_destroy();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
}

/**
 * Verifica si el usuario está autenticado.
 */
function estaAutenticado(): bool {
    if (!isset($_SESSION['auth_id'])) return false;
    
    // Verificar si la sesión ha expirado (timeout de inactividad de 8 horas)
    if (time() - $_SESSION['last_activity'] > 28800) {
        cerrarSesion();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Obtiene el usuario actual logueado.
 */
function obtenerUsuarioActual(): array {
    return [
        'id'      => $_SESSION['auth_id'] ?? null,
        'nombre'  => $_SESSION['auth_nombre'] ?? null,
        'rol'     => $_SESSION['auth_rol'] ?? null,
        'usuario' => $_SESSION['auth_usuario'] ?? null
    ];
}

/**
 * Verifica si el usuario tiene un rol mínimo requerido.
 * Orden de jerarquía: admin > supervisor > cajera > limpieza
 */
function tienePermiso(string $rol_minimo): bool {
    // Escala para el futuro: Aquí se podrán definir permisos granulares.
    // Por ahora, todos tienen acceso a sus niveles según la jerarquía.
    $roles = ['limpieza', 'cajera', 'supervisor', 'admin'];
    $user_rol = $_SESSION['auth_rol'] ?? 'limpieza';
    
    $idx_user = array_search($user_rol, $roles);
    $idx_min  = array_search($rol_minimo, $roles);
    
    return $idx_user >= $idx_min;
}
