<?php
// api/gastos.php
require_once '../config/conexion.php';
require_once '../app/Controllers/GastoController.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') json_response(true, null);

$ctrl   = new GastoController($conn);
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);
$fecha  = $_GET['fecha'] ?? '';
$body   = get_json_body();

match($method) {
    'GET'    => $ctrl->index($fecha),
    'POST'   => $ctrl->store($body),
    'DELETE' => $ctrl->destroy($id),
    default  => json_response(false, null, 405, 'Método no permitido'),
};
