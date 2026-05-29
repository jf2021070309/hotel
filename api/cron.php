<?php
/**
 * api/cron.php
 * Tareas programadas y reset nocturno.
 */

function nocheReset(PDO $pdo) {
    try {
        $pdo->beginTransaction();
        
        // Marcar todas las habitaciones ocupadas como sucias para limpieza diaria
        $stmt = $pdo->prepare("UPDATE habitaciones SET estado = 'sucio' WHERE estado = 'ocupado'");
        $stmt->execute();
        $count = $stmt->rowCount();
        
        $pdo->commit();
        return ['habitaciones_procesadas' => $count];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
