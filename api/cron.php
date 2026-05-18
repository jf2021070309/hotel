<?php
/**
 * api/cron.php
 * ─────────────────────────────────────────────────────────────────────────────
 * Endpoint HTTP del cron nocturno  +  función reutilizable nocheReset().
 *
 * CÓMO LLAMARLO (Railway / UptimeRobot / cron externo):
 *   GET https://tu-dominio/api/cron.php?action=noche_reset&token=TU_TOKEN_SECRETO
 *
 * TOKEN: Variable de entorno  CRON_SECRET_TOKEN  en Railway.
 *        En local se acepta el valor por defecto "hotel_cron_2025".
 *
 * QUÉ HACE (función nocheReset):
 *   1. Busca habitaciones "ocupado" con estadía activa que NO hacen checkout hoy.
 *   2. Las cambia a estado "sucio".
 *   3. Inserta / resetea un registro en limpieza_registros (tipo "reposo")
 *      para que aparezcan en el Panel de Limpieza del día siguiente.
 * ─────────────────────────────────────────────────────────────────────────────
 */

// ═══════════════════════════════════════════════════════════════════════════
// FUNCIÓN PRINCIPAL — reutilizable desde limpieza.php y cron/noche_reset.php
// ═══════════════════════════════════════════════════════════════════════════
if (!function_exists('nocheReset')) {
    function nocheReset(PDO $pdo): array
    {
        $hoy      = date('Y-m-d');
        $logItems = [];

        // ── A. Habitaciones OCUPADAS con estadía activa (no hacen checkout hoy) ──
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
            return [
                'habitaciones_procesadas' => 0,
                'mensaje' => 'No hay habitaciones ocupadas activas esta noche.',
            ];
        }

        // ── B. Usuario fallback ────────────────────────────────────────────────
        $sessionUid  = $_SESSION['auth_id'] ?? null;
        $fallbackUid = (int)$pdo->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn() ?: 1;
        if ($sessionUid) {
            $stmtChk = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
            $stmtChk->execute([$sessionUid]);
            $fallbackUid = (int)($stmtChk->fetchColumn() ?: $fallbackUid);
        }

        // ── C. Statements ──────────────────────────────────────────────────────
        $stmtSucio  = $pdo->prepare(
            "UPDATE habitaciones SET estado = 'sucio' WHERE id = :id AND estado = 'ocupado'"
        );
        $stmtCheck  = $pdo->prepare(
            "SELECT id, estado FROM limpieza_registros
             WHERE fecha = :fecha AND habitacion_id = :hab_id LIMIT 1"
        );
        $stmtInsert = $pdo->prepare("
            INSERT INTO limpieza_registros
                (fecha, habitacion_id, habitacion, tipo_limpieza, prioridad, estado, usuario_id)
            VALUES
                (:fecha, :hab_id, :habitacion, 'reposo', 'normal', 'pendiente', :uid)
        ");
        $stmtReset  = $pdo->prepare(
            "UPDATE limpieza_registros
             SET estado = 'pendiente', tipo_limpieza = 'reposo',
                 hora_inicio = NULL, hora_fin = NULL
             WHERE id = :id"
        );

        // ── D. Procesar ────────────────────────────────────────────────────────
        $pdo->beginTransaction();
        try {
            foreach ($habitacionesOcupadas as $hab) {
                // 1. Marcar habitación → sucio
                $stmtSucio->execute([':id' => $hab['id']]);

                // 2. Registro limpieza_registros
                $stmtCheck->execute([':fecha' => $hoy, ':hab_id' => $hab['id']]);
                $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                if (!$existing) {
                    $stmtInsert->execute([
                        ':fecha'      => $hoy,
                        ':hab_id'     => $hab['id'],
                        ':habitacion' => $hab['numero'],
                        ':uid'        => $fallbackUid,
                    ]);
                    $accion = 'insertado';
                } else {
                    $stmtReset->execute([':id' => $existing['id']]);
                    $accion = 'reseteado';
                }

                $logItems[] = ['habitacion' => $hab['numero'], 'accion' => $accion];
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

        return [
            'habitaciones_procesadas' => count($logItems),
            'detalle'                 => $logItems,
        ];
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// RUNNER HTTP — solo se ejecuta cuando este archivo es llamado directamente
// ═══════════════════════════════════════════════════════════════════════════
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'cron.php') {

    require_once __DIR__ . '/../config/db.php';

    header('Content-Type: application/json; charset=utf-8');

    // ── Validar token ──────────────────────────────────────────────────────
    $tokenEsperado = getenv('CRON_SECRET_TOKEN') ?: 'hotel_cron_2025';
    $tokenRecibido = $_GET['token'] ?? $_SERVER['HTTP_X_CRON_TOKEN'] ?? '';

    if (!hash_equals($tokenEsperado, $tokenRecibido)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'Token inválido.']);
        exit;
    }

    $action = $_GET['action'] ?? '';
    if ($action !== 'noche_reset') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => "Acción '$action' no reconocida. Use action=noche_reset"]);
        exit;
    }

    try {
        $resultado = nocheReset($pdo);
        echo json_encode([
            'ok'        => true,
            'msg'       => 'Reset nocturno ejecutado correctamente.',
            'ejecutado' => date('Y-m-d H:i:s'),
            'detalle'   => $resultado,
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
    }
}
