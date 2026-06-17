<?php
/**
 * ajax/notificaciones_action.php
 * Endpoint para manejar acciones directas desde el panel de notificaciones.
 */
require_once 'bootstrap.php';
require_once BASE_PATH . 'config/db.php';
require_once BASE_PATH . 'app/Middleware/session.php';
require_once BASE_PATH . 'app/Models/AuditoriaModel.php';

header('Content-Type: application/json');

if (!isset($_SESSION['auth_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? '';

$audit = new AuditoriaModel($pdo);

try {
    switch ($action) {
        case 'marcar_limpia':
            $hab_id = (int)($input['habitacion_id'] ?? 0);
            if ($hab_id <= 0) throw new Exception("ID de habitación inválido");
            
            // Verificamos si la habitación existe y obtenemos su número
            $stmt = $pdo->prepare("SELECT numero FROM habitaciones WHERE id = ?");
            $stmt->execute([$hab_id]);
            $numero = $stmt->fetchColumn();
            
            if (!$numero) throw new Exception("Habitación no encontrada");
            
            // Actualizamos la habitación a 'libre' si estaba 'limpieza' o 'sucio'
            $stmt = $pdo->prepare("UPDATE habitaciones SET estado = 'libre' WHERE id = ? AND estado IN ('limpieza', 'sucio')");
            $stmt->execute([$hab_id]);
            
            if ($stmt->rowCount() > 0) {
                // Registrar en auditoría
                $audit->registrar($_SESSION['auth_id'], 'NOTIFICACION_ACCION', 'LIMPIEZA', "Marcó como LIMPIA la Habitación #$numero desde notificaciones.");
                
                echo json_encode(['status' => 'success', 'message' => "Habitación $numero marcada como limpia."]);
            } else {
                echo json_encode(['status' => 'error', 'message' => "La habitación no requería limpieza o ya fue actualizada."]);
            }
            break;
            
        default:
            throw new Exception("Acción no reconocida");
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
