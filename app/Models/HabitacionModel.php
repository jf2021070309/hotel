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
        $res = $stmt->execute([$estado, $id]);
        if ($res && $estado === 'libre') {
            $this->sincronizarLimpieza($id);
        }
        return $res;
    }

    public function crear(array $data): bool {
        $sql = "INSERT INTO habitaciones (numero, tipo, piso, precio_base, estado, activa) VALUES (?, ?, ?, ?, ?, 1)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['numero'], $data['tipo'], $data['piso'], $data['precio_base'], $data['estado'] ?? 'libre'
        ]);
    }

    public function actualizar(int $id, array $data): bool {
        $sql = "UPDATE habitaciones SET numero = ?, tipo = ?, piso = ?, precio_base = ?, estado = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $res = $stmt->execute([
            $data['numero'], $data['tipo'], $data['piso'], $data['precio_base'], $data['estado'], $id
        ]);
        if ($res && $data['estado'] === 'libre') {
            $this->sincronizarLimpieza($id);
        }
        return $res;
    }

    /**
     * Si la habitación se pone LIBRE manualmente, sincroniza el log de limpieza para que no bloquee el check-in.
     */
    private function sincronizarLimpieza(int $habId): void {
        $fecha = date('Y-m-d');
        $stmt = $this->pdo->prepare("UPDATE limpieza_registros SET estado = 'lista' WHERE habitacion_id = ? AND fecha = ? AND estado != 'lista'");
        $stmt->execute([$habId, $fecha]);
    }
}
