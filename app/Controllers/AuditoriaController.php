<?php
/**
 * app/Controllers/AuditoriaController.php
 */
class AuditoriaController {
    private PDO $pdo;
    private AuditoriaModel $model;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        require_once __DIR__ . '/../Models/AuditoriaModel.php';
        $this->model = new AuditoriaModel($pdo);
    }

    /**
     * Listar logs de auditoría con filtros aplicados.
     */
    public function index(array $filters = []) {
        return $this->model->getAll($filters, 500); // Aumentamos límite para búsquedas
    }

    /**
     * Exporta los logs a Excel (formato CSV compatible)
     */
    public function export(array $filters = []) {
        $logs = $this->model->getAll($filters, 2000); // Límite más alto para reporte
        
        $filename = "auditoria_" . date('Ymd_His') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        // BOM para que Excel detecte UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Cabeceras
        fputcsv($output, ['ID', 'FECHA', 'HORA', 'USUARIO', 'ROL', 'MODULO', 'ACCION', 'DETALLE', 'IP', 'DISPOSITIVO']);
        
        foreach ($logs as $log) {
            $fecha = date('d/m/Y', strtotime($log['fecha_hora']));
            $hora  = date('H:i:s', strtotime($log['fecha_hora']));
            
            fputcsv($output, [
                $log['id'],
                $fecha,
                $hora,
                $log['usuario_nombre'],
                $log['rol_usuario'] ?? 'N/A',
                $log['modulo'],
                $log['accion'],
                strip_tags($log['detalle']),
                $log['ip'],
                $log['dispositivo'] ?? 'N/A'
            ]);
        }
        fclose($output);
        exit;
    }
}
