<?php
// gastos/eliminar.php — Eliminar gasto
require_once '../config/conexion.php';
$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM gastos WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
}
redirigir('index.php?msg=eliminado');
