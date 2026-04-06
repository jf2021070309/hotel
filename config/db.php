<?php
/**
 * config/db.php
 * Conexión centralizada (PDO), detección de entorno (Railway/Local) y Helpers.
 */
date_default_timezone_set('America/Lima');

// ============================================================
// 1. Detectar Entorno (Railway vs Local)
// ============================================================
$railway_host = getenv('MYSQLHOST') ?: getenv('MYSQL_HOST');
$mysql_url = getenv('MYSQL_URL');

if ($railway_host) {
    // PRODUCCIÓN (Railway)
    $host = $railway_host;
    $port = getenv('MYSQLPORT') ?: (getenv('MYSQL_PORT') ?: 3306);
    $user = getenv('MYSQLUSER') ?: getenv('MYSQL_USER');
    $pass = getenv('MYSQLPASSWORD') ?: getenv('MYSQL_PASSWORD');
    $db = getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE');
} elseif ($mysql_url) {
    // PRODUCCIÓN (Railway URL)
    $parts = parse_url($mysql_url);
    $host = $parts['host'];
    $port = $parts['port'] ?? 3306;
    $user = $parts['user'];
    $pass = $parts['pass'] ?? '';
    $db = ltrim($parts['path'], '/');
} else {
    // LOCAL (Obligamos TCP con 127.0.0.1)
    $host = '127.0.0.1';
    $port = 3306;
    $user = 'root';
    $pass = '';
    $db = 'hotel_db';
}

// ============================================================
// 2. CONEXIÓN PDO
// ============================================================
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec("SET time_zone = '-05:00'");
} catch (\PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// ============================================================
// 3. HELPERS GLOBALES
// ============================================================

if (!function_exists('json_response')) {
    function json_response(bool $ok, $data = null, int $status = 200, string $msg = ''): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        echo json_encode([
            'ok' => $ok,
            'data' => $data,
            'msg' => $msg
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }
}

if (!function_exists('e')) {
    function e(string $str): string
    {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('hoy')) {
    function hoy(): string
    {
        return date('Y-m-d');
    }
}

if (!function_exists('redirigir')) {
    function redirigir(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('moneda')) {
    function moneda(float $valor): string
    {
        return 'S/ ' . number_format($valor, 2);
    }
}

if (!function_exists('get_json_body')) {
    function get_json_body(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? [];
    }
}
?>