<?php
require_once __DIR__ . '/config/db.php';

try {
    $columnExists = $pdo->query("SHOW COLUMNS FROM desayunos_detalle LIKE 'habitacion'")->fetch();
    $indexExists = $pdo->query("SHOW INDEX FROM desayunos_detalle WHERE Key_name = 'habitacion_id'")->fetch();
    $fkExists = $pdo->query("
        SELECT CONSTRAINT_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'desayunos_detalle'
          AND COLUMN_NAME = 'habitacion_id'
          AND REFERENCED_TABLE_NAME = 'habitaciones'
    ")->fetch();

    if ($columnExists) {
        $pdo->exec("ALTER TABLE desayunos_detalle DROP COLUMN habitacion");
    }

    if (!$indexExists) {
        $pdo->exec("ALTER TABLE desayunos_detalle ADD KEY habitacion_id (habitacion_id)");
    }

    if (!$fkExists) {
        $pdo->exec("ALTER TABLE desayunos_detalle
                    ADD CONSTRAINT desayunos_detalle_ibfk_2
                    FOREIGN KEY (habitacion_id) REFERENCES habitaciones(id)");
    }

    echo "Base de datos actualizada con exito.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
