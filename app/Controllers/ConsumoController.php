<?php
/**
 * app/Controllers/ConsumoController.php
 */
class ConsumoController {
    private ConsumoModel $model;
    private InventarioModel $invModel;
    private RoomingModel $roomModel;

    public function __construct(PDO $pdo) {
        require_once __DIR__ . '/../Models/ConsumoModel.php';
        require_once __DIR__ . '/../Models/InventarioModel.php';
        require_once __DIR__ . '/../Models/RoomingModel.php';
        
        $this->model = new ConsumoModel($pdo);
        $this->invModel = new InventarioModel($pdo);
        $this->roomModel = new RoomingModel($pdo);
    }

    public function registrar(array $input): array {
        $stayId     = (int)($input['stay_id'] ?? 0);
        $productoId = (int)($input['producto_id'] ?? 0);
        $cantidad   = (int)($input['cantidad'] ?? 0);
        $metodo     = $input['metodo_pago'] ?? null; // NULL = Cargo a Habitación
        
        if ($stayId <= 0 || $productoId <= 0 || $cantidad <= 0) {
            return ['ok' => false, 'msg' => 'Datos de consumo incompletos o inválidos.'];
        }

        $producto = $this->invModel->getPorId($productoId);
        if (!$producto) return ['ok' => false, 'msg' => 'Producto no encontrado en inventario.'];
        if ($producto['stock_actual'] < $cantidad) {
            return ['ok' => false, 'msg' => "Stock insuficiente. Solo quedan {$producto['stock_actual']} unidades."];
        }

        $total = $producto['precio_venta'] * $cantidad;

        try {
            // 1. Registrar consumo
            $this->model->registrar([
                'stay_id'         => $stayId,
                'producto_id'     => $productoId,
                'nombre_producto' => $producto['nombre'],
                'cantidad'        => $cantidad,
                'precio_unitario' => $producto['precio_venta'],
                'total'           => $total,
                'metodo_pago'     => $metodo,
                'pagado'          => ($metodo !== null) ? 1 : 0,
                'usuario_id'      => $_SESSION['auth_id'] ?? 1
            ]);

            // 2. Descontar Stock
            $this->invModel->descontarStock($productoId, $cantidad);

            // 3. Sincronización Financiera
            if ($metodo !== null) {
                // Pago Inmediato: Registrar anticipo + Flujo
                $this->roomModel->registrarPago([
                    'stay_id'   => $stayId,
                    'monto'     => $total,
                    'moneda'    => 'PEN',
                    'monto_pen' => $total,
                    'tc'        => 1.0,
                    'tipo'      => $metodo,
                    'recibo'    => 'CONSUMO-' . date('His'),
                    'fecha'     => date('Y-m-d'),
                    'uid'       => $_SESSION['auth_id'] ?? 1
                ]);
                // El total_pago del stay NO aumenta porque se pagó en el acto? 
                // En realidad, para el Sr. Mendoza "OTROS INGRESOS" son ventas de bebidas pagadas.
                // Si lo cargamos a la habitación, el "total_pago" de la estadía aumenta.
            } else {
                // Cargo a Habitación: Aumentar deuda del stay
                $this->roomModel->incrementarTotal($stayId, $total);
            }

            return ['ok' => true, 'msg' => 'Consumo registrado exitosamente.'];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => 'Error: ' . $e->getMessage()];
        }
    }

    public function listarPorStay(int $stayId): array {
        return ['ok' => true, 'data' => $this->model->listarPorStay($stayId)];
    }
}
