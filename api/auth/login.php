<?php
/**
 * api/auth/login.php
 */
require_once '../../config/db.php';
require_once '../../app/Models/UsuarioModel.php';
require_once '../../app/Models/AuditoriaModel.php';
require_once '../../auth/session.php';

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
    $audit_model->registrar($userData['id'], $userData['nombre'], 'LOGIN_EXITOSO', 'AUTH');
    
    // Redirigir según rol (Todos al dashboard por ahora)
    $redirect = 'index.php';

    json_response(true, ['redirect' => $redirect], 200, "Login exitoso");
} else {
    $audit_model->registrar(null, $user, 'LOGIN_FALLIDO', 'AUTH', "Intento fallido de login");
    json_response(false, null, 401, "Credenciales incorrectas");
}
