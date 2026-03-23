<?php
/**
 * app/Models/HabitacionModel.php
 */
class HabitacionModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAll(): array {
        return $this->pdo->query("SELECT * FROM habitaciones ORDER BY numero ASC")->fetchAll();
    }

    public function getLibres(): array {
        return $this->pdo->query("SELECT * FROM habitaciones WHERE estado = 'libre' AND activa = 1 ORDER BY numero ASC")->fetchAll();
    }

    public function getById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM habitaciones WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function actualizarEstado(int $id, string $estado): bool {
        $stmt = $this->pdo->prepare("UPDATE habitaciones SET estado = ? WHERE id = ?");
        return $stmt->execute([$estado, $id]);
    }
}
