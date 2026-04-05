<?php
require_once 'c:/xampp/htdocs/hotel/config/db.php';
try {
    $pdo->exec("ALTER TABLE limpieza_registros ADD COLUMN responsable VARCHAR(100) NULL AFTER estado");
    echo "Columna 'responsable' añadida con éxito.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
