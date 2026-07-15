<?php
require 'config/db.php';
$stmt = $pdo->query('SELECT id, nombre, modulo, tipo FROM finanzas_categorias');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
