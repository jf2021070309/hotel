<?php
/**
 * app/Models/ConsumoModel.php
 */
class ConsumoModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function registrar(array $data): int {
        $sql = "INSERT INTO rooming_consumos 
                (stay_id, producto_id, nombre_producto, cantidad, precio_unitario, total, metodo_pago, pagado, usuario_id) 
                VALUES (:stay_id, :producto_id, :nombre_producto, :cantidad, :precio_unitario, :total, :metodo_pago, :pagado, :usuario_id)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return (int)$this->pdo->lastInsertId();
    }

    public function listarPorStay(int $stayId): array {
        $sql = "SELECT * FROM rooming_consumos WHERE stay_id = ? ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$stayId]);
        return $stmt->fetchAll();
    }

    public function getConsumo(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM rooming_consumos WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}
