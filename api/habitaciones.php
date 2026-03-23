<?php
/**
 * api/habitaciones.php
 */
require_once '../config/db.php';
require_once '../auth/session.php';
require_once '../auth/middleware.php';
require_once '../app/Models/HabitacionModel.php';

protegerPorRol('cajera');

$model = new HabitacionModel($pdo);
$action = $_GET['action'] ?? 'libres';

switch ($action) {
    case 'libres':
        json_response(true, $model->getLibres());
        break;
    default:
        json_response(false, null, 400);
}
