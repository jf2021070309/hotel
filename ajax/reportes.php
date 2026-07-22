<?php
/**
 * api/reportes.php
 */
require_once __DIR__ . '/../ajax/bootstrap.php';
require_once BASE_PATH . 'config/db.php';
require_once BASE_PATH . 'app/Middleware/session.php';
require_once BASE_PATH . 'app/Middleware/auth.php';
require_once BASE_PATH . 'app/Controllers/ReportesController.php';

protegerPorRol('cajera', 'reportes');

$action = $_GET['action'] ?? '';
$controller = new ReportesController($pdo);

switch ($action) {
    case 'facturas':
        $desde  = $_GET['desde']  ?? date('Y-m-d');
        $hasta  = $_GET['hasta']  ?? date('Y-m-d');
        $estado = $_GET['estado'] ?? null;
        json_response(true, $controller->facturas($desde, $hasta, $estado));
        break;

    case 'sunat':
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        json_response(true, $controller->sunat($desde, $hasta));
        break;

    case 'corporativas':
        json_response(true, $controller->corporativasExtranjeras());
        break;

    case 'recurrentes':
        $min = (int)($_GET['min'] ?? 2);
        json_response(true, $controller->extranjerosRecurrentes($min));
        break;

    case 'mendoza':
        $mes  = (int)($_GET['mes']  ?? date('m'));
        $anio = (int)($_GET['anio'] ?? date('Y'));
        json_response(true, $controller->mendoza($mes, $anio));
        break;

    case 'subir_voucher':
        $data = json_decode(file_get_contents('php://input'), true);
        $tipo = $data['tipo'] ?? '';
        $id = (int)($data['id'] ?? 0);
        $b64 = $data['b64'] ?? '';
        
        if (!$tipo || !$id || !$b64) {
            json_response(false, null, 400, "Faltan datos obligatorios");
            exit;
        }
        
        require_once __DIR__ . '/../app/Models/ReporteModel.php';
        $model = new ReporteModel($pdo);
        $res = $model->guardarVoucherB64($tipo, $id, $b64);
        json_response($res, null, $res ? 200 : 500, $res ? "Guardado" : "Error al guardar");
        break;

    default:
        json_response(false, null, 400, "Acción no válida");
        break;
}
