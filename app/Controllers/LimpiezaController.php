<?php
/**
 * app/Controllers/LimpiezaController.php
 */
require_once __DIR__ . '/../Models/LimpiezaModel.php';

class LimpiezaController {
    private LimpiezaModel $model;

    public function __construct(PDO $pdo) {
        $this->model = new LimpiezaModel($pdo);
    }

    public function getHoy(): array {
        $fecha = date('Y-m-d');
        try {
            $detalle = $this->model->getDetalleDia($fecha);
            if (!empty($detalle)) {
                return ['ok' => true, 'data' => $detalle, 'ya_generado' => true];
            }
            // Si no existe, proponer
            return ['ok' => true, 'data' => $this->model->getCalculoPropuesta($fecha), 'ya_generado' => false];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    public function generar(): array {
        $fecha = date('Y-m-d');
        $usuarioId = $_SESSION['auth_id'] ?? 1;
        try {
            $propuesta = $this->model->getCalculoPropuesta($fecha);
            foreach ($propuesta as &$p) {
                $p['fecha'] = $fecha;
                $p['tipo_limpieza'] = $p['tipo'];
                $p['hab_id'] = $p['habitacion_id'];
                $p['hab'] = $p['habitacion'];
                $p['uid'] = $usuarioId;
            }
            $this->model->guardarMasivo($propuesta);
            return ['ok' => true, 'msg' => 'Lista de limpieza generada correctamente.'];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    public function actualizar(): array {
        $id = $_POST['id'] ?? 0;
        $estado = $_POST['estado'] ?? null;
        $responsable = $_POST['responsable'] ?? null;
        
        $data = [];
        if ($estado) {
            $data['estado'] = $estado;
            if ($estado === 'en_proceso') $data['hora_inicio'] = date('H:i:s');
            if ($estado === 'lista') $data['hora_fin'] = date('H:i:s');
        }
        if ($responsable !== null) $data['responsable'] = $responsable;

        if (empty($data)) return ['ok' => false, 'msg' => 'No hay datos para actualizar.'];

        try {
            $this->model->actualizar($id, $data);
            return ['ok' => true, 'msg' => 'Registro actualizado.', 'data' => $data];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    public function agregarObservacion(): array {
        $id = $_POST['id'] ?? 0;
        $obs = $_POST['observacion'] ?? '';
        try {
            $this->model->actualizar($id, ['observacion' => $obs]);
            return ['ok' => true, 'msg' => 'Observación guardada.'];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    public function listarHistorial(): array {
        $mes = $_GET['mes'] ?? date('m');
        $anio = $_GET['anio'] ?? date('Y');
        try {
            return ['ok' => true, 'data' => $this->model->listarHistorial((int)$mes, (int)$anio)];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    public function getDetalleDia(): array {
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        try {
            return ['ok' => true, 'data' => $this->model->getDetalleDia($fecha)];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }
}
