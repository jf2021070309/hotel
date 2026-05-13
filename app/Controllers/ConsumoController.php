<?php
/**
 * app/Controllers/ConsumoController.php
 */
class ConsumoController {
    private ConsumoModel $model;
    private InventarioModel $invModel;
    private RoomingModel $roomModel;
    private AuditoriaModel $audit;

    public function __construct(PDO $pdo) {
        require_once __DIR__ . '/../Models/ConsumoModel.php';
        require_once __DIR__ . '/../Models/InventarioModel.php';
        require_once __DIR__ . '/../Models/RoomingModel.php';
        require_once __DIR__ . '/../Models/AuditoriaModel.php';
        
        $this->model = new ConsumoModel($pdo);
        $this->invModel = new InventarioModel($pdo);
        $this->roomModel = new RoomingModel($pdo);
        $this->audit = new AuditoriaModel($pdo);
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

            // 3. Sincronización Financiera y Aumento de Deuda
            // Siempre aumentamos el total_pago del stay para que el balance sea correcto
            $this->roomModel->incrementarTotal($stayId, $total);

            if ($metodo !== null) {
                // Pago Inmediato: Registrar anticipo + Flujo
                $this->roomModel->registrarPago([
                    'stay_id'   => $stayId,
                    'monto'     => $total,
                    'moneda'    => 'PEN',
                    'monto_pen' => $total,
                    'tc'        => 1.0,
                    'tipo'      => $metodo,
                    'recargo_pos' => $input['recargo_pos'] ?? false,
                    'recibo'    => 'CONSUMO-' . date('His'),
                    'fecha'     => date('Y-m-d'),
                    'uid'       => $_SESSION['auth_id'] ?? 1
                ]);
            }

            // --- REGISTRO DE AUDITORÍA ESTRUCTURADA ---
            $metodoDesc = ($metodo === null) ? 'CARGADO A HAB.' : "PAGO ($metodo)";
            $detalleJson = json_encode([
                'mensaje' => "Registró consumo de productos",
                'cambios' => [
                    'Producto' => ['antes' => '-', 'despues' => $producto['nombre']],
                    'Cantidad' => ['antes' => '0', 'despues' => $cantidad],
                    'Total'    => ['antes' => 'S/ 0.00', 'despues' => 'S/ ' . number_format($total, 2)],
                    'Método'   => ['antes' => '-', 'despues' => $metodoDesc]
                ]
            ], JSON_UNESCAPED_UNICODE);

            $this->audit->registrar(
                $_SESSION['auth_id'], 
                $_SESSION['auth_nombre'], 
                'REGISTRAR_CONSUMO', 
                'ROOMING', 
                $detalleJson
            );

            return ['ok' => true, 'msg' => 'Consumo registrado exitosamente.'];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => 'Error: ' . $e->getMessage()];
        }
    }

    public function listarPorStay(int $stayId): array {
        return ['ok' => true, 'data' => $this->model->listarPorStay($stayId)];
    }
}
