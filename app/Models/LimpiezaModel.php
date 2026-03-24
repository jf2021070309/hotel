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
        $stmt = $this->pdo->prepare("SELECT * FROM limpieza_registros WHERE fecha = ? ORDER BY prioridad ASC, habitacion ASC");
        $stmt->execute([$fecha]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            $sql = "INSERT IGNORE INTO limpieza_registros (fecha, habitacion_id, habitacion, tipo_limpieza, prioridad, usuario_id) 
                    VALUES (:fecha, :hab_id, :hab, :tipo, :prioridad, :uid)";
            $stmt = $this->pdo->prepare($sql);
            foreach ($registros as $r) {
                $stmt->execute([
                    ':fecha'     => $r['fecha'],
                    ':hab_id'    => $r['habitacion_id'],
                    ':hab'       => $r['habitacion'],
                    ':tipo'      => $r['tipo_limpieza'],
                    ':prioridad' => $r['prioridad'],
                    ':uid'       => $r['usuario_id']
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

    public function getCalculoPropuesta(string $fecha): array {
        // 1. Salidas (Check-out hoy)
        $sqlSalidas = "SELECT s.habitacion_id, h.numero as habitacion, 'salida' as tipo, 'alta' as prioridad, 
                              (SELECT nombre_completo FROM rooming_pax WHERE stay_id = s.id AND es_titular=1 LIMIT 1) as titular,
                              s.fecha_checkout
                       FROM rooming_stays s
                       JOIN habitaciones h ON s.habitacion_id = h.id
                       WHERE s.estado IN ('activo', 'late_checkout') AND s.fecha_checkout = ?";
        
        // 2. Estadías (Ocupadas pero no salen hoy)
        $sqlEstadias = "SELECT s.habitacion_id, h.numero as habitacion, 'estadía' as tipo, 'normal' as prioridad,
                               (SELECT nombre_completo FROM rooming_pax WHERE stay_id = s.id AND es_titular=1 LIMIT 1) as titular,
                               s.fecha_checkout
                        FROM rooming_stays s
                        JOIN habitaciones h ON s.habitacion_id = h.id
                        WHERE s.estado IN ('activo', 'late_checkout') AND s.fecha_checkout > ? AND s.fecha_registro <= ?";

        // 3. Programadas (Libres con checkin hoy o mañana)
        $sqlProgramadas = "SELECT s.habitacion_id, h.numero as habitacion, 'programada' as tipo, 'normal' as prioridad,
                                  (SELECT nombre_completo FROM rooming_pax WHERE stay_id = s.id AND es_titular=1 LIMIT 1) as titular,
                                  s.fecha_registro
                           FROM rooming_stays s
                           JOIN habitaciones h ON s.habitacion_id = h.id
                           WHERE s.estado = 'reserva' AND s.fecha_registro IN (?, DATE_ADD(?, INTERVAL 1 DAY))";

        // Ejecutar y unir
        $stmtS = $this->pdo->prepare($sqlSalidas); $stmtS->execute([$fecha]);
        $stmtE = $this->pdo->prepare($sqlEstadias); $stmtE->execute([$fecha, $fecha]);
        $stmtP = $this->pdo->prepare($sqlProgramadas); $stmtP->execute([$fecha, $fecha]);

        return array_merge($stmtS->fetchAll(PDO::FETCH_ASSOC), $stmtE->fetchAll(PDO::FETCH_ASSOC), $stmtP->fetchAll(PDO::FETCH_ASSOC));
    }
}
