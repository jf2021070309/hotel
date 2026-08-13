<?php
/**
 * app/Models/LimpiezaModel.php
 */
class LimpiezaModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getDetalleDia(string $fecha): array {
        // --- AUTO SYNC MANUAL STATES ---
        $stmtHabs = $this->pdo->query("SELECT id, numero, estado FROM habitaciones WHERE estado IN ('limpieza', 'sucio', 'mantenimiento')");
        $dirtyRooms = $stmtHabs->fetchAll(PDO::FETCH_ASSOC);

        // Determinar un usuario válido para asignar a los registros de limpieza.
        $sessionUid = $_SESSION['auth_id'] ?? null;
        $validUid = null;
        if ($sessionUid) {
            $stmtChk = $this->pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
            $stmtChk->execute([$sessionUid]);
            $validUid = $stmtChk->fetchColumn() ?: null;
        }
        if (!$validUid) {
            $validUid = (int)$this->pdo->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn() ?: 1;
        }

        foreach ($dirtyRooms as $room) {
            $stmtCheck = $this->pdo->prepare("SELECT id, estado, tipo_limpieza FROM limpieza_registros WHERE fecha = ? AND habitacion_id = ?");
            $stmtCheck->execute([$fecha, $room['id']]);
            $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            $tipo = ($room['estado'] === 'mantenimiento') ? 'estimacion' : 'salida';
            $prioridad = ($room['estado'] === 'mantenimiento') ? 'alta' : 'normal';

            if (!$existing) {
                $stmtInsert = $this->pdo->prepare("
                    INSERT INTO limpieza_registros (fecha, habitacion_id, tipo_limpieza, prioridad, estado, usuario_id)
                    VALUES (?, ?, ?, ?, 'pendiente', ?)
                ");
                $stmtInsert->execute([$fecha, $room['id'], $tipo, $prioridad, $validUid]);
            } else {
                $updates = [];
                // Si la habitación ahora está sucia o en mantenimiento, pero el registro de limpieza ya estaba finalizado (lista), lo reseteamos a pendiente
                if (in_array($room['estado'], ['limpieza', 'sucio', 'mantenimiento']) && $existing['estado'] === 'lista') {
                    $updates[] = "estado = 'pendiente'";
                    $updates[] = "hora_fin = NULL";
                }
                
                // Si la habitación está en mantenimiento, nos aseguramos que el tipo y prioridad sean los correctos
                if ($room['estado'] === 'mantenimiento') {
                    if ($existing['tipo_limpieza'] !== 'estimacion') $updates[] = "tipo_limpieza = 'estimacion'";
                    $updates[] = "prioridad = 'alta'"; // Asegurar prioridad alta para mantenimiento
                }

                if (!empty($updates)) {
                    $stmtReset = $this->pdo->prepare("UPDATE limpieza_registros SET " . implode(", ", $updates) . " WHERE id = ?");
                    $stmtReset->execute([$existing['id']]);
                }
            }
        }
        // --------------------------------

        $sql = "SELECT r.*, h.numero as habitacion, u.nombre as responsable_nombre, h.estado as room_estado, h.tipo as tipo_hab,
                   (SELECT COALESCE(s.pax_total, NULL) FROM rooming_stays s WHERE s.habitacion_id = h.id AND s.fecha_registro <= ? AND s.fecha_checkout >= ? ORDER BY s.id DESC LIMIT 1) as pax
            FROM limpieza_registros r
            LEFT JOIN usuarios u ON r.usuario_id = u.id
            JOIN habitaciones h ON r.habitacion_id = h.id
            WHERE r.fecha = ? 
            ORDER BY r.prioridad ASC, h.numero ASC";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$fecha, $fecha, $fecha]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // If the table doesn't have the new columns and r.* fails, or for safety
            $rows = [];
        }

        foreach ($rows as &$row) {
            if ($row['room_estado'] === 'mantenimiento') {
                $row['estado'] = 'mantenimiento';
            }
        }

        return $rows;
    }

    public function listarHistorial(int $mes, int $anio): array {
        $sql = "SELECT fecha, 
                       COUNT(*) as total, 
                       SUM(CASE WHEN estado='lista' THEN 1 ELSE 0 END) as completadas,
                       SUM(CASE WHEN estado!='lista' THEN 1 ELSE 0 END) as pendientes
                FROM limpieza_registros 
                WHERE MONTH(fecha) = ? AND YEAR(fecha) = ?
                GROUP BY fecha ORDER BY fecha DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$mes, $anio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function checkExisteRegistro(string $fecha, int $hab_id): bool {
        $stmt = $this->pdo->prepare("SELECT id FROM limpieza_registros WHERE fecha = ? AND habitacion_id = ?");
        $stmt->execute([$fecha, $hab_id]);
        return (bool)$stmt->fetchColumn();
    }

    public function guardarMasivo(array $registros): bool {
        $this->pdo->beginTransaction();
        try {
            $sql = "INSERT IGNORE INTO limpieza_registros (fecha, habitacion_id, tipo_limpieza, prioridad, usuario_id) 
                    VALUES (:fecha, :hab_id, :tipo, :prioridad, :uid)";
            $stmt = $this->pdo->prepare($sql);
            // preparar verificación de usuario y fallback
            $stmtUser = $this->pdo->prepare("SELECT id FROM usuarios WHERE id = ? LIMIT 1");
            $fallbackUid = (int)$this->pdo->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn() ?: 1;
            foreach ($registros as $r) {
                $uid = isset($r['usuario_id']) ? (int)$r['usuario_id'] : 0;
                if ($uid <= 0) {
                    $uid = $fallbackUid;
                } else {
                    $stmtUser->execute([$uid]);
                    if (!$stmtUser->fetchColumn()) $uid = $fallbackUid;
                }

                $stmt->execute([
                    ':fecha'     => $r['fecha'],
                    ':hab_id'    => $r['habitacion_id'],
                    ':tipo'      => $r['tipo_limpieza'],
                    ':prioridad' => $r['prioridad'],
                    ':uid'       => $uid
                ]);
            }
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function actualizar(int $id, array $data): bool {
        $fields = [];
        $params = [':id' => $id];
        foreach ($data as $key => $val) {
            $fields[] = "$key = :$key";
            $params[":$key"] = $val;
        }
        $sql = "UPDATE limpieza_registros SET " . implode(", ", $fields) . " WHERE id = :id";
        return $this->pdo->prepare($sql)->execute($params);
    }

    public function guardarCambiosManuales(array $registros): bool {
        // Asegurar columnas (ignora error si ya existen)
        try {
            $this->pdo->exec("ALTER TABLE limpieza_registros ADD COLUMN pax_mark VARCHAR(255) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE limpieza_registros ADD COLUMN reservas_mark VARCHAR(255) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE limpieza_registros ADD COLUMN salidas_mark VARCHAR(255) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE limpieza_registros ADD COLUMN repasos_mark VARCHAR(255) DEFAULT NULL");
            $this->pdo->exec("ALTER TABLE limpieza_registros ADD COLUMN pendientes_mark VARCHAR(255) DEFAULT NULL");
        } catch (Exception $e) { }

        $this->pdo->beginTransaction();
        try {
            $sql = "UPDATE limpieza_registros 
                    SET pax_mark = :pax_mark,
                        reservas_mark = :reservas,
                        salidas_mark = :salidas,
                        repasos_mark = :repasos,
                        pendientes_mark = :pendientes
                    WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($registros as $r) {
                if (empty($r['id'])) continue; 
                
                $stmt->execute([
                    ':pax_mark'   => $r['pax_mark'] ?? null,
                    ':reservas'   => $r['reservas_mark'] ?? null,
                    ':salidas'    => $r['salidas_mark'] ?? null,
                    ':repasos'    => $r['repasos_mark'] ?? null,
                    ':pendientes' => $r['pendientes_mark'] ?? null,
                    ':id'         => $r['id']
                ]);
            }
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getCalculoPropuesta(string $fecha): array {
        // 1. Salidas (Check-out hoy)
        $sqlSalidas = "SELECT s.habitacion_id, h.numero as habitacion, 'salida' as tipo, 'alta' as prioridad, 
                              (SELECT c.nombre_razon_social FROM rooming_pax rp JOIN clientes c ON rp.cliente_id = c.id WHERE rp.stay_id = s.id AND rp.es_titular_acompanante=1 LIMIT 1) as titular,
                              s.fecha_checkout
                       FROM rooming_stays s
                       JOIN habitaciones h ON s.habitacion_id = h.id
                       WHERE s.estado IN ('activo', 'late_checkout', 'finalizado') AND s.fecha_checkout = ?";
        
        // 2. Reposos (Ocupadas pero no salen hoy)
        $sqlEstadias = "SELECT s.habitacion_id, h.numero as habitacion, 'reposo' as tipo, 'normal' as prioridad,
                               (SELECT c.nombre_razon_social FROM rooming_pax rp JOIN clientes c ON rp.cliente_id = c.id WHERE rp.stay_id = s.id AND rp.es_titular_acompanante=1 LIMIT 1) as titular,
                               s.fecha_checkout
                        FROM rooming_stays s
                        JOIN habitaciones h ON s.habitacion_id = h.id
                        WHERE s.estado IN ('activo', 'late_checkout', 'finalizado') AND DATE(s.fecha_checkout) > ? AND DATE(s.fecha_registro) <= ?";

        // 3. Programadas (Libres con checkin hoy o mañana)
        $sqlProgramadas = "SELECT s.habitacion_id, h.numero as habitacion, 'programada' as tipo, 'normal' as prioridad,
                                  (SELECT c.nombre_razon_social FROM rooming_pax rp JOIN clientes c ON rp.cliente_id = c.id WHERE rp.stay_id = s.id AND rp.es_titular_acompanante=1 LIMIT 1) as titular,
                                  s.fecha_registro
                           FROM rooming_stays s
                           JOIN habitaciones h ON s.habitacion_id = h.id
                           WHERE s.estado = 'reservado' AND DATE(s.fecha_registro) IN (?, DATE_ADD(?, INTERVAL 1 DAY))";

        // 4. Manuales (Limpieza o Mantenimiento manual)
        $sqlManuales = "SELECT h.id as habitacion_id, h.numero as habitacion, 
                               CASE WHEN h.estado = 'mantenimiento' THEN 'estimacion' ELSE 'salida' END as tipo,
                               CASE WHEN h.estado = 'mantenimiento' THEN 'alta' ELSE 'normal' END as prioridad,
                               'Registro Manual' as titular,
                               NULL as fecha_checkout
                        FROM habitaciones h
                        WHERE h.estado IN ('limpieza', 'sucio', 'mantenimiento')";

        // Ejecutar y unir
        $stmtS = $this->pdo->prepare($sqlSalidas); $stmtS->execute([$fecha]);
        $stmtE = $this->pdo->prepare($sqlEstadias); $stmtE->execute([$fecha, $fecha]);
        $stmtP = $this->pdo->prepare($sqlProgramadas); $stmtP->execute([$fecha, $fecha]);
        $stmtM = $this->pdo->prepare($sqlManuales); $stmtM->execute();

        $todos = array_merge(
            $stmtS->fetchAll(PDO::FETCH_ASSOC), 
            $stmtE->fetchAll(PDO::FETCH_ASSOC), 
            $stmtP->fetchAll(PDO::FETCH_ASSOC),
            $stmtM->fetchAll(PDO::FETCH_ASSOC)
        );

        // Deduplicar por habitacion_id
        $resultado = [];
        $idsVistos = [];
        foreach ($todos as $item) {
            $habId = $item['habitacion_id'];
            if (!in_array($habId, $idsVistos)) {
                $resultado[] = $item;
                $idsVistos[] = $habId;
            } else {
                if ($item['tipo'] === 'estimacion' || $item['tipo'] === 'salida') {
                    foreach ($resultado as $idx => $res) {
                        if ($res['habitacion_id'] == $habId) {
                            $resultado[$idx] = $item;
                            break;
                        }
                    }
                }
            }
        }

        return $resultado;
    }
}
