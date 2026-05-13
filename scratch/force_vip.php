<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=hotel_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $dni = '76032957';
    
    // 1. Obtener datos base
    $stmt = $pdo->prepare("SELECT * FROM rooming_pax WHERE documento_num = ? AND es_titular = 1 LIMIT 1");
    $stmt->execute([$dni]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obtener una habitación válida
    $habId = $pdo->query("SELECT id FROM habitaciones LIMIT 1")->fetchColumn();
    // Obtener un usuario válido
    $usuId = $pdo->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn();

    // 2. Crear 2 estadías ficticias
    for ($i = 1; $i <= 2; $i++) {
        $stmt = $pdo->prepare("INSERT INTO rooming_stays (habitacion_id, usuario_id, fecha_registro, fecha_checkout, estado, total_pago) VALUES (?, ?, '2026-01-01', '2026-01-02', 'finalizado', 100)");
        $stmt->execute([$habId, $usuId]);
        $newStayId = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO rooming_pax (stay_id, nombre_completo, documento_tipo, documento_num, nacionalidad, ciudad, es_titular) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $newStayId,
            $template['nombre_completo'],
            $template['documento_tipo'],
            $template['documento_num'],
            $template['nacionalidad'],
            $template['ciudad'],
            1
        ]);
        echo "Creada estadía #$newStayId para Andree.\n";
    }
    
    echo "¡LISTO! Andree ahora tiene 3+ visitas en total.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
