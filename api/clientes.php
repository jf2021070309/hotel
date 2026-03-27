<?php
// api/clientes.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../app/Controllers/ClienteController.php';

$action = $_GET['action'] ?? 'listar';
$ctrl   = new ClienteController($pdo);

switch ($action) {
    case 'listar':
        $buscar = trim($_GET['buscar'] ?? '');
        json_response(true, $ctrl->index($buscar));
        break;
    case 'buscar_pax':
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 3) { json_response(true, []); break; }
        $stmt = $pdo->prepare(
            "SELECT documento_tipo, documento_num, nombre_completo, nacionalidad, ciudad
             FROM rooming_pax
             WHERE documento_num LIKE ?
             GROUP BY documento_num, documento_tipo
             ORDER BY nombre_completo
             LIMIT 6"
        );
        $stmt->execute([$q . '%']);
        json_response(true, $stmt->fetchAll());
        break;
    case 'historial':
        $dni = trim($_GET['dni'] ?? '');
        if ($dni === '') { json_response(false, null, 400, 'DNI requerido'); }
        try {
            $resultado = $ctrl->historial($dni);
            json_response(true, $resultado);
        } catch (Exception $e) {
            json_response(false, null, 500, $e->getMessage());
        }
        break;
    default:
        json_response(false, null, 400, 'Acción no válida');
}
