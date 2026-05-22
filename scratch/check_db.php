<?php
require 'config/db.php';
$stmt = $pdo->query("SELECT id, nombre, modulo, tipo, activo FROM finanzas_categorias");
$rows = $stmt->fetchAll();
foreach ($rows as $row) {
    echo "ID: {$row['id']} | Nombre: {$row['nombre']} | Modulo: {$row['modulo']} | Tipo: {$row['tipo']} | Activo: {$row['activo']}\n";
}
