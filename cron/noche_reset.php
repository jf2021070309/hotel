<?php
/**
 * cron/noche_reset.php
 * ─────────────────────────────────────────────────────────────────────────────
 * Script CLI para el reset nocturno — ejecutar desde el servidor vía cron job.
 *
 * INSTALACIÓN EN LINUX (servidor):
 *   Editar crontab:  crontab -e
 *   Agregar línea para ejecutar a las 00:01 cada noche (hora Lima = UTC-5):
 *
 *     1 0 * * * php /var/www/html/cron/noche_reset.php >> /var/log/hotel_noche.log 2>&1
 *
 * EN RAILWAY (sin acceso a crontab):
 *   Usar el endpoint HTTP:  api/cron.php?action=noche_reset&token=TU_TOKEN
 *   Configurar en UptimeRobot, cron-job.org, o GitHub Actions.
 *
 * EN XAMPP LOCAL (Windows, para pruebas):
 *   php c:\xampp\htdocs\hotel\cron\noche_reset.php
 * ─────────────────────────────────────────────────────────────────────────────
 */

// Solo ejecutable desde CLI
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Este script solo puede ejecutarse desde la línea de comandos.\n";
    exit(1);
}

// Ruta base del proyecto
define('ROOT', dirname(__DIR__));

require_once ROOT . '/config/db.php';

echo "═══════════════════════════════════════════════════════\n";
echo "  HOTEL MANAGER — RESET NOCTURNO\n";
echo "  Ejecutado: " . date('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════\n\n";

$hoy = date('Y-m-d');

// ── Obtener habitaciones OCUPADAS con estadía activa ─────────────────────────
$sqlOcupadas = "
    SELECT DISTINCT h.id, h.numero
    FROM habitaciones h
    INNER JOIN rooming_stays s
           ON s.habitacion_id = h.id
          AND s.estado IN ('activo', 'late_checkout')
          AND DATE(s.fecha_checkout) > :hoy
    WHERE h.estado = 'ocupado'
";
$stmtOcupadas = $pdo->prepare($sqlOcupadas);
$stmtOcupadas->execute([':hoy' => $hoy]);
$habitacionesOcupadas = $stmtOcupadas->fetchAll(PDO::FETCH_ASSOC);

if (empty($habitacionesOcupadas)) {
    echo "✓ No hay habitaciones ocupadas activas esta noche. Nada que procesar.\n";
    exit(0);
}

echo "Habitaciones ocupadas encontradas: " . count($habitacionesOcupadas) . "\n\n";

// ── Usuario fallback ──────────────────────────────────────────────────────────
$fallbackUid = (int)$pdo->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn() ?: 1;

// ── Statements ────────────────────────────────────────────────────────────────
$stmtSucio  = $pdo->prepare(
    "UPDATE habitaciones SET estado = 'sucio' WHERE id = :id AND estado = 'ocupado'"
);
$stmtCheck  = $pdo->prepare(
    "SELECT id, estado FROM limpieza_registros WHERE fecha = :fecha AND habitacion_id = :hab_id LIMIT 1"
);
$stmtInsert = $pdo->prepare("
    INSERT INTO limpieza_registros
        (fecha, habitacion_id, habitacion, tipo_limpieza, prioridad, estado, usuario_id)
    VALUES
        (:fecha, :hab_id, :habitacion, 'reposo', 'normal', 'pendiente', :uid)
");
$stmtReset  = $pdo->prepare(
    "UPDATE limpieza_registros
     SET estado = 'pendiente', tipo_limpieza = 'reposo', hora_inicio = NULL, hora_fin = NULL
     WHERE id = :id AND estado IN ('lista', 'sucio', 'pendiente')"
);

// ── Procesar ──────────────────────────────────────────────────────────────────
$ok  = 0;
$err = 0;

$pdo->beginTransaction();

try {
    foreach ($habitacionesOcupadas as $hab) {
        // Marcar como SUCIA
        $stmtSucio->execute([':id' => $hab['id']]);

        // Limpieza_registros para hoy
        $stmtCheck->execute([':fecha' => $hoy, ':hab_id' => $hab['id']]);
        $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            $stmtInsert->execute([
                ':fecha'      => $hoy,
                ':hab_id'     => $hab['id'],
                ':habitacion' => $hab['numero'],
                ':uid'        => $fallbackUid,
            ]);
            $accion = 'NUEVO registro limpieza';
        } else {
            $stmtReset->execute([':id' => $existing['id']]);
            $accion = 'RESETEADO a pendiente';
        }

        echo "  ✓ Hab #{$hab['numero']} → SUCIA | {$accion}\n";
        $ok++;
    }

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    echo "\n[ERROR] " . $e->getMessage() . "\n";
    $err++;
    exit(1);
}

echo "\n═══════════════════════════════════════════════════════\n";
echo "  COMPLETADO: $ok habitaciones procesadas";
if ($err > 0) echo ", $err errores";
echo "\n═══════════════════════════════════════════════════════\n";
exit(0);
