<?php
/**
 * app/Controllers/InventarioController.php
 */
class InventarioController {
    private InventarioModel $model;

    public function __construct(PDO $pdo) {
        require_once __DIR__ . '/../Models/InventarioModel.php';
        $this->model = new InventarioModel($pdo);
    }

    public function listar(): array {
        return ['ok' => true, 'data' => $this->model->listar()];
    }

    public function crear(array $data): array {
        $id = $this->model->crear($data);
        return ['ok' => true, 'msg' => 'Producto creado', 'id' => $id];
    }

    public function actualizar(int $id, array $data): array {
        $this->model->actualizar($id, $data);
        return ['ok' => true, 'msg' => 'Producto actualizado'];
    }

    public function recargar(int $id, int $cant): array {
        $this->model->recargarStock($id, $cant);
        return ['ok' => true, 'msg' => 'Stock actualizado'];
    }

    public function eliminar(int $id): array {
        $this->model->eliminar($id);
        return ['ok' => true, 'msg' => 'Producto eliminado'];
    }

    public function consumoInterno(array $data): array {
        $id   = (int)($data['producto_id'] ?? 0);
        $cant = (int)($data['cantidad'] ?? 0);
        $ref  = trim($data['referencia'] ?? 'Consumo interno');
        $uid  = (int)($data['usuario_id'] ?? 1);
        if ($id <= 0 || $cant <= 0) return ['ok' => false, 'msg' => 'Datos inválidos'];
        $ok = $this->model->consumoInterno($id, $cant, $ref, $uid);
        return $ok ? ['ok' => true, 'msg' => 'Consumo interno registrado'] : ['ok' => false, 'msg' => 'Stock insuficiente o producto no encontrado'];
    }

    public function historial(array $filtros): array {
        return ['ok' => true, 'data' => $this->model->getMovimientos($filtros)];
    }

    public function alertas(): array {
        return ['ok' => true, 'data' => $this->model->alertasStockBajo()];
    }
}
