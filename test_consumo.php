<?php
require 'config/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
try {
    $stmt = $pdo->prepare("INSERT INTO rooming_consumos (stay_id, producto_id, cantidad, precio_unitario, total, metodo_pago, pagado, usuario_id) VALUES (1, NULL, 1, 7, 7, 'POS', 1, 1)");
    $stmt->execute();
    echo 'OK';
} catch (Exception $e) {
    echo $e->getMessage();
}
