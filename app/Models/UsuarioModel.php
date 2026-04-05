<?php
/**
 * Modelo de Usuarios.
 *
 * Gestiona las operaciones de base de datos (CRUD) relacionadas 
 * con los usuarios del sistema.
 *
 * @package App\Models
 */
class UsuarioModel {
    /**
     * @var PDO Conexión activa a la base de datos
     */
    private PDO $pdo;

    /**
     * Constructor del modelo.
     *
     * @param PDO $pdo Instancia de PDO para conexión a BD
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene todos los usuarios ordenados del más reciente al más antiguo.
     *
     * @return array Lista asociativa de todos los usuarios
     */
    public function getAll(): array {
        $stmt = $this->pdo->query("SELECT id, usuario, rol, nombre, estado, created_at FROM usuarios ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    /**
     * Busca un usuario por su identificador único.
     *
     * @param int $id Identificador del usuario
     * @return array|null Arreglo con los datos del usuario o null si no existe
     */
    public function getById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Busca un usuario activo por su nombre de usuario.
     *
     * @param string $usuario Nombre de acceso (username)
     * @return array|null Arreglo con los datos del usuario o null si no se encuentra/está inactivo
     */
    public function getByUsuario(string $usuario): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? AND estado = 1");
        $stmt->execute([$usuario]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Inserta un nuevo usuario en la base de datos.
     * Hashea la contraseña usando BCRYPT antes de guardarla.
     *
     * @param array $data Arreglo con los datos (usuario, password, rol, nombre, estado opcional)
     * @return int ID del usuario recién insertado
     */
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

    /**
     * Modifica los datos principales de un usuario (rol, nombre, estado, nombre de usuario).
     *
     * @param int $id Identificador del usuario a editar
     * @param array $data Nuevos datos a guardar
     * @return bool True si la ejecución fue exitosa, False en caso contrario
     */
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

    /**
     * Actualiza únicamente la contraseña de un usuario.
     * Aplica hashing BCRYPT antes del Update.
     *
     * @param int $id Identificador del usuario
     * @param string $newPassword Nueva contraseña en texto plano
     * @return bool True si la ejecución fue exitosa, False en caso contrario
     */
    public function updatePassword(int $id, string $newPassword): bool {
        $stmt = $this->pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        return $stmt->execute([password_hash($newPassword, PASSWORD_BCRYPT), $id]);
    }
}
