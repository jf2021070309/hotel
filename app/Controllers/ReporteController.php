<?php
/**
 * app/Controllers/ReporteController.php
 */
require_once __DIR__ . '/../Models/ReporteModel.php';

class ReporteController {
    private ReporteModel $model;

    public function __construct(PDO $pdo) {
        $this->model = new ReporteModel($pdo);
    }

    /**
     * Reporte Mendoza: Venta de Hospedaje Detallada
     */
    public function mendoza(): array {
        $mes = (int)($_GET['mes'] ?? date('m'));
        $anio = (int)($_GET['anio'] ?? date('Y'));
        
        try {
            $data = $this->model->getVentaHospedaje($mes, $anio);
            $resumen = $this->model->getResumenP_L($mes, $anio);
            return [
                'ok' => true,
                'data' => $data,
                'resumen' => $resumen,
                'filtros' => ['mes' => $mes, 'anio' => $anio]
            ];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * Reporte Alex: Gastos Yape Detallados
     */
    public function alex(): array {
        $mes = (int)($_GET['mes'] ?? date('m'));
        $anio = (int)($_GET['anio'] ?? date('Y'));
        
        try {
            $data = $this->model->getGastosYape($mes, $anio);
            return [
                'ok' => true,
                'data' => $data,
                'filtros' => ['mes' => $mes, 'anio' => $anio]
            ];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * Resumen P&L para Dashboard
     */
    public function resumenPL(): array {
        $mes = (int)($_GET['mes'] ?? date('m'));
        $anio = (int)($_GET['anio'] ?? date('Y'));
        try {
            return [
                'ok' => true,
                'data' => $this->model->getResumenP_L($mes, $anio)
            ];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }
}
