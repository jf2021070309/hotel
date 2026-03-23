<?php
/**
 * app/Models/UsuarioModel.php
 */
class UsuarioModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAll(): array {
        $stmt = $this->pdo->query("SELECT id, usuario, rol, nombre, estado, created_at FROM usuarios ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getByUsuario(string $usuario): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? AND estado = 1");
        $stmt->execute([$usuario]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int {
        $sql = "INSERT INTO usuarios (usuario, password, rol, nombre, estado) 
                VALUES (:usuario, :password, :rol, :nombre, :estado)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'usuario'  => $data['usuario'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'rol'      => $data['rol'] ?? 'cajera',
            'nombre'   => $data['nombre'],
            'estado'   => $data['estado'] ?? 1
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $sql = "UPDATE usuarios SET usuario = :usuario, rol = :rol, nombre = :nombre, estado = :estado WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id'      => $id,
            'usuario' => $data['usuario'],
            'rol'     => $data['rol'],
            'nombre'  => $data['nombre'],
            'estado'  => $data['estado']
        ]);
    }

    public function updatePassword(int $id, string $newPassword): bool {
        $stmt = $this->pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        return $stmt->execute([password_hash($newPassword, PASSWORD_BCRYPT), $id]);
    }
}
