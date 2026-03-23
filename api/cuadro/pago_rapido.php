<?php
/**
 * api/cuadro/pago_rapido.php
 * POST — registers a quick payment (anticipo) for a stay
 * and returns updated estado_pago + total_cobrado.
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../auth/session.php';
requerirSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método no permitido');
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];

$stay_id  = (int)($body['stay_id']   ?? 0);
$monto    = (float)($body['monto']   ?? 0);
$moneda   = $body['moneda']   ?? 'PEN';
$metodo   = $body['metodo']   ?? 'efectivo';
$tc       = (float)($body['tc']      ?? 1);
$uid      = $_SESSION['auth_id'] ?? 0;

if (!$stay_id || $monto <= 0) {
    jsonResponse(false, 'Datos incompletos: stay_id y monto son requeridos');
}

// Calcular monto en PEN para el resumen
$monto_pen = $moneda === 'PEN' ? $monto : round($monto * $tc, 2);

try {
    $pdo->beginTransaction();

    // Insert anticipo
    $stmt = $pdo->prepare(
        "INSERT INTO anticipos (stay_id, monto, moneda, monto_pen, tc_aplicado, tipo_pago, recibo, fecha, usuario_id)
         VALUES (:stay_id, :monto, :moneda, :monto_pen, :tc, :tipo, :recibo, :fecha, :uid)"
    );
    $stmt->execute([
        ':stay_id'   => $stay_id,
        ':monto'     => $monto,
        ':moneda'    => $moneda,
        ':monto_pen' => $monto_pen,
        ':tc'        => $tc,
        ':tipo'      => $metodo,
        ':recibo'    => '',
        ':fecha'     => date('Y-m-d H:i:s'),
        ':uid'       => $uid,
    ]);

    // Recalculate total_cobrado
    $stmt = $pdo->prepare("SELECT SUM(monto_pen) FROM anticipos WHERE stay_id = ?");
    $stmt->execute([$stay_id]);
    $totalCobrado = (float)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT total_pago FROM rooming_stays WHERE id = ?");
    $stmt->execute([$stay_id]);
    $totalPago = (float)$stmt->fetchColumn();

    // Determine new estado_pago
    $estadoPago = 'pendiente';
    if ($totalCobrado >= $totalPago) {
        $estadoPago = 'pagado';
    } elseif ($totalCobrado > 0 && $totalCobrado < $totalPago * 0.5) {
        $estadoPago = 'adelanto';
    } elseif ($totalCobrado >= $totalPago * 0.5) {
        $estadoPago = 'parcial';
    }

    $stmt = $pdo->prepare(
        "UPDATE rooming_stays SET total_cobrado = ?, estado_pago = ? WHERE id = ?"
    );
    $stmt->execute([$totalCobrado, $estadoPago, $stay_id]);

    $pdo->commit();

    jsonResponse(true, 'Pago registrado', [
        'stay_id'       => $stay_id,
        'total_cobrado' => $totalCobrado,
        'estado_pago'   => $estadoPago,
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    jsonResponse(false, 'Error al registrar pago: ' . $e->getMessage());
}
