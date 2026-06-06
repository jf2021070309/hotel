<?php
/**
 * api/auditoria.php - API para el módulo de auditoría
 */
require_once BASE_PATH . 'config/db.php';
require_once BASE_PATH . 'app/Middleware/session.php';
require_once BASE_PATH . 'app/Middleware/auth.php';
require_once BASE_PATH . 'app/Controllers/AuditoriaController.php';

// Solo admin puede ver la auditoría
protegerPorRol('cajera', 'auditoria');

$action = $_GET['action'] ?? 'listar';
$controller = new AuditoriaController($pdo);

switch ($action) {
    case 'listar':
        json_response(true, $controller->index($_GET));
        break;

    case 'exportar':
        $controller->export($_GET);
        break;

    default:
        json_response(false, null, 400, "Acción no válida");
        break;
}
