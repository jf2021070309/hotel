<?php
/**
 * api/habitaciones.php
 */
require_once BASE_PATH . 'config/db.php';
require_once BASE_PATH . 'app/Middleware/session.php';
require_once '../app/Middleware/auth.php';
require_once '../app/Models/HabitacionModel.php';

protegerPorRol('cajera', 'habitaciones');

$model = new HabitacionModel($pdo);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'libres';

if ($method === 'GET') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $hab = $model->getById($id);
        if ($hab) {
            json_response(true, $hab);
        } else {
            json_response(false, null, 404, 'Habitación no encontrada');
        }
    }

    switch ($action) {
        case 'libres':
            json_response(true, $model->getLibres());
            break;
        case 'disponibles':
            $fi = $_GET['fecha_in'] ?? date('Y-m-d');
            $fo = $_GET['fecha_out'] ?? date('Y-m-d', strtotime('+1 day'));
            $ex = isset($_GET['exclude']) ? (int)$_GET['exclude'] : null;
            json_response(true, $model->getLibresParaFechas($fi, $fo, $ex));
            break;
        case 'todos':
            json_response(true, $model->getAll());
            break;
        default:
            json_response(false, null, 400, 'Acción no válida');
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($model->crear($input)) {
        json_response(true, null, 201, 'Habitación creada');
    } else {
        json_response(false, null, 500, 'Error al crear habitación');
    }
} elseif ($method === 'PUT') {
    $id = (int)($_GET['id'] ?? 0);
    $input = json_decode(file_get_contents('php://input'), true);
    if ($model->actualizar($id, $input)) {
        json_response(true, null, 200, 'Habitación actualizada');
    } else {
        json_response(false, null, 500, 'Error al actualizar habitación');
    }
}
