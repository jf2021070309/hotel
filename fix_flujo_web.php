<?php
require_once __DIR__ . '/config/db.php';

// Find the recent RECEPCION C.CH.
$stmt = $pdo->query("
    SELECT m.id, m.flujo_id, m.monto, f.fecha, f.turno, c.nombre 
    FROM flujo_caja_movimientos m 
    JOIN flujo_caja f ON m.flujo_id = f.id 
    JOIN finanzas_categorias c ON m.categoria_id = c.id 
    WHERE c.nombre LIKE '%RECEPCI%C.CH%' 
    ORDER BY m.id DESC LIMIT 1
");
$mov = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mov) {
    echo "No recent RECEPCION C.CH. found.\n";
    exit;
}

echo "Found recent movement: ID={$mov['id']}, FlujoID={$mov['flujo_id']}, Fecha={$mov['fecha']}, Turno={$mov['turno']}, Monto={$mov['monto']}<br>\n";

$today = '2026-06-25';

if ($mov['fecha'] == $today) {
    echo "Movement is already on today's date ($today). Nothing to fix.\n";
    exit;
}

$turno = 'MAÑANA'; 
$stmtCheck = $pdo->prepare("SELECT id FROM flujo_caja WHERE fecha = ? AND turno = ?");
$stmtCheck->execute([$today, $turno]);
$todayFlujoId = $stmtCheck->fetchColumn();

if (!$todayFlujoId) {
    echo "Creating new flujo_caja for $today $turno...<br>\n";
    $stmtInsert = $pdo->prepare("INSERT INTO flujo_caja (fecha, turno, estado, usuario_id, nota_entrega) VALUES (?, ?, 'borrador', 1, 'Creado automaticamente para correccion')");
    $stmtInsert->execute([$today, $turno]);
    $todayFlujoId = $pdo->lastInsertId();
}

echo "Target Flujo ID for $today $turno is: $todayFlujoId<br>\n";

$stmtUpdate = $pdo->prepare("UPDATE flujo_caja_movimientos SET flujo_id = ? WHERE id = ?");
$stmtUpdate->execute([$todayFlujoId, $mov['id']]);

echo "Successfully moved movement {$mov['id']} from flujo {$mov['flujo_id']} ({$mov['fecha']}) to flujo $todayFlujoId ($today).<br>\n";

$stmtClose = $pdo->prepare("UPDATE flujo_caja SET estado = 'cerrado' WHERE id = ? AND estado = 'borrador'");
$stmtClose->execute([$mov['flujo_id']]);
if ($stmtClose->rowCount() > 0) {
    echo "Old flujo {$mov['flujo_id']} was closed.<br>\n";
} else {
    echo "Old flujo {$mov['flujo_id']} was already closed or didn't exist.<br>\n";
}
