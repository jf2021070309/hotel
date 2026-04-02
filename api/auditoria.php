<?php
/**
 * api/auditoria.php - API para el módulo de auditoría
 */
require_once '../config/db.php';
require_once '../auth/session.php';
require_once '../auth/middleware.php';
require_once '../app/Controllers/AuditoriaController.php';

// Solo admin puede ver la auditoría
protegerPorRol('cajera', 'auditoria');

$action = $_GET['action'] ?? 'listar';
$controller = new AuditoriaController($pdo);

switch ($action) {
    case 'listar':
        json_response(true, $controller->index());
        break;

    default:
        json_response(false, null, 400, "Acción no válida");
        break;
}
