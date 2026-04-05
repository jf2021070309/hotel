<?php
/**
 * Controlador del Módulo de Dashboard.
 *
 * Orquesta la recopilación de métricas y datos operativos para los
 * diferentes roles del sistema (Administrador y Cajera).
 *
 * @package App\Controllers
 */
require_once __DIR__ . '/../Models/DashboardModel.php';

class DashboardController {
    private DashboardModel $model;

    /**
     * Constructor del controlador.
     *
     * @param PDO $pdo Conexión a la base de datos.
     */
    public function __construct(PDO $pdo) {
        $this->model = new DashboardModel($pdo);
    }

    /**
     * Obtiene el conjunto de datos completo para el Dashboard de Administrador.
     * Incluye KPIs de ingresos, ocupación, cobros pendientes y gráficos mensuales.
     *
     * @return array Respuesta estructurada con 'ok', 'data' (métricas) y 'msg'.
     */
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

    /**
     * Obtiene los datos operativos para el Dashboard de Cajera.
     * Filtra información relevante al turno actual, check-ins/outs del día
     * y el estado del flujo de caja del usuario.
     *
     * @return array Respuesta estructurada con 'ok', 'data' y 'msg'.
     */
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
