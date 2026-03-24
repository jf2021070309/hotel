<?php
/**
 * api/desayunos/guardar.php
 */
require_once '../../config/db.php';
require_once '../../auth/session.php';
require_once '../../app/Controllers/DesayunoController.php';

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['ok' => false, 'msg' => 'Datos inválidos.']);
    exit;
}

$input['usuario_id'] = $_SESSION['auth_id'] ?? 1;

$controller = new DesayunoController($pdo);
echo json_encode($controller->guardar($input));
