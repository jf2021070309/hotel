<?php
// ============================================================
// config/conexion.php
// Compatible con Railway, XAMPP y cualquier hosting
// ============================================================

define('DB_CHARSET', 'utf8mb4');

// ============================================================
// Detectar Railway mediante variables de entorno
// ============================================================

// Railway usa normalmente MYSQLHOST, pero verificamos también con guión bajo por si acaso
$railway_host = getenv('MYSQLHOST') ?: getenv('MYSQL_HOST');
$mysql_url = getenv('MYSQL_URL');

if ($railway_host) {

    // PRODUCCIÓN (Railway - Variables individuales)
    $host = $railway_host;
    $port = getenv('MYSQLPORT') ?: (getenv('MYSQL_PORT') ?: 3306);
    $user = getenv('MYSQLUSER') ?: getenv('MYSQL_USER');
    $pass = getenv('MYSQLPASSWORD') ?: getenv('MYSQL_PASSWORD');
    $db = getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE');

} elseif ($mysql_url) {

    // PRODUCCIÓN (Railway - Método URL fallback)
    $parts = parse_url($mysql_url);
    $host = $parts['host'];
    $port = $parts['port'] ?? 3306;
    $user = $parts['user'];
    $pass = $parts['pass'] ?? '';
    $db = ltrim($parts['path'], '/');

} else {

    // LOCAL (XAMPP)
    // Cambiamos 'localhost' por '127.0.0.1' para obligar a usar TCP 
    // y evitar el error de socket incluso en local.
    $host = '127.0.0.1';
    $port = 3306;
    $user = 'root';
    $pass = '';
    $db = 'hotel_db';

}

// ============================================================
// CONFIGURACIÓN GLOBAL
// ============================================================
date_default_timezone_set('America/Lima');

// ============================================================
// CONEXIÓN
// ============================================================

$conn = new mysqli($host, $user, $pass, $db, (int) $port);

if ($conn->connect_error) {
    die("Error de conexión MySQL: " . $conn->connect_error);
}

$conn->set_charset(DB_CHARSET);
// Sincronizar zona horaria de MySQL con PHP
$conn->query("SET time_zone = '-05:00'");

// ============================================================
// HELPERS
// ============================================================

function json_response(bool $ok, $data = null, int $status = 200, string $message = ''): void
{

    http_response_code($status);

    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    echo json_encode([
        'ok' => $ok,
        'data' => $data,
        'message' => $message,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}

function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function hoy(): string
{
    return date('Y-m-d');
}

function redirigir(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function moneda(float $valor): string
{
    return 'S/ ' . number_format($valor, 2);
}

function get_json_body(): array
{
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}