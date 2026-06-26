<?php
require_once __DIR__ . '/config/db.php';

try {
    // Intentar agregar hora_checkin
    $pdo->exec("ALTER TABLE rooming_stays ADD COLUMN hora_checkin VARCHAR(10) NULL DEFAULT ''");
    echo "Columna hora_checkin agregada correctamente.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "La columna hora_checkin ya existe.<br>";
    } else {
        echo "Error al agregar hora_checkin: " . $e->getMessage() . "<br>";
    }
}

try {
    // Intentar agregar carro por si acaso también falta
    $pdo->exec("ALTER TABLE rooming_stays ADD COLUMN carro VARCHAR(20) NULL DEFAULT 'NO'");
    echo "Columna carro agregada correctamente.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "La columna carro ya existe.<br>";
    } else {
        echo "Error al agregar carro: " . $e->getMessage() . "<br>";
    }
}

try {
    // Intentar agregar procedencia por si acaso también falta
    $pdo->exec("ALTER TABLE rooming_stays ADD COLUMN procedencia VARCHAR(255) NULL DEFAULT ''");
    echo "Columna procedencia agregada correctamente.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "La columna procedencia ya existe.<br>";
    } else {
        echo "Error al agregar procedencia: " . $e->getMessage() . "<br>";
    }
}

try {
    $pdo->exec("ALTER TABLE rooming_stays MODIFY tipo_comprobante ENUM('BOLETA','FACTURA','TICKET','F.X.') NOT NULL DEFAULT 'BOLETA'");
    echo "Columna tipo_comprobante actualizada correctamente para soportar F.X.<br>";
} catch (PDOException $e) {
    echo "Error al modificar tipo_comprobante: " . $e->getMessage() . "<br>";
}

try {
    $pdo->exec("UPDATE rooming_stays s JOIN habitaciones h ON s.habitacion_id = h.id SET s.tipo_hab_declarado = h.tipo WHERE s.tipo_hab_declarado = 'RESERVA'");
    echo "Tipos de habitación 'RESERVA' corregidos correctamente.<br>";
} catch (PDOException $e) {
    echo "Error al corregir tipo_hab_declarado: " . $e->getMessage() . "<br>";
}

try {
    $pdo->exec("ALTER TABLE rooming_stays ADD COLUMN marcado TINYINT(1) NOT NULL DEFAULT 0");
    echo "Columna marcado agregada correctamente.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "La columna marcado ya existe.<br>";
    } else {
        echo "Error al agregar marcado: " . $e->getMessage() . "<br>";
    }
}

echo "<br><b>Parche de base de datos finalizado.</b>";
