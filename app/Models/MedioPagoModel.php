<?php
/**
 * app/Models/MedioPagoModel.php
 */
class MedioPagoModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Listar todos los medios de pago (Flujo + Ingreso)
     */
    public function listar(): array {
        $sql = "SELECT id, nombre, orden, activo 
                FROM finanzas_categorias 
                WHERE modulo = 'Flujo' AND tipo = 'Ingreso' 
                ORDER BY orden ASC, nombre ASC";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function getById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM finanzas_categorias WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function crear(string $nombre, int $orden = 0): bool {
        $sql = "INSERT INTO finanzas_categorias (modulo, tipo, nombre, orden, activo) 
                VALUES ('Flujo', 'Ingreso', :nombre, :orden, 1)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['nombre' => $nombre, 'orden' => $orden]);
    }

    public function actualizar(int $id, string $nombre, int $orden, int $activo): bool {
        $sql = "UPDATE finanzas_categorias 
                SET nombre = :nombre, orden = :orden, activo = :activo 
                WHERE id = :id AND modulo = 'Flujo' AND tipo = 'Ingreso'";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id'     => $id,
            'nombre' => $nombre,
            'orden'  => $orden,
            'activo' => $activo
        ]);
    }

    public function eliminar(int $id): bool {
        // Ojo: Podría haber FK en flujo_caja_movimientos. 
        // Se recomienda desactivar en lugar de eliminar si ya tiene usos.
        $sql = "DELETE FROM finanzas_categorias WHERE id = ? AND modulo = 'Flujo' AND tipo = 'Ingreso'";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    public function toggleEstado(int $id): bool {
        $sql = "UPDATE finanzas_categorias SET activo = 1 - activo WHERE id = ? AND modulo = 'Flujo' AND tipo = 'Ingreso'";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }
}
