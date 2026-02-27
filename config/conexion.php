<?php
// ============================================================
// config/conexion.php
// Compatible con LOCAL (XAMPP) y RAILWAY
// ============================================================

define('DB_CHARSET', 'utf8mb4');

// ============================================================
// CONFIGURACIÓN RAILWAY (PRODUCCIÓN)
// Railway usa variables de entorno automáticamente
// ============================================================

$host = getenv('MYSQLHOST');
$port = getenv('MYSQLPORT');
$db   = getenv('MYSQLDATABASE');
$user = getenv('MYSQLUSER');
$pass = getenv('MYSQLPASSWORD');

// Si Railway no está presente, usar localhost (fallback)
if (!$host) {

    // ========================================================
    // CONFIGURACIÓN LOCAL (XAMPP)
    // ========================================================
    /*
    $host = 'localhost';
    $port = 3306;
    $db   = 'hotel_db';
    $user = 'root';
    $pass = '';
    */

    // Puedes activar local descomentando arriba
}

// ============================================================
// CONEXIÓN
// ============================================================

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {

    if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {

        header('Content-Type: application/json');

        echo json_encode([
            'ok' => false,
            'message' => 'Error de conexión a la base de datos',
            'error' => $conn->connect_error
        ]);

        exit;
    }

    die('Error de conexión: ' . $conn->connect_error);
}

$conn->set_charset(DB_CHARSET);

// ============================================================
// HELPERS
// ============================================================

function json_response(bool $ok, $data = null, int $status = 200, string $message = ''): void {

    http_response_code($status);

    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    echo json_encode([
        'ok'      => $ok,
        'data'    => $data,
        'message' => $message,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function hoy(): string {
    return date('Y-m-d');
}

function redirigir(string $url): void {
    header('Location: ' . $url);
    exit;
}

function moneda(float $valor): string {
    return 'S/ ' . number_format($valor, 2);
}

function get_json_body(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}