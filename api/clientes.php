<?php
/**
 * Endpoints del Módulo de Clientes.
 * 
 * Gestiona la búsqueda de huéspedes corporativos y frecuentes.
 * 
 * @package API\Clientes
 */
require_once '../config/db.php';
require_once '../auth/session.php';
require_once '../auth/middleware.php';
require_once '../app/Controllers/ClienteController.php';

// Proteger endpoint para todos los operativos
protegerPorRol('cajera', 'clientes');

$action = $_GET['action'] ?? 'listar';
$controller = new ClienteController($pdo);

switch ($action) {
    case 'listar':
        // --- MIGRACIÓN AUTOMÁTICA DE EMERGENCIA (Self-Healing) ---
        try {
            $pdo->exec("ALTER TABLE `rooming_pax` MODIFY `stay_id` INT(10) UNSIGNED NULL;");
        } catch (Exception $e) {
            // Ya modificado o ignorar
        }
        try {
            $pdo->exec("ALTER TABLE `rooming_pax` ADD COLUMN `ruc` VARCHAR(20) DEFAULT NULL AFTER `documento_num`;");
        } catch (Exception $e) {
            // Ya existe o ignorar
        }
        try {
            $pdo->exec("ALTER TABLE `rooming_pax` ADD COLUMN `vip` TINYINT(1) DEFAULT 0;");
        } catch (Exception $e) {
            // Ya existe o ignorar
        }
        // ---------------------------------------------------------

        $buscar = $_GET['buscar'] ?? '';
        json_response(true, $controller->index($buscar));
        break;
        
    case 'buscar_pax':
        $q = $_GET['q'] ?? '';
        json_response(true, $controller->buscar_pax($q));
        break;
        
    case 'historial':
        $dni = $_GET['dni'] ?? '';
        json_response(true, $controller->historial($dni));
        break;

    case 'guardar':
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        if (empty($data)) {
            json_response(false, null, 400, "Datos no recibidos");
        }
        try {
            if ($controller->store($data)) {
                json_response(true, null, 200, "Cliente registrado correctamente");
            } else {
                json_response(false, null, 500, "No se pudo registrar el cliente");
            }
        } catch (Throwable $e) {
            json_response(false, null, 500, $e->getMessage());
        }
        break;
        
    default:
        json_response(false, null, 400, "Acción no válida");
        break;
}
