<?php
// ============================================================
// config/conexion.php
// Conexión mysqli + helpers globales
// ============================================================

define('DB_HOST',    'localhost');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_NAME',    'hotel_db');
define('DB_CHARSET', 'utf8mb4');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    // Si estamos en contexto API, devolver JSON; si no, morir con texto
    if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'message' => 'Error de conexión a la base de datos']);
        exit;
    }
    die('Error de conexión: ' . $conn->connect_error);
}

$conn->set_charset(DB_CHARSET);

// ─── Helpers globales ────────────────────────────────────────

/**
 * Enviar respuesta JSON estandarizada y terminar ejecución.
 */
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

/** Sanitizar salida HTML */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/** Fecha de hoy en formato Y-m-d */
function hoy(): string {
    return date('Y-m-d');
}

/** Redirigir y terminar */
function redirigir(string $url): void {
    header('Location: ' . $url);
    exit;
}

/** Formatear moneda */
function moneda(float $valor): string {
    return 'S/ ' . number_format($valor, 2);
}

/** Leer body JSON de la petición */
function get_json_body(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}
