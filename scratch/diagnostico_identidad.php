<?php
require_once 'config/db.php';
session_start();

echo "--- SESIÓN ACTUAL ---\n";
echo "ID Usuario en Sesión: " . ($_SESSION['auth_id'] ?? 'NO DEFINIDO') . "\n";
echo "Nombre en Sesión: " . ($_SESSION['auth_nombre'] ?? 'NO DEFINIDO') . "\n\n";

echo "--- DATOS DE USUARIOS ---\n";
$users = $pdo->query("SELECT id, usuario, nombre FROM usuarios")->fetchAll(PDO::FETCH_ASSOC);
foreach($users as $u) {
    echo "ID: {$u['id']} | Usuario: {$u['usuario']} | Nombre: {$u['nombre']}\n";
}

echo "\n--- CAJAS ABIERTAS HOY ---\n";
$cajas = $pdo->query("SELECT id, usuario_id, fecha, turno, estado FROM flujo_caja WHERE fecha = CURDATE()")->fetchAll(PDO::FETCH_ASSOC);
if (empty($cajas)) {
    echo "No hay cajas abiertas hoy en la DB.\n";
} else {
    foreach($cajas as $c) {
        echo "Caja ID: {$c['id']} | Dueño (User ID): {$c['usuario_id']} | Turno: {$c['turno']} | Estado: {$c['estado']}\n";
    }
}

require_once 'app/Helpers/FinanzasHelper.php';
$helper = new FinanzasHelper($pdo);
echo "\n--- RESULTADO DEL HELPER (SALVAVIDAS) ---\n";
$activo = $helper->getFlujoIdActivo($_SESSION['auth_id'] ?? 0);
echo "ID de Caja detectado por el sistema: " . ($activo ?? 'NINGUNO (ERROR)') . "\n";
