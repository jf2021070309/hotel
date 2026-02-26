<?php
// api/pagos.php
require_once '../config/conexion.php';
require_once '../app/Controllers/PagoController.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') json_response(true, null);

$ctrl   = new PagoController($conn);
$method = $_SERVER['REQUEST_METHOD'];
$body   = get_json_body();

match($method) {
    'GET'  => $ctrl->index(),
    'POST' => $ctrl->store($body),
    default => json_response(false, null, 405, 'Método no permitido'),
};
