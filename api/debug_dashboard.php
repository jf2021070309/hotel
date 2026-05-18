<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

try {
    $fecha = date('Y-m-d');

    // Conteo de flujos y movimientos del día
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM flujo_caja WHERE fecha = ?");
    $stmt->execute([$fecha]);
    $flujos_count = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(m.id) FROM flujo_caja_movimientos m JOIN flujo_caja f ON m.flujo_id = f.id WHERE f.fecha = ?");
    $stmt->execute([$fecha]);
    $movs_count = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM flujo_caja_movimientos m JOIN flujo_caja f ON m.flujo_id = f.id WHERE f.fecha = ? AND m.tipo = 'Ingreso'");
    $stmt->execute([$fecha]);
    $ingresos_sum = (float)$stmt->fetchColumn();

    // Anticipos (pagos de rooming)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM anticipos WHERE fecha = ?");
    $stmt->execute([$fecha]);
    $anticipos_count = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(monto_pen),0) FROM anticipos WHERE fecha = ?");
    $stmt->execute([$fecha]);
    $anticipos_sum = (float)$stmt->fetchColumn();

    // Últimos movimientos del día (limit 20)
    $stmt = $pdo->prepare("SELECT f.id as flujo_id, f.turno, f.estado as flujo_estado, m.id, m.tipo, m.categoria, m.monto, m.moneda, m.medio_pago, m.observacion FROM flujo_caja_movimientos m JOIN flujo_caja f ON m.flujo_id = f.id WHERE f.fecha = ? ORDER BY m.id DESC LIMIT 20");
    $stmt->execute([$fecha]);
    $last_movs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'fecha' => $fecha,
        'flujos_count' => $flujos_count,
        'movimientos_count' => $movs_count,
        'ingresos_sum' => $ingresos_sum,
        'anticipos_count' => $anticipos_count,
        'anticipos_sum' => $anticipos_sum,
        'last_movimientos' => $last_movs
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
