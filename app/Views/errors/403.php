<?php
/**
 * app/Views/errors/403.php
 */
require_once __DIR__ . '/../../../rutas.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>403 Acceso Denegado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; height: 100vh; display: flex; align-items: center; justify-content: center; text-align: center; }
        .error-card { max-width: 500px; padding: 40px; background: white; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .error-code { font-size: 80px; font-weight: 800; color: #dc3545; line-height: 1; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-code">403</div>
        <h2 class="fw-bold">Acceso Denegado</h2>
        <p class="text-muted mb-4">Lo sentimos, no tienes los permisos suficientes para acceder a este módulo. Si crees que esto es un error, contacta al administrador.</p>
        <a href="<?= route('index.php') ?>" class="btn btn-primary px-4 py-2 fw-bold">Volver al Dashboard</a>
    </div>
</body>
</html>
