<?php
// ============================================================
// api/clientes.php
// GET  /?buscar=texto    → buscar por nombre/DNI
// GET  /                 → todos
// POST /                 → crear
// ============================================================
require_once '../config/conexion.php';
require_once '../app/Controllers/ClienteController.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') json_response(true, null);

$ctrl   = new ClienteController($conn);
$method = $_SERVER['REQUEST_METHOD'];
$buscar = trim($_GET['buscar'] ?? '');
$body   = get_json_body();

match($method) {
    'GET'  => $ctrl->index($buscar),
    'POST' => $ctrl->store($body),
    default => json_response(false, null, 405, 'Método no permitido'),
};
