<?php
/**
 * app/Controllers/MedioPagoController.php
 */
class MedioPagoController {
    private PDO $pdo;
    private MedioPagoModel $model;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        require_once __DIR__ . '/../Models/MedioPagoModel.php';
        $this->model = new MedioPagoModel($pdo);
    }

    public function listar() {
        return ['ok' => true, 'data' => $this->model->listar()];
    }

    public function guardar(array $input) {
        $id     = $input['id'] ?? null;
        $nombre = strtoupper(trim($input['nombre'] ?? ''));
        $orden  = (int)($input['orden'] ?? 0);
        $activo = (int)($input['activo'] ?? 1);

        if (empty($nombre)) {
            return ['ok' => false, 'msg' => 'El nombre es obligatorio'];
        }

        if ($id) {
            $res = $this->model->actualizar($id, $nombre, $orden, $activo);
            $msg = $res ? "Medio de pago actualizado" : "Error al actualizar";
        } else {
            $res = $this->model->crear($nombre, $orden);
            $msg = $res ? "Medio de pago creado" : "Error al crear";
        }

        return ['ok' => $res, 'msg' => $msg];
    }

    public function toggle(int $id) {
        if ($this->model->toggleEstado($id)) {
            return ['ok' => true, 'msg' => "Estado cambiado"];
        }
        return ['ok' => false, 'msg' => "Error al cambiar estado"];
    }

    public function eliminar(int $id) {
        try {
            if ($this->model->eliminar($id)) {
                return ['ok' => true, 'msg' => "Medio de pago eliminado"];
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                return ['ok' => false, 'msg' => "No se puede eliminar porque ya tiene movimientos asociados. Desactívelo en su lugar."];
            }
        }
        return ['ok' => false, 'msg' => "Error al eliminar"];
    }
}
