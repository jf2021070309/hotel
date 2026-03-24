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
        $stmt = $this->pdo->prepare("SELECT * FROM desayunos_detalle WHERE desayuno_id = ? ORDER BY habitacion ASC");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOcupacionActual(string $fecha): array {
        // Consultar rooming_stays activos para la fecha calculada
        $sql = "SELECT s.id as checkin_id, h.numero as habitacion, h.id as habitacion_id,
                       (SELECT nombre_completo FROM rooming_pax WHERE stay_id = s.id AND es_titular = 1 LIMIT 1) as titular,
                       s.pax_total as pax
                FROM rooming_stays s
                JOIN habitaciones h ON s.habitacion_id = h.id
                WHERE s.estado IN ('activo', 'late_checkout')
                  AND s.fecha_registro <= :f1
                  AND s.fecha_checkout > :f2";
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
            $stmtDet = $this->pdo->prepare("INSERT INTO desayunos_detalle (desayuno_id, habitacion_id, habitacion, titular, pax, incluye_desayuno) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($detalles as $det) {
                $stmtDet->execute([
                    $id,
                    $det['habitacion_id'],
                    $det['habitacion'],
                    $det['titular'] ?? '---',
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
}
