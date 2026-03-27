<?php
/**
 * app/Models/InventarioModel.php
 */
class InventarioModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function listar(array $filtros = []): array {
        $sql = "SELECT * FROM inventario_productos WHERE activo = 1";
        if (isset($filtros['categoria'])) $sql .= " AND categoria = " . $this->pdo->quote($filtros['categoria']);
        $sql .= " ORDER BY categoria, nombre";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function getPorId(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM inventario_productos WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Registra un movimiento en la bitácora de inventario.
     */
    private function registrarMovimiento(int $productoId, string $tipo, int $cantidad, int $stockAntes, int $stockDespues, string $referencia = '', int $usuarioId = 1): void {
        // Silenciar si la tabla no existe aún
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO inventario_movimientos (producto_id, tipo, cantidad, stock_antes, stock_despues, referencia, usuario_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$productoId, $tipo, $cantidad, $stockAntes, $stockDespues, $referencia, $usuarioId]);
        } catch (Exception $e) { /* Tabla no existe aún */ }
    }

    public function descontarStock(int $id, int $cantidad, string $referencia = 'Venta', int $uid = 1): bool {
        $prod = $this->getPorId($id);
        $stockAntes = $prod ? (int)$prod['stock_actual'] : 0;
        $stmt = $this->pdo->prepare("UPDATE inventario_productos SET stock_actual = stock_actual - ? WHERE id = ? AND stock_actual >= ?");
        $res = $stmt->execute([$cantidad, $id, $cantidad]);
        if ($res) {
            $this->registrarMovimiento($id, 'VENTA', $cantidad, $stockAntes, $stockAntes - $cantidad, $referencia, $uid);
        }
        return $res;
    }

    public function recargarStock(int $id, int $cantidad, string $referencia = 'Recarga', int $uid = 1): bool {
        $prod = $this->getPorId($id);
        $stockAntes = $prod ? (int)$prod['stock_actual'] : 0;
        $stmt = $this->pdo->prepare("UPDATE inventario_productos SET stock_actual = stock_actual + ? WHERE id = ?");
        $res = $stmt->execute([$cantidad, $id]);
        if ($res) {
            $this->registrarMovimiento($id, 'RECARGA', $cantidad, $stockAntes, $stockAntes + $cantidad, $referencia, $uid);
        }
        return $res;
    }

    public function consumoInterno(int $id, int $cantidad, string $referencia = 'Consumo interno', int $uid = 1): bool {
        $prod = $this->getPorId($id);
        if (!$prod || $prod['stock_actual'] < $cantidad) return false;
        $stockAntes = (int)$prod['stock_actual'];
        $stmt = $this->pdo->prepare("UPDATE inventario_productos SET stock_actual = stock_actual - ? WHERE id = ? AND stock_actual >= ?");
        $res = $stmt->execute([$cantidad, $id, $cantidad]);
        if ($res) {
            $this->registrarMovimiento($id, 'CONSUMO_INTERNO', $cantidad, $stockAntes, $stockAntes - $cantidad, $referencia, $uid);
        }
        return $res;
    }

    public function crear(array $data): int {
        $sql = "INSERT INTO inventario_productos (nombre, categoria, precio_venta, stock_actual, refrigeradora, activo) 
                VALUES (:nombre, :categoria, :precio_venta, :stock_actual, :refrigeradora, 1)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        $newId = (int)$this->pdo->lastInsertId();
        $this->registrarMovimiento($newId, 'RECARGA', (int)$data['stock_actual'], 0, (int)$data['stock_actual'], 'Stock inicial', $data['usuario_id'] ?? 1);
        return $newId;
    }

    public function actualizar(int $id, array $data): bool {
        $sql = "UPDATE inventario_productos SET 
                nombre = :nombre, 
                categoria = :categoria, 
                precio_venta = :precio_venta, 
                refrigeradora = :refrigeradora 
                WHERE id = :id";
        $data['id'] = $id;
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    public function eliminar(int $id): bool {
        $stmt = $this->pdo->prepare("UPDATE inventario_productos SET activo = 0 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function alertasStockBajo(int $limit = 3): array {
        $stmt = $this->pdo->prepare("SELECT * FROM inventario_productos WHERE stock_actual <= ? AND activo = 1");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function getMovimientos(array $filtros = []): array {
        $sql = "SELECT m.*, p.nombre as nombre_producto, p.categoria
                FROM inventario_movimientos m
                JOIN inventario_productos p ON p.id = m.producto_id
                WHERE 1=1";
        $params = [];
        if (!empty($filtros['producto_id'])) {
            $sql .= " AND m.producto_id = ?";
            $params[] = $filtros['producto_id'];
        }
        if (!empty($filtros['tipo'])) {
            $sql .= " AND m.tipo = ?";
            $params[] = $filtros['tipo'];
        }
        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND DATE(m.created_at) >= ?";
            $params[] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND DATE(m.created_at) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }
        $sql .= " ORDER BY m.created_at DESC LIMIT 500";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
