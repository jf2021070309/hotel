<?php
/**
 * ajax/notificaciones.php
 * Retorna las notificaciones activas para el panel.
 */
require_once 'bootstrap.php';
require_once BASE_PATH . 'config/db.php';
require_once BASE_PATH . 'app/Middleware/session.php';

header('Content-Type: application/json');

if (!isset($_SESSION['auth_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

try {
    $notificaciones = [];
    $hoy = date('Y-m-d');
    $hora_actual = date('H:i');
    
    // 1. Turno de Caja Abierto / Recordatorio de cierre
    $turno_actual = (date('H') >= 6 && date('H') < 14) ? 'MAÑANA' : 'TARDE';
    $stmt = $pdo->prepare("SELECT estado FROM flujo_caja WHERE fecha = ? AND turno = ?");
    $stmt->execute([$hoy, $turno_actual]);
    $flujo = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$flujo || $flujo['estado'] === 'borrador') {
        $notificaciones[] = [
            'tipo' => 'warning',
            'titulo' => 'Flujo de Caja',
            'mensaje' => 'Recuerda revisar y gestionar tu caja (Turno ' . $turno_actual . ').',
            'icono' => 'bi-cash-stack',
            'url' => 'flujo/v2.php'
        ];
    }
    
    // 2. Huéspedes que pronto van a salir (Checkout hoy)
    $stmt = $pdo->prepare("
        SELECT h.numero, c.nombre_razon_social
        FROM rooming_stays r
        JOIN habitaciones h ON r.habitacion_id = h.id
        JOIN clientes c ON r.cliente_titular_id = c.id
        WHERE r.estado = 'activo' AND r.fecha_checkout <= ?
    ");
    $stmt->execute([$hoy]);
    $checkouts_pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($checkouts_pendientes as $co) {
        $notificaciones[] = [
            'tipo' => 'danger',
            'titulo' => 'Salida Programada',
            'mensaje' => 'La Hab. ' . $co['numero'] . ' (' . $co['nombre_razon_social'] . ') tiene salida para hoy.',
            'icono' => 'bi-box-arrow-right',
            'url' => 'rooming/v2.php?buscar=' . $co['numero']
        ];
    }
    
    // 3. Huéspedes que ya salieron (Checkout finalizado hoy)
    $stmt = $pdo->prepare("
        SELECT h.numero, c.nombre_razon_social
        FROM rooming_stays r
        JOIN habitaciones h ON r.habitacion_id = h.id
        JOIN clientes c ON r.cliente_titular_id = c.id
        WHERE r.estado = 'finalizado' AND r.fecha_checkout = ?
    ");
    $stmt->execute([$hoy]);
    $checkouts_listos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($checkouts_listos as $cl) {
        $notificaciones[] = [
            'tipo' => 'success',
            'titulo' => 'Checkout Realizado',
            'mensaje' => 'La Hab. ' . $cl['numero'] . ' (' . $cl['nombre_razon_social'] . ') ya se retiró.',
            'icono' => 'bi-check-circle-fill',
            'url' => 'rooming/v2.php'
        ];
    }
    
    // 4. Recordatorios de Limpieza
    $stmt = $pdo->prepare("
        SELECT numero FROM habitaciones WHERE estado IN ('limpieza', 'sucio')
    ");
    $stmt->execute();
    $limpieza = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($limpieza as $l) {
        $notificaciones[] = [
            'tipo' => 'info',
            'titulo' => 'Limpieza de Habitación',
            'mensaje' => 'La Hab. ' . $l['numero'] . ' requiere limpieza.',
            'icono' => 'bi-stars',
            'url' => 'limpieza/v2.php'
        ];
    }

    // 5. Desayunos registrados para hoy
    $stmt = $pdo->prepare("
        SELECT SUM(d.pax_calculado) as total_pax FROM desayunos d WHERE d.fecha = ?
    ");
    $stmt->execute([$hoy]);
    $desayuno = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($desayuno && $desayuno['total_pax'] > 0) {
        $notificaciones[] = [
            'tipo' => 'primary',
            'titulo' => 'Servicio de Desayunos',
            'mensaje' => 'Hay ' . $desayuno['total_pax'] . ' desayunos registrados para hoy.',
            'icono' => 'bi-egg-fried',
            'url' => 'desayunos/index.php'
        ];
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $notificaciones,
        'count' => count($notificaciones)
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
