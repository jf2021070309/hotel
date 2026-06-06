<?php
/**
 * api/auth/login.php
 */
require_once __DIR__ . '/../../ajax/bootstrap.php';
require_once BASE_PATH . 'config/db.php';
require_once BASE_PATH . 'app/Models/UsuarioModel.php';
require_once BASE_PATH . 'app/Models/AuditoriaModel.php';
require_once BASE_PATH . 'app/Middleware/session.php';
require_once BASE_PATH . 'app/Helpers/url.php';

$usuario_model = new UsuarioModel($pdo);
$audit_model   = new AuditoriaModel($pdo);

// BOOTSTRAP: Si no hay usuarios, crear el admin inicial
$checkUsers = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
if ($checkUsers == 0) {
    $usuario_model->create([
        'usuario' => 'admin',
        'password' => 'admin123',
        'nombre' => 'Administrador Inicial',
        'rol' => 'admin',
        'estado' => 1
    ]);
}

$input = json_decode(file_get_contents('php://input'), true);
$user  = trim($input['usuario'] ?? '');
$pass  = trim($input['password'] ?? '');

if (empty($user) || empty($pass)) {
    json_response(false, null, 400, "Usuario y contraseña son obligatorios");
}

$userData = $usuario_model->getByUsuario($user);

if ($userData && password_verify($pass, $userData['password'])) {
    iniciarSesion($userData);
    
    // Redirigir según rol (Todos al dashboard por ahora)
    $redirect = route('index.php');

    json_response(true, ['redirect' => $redirect], 200, "Login exitoso");
} else {
    $audit_model->registrar(null, 'LOGIN_FALLIDO', 'AUTH', "Intento fallido de login");
    json_response(false, null, 401, "Credenciales incorrectas");
}
