<?php
// ============================================================
// api/registros.php
// GET  /?activos=1       → solo activos
// GET  /?id=#            → uno por id
// GET  /                 → historial completo
// POST /                 → check-in
// PUT  /?id=#            → checkout
// ============================================================
require_once '../config/conexion.php';
require_once '../app/Controllers/RegistroController.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') json_response(true, null);

$ctrl   = new RegistroController($conn);
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id']     ?? 0);
$activos= isset($_GET['activos']) && $_GET['activos'] === '1';
$body   = get_json_body();

match(true) {
    $method === 'GET' && $id > 0   => $ctrl->show($id),
    $method === 'GET' && $activos  => $ctrl->index(true),
    $method === 'GET'              => $ctrl->index(false),
    $method === 'POST'             => $ctrl->checkin($body),
    $method === 'PUT'              => $ctrl->checkout($id, $body),
    default                       => json_response(false, null, 405, 'Método no permitido'),
};
