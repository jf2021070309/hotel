<?php
/**
 * config/db.php
 * Conexión centralizada usando PDO para mayor seguridad y prepared statements.
 */
date_default_timezone_set('America/Lima');

$host    = 'localhost';
$db      = 'hotel_db';
$user    = 'root';
$pass    = ''; // Ajustar según entorno
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // En producción, no mostrar el error detallado
    die("Error de conexión: " . $e->getMessage());
}

/**
 * Helper para respuestas JSON consistentes
 */
if (!function_exists('json_response')) {
    function json_response(bool $ok, $data = null, int $code = 200, string $msg = '') {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode([
            'ok'   => $ok,
            'data' => $data,
            'msg'  => $msg
        ]);
        exit;
    }
}
