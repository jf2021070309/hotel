<?php
/**
 * app/Controllers/DashboardController.php
 */
require_once __DIR__ . '/../Models/DashboardModel.php';

class DashboardController {
    private DashboardModel $model;

    public function __construct(PDO $pdo) {
        $this->model = new DashboardModel($pdo);
    }

    public function getAdminData(): array {
        $fecha = date('Y-m-d');
        try {
            $data = $this->model->getAdminData($fecha);
            return [
                'ok' => true,
                'data' => $data,
                'msg' => 'Datos obtenidos con éxito'
            ];
        } catch (Exception $e) {
            return [
                'ok' => false,
                'msg' => 'Error al obtener datos: ' . $e->getMessage()
            ];
        }
    }

    public function getCajeraData(): array {
        $fecha = date('Y-m-d');
        $usuarioId = $_SESSION['auth_id'] ?? 0;
        
        // Detectar turno en vivo aproximado como default si no ha iniciado flujo
        $hora = (int)date('H');
        $turnoDefault = ($hora >= 6 && $hora < 14) ? 'MAÑANA' : 'TARDE';
        
        $nombre = $_SESSION['auth_nombre'] ?? 'Operador';
        // Extraer solo su primer nombre referencial amigable
        $primerNombre = explode(' ', $nombre)[0];

        try {
            $data = $this->model->getCajeraData($fecha, $usuarioId, $turnoDefault);
            
            // Adjuntamos la data de usuario en el controlador para preservar MVC
            $data['usuario'] = [
                'nombre' => $primerNombre,
                'turno'  => $turnoDefault
            ];

            return [
                'ok' => true,
                'data' => $data,
                'msg' => 'Datos operativos obtenidos con éxito'
            ];

        } catch (Exception $e) {
            return [
                'ok' => false,
                'msg' => 'Error al cargar panel operativo: ' . $e->getMessage()
            ];
        }
    }
}
