<?php require_once "config/db.php"; $stmt = $pdo->query("SELECT id, nombre, modulo, activo FROM finanzas_categorias WHERE nombre LIKE '%RECEPCI%'"); print_r($stmt->fetchAll(PDO::FETCH_ASSOC)); ?>
