<?php
/**
 * Controlador del Módulo de Usuarios.
 *
 * Contiene la lógica de negocio para la gestión de usuarios, incluyendo
 * creación, actualización, listado y cambios de contraseña.
 * Actúa de intermediario entre las rutas de la API y el modelo de BD.
 *
 * @package App\Controllers
 */
class UsuarioController
{
    /**
     * @var PDO Conexión a la base de datos
     */
    private PDO $pdo;

    /**
     * @var UsuarioModel Instancia del modelo de usuarios
     */
    private UsuarioModel $model;

    /**
     * @var AuditoriaModel Instancia del modelo de auditoría para registro de acciones
     */
    private AuditoriaModel $audit;

    /**
     * Constructor del controlador.
     * Importa e inicializa los modelos requeridos para las operaciones.
     *
     * @param PDO $pdo Instancia activa de conexión a la base de datos
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        require_once __DIR__ . '/../Models/UsuarioModel.php';
        require_once __DIR__ . '/../Models/AuditoriaModel.php';
        $this->model = new UsuarioModel($pdo);
        $this->audit = new AuditoriaModel($pdo);
    }

    /**
     * Obtiene el listado completo de usuarios registrados.
     *
     * @return array Lista de usuarios obtenida desde el modelo
     */
    public function index()
    {
        return $this->model->getAll();
    }

    /**
     * Procesa la creación de un nuevo usuario en el sistema.
     * 
     * Valida la presencia de campos obligatorios, verifica si el
     * nombre de usuario ya está registrado, y si tiene éxito, interacciona
     * con Auditoria para registrar la acción.
     *
     * @param array $data Datos proporcionados por la solicitud POST
     * @return array Respuesta estructurada con claves 'ok', 'msg', y opcionalmente 'code', 'id'
     */
    public function create(array $data)
    {
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
            $detalle = json_encode([
                'mensaje' => "Registró un nuevo TRABAJADOR",
                'cambios' => [
                    'Nombre' => ['antes' => '-', 'despues' => $data['nombre']],
                    'Usuario' => ['antes' => '-', 'despues' => $data['usuario']],
                    'Rol' => ['antes' => '-', 'despues' => strtoupper($data['rol'])]
                ]
            ], JSON_UNESCAPED_UNICODE);

            $this->audit->registrar($currentUser['id'], $currentUser['nombre'], 'USUARIO_CREADO', 'USUARIOS', $detalle);
            return ['ok' => true, 'msg' => "Usuario creado correctamente", 'id' => $id];
        }

        return ['ok' => false, 'msg' => "Error al crear usuario", 'code' => 500];
    }

    /**
     * Actualiza la información de un usuario existente.
     * 
     * Aplica reglas de validación y de negocio, como impedir quitar
     * privilegios de administrador al id=1, o que el usuario desactive su propia
     * cuenta. También previene duplicidades del nombre de usuario.
     * Actualiza la sesión actual si el usuario que llama se editó a un sí mismo.
     *
     * @param int $id Identificador del usuario a ser actualizado
     * @param array $data Mapa con los valores actualizados
     * @return array Respuesta estructurada con claves 'ok', 'msg' y 'code' opcional
     */
    public function update(int $id, array $data)
    {
        if (!$id)
            return ['ok' => false, 'msg' => "ID inválido", 'code' => 400];

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

        // --- CAPTURA DE DATOS PARA AUDITORÍA ---
        $original = $this->model->getById($id);

        if ($this->model->update($id, $data)) {
            // Sincronizar sesión si se edita a sí mismo
            if ($id === $currentUser['id']) {
                if (session_status() === PHP_SESSION_NONE)
                    session_start();
                $_SESSION['auth_nombre'] = $data['nombre'] ?? $_SESSION['auth_nombre'];
                $_SESSION['auth_usuario'] = $data['usuario'] ?? $_SESSION['auth_usuario'];
                $_SESSION['auth_rol'] = $data['rol'] ?? $_SESSION['auth_rol'];
            }

            // Construir detalle JSON de cambios
            $cambios = [];
            $labels = [
                'usuario' => 'User',
                'nombre' => 'Nombre',
                'rol' => 'Rol',
                'estado' => 'Estado'
            ];

            foreach ($data as $key => $val) {
                if (isset($original[$key]) && $original[$key] != $val) {
                    $antes = $original[$key];
                    $despues = $val;

                    // Formatear estado para legibilidad
                    if ($key === 'estado') {
                        $antes = ($antes == 1) ? 'Activo' : 'Inactivo';
                        $despues = ($despues == 1) ? 'Activo' : 'Inactivo';
                    }

                    $label = $labels[$key] ?? $key;
                    $cambios[$label] = ['antes' => $antes, 'despues' => $despues];
                }
            }

            $detalle = json_encode([
                'mensaje' => "Actualizó datos del usuario: " . ($original['usuario'] ?? 'N/A'),
                'cambios' => !empty($cambios) ? $cambios : null
            ], JSON_UNESCAPED_UNICODE);

            $this->audit->registrar($currentUser['id'], $currentUser['nombre'], 'ACTUALIZAR_USUARIO', 'USUARIOS', $detalle);
            return ['ok' => true, 'msg' => "Usuario actualizado correctamente"];
        }

        return ['ok' => false, 'msg' => "Error al actualizar usuario", 'code' => 500];
    }

    /**
     * Modifica la contraseña de un usuario determinado.
     * 
     * Procesa el cambio en el modelo y registra un log de auditoría
     *
     * @param int $id Identificador del usuario a modificar
     * @param string $password Nueva credencial de acceso
     * @return array Respuesta estructurada con claves 'ok', 'msg' y 'code'
     */
    public function updatePassword(int $id, string $password)
    {
        if (!$id || empty($password))
            return ['ok' => false, 'msg' => "Datos inválidos", 'code' => 400];

        if ($this->model->updatePassword($id, $password)) {
            $target = $this->model->getById($id);
            $currentUser = obtenerUsuarioActual();
            $this->audit->registrar($currentUser['id'], $currentUser['nombre'], 'PASS_CAMBIADA', 'USUARIOS', "Actualizó la contraseña de: " . ($target['nombre'] ?? 'Usuario'));
            return ['ok' => true, 'msg' => "Contraseña actualizada"];
        }

        return ['ok' => false, 'msg' => "Error al actualizar contraseña", 'code' => 500];
    }


    public function consultarDni(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $dni = $_GET['dni'] ?? null;

        if (!$dni || strlen($dni) !== 8 || !is_numeric($dni)) {
            echo json_encode(['success' => false, 'message' => 'DNI inválido.']);
            exit;
        }

        $documentLookup = new DocumentLookupService();
        $data = $documentLookup->consultarDni($dni);

        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'No se encontraron resultados o API inactiva para el DNI ' . $dni]);
        exit;
    }

    public function consultarRuc(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $ruc = $_GET['ruc'] ?? null;
        if (!$ruc || strlen($ruc) !== 11 || !is_numeric($ruc)) {
            echo json_encode(['success' => false, 'message' => 'RUC inválido.']);
            exit;
        }

        $documentLookup = new DocumentLookupService();
        $data = $documentLookup->consultarRuc($ruc);

        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'RUC no encontrado o API no disponible. Digite manualmente.']);
        exit;
    }

}
