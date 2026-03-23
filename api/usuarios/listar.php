<?php
/**
 * api/usuarios/listar.php
 */
require_once '../../config/db.php';
require_once '../../app/Models/UsuarioModel.php';
require_once '../../auth/session.php';
require_once '../../auth/middleware.php';

// Solo admin puede listar usuarios
protegerPorRol('admin');

$usuario_model = new UsuarioModel($pdo);
json_response(true, $usuario_model->getAll());
