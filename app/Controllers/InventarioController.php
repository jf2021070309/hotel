<?php
/**
 * app/Controllers/InventarioController.php
 */
class InventarioController {
    private InventarioModel $model;
    private AuditoriaModel $audit;

    public function __construct(PDO $pdo) {
        require_once __DIR__ . '/../Models/InventarioModel.php';
        require_once __DIR__ . '/../Models/AuditoriaModel.php';
        $this->model = new InventarioModel($pdo);
        $this->audit = new AuditoriaModel($pdo);
    }

    public function listar(): array {
        return ['ok' => true, 'data' => $this->model->listar()];
    }

    public function crear(array $data): array {
        $id = $this->model->crear($data);
        $detalle = json_encode([
            'mensaje' => "Registró nuevo producto: {$data['nombre']}",
            'cambios' => [
                'Nombre'    => ['antes' => '-', 'despues' => $data['nombre']],
                'Categoría' => ['antes' => '-', 'despues' => $data['categoria'] ?? 'General'],
                'Precio'    => ['antes' => 'S/ 0.00', 'despues' => 'S/ ' . number_format($data['precio_venta'] ?? 0, 2)],
                'Stock'     => ['antes' => '0', 'despues' => $data['stock_actual'] ?? 0]
            ]
        ], JSON_UNESCAPED_UNICODE);
        
        $this->audit->registrar($_SESSION['auth_id'], 'CREAR_PRODUCTO', 'INVENTARIO', $detalle);
        return ['ok' => true, 'msg' => 'Producto creado', 'id' => $id];
    }

    public function actualizar(int $id, array $data): array {
        $original = $this->model->getPorId($id);
        $this->model->actualizar($id, $data);

        // Comparar cambios
        $cambios = [];
        $labels = [
            'nombre'       => 'Nombre',
            'categoria'    => 'Categoría',
            'precio_venta' => 'Precio',
            'refrigeradora'=> 'Refri',
            'stock_actual' => 'Stock'
        ];

        foreach (['nombre', 'categoria', 'precio_venta', 'refrigeradora', 'stock_actual'] as $key) {
            if (isset($data[$key]) && (string)$original[$key] !== (string)$data[$key]) {
                $cambios[$labels[$key]] = ['antes' => $original[$key], 'despues' => $data[$key]];
            }
        }

        $detalle = json_encode([
            'mensaje' => "Actualizó producto: {$original['nombre']} (" . count($cambios) . " campos modificados)",
            'cambios' => !empty($cambios) ? $cambios : null
        ], JSON_UNESCAPED_UNICODE);

        $this->audit->registrar($_SESSION['auth_id'], 'ACTUALIZAR_PRODUCTO', 'INVENTARIO', $detalle);

        return ['ok' => true, 'msg' => 'Producto actualizado'];
    }

    public function recargar(int $id, int $cant): array {
        $prod = $this->model->getPorId($id);
        $oldStock = (int)$prod['stock_actual'];
        $newStock = $oldStock + $cant;
        $this->model->recargarStock($id, $cant);
        
        $detalle = json_encode([
            'mensaje' => "Recargó stock de: {$prod['nombre']}",
            'cambios' => [
                'Stock' => ['antes' => $oldStock, 'despues' => $newStock]
            ]
        ], JSON_UNESCAPED_UNICODE);

        $this->audit->registrar($_SESSION['auth_id'], 'RECARGA_STOCK', 'INVENTARIO', $detalle);
        return ['ok' => true, 'msg' => 'Stock actualizado'];
    }

    public function eliminar(int $id): array {
        $prod = $this->model->getPorId($id);
        $this->model->eliminar($id);
        $this->audit->registrar($_SESSION['auth_id'], 'ELIMINAR_PRODUCTO', 'INVENTARIO', "Eliminó definitivamente el producto: {$prod['nombre']}");
        return ['ok' => true, 'msg' => 'Producto eliminado'];
    }

    public function consumoInterno(array $data): array {
        $id   = (int)($data['producto_id'] ?? 0);
        $cant = (int)($data['cantidad'] ?? 0);
        $uid  = (int)($data['usuario_id'] ?? 1);
        if ($id <= 0 || $cant <= 0) return ['ok' => false, 'msg' => 'Datos inválidos'];
        $ok = $this->model->consumoInterno($id, $cant, $uid);
        if ($ok) {
            $prod = $this->model->getPorId($id);
            $this->audit->registrar($_SESSION['auth_id'], 'BAJA_PRODUCTO', 'INVENTARIO', "Baja por consumo/merma de $cant unidades a: {$prod['nombre']}");
            return ['ok' => true, 'msg' => 'Consumo interno registrado'];
        }
        return ['ok' => false, 'msg' => 'Stock insuficiente o producto no encontrado'];
    }

    public function historial(array $filtros): array {
        return ['ok' => true, 'data' => $this->model->getMovimientos($filtros)];
    }

    public function alertas(): array {
        return ['ok' => true, 'data' => $this->model->alertasStockBajo()];
    }
}
