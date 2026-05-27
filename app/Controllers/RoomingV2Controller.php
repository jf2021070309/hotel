<?php
/**
 * app/Controllers/RoomingV2Controller.php
 * Controlador para la grilla Rooming V2.
 */
class RoomingV2Controller {
    private PDO $pdo;
    private RoomingV2Model $model;
    private AuditoriaModel $audit;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        require_once __DIR__ . '/../Models/RoomingV2Model.php';
        require_once __DIR__ . '/../Models/AuditoriaModel.php';
        $this->model = new RoomingV2Model($pdo);
        $this->audit = new AuditoriaModel($pdo);
    }

    /**
     * Lista los registros del mes.
     */
    public function listar(int $mes, int $anio): array {
        return $this->model->getReporte($mes, $anio);
    }

    /**
     * Guarda masivamente (Nuevos y Editados).
     */
    public function guardar(array $rows): array {
        $res = $this->model->guardarReporte($rows);
        if ($res['ok']) {
            $user_id = $_SESSION['auth_id'] ?? 1;
            $this->audit->registrar($user_id, 'GUARDAR_ROOMING_V2', 'ROOMING', "Guardó/actualizó registros en la grilla plana Rooming V2");
        }
        return $res;
    }

    /**
     * Elimina un registro de Rooming V2.
     */
    public function eliminar(int $id): array {
        $res = $this->model->eliminarRegistro($id);
        if ($res) {
            $user_id = $_SESSION['auth_id'] ?? 1;
            $this->audit->registrar($user_id, 'ELIMINAR_ROOMING_V2', 'ROOMING', "Eliminó el registro #$id en Rooming V2");
            return ['ok' => true, 'msg' => 'Registro eliminado exitosamente.'];
        }
        return ['ok' => false, 'msg' => 'No se pudo eliminar el registro.'];
    }
}
