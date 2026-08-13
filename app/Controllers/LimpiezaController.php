<?php
/**
 * app/Controllers/LimpiezaController.php
 */
require_once __DIR__ . '/../Models/LimpiezaModel.php';

class LimpiezaController {
    private LimpiezaModel $model;
    private AuditoriaModel $audit;
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        require_once __DIR__ . '/../Models/AuditoriaModel.php';
        $this->model = new LimpiezaModel($pdo);
        $this->audit = new AuditoriaModel($pdo);
        $this->pdo   = $pdo;
    }

    public function getHoy(): array {
        $fecha = date('Y-m-d');
        try {
            $detalle = $this->model->getDetalleDia($fecha);
            if (!empty($detalle)) {
                return ['ok' => true, 'data' => $detalle, 'ya_generado' => true];
            }
            
            // AUTOMATICO: Si no hay nada, generamos de una vez (Pilar 9)
            $this->generar();
            $detalle = $this->model->getDetalleDia($fecha);
            
            return ['ok' => true, 'data' => $detalle, 'ya_generado' => true];
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
                $p['usuario_id'] = $usuarioId;
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
        $usuario_id = $_POST['usuario_id'] ?? null;
        $observacion = $_POST['observacion'] ?? null;
        
        $data = [];
        if ($estado) {
            $data['estado'] = $estado;
            if ($estado === 'en proceso') $data['hora_inicio'] = date('Y-m-d H:i:s');
            if ($estado === 'lista') $data['hora_fin'] = date('Y-m-d H:i:s');
        }
        if ($usuario_id !== null) $data['usuario_id'] = $usuario_id;
        if ($observacion !== null) $data['observacion'] = $observacion;

        if (empty($data)) return ['ok' => false, 'msg' => 'No hay datos para actualizar.'];

        try {
            $this->model->actualizar($id, $data);
            
            // Fetch room for audit
            $stmtHab = $this->pdo->prepare("SELECT h.numero FROM limpieza_registros lr JOIN habitaciones h ON lr.habitacion_id = h.id WHERE lr.id = ?");
            $stmtHab->execute([$id]);
            $nroHab = $stmtHab->fetchColumn() ?: 'S/N';

            $msgAudit = "Actualizó tarea de limpieza de Habitación #$nroHab";
            if ($estado === 'en proceso') $msgAudit = "Inició limpieza de Habitación #$nroHab";
            if ($estado === 'lista') $msgAudit = "Marcó como LIMPIA la Habitación #$nroHab";

            $this->audit->registrar($_SESSION['auth_id'], 'LIMPIEZA_ESTADO', 'LIMPIEZA', $msgAudit);
            
            // Si la limpieza terminó, liberar la habitación
            if ($estado === 'lista') {
                $stmt = $this->pdo->prepare("SELECT habitacion_id FROM limpieza_registros WHERE id = ?");
                $stmt->execute([$id]);
                $hab_id = $stmt->fetchColumn();
                if ($hab_id) {
                    $this->pdo->prepare("UPDATE habitaciones SET estado = 'libre' WHERE id = ? AND estado != 'ocupado'")->execute([$hab_id]);
                }
            }
            
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
            $data = $this->model->getDetalleDia($fecha);
<?php
/**
 * app/Controllers/LimpiezaController.php
 */
require_once __DIR__ . '/../Models/LimpiezaModel.php';

class LimpiezaController {
    private LimpiezaModel $model;
    private AuditoriaModel $audit;
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        require_once __DIR__ . '/../Models/AuditoriaModel.php';
        $this->model = new LimpiezaModel($pdo);
        $this->audit = new AuditoriaModel($pdo);
        $this->pdo   = $pdo;
    }

    public function getHoy(): array {
        $fecha = date('Y-m-d');
        try {
            $detalle = $this->model->getDetalleDia($fecha);
            if (!empty($detalle)) {
                return ['ok' => true, 'data' => $detalle, 'ya_generado' => true];
            }
            
            // AUTOMATICO: Si no hay nada, generamos de una vez (Pilar 9)
            $this->generar();
            $detalle = $this->model->getDetalleDia($fecha);
            
            return ['ok' => true, 'data' => $detalle, 'ya_generado' => true];
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
                $p['usuario_id'] = $usuarioId;
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
        $usuario_id = $_POST['usuario_id'] ?? null;
        $observacion = $_POST['observacion'] ?? null;
        
        $data = [];
        if ($estado) {
            $data['estado'] = $estado;
            if ($estado === 'en proceso') $data['hora_inicio'] = date('Y-m-d H:i:s');
            if ($estado === 'lista') $data['hora_fin'] = date('Y-m-d H:i:s');
        }
        if ($usuario_id !== null) $data['usuario_id'] = $usuario_id;
        if ($observacion !== null) $data['observacion'] = $observacion;

        if (empty($data)) return ['ok' => false, 'msg' => 'No hay datos para actualizar.'];

        try {
            $this->model->actualizar($id, $data);
            
            // Fetch room for audit
            $stmtHab = $this->pdo->prepare("SELECT h.numero FROM limpieza_registros lr JOIN habitaciones h ON lr.habitacion_id = h.id WHERE lr.id = ?");
            $stmtHab->execute([$id]);
            $nroHab = $stmtHab->fetchColumn() ?: 'S/N';

            $msgAudit = "Actualizó tarea de limpieza de Habitación #$nroHab";
            if ($estado === 'en proceso') $msgAudit = "Inició limpieza de Habitación #$nroHab";
            if ($estado === 'lista') $msgAudit = "Marcó como LIMPIA la Habitación #$nroHab";

            $this->audit->registrar($_SESSION['auth_id'], 'LIMPIEZA_ESTADO', 'LIMPIEZA', $msgAudit);
            
            // Si la limpieza terminó, liberar la habitación
            if ($estado === 'lista') {
                $stmt = $this->pdo->prepare("SELECT habitacion_id FROM limpieza_registros WHERE id = ?");
                $stmt->execute([$id]);
                $hab_id = $stmt->fetchColumn();
                if ($hab_id) {
                    $this->pdo->prepare("UPDATE habitaciones SET estado = 'libre' WHERE id = ? AND estado != 'ocupado'")->execute([$hab_id]);
                }
            }
            
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
            $data = $this->model->getDetalleDia($fecha);
            return ['ok' => true, 'data' => $data];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    public function guardarCambiosManuales(): array {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $registros = $data['registros'] ?? [];
        $fecha = $data['fecha'] ?? date('Y-m-d');

        if (empty($registros)) {
            return ['ok' => false, 'msg' => 'No hay registros para guardar.'];
        }

        try {
            $this->model->guardarCambiosManuales($registros, $fecha);
            return ['ok' => true, 'msg' => 'Cambios guardados correctamente.'];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }
}
