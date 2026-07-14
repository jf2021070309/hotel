<?php
$host = 'localhost';
$port = 3306;
$user = 'root';
$pass = '';
$db = 'hotel';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("ALTER TABLE rooming_stays MODIFY tipo_comprobante ENUM('BOLETA','FACTURA','TICKET','F.X.','NINGUNO','-') DEFAULT 'NINGUNO'");
    echo "OK";
} catch (Exception $e) {
    echo "Error 1: " . $e->getMessage() . "\n";
    try {
        $user = 'hotel_user';
        $pass = 'Surgas654321';
        $db = 'hotel_db';
        $pdo = new PDO("mysql:host=127.0.0.1;dbname=$db;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("ALTER TABLE rooming_stays MODIFY tipo_comprobante ENUM('BOLETA','FACTURA','TICKET','F.X.','NINGUNO','-') DEFAULT 'NINGUNO'");
        echo "OK 2";
    } catch (Exception $e2) {
        echo "Error 2: " . $e2->getMessage();
    }
}
