<?php
/**
 * app/Middleware/session.php
 * Manejo centralizado de la sesión del usuario con Auditoría de Seguridad.
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 28800);
    session_set_cookie_params(28800);
    session_start();
}

/**
 * Inicia la sesión del usuario y registra el ingreso en Auditoría.
 */
function iniciarSesion(array $usuario): void {
    session_regenerate_id(true);
    $_SESSION['auth_id']      = $usuario['id'];
    $_SESSION['auth_nombre']  = $usuario['nombre'];
    $_SESSION['auth_rol']     = $usuario['rol'];
    $_SESSION['auth_usuario'] = $usuario['usuario'];
    $_SESSION['last_activity'] = time();
    
    cargarPermisosEnSesion($usuario['id']);

    // --- REGISTRO DE AUDITORÍA DE SEGURIDAD ---
    global $pdo;
    if (isset($pdo)) {
        require_once dirname(__DIR__, 2) . '/app/Models/AuditoriaModel.php';
        $audit = new AuditoriaModel($pdo);
        $audit->registrar($usuario['id'], 'INICIO_SESION', 'SEGURIDAD', "Inicio de sesión exitoso (Trabajador)");
    }
}

/**
 * Cierra la sesión y registra la salida en Auditoría.
 */
function cerrarSesion(): void {
    if (isset($_SESSION['auth_id'])) {
        global $pdo;
        if (isset($pdo)) {
            require_once dirname(__DIR__, 2) . '/app/Models/AuditoriaModel.php';
            $audit = new AuditoriaModel($pdo);
            $audit->registrar($_SESSION['auth_id'], 'CIERRE_SESION', 'SEGURIDAD', "El usuario cerró su sesión");
        }
    }

    session_unset();
    session_destroy();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
}

function cargarPermisosEnSesion(int $uid): void {
    if (($_SESSION['auth_rol'] ?? '') === 'admin') {
        $_SESSION['auth_permisos'] = null;
        return;
    }
    try {
        global $pdo;
        if (!isset($pdo)) {
            require_once dirname(__DIR__, 2) . '/config/db.php';
        }
        $stmt = $pdo->prepare("SELECT modulo, activo FROM usuario_permisos WHERE usuario_id = ?");
        $stmt->execute([$uid]);
        $_SESSION['auth_permisos'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) { $_SESSION['auth_permisos'] = null; }
}

function estaAutenticado(): bool {
    if (!isset($_SESSION['auth_id'])) return false;

    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
        return true;
    }

    if (time() - $_SESSION['last_activity'] > 28800) {
        cerrarSesion();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

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
 */
function tienePermiso(string $rol_minimo): bool {
    $roles = ['limpieza', 'cajera', 'supervisor', 'admin'];
    $user_rol = strtolower($_SESSION['auth_rol'] ?? 'limpieza');
    $idx_user = array_search($user_rol, $roles);
    $idx_min  = array_search(strtolower($rol_minimo), $roles);
    if ($idx_user === false) return false;
    return $idx_user >= $idx_min;
}

/**
 * Verifica si el usuario tiene acceso a un módulo específico.
 */
function tieneAccesoModulo(string $modulo): bool {
    if (($_SESSION['auth_rol'] ?? '') === 'admin') return true;
    $uid = $_SESSION['auth_id'] ?? null;
    if (!$uid) return false;

    static $cache = null;
    if ($cache === null) {
        try {
            global $pdo;
            if (!isset($pdo)) require_once dirname(__DIR__, 2) . '/config/db.php';
            $stmt = $pdo->prepare("SELECT modulo, activo FROM usuario_permisos WHERE usuario_id = ?");
            $stmt->execute([$uid]);
            $cache = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) { $cache = []; }
    }
    if (!array_key_exists($modulo, $cache)) return true;
    return (bool)$cache[$modulo];
}
