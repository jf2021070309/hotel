<?php
/**
 * app/Models/YapeModel.php
 */
class YapeModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function listar(array $filtros): array {
        $where = ["1=1"];
        $params = [];

        if (!empty($filtros['mes']) && !empty($filtros['anio'])) {
            $where[] = "MONTH(y.fecha) = :mes AND YEAR(y.fecha) = :anio";
            $params[':mes']  = $filtros['mes'];
            $params[':anio'] = $filtros['anio'];
        }

        $sqlWhere = implode(" AND ", $where);

        $sql = "
            SELECT 
                y.id, y.fecha, y.turno, y.yape_recibido, y.total_gastado, y.vuelto, y.estado,
                u.nombre AS operador
            FROM gastos_yape y
            LEFT JOIN usuarios u ON y.usuario_id = u.id
            WHERE $sqlWhere
            ORDER BY y.fecha DESC, y.turno ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDetalle(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT y.*, u.nombre AS operador FROM gastos_yape y LEFT JOIN usuarios u ON y.usuario_id = u.id WHERE y.id = ?");
        $stmt->execute([$id]);
        $yape = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$yape) return null;

        $stmtDet = $this->pdo->prepare("SELECT * FROM gastos_yape_detalle WHERE gasto_yape_id = ? ORDER BY id ASC");
        $stmtDet->execute([$id]);
        $yape['detalles'] = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

        return $yape;
    }

    public function verificarUnico(string $fecha, string $turno, int $excludeId = 0): bool {
        $stmt = $this->pdo->prepare("SELECT id FROM gastos_yape WHERE fecha = ? AND turno = ? AND id != ?");
        $stmt->execute([$fecha, $turno, $excludeId]);
        return (bool)$stmt->fetchColumn();
    }

    public function guardar(array $data, array $detalles): int {
        $id = (int)($data['id'] ?? 0);
        
        $this->pdo->beginTransaction();
        try {
            if ($id > 0) {
                // Update
                $stmt = $this->pdo->prepare("UPDATE gastos_yape SET yape_recibido = ?, total_gastado = ?, vuelto = ?, observacion = ? WHERE id = ?");
                $stmt->execute([
                    $data['yape_recibido'],
                    $data['total_gastado'],
                    $data['vuelto'],
                    $data['observacion'],
                    $id
                ]);
            } else {
                // Insert
                $stmt = $this->pdo->prepare("INSERT INTO gastos_yape (fecha, turno, yape_recibido, total_gastado, vuelto, observacion, estado, usuario_id) VALUES (?, ?, ?, ?, ?, ?, 'borrador', ?)");
                $stmt->execute([
                    $data['fecha'],
                    $data['turno'],
                    $data['yape_recibido'],
                    $data['total_gastado'],
                    $data['vuelto'],
                    $data['observacion'],
                    $data['usuario_id']
                ]);
                $id = (int)$this->pdo->lastInsertId();
            }

            // Clear old details and insert fresh ones
            $this->pdo->prepare("DELETE FROM gastos_yape_detalle WHERE gasto_yape_id = ?")->execute([$id]);

            $stmtDet = $this->pdo->prepare("INSERT INTO gastos_yape_detalle (gasto_yape_id, categoria_id, rubro, monto, observacion, documento) VALUES (?, ?, ?, ?, ?, ?)");
            
            foreach ($detalles as $det) {
                if (empty($det['rubro']) || empty($det['monto']) || $det['monto'] <= 0) continue;
                
                $catId = !empty($det['categoria_id']) ? $det['categoria_id'] : null;
                $stmtDet->execute([
                    $id, 
                    $catId, 
                    $det['rubro'], 
                    $det['monto'], 
                    $det['observacion'] ?? '',
                    $det['documento'] ?? ''
                ]);
            }

            $this->pdo->commit();
            return $id;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function ejecutarTransaccionCierre(callable $callback) {
        $this->pdo->beginTransaction();
        try {
            $result = $callback($this->pdo);
            $this->pdo->commit();
            return $result;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function cambiarEstado(int $id, string $estado): bool {
        $stmt = $this->pdo->prepare("UPDATE gastos_yape SET estado = ? WHERE id = ?");
        return $stmt->execute([$estado, $id]);
    }
}
