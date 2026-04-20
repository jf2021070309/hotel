<?php
require_once 'auth/session.php';
require_once 'config/db.php'; // Ruta corregida
require_once 'app/Helpers/FinanzasHelper.php';

// En este sistema, config/db.php ya crea la variable $pdo

try {
    echo "--- DIAGNÓSTICO DE SESIÓN ---\n";
    echo "Usuario ID en sesión: " . ($_SESSION['auth_id'] ?? 'NO INICIADA') . "\n";
    echo "Usuario Nombre: " . ($_SESSION['auth_nombre'] ?? 'N/A') . "\n";
    
    echo "\n--- DIAGNÓSTICO DE TURNOS ---\n";
    $hora = (int)date('H');
    $turnoCalculado = FinanzasHelper::getTurnoActual();
    echo "Hora Servidor: " . date('H:i:s') . "\n";
    echo "Turno Calculado por PHP: $turnoCalculado\n";
    
    echo "\n--- BÚSQUEDA EN BASE DE DATOS ---\n";
    $usuarioId = $_SESSION['auth_id'] ?? 9; // Forzamos Roy para la prueba
    $fecha = date('Y-m-d');
    
    echo "Buscando para Fecha: $fecha | Usuario: $usuarioId | Turno: $turnoCalculado\n";

    $stmt = $pdo->prepare("SELECT id, turno, HEX(turno) as turno_hex, estado FROM flujo_caja WHERE fecha = ? AND usuario_id = ?");
    $stmt->execute([$fecha, $usuarioId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($results)) {
        echo "❌ ¡ERROR! No se encontró ninguna caja para el usuario $usuarioId en la fecha $fecha\n";
    } else {
        foreach ($results as $row) {
            echo "ID Caja: {$row['id']} | Turno en BD: {$row['turno']} | HEX: {$row['turno_hex']} | Estado: {$row['estado']}\n";
            
            // Comparación binaria para detectar problemas de Ñ
            if ($row['turno'] === $turnoCalculado) {
                echo "✅ COINCIDENCIA EXACTA ENCONTRADA\n";
            } else {
                echo "❌ NO COINCIDE con $turnoCalculado\n";
                echo "DEBUG: PHP '".bin2hex($turnoCalculado)."' vs BD '".$row['turno_hex']."'\n";
            }
        }
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
