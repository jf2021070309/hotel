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
    // Cargar permisos de módulos desde la BD
    cargarPermisosEnSesion($usuario['id']);
}

/**
 * Lee los permisos del usuario desde usuario_permisos y los guarda en sesión.
 * Admin siempre tiene acceso total.
 */
function cargarPermisosEnSesion(int $uid): void {
    if (($_SESSION['auth_rol'] ?? '') === 'admin') {
        $_SESSION['auth_permisos'] = null; // null = acceso total
        return;
    }
    try {
        // Necesitamos PDO — incluirlo si no está disponible
        if (!isset($GLOBALS['pdo'])) {
            $base = dirname(__DIR__) . '/';
            require_once $base . 'config/db.php';
        }
        $pdo  = $GLOBALS['pdo'];
        $stmt = $pdo->prepare("SELECT modulo, activo FROM usuario_permisos WHERE usuario_id = ?");
        $stmt->execute([$uid]);
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $_SESSION['auth_permisos'] = $rows; // ['rooming' => 1, 'flujo' => 0, ...]
    } catch (Exception $e) {
        $_SESSION['auth_permisos'] = null; // Si falla, acceso total por seguridad
    }
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
    $roles = ['limpieza', 'cajera', 'supervisor', 'admin'];
    $user_rol = strtolower($_SESSION['auth_rol'] ?? 'limpieza');
    $idx_user = array_search($user_rol, $roles);
    $idx_min  = array_search(strtolower($rol_minimo), $roles);
    
    // Si el rol no existe en la lista, por seguridad denegamos a menos que sea admin
    if ($idx_user === false) return false;
    return $idx_user >= $idx_min;
}

/**
 * Verifica si el usuario tiene acceso a un módulo específico.
 * Lee directo de la BD (cache por request) — los cambios aplican de inmediato.
 */
function tieneAccesoModulo(string $modulo): bool {
    // Admin siempre tiene todo
    if (($_SESSION['auth_rol'] ?? '') === 'admin') return true;

    $uid = $_SESSION['auth_id'] ?? null;
    if (!$uid) return false;

    // Cache estático por request (solo 1 query por página, sin importar cuántos módulos)
    static $cache = null;
    if ($cache === null) {
        try {
            // Conexión propia — no depende de que otra página haya incluido db.php
            static $db = null;
            if ($db === null) {
                // Reutilizar $pdo global si ya existe, si no crear uno nuevo
                if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
                    $db = $GLOBALS['pdo'];
                } else {
                    $db = new PDO(
                        'mysql:host=localhost;dbname=hotel_db;charset=utf8mb4',
                        'root', '',
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
                    );
                }
            }
            $stmt = $db->prepare(
                "SELECT modulo, activo FROM usuario_permisos WHERE usuario_id = ?"
            );
            $stmt->execute([$uid]);
            $cache = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // ['rooming' => 0, ...]
        } catch (Exception $e) {
            $cache = []; // Si falla → acceso total por seguridad
        }
    }

    // Sin registro = activo por defecto (open by default)
    if (!array_key_exists($modulo, $cache)) return true;
    return (bool)$cache[$modulo];
}
