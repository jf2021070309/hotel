<?php
/**
 * api/limpieza.php
 * Router for Cleaning module actions.
 */
require_once __DIR__ . '/../ajax/bootstrap.php';
require_once BASE_PATH . 'config/db.php';
require_once BASE_PATH . 'app/Middleware/session.php';
require_once BASE_PATH . 'app/Middleware/auth.php';
require_once BASE_PATH . 'app/Models/LimpiezaModel.php';
require_once BASE_PATH . 'app/Controllers/LimpiezaController.php';
require_once BASE_PATH . 'api/cron.php'; // expone la función nocheReset()

protegerPorRol('limpieza', 'limpieza');

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'hoy';
$controller = new LimpiezaController($pdo);

switch ($action) {
    case 'hoy':
        echo json_encode($controller->getHoy());
        break;

    case 'generar':
        echo json_encode($controller->generar());
        break;

    case 'actualizar':
        echo json_encode($controller->actualizar());
        break;

    case 'observacion':
        echo json_encode($controller->agregarObservacion());
        break;

    case 'listar':
        echo json_encode($controller->listarHistorial());
        break;

    case 'detalle':
        echo json_encode($controller->getDetalleDia());
        break;

    case 'detalle_fecha':
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $model = new LimpiezaModel($pdo);
        $data = $model->getDetalleDia($fecha);
        echo json_encode(['ok' => true, 'data' => $data]);
        break;

    case 'propuesta':
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $model = new LimpiezaModel($pdo);
        $data = $model->getCalculoPropuesta($fecha);
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    // ── Reset nocturno manual (solo admin / cajera) ──────────────────────────
    case 'noche_reset':
        try {
            $resultado = nocheReset($pdo);
            echo json_encode([
                'ok'      => true,
                'msg'     => 'Reset nocturno ejecutado: ' . $resultado['habitaciones_procesadas'] . ' habitaciones marcadas como sucias.',
                'detalle' => $resultado,
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => 'Acción no reconocida']);
        break;
}
