<?php
$ports = [3306, 3307, 3308];
$user = 'hotel_user';
$pass = 'Surgas654321';
$db = 'hotel_db';

foreach ($ports as $p) {
    try {
        $pdo = new PDO("mysql:host=127.0.0.1;port=$p;dbname=$db;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "Connected on port $p\n";
        $pdo->exec("ALTER TABLE rooming_stays MODIFY tipo_comprobante ENUM('BOLETA','FACTURA','TICKET','F.X.','NINGUNO','-') DEFAULT 'NINGUNO'");
        echo "Altered on port $p\n";
    } catch (Exception $e) {
        echo "Port $p failed: " . $e->getMessage() . "\n";
    }
}
