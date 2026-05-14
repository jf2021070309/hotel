<?php
/**
 * api/reportes.php
 */
require_once '../config/db.php';
require_once '../auth/session.php';
require_once '../auth/middleware.php';
require_once '../app/Controllers/ReportesController.php';

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

    case 'alex':
        $mes  = (int)($_GET['mes']  ?? date('m'));
        $anio = (int)($_GET['anio'] ?? date('Y'));
        json_response(true, $controller->alex($mes, $anio));
        break;

    case 'diario':
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        json_response(true, $controller->cuadre($fecha));
        break;

    case 'mensual':
        $mes  = (int)($_GET['month'] ?? date('m'));
        $anio = (int)($_GET['year']  ?? date('Y'));
        json_response(true, $controller->mensual($mes, $anio));
        break;

    default:
        // Soporte para parámetro 'tipo' usado en algunos JS
        $tipo = $_GET['tipo'] ?? '';
        if ($tipo === 'diario') {
            $fecha = $_GET['fecha'] ?? date('Y-m-d');
            json_response(true, $controller->cuadre($fecha));
        } elseif ($tipo === 'mensual') {
            $mes  = (int)($_GET['month'] ?? date('m'));
            $anio = (int)($_GET['year']  ?? date('Y'));
            json_response(true, $controller->mensual($mes, $anio));
        } elseif ($tipo === 'graficos') {
            $mes  = (int)($_GET['month'] ?? date('m'));
            $anio = (int)($_GET['year']  ?? date('Y'));
            json_response(true, $controller->graficos($mes, $anio));
        } else {
            json_response(false, null, 400, "Acción no válida");
        }
        break;
}
