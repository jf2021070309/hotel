<?php
/**
 * app/Controllers/UsuarioController.php
 */
class UsuarioController {
    private PDO $pdo;
    private UsuarioModel $model;
    private AuditoriaModel $audit;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        require_once __DIR__ . '/../Models/UsuarioModel.php';
        require_once __DIR__ . '/../Models/AuditoriaModel.php';
        $this->model = new UsuarioModel($pdo);
        $this->audit = new AuditoriaModel($pdo);
    }

    /**
     * Listar todos los usuarios
     */
    public function index() {
        return $this->model->getAll();
    }

    /**
     * Crear un nuevo usuario
     */
    public function create(array $data) {
        if (empty($data['usuario']) || empty($data['nombre']) || empty($data['password'])) {
            return ['ok' => false, 'msg' => "Todos los campos son obligatorios", 'code' => 400];
        }

        // Verificar si existe el usuario
        if ($this->model->getByUsuario($data['usuario'])) {
            return ['ok' => false, 'msg' => "El nombre de usuario ya está en uso", 'code' => 409];
        }

        $id = $this->model->create($data);
        if ($id) {
            $currentUser = obtenerUsuarioActual();
            $this->audit->registrar($currentUser['id'], $currentUser['nombre'], 'USUARIO_CREADO', 'USUARIOS', "Creado usuario con ID: " . $id);
            return ['ok' => true, 'msg' => "Usuario creado correctamente", 'id' => $id];
        }

        return ['ok' => false, 'msg' => "Error al crear usuario", 'code' => 500];
    }

    /**
     * Actualizar un usuario existente
     */
    public function update(int $id, array $data) {
        if (!$id) return ['ok' => false, 'msg' => "ID inválido", 'code' => 400];

        // Regla: No se puede cambiar el rol del admin id=1
        if ($id === 1 && $data['rol'] !== 'admin') {
            return ['ok' => false, 'msg' => "No se puede cambiar el rol del administrador principal", 'code' => 403];
        }

        // Regla: No se puede desactivar el propio usuario logueado
        $currentUser = obtenerUsuarioActual();
        if ($id === $currentUser['id'] && $data['estado'] == 0) {
            return ['ok' => false, 'msg' => "No puedes desactivar tu propio usuario", 'code' => 403];
        }

        // Verificar si el nombre de usuario ya existe en otro registro
        if (!empty($data['usuario'])) {
            $stmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? AND id != ?");
            $stmt->execute([$data['usuario'], $id]);
            if ($stmt->fetch()) {
                return ['ok' => false, 'msg' => "El nombre de usuario '{$data['usuario']}' ya está en uso", 'code' => 409];
            }
        }

        if ($this->model->update($id, $data)) {
            // Sincronizar sesión si se edita a sí mismo
            if ($id === $currentUser['id']) {
                if (session_status() === PHP_SESSION_NONE) session_start();
                $_SESSION['auth_nombre']  = $data['nombre'] ?? $_SESSION['auth_nombre'];
                $_SESSION['auth_usuario'] = $data['usuario'] ?? $_SESSION['auth_usuario'];
                $_SESSION['auth_rol']     = $data['rol'] ?? $_SESSION['auth_rol'];
            }

            $this->audit->registrar($currentUser['id'], $currentUser['nombre'], 'USUARIO_EDITADO', 'USUARIOS', "Editado usuario con ID: " . $id);
            return ['ok' => true, 'msg' => "Usuario actualizado correctamente"];
        }

        return ['ok' => false, 'msg' => "Error al actualizar usuario", 'code' => 500];
    }

    /**
     * Cambiar contraseña
     */
    public function updatePassword(int $id, string $password) {
        if (!$id || empty($password)) return ['ok' => false, 'msg' => "Datos inválidos", 'code' => 400];

        if ($this->model->updatePassword($id, $password)) {
            $currentUser = obtenerUsuarioActual();
            $this->audit->registrar($currentUser['id'], $currentUser['nombre'], 'PASS_CAMBIADA', 'USUARIOS', "Cambiada pass de usuario ID: " . $id);
            return ['ok' => true, 'msg' => "Contraseña actualizada"];
        }

        return ['ok' => false, 'msg' => "Error al actualizar contraseña", 'code' => 500];
    }
}
