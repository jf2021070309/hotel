<?php
// ============================================================
// api/habitaciones.php
// GET  /?libres=1        → habitaciones libres
// GET  /                 → todas las habitaciones
// GET  /?id=#            → una habitación
// POST /                 → crear
// PUT  /?id=#            → actualizar
// ============================================================
require_once '../config/conexion.php';
require_once '../app/Controllers/HabitacionController.php';


require_once __DIR__ . '/../auth/session.php';
if (!estaAutenticado()) { json_response(false, null, 401, 'No autorizado'); }
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') json_response(true, null);

$ctrl   = new HabitacionController($conn);
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id']     ?? 0);
$libres = isset($_GET['libres']) && $_GET['libres'] === '1';
$body   = get_json_body();

match(true) {
    $method === 'GET' && $libres => $ctrl->libres(),
    $method === 'GET'            => $ctrl->index($id),
    $method === 'POST'           => $ctrl->store($body),
    $method === 'PUT'            => $ctrl->update($id, $body),
    default                     => json_response(false, null, 405, 'Método no permitido'),
};

