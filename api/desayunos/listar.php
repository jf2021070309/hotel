<?php
/**
 * api/desayunos/listar.php
 */
require_once '../../config/db.php';
require_once '../../auth/session.php';
require_once '../../app/Controllers/DesayunoController.php';

header('Content-Type: application/json; charset=utf-8');

$controller = new DesayunoController($pdo);
echo json_encode($controller->listar());
