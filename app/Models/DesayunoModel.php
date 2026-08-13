<?php
/**
 * app/Models/DesayunoModel.php
 */
class DesayunoModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function listar(array $filtros = []): array {
        $where = ["1=1"];
        $params = [];

        if (!empty($filtros['mes']) && !empty($filtros['anio'])) {
            $where[] = "MONTH(fecha) = :mes AND YEAR(fecha) = :anio";
            $params[':mes'] = $filtros['mes'];
            $params[':anio'] = $filtros['anio'];
        }

        $sqlWhere = implode(" AND ", $where);
        $stmt = $this->pdo->prepare("SELECT * FROM desayunos WHERE $sqlWhere ORDER BY fecha DESC");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPorFecha(string $fecha): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM desayunos WHERE fecha = ?");
        $stmt->execute([$fecha]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getDetalle(int $id): array {
        $stmt = $this->pdo->prepare(
            "SELECT dd.*, h.numero AS habitacion, h.id AS habitacion_id,
                    (SELECT c.nombre_razon_social FROM rooming_pax rp JOIN clientes c ON rp.cliente_id = c.id WHERE rp.stay_id = dd.stay_id AND rp.es_titular_acompanante = 1 LIMIT 1) AS titular
             FROM desayunos_detalle dd
             JOIN rooming_stays rs ON rs.id = dd.stay_id
             JOIN habitaciones h ON h.id = rs.habitacion_id
             WHERE dd.desayuno_id = ?
             ORDER BY h.numero ASC"
        );
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOcupacionActual(string $fecha): array {
        // Huéspedes activos para la fecha: check-in <= fecha Y checkout >= fecha
        $sql = "SELECT s.id as checkin_id, h.numero as habitacion, h.id as habitacion_id, s.id as stay_id,
                       COALESCE((SELECT c.nombre_razon_social FROM rooming_pax rp JOIN clientes c ON rp.cliente_id = c.id WHERE rp.stay_id = s.id AND rp.es_titular_acompanante = 1 LIMIT 1), '---') as titular,
                       s.pax_total as pax
                FROM rooming_stays s
                JOIN habitaciones h ON s.habitacion_id = h.id
                WHERE s.estado IN ('activo', 'late_checkout', 'finalizado')
                  AND DATE(s.fecha_registro) < :f1
                  AND DATE(s.fecha_checkout) >= :f2";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':f1' => $fecha, ':f2' => $fecha]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardar(array $data, array $detalles): int {
        $this->pdo->beginTransaction();
        try {
            if (!empty($data['id'])) {
                // Update
                $stmt = $this->pdo->prepare("UPDATE desayunos SET pax_calculado = ?, pax_ajustado = ?, observacion = ?, usuario_id = ? WHERE id = ?");
                $stmt->execute([$data['pax_calculado'], $data['pax_ajustado'], $data['observacion'], $data['usuario_id'], $data['id']]);
                $id = (int)$data['id'];
                // Limpiar detalles antiguos
                $this->pdo->prepare("DELETE FROM desayunos_detalle WHERE desayuno_id = ?")->execute([$id]);
            } else {
                // Insert
                $stmt = $this->pdo->prepare("INSERT INTO desayunos (fecha, pax_calculado, pax_ajustado, observacion, usuario_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$data['fecha'], $data['pax_calculado'], $data['pax_ajustado'], $data['observacion'], $data['usuario_id']]);
                $id = (int)$this->pdo->lastInsertId();
            }

            // Insertar detalles
            $stmtDet = $this->pdo->prepare("INSERT INTO desayunos_detalle (desayuno_id, stay_id, pax, incluye_desayuno) VALUES (?, ?, ?, ?)");
            foreach ($detalles as $det) {
                $stmtDet->execute([
                    $id,
                    $det['stay_id'] ?? $det['checkin_id'] ?? null,
                    $det['pax'],
                    ($det['incluye_desayuno'] ? 1 : 0)
                ]);
            }

            $this->pdo->commit();
            return $id;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    public function getDetalleManualDia(string $fecha): array {
        $sql = "SELECT h.numero as habitacion, h.id as habitacion_id, h.tipo as tipo_hab, h.estado as room_estado,
                       dm.id, dm.pax, dm.observaciones
                FROM habitaciones h
                LEFT JOIN desayunos_manual dm ON h.id = dm.habitacion_id AND dm.fecha = :fecha
                ORDER BY h.numero ASC";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':fecha' => $fecha]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Table might not exist yet
            $stmt = $this->pdo->prepare("SELECT numero as habitacion, id as habitacion_id, tipo as tipo_hab, estado as room_estado FROM habitaciones ORDER BY numero ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function guardarCambiosManuales(array $registros, string $fecha): bool {
        // Create table if not exists
        try {
            $sqlCreate = "CREATE TABLE IF NOT EXISTS desayunos_manual (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            fecha DATE NOT NULL,
                            habitacion_id INT NOT NULL,
                            pax VARCHAR(255) DEFAULT NULL,
                            observaciones VARCHAR(255) DEFAULT NULL,
                            UNIQUE KEY unq_fecha_hab (fecha, habitacion_id)
                          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $this->pdo->exec($sqlCreate);
        } catch (Exception $e) {
            // Ignore error
        }

        $this->pdo->beginTransaction();
        try {
            $sql = "INSERT INTO desayunos_manual 
                    (fecha, habitacion_id, pax, observaciones)
                    VALUES (:fecha, :hab_id, :pax, :observaciones)
                    ON DUPLICATE KEY UPDATE
                    pax = VALUES(pax),
                    observaciones = VALUES(observaciones)";
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($registros as $r) {
                if (empty($r['id']) && empty($r['pax']) && empty($r['observaciones'])) {
                    continue; 
                }
                
                $stmt->execute([
                    ':fecha'         => $fecha,
                    ':hab_id'        => $r['habitacion_id'],
                    ':pax'           => $r['pax'] ?? null,
                    ':observaciones' => $r['observaciones'] ?? null
                ]);
            }
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
