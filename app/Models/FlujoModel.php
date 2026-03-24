<?php
/**
 * app/Models/FlujoModel.php
 */
class FlujoModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getCategorias(): array {
        $stmt = $this->pdo->query("SELECT id, tipo, nombre FROM finanzas_categorias WHERE modulo='Flujo' AND activo=1 ORDER BY tipo, orden, nombre");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listar(array $filtros): array {
        $where = ["1=1"];
        $params = [];

        if (!empty($filtros['mes']) && !empty($filtros['anio'])) {
            $where[] = "MONTH(f.fecha) = :mes AND YEAR(f.fecha) = :anio";
            $params[':mes']  = $filtros['mes'];
            $params[':anio'] = $filtros['anio'];
        }

        if (!empty($filtros['estado']) && $filtros['estado'] !== 'todos') {
            $where[] = "f.estado = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        $sqlWhere = implode(" AND ", $where);

        $sql = "
            SELECT 
                f.id, f.fecha, f.turno, f.estado, f.nota_entrega,
                u.nombre AS operador,
                COALESCE(SUM(CASE WHEN m.tipo='Ingreso' THEN 
                    (CASE WHEN m.moneda='USD' THEN m.monto * COALESCE((SELECT factor FROM tipos_cambio WHERE fecha=f.fecha AND moneda_origen='USD' LIMIT 1), 3.7)
                          WHEN m.moneda='CLP' THEN m.monto * COALESCE((SELECT factor FROM tipos_cambio WHERE fecha=f.fecha AND moneda_origen='CLP' LIMIT 1), 0.0039)
                          ELSE m.monto END) ELSE 0 END), 0) AS total_ingresos,
                COALESCE(SUM(CASE WHEN m.tipo='Egreso' THEN 
                    (CASE WHEN m.moneda='USD' THEN m.monto * COALESCE((SELECT factor FROM tipos_cambio WHERE fecha=f.fecha AND moneda_origen='USD' LIMIT 1), 3.7)
                          WHEN m.moneda='CLP' THEN m.monto * COALESCE((SELECT factor FROM tipos_cambio WHERE fecha=f.fecha AND moneda_origen='CLP' LIMIT 1), 0.0039)
                          ELSE m.monto END) ELSE 0 END), 0) AS total_egresos,
                COALESCE(SUM(CASE WHEN m.medio_pago='EFECTIVO' AND m.tipo='Ingreso' THEN m.monto ELSE 0 END), 0) 
              - COALESCE(SUM(CASE WHEN m.medio_pago='EFECTIVO' AND m.tipo='Egreso'  THEN m.monto ELSE 0 END), 0) AS efectivo_sobre
            FROM flujo_caja f
            LEFT JOIN usuarios u ON f.usuario_id = u.id
            LEFT JOIN flujo_caja_movimientos m ON f.id = m.flujo_id
            WHERE $sqlWhere
            GROUP BY f.id
            ORDER BY f.fecha DESC, f.turno ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDetalle(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT f.*, u.nombre AS operador FROM flujo_caja f LEFT JOIN usuarios u ON f.usuario_id = u.id WHERE f.id = ?");
        $stmt->execute([$id]);
        $flujo = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$flujo) return null;

        $stmtMovs = $this->pdo->prepare("SELECT * FROM flujo_caja_movimientos WHERE flujo_id = ? ORDER BY id ASC");
        $stmtMovs->execute([$id]);
        $movs = $stmtMovs->fetchAll(PDO::FETCH_ASSOC);

        $flujo['ingresos'] = array_values(array_filter($movs, fn($m) => $m['tipo'] === 'Ingreso'));
        $flujo['egresos']  = array_values(array_filter($movs, fn($m) => $m['tipo'] === 'Egreso'));

        // Extraer TC del día
        $stmtTC = $this->pdo->prepare("SELECT moneda_origen, factor FROM tipos_cambio WHERE fecha = ?");
        $stmtTC->execute([$flujo['fecha']]);
        $tcData = $stmtTC->fetchAll(PDO::FETCH_ASSOC);
        $tc = ['USD' => 3.7, 'CLP' => 0.0039]; // Fallbacks
        foreach($tcData as $row) { $tc[$row['moneda_origen']] = (float)$row['factor']; }
        $flujo['tc'] = $tc;

        return $flujo;
    }

    public function checkExisteTurno(string $fecha, string $turno, int $excludeId = 0): bool {
        $stmt = $this->pdo->prepare("SELECT id FROM flujo_caja WHERE fecha = ? AND turno = ? AND id != ?");
        $stmt->execute([$fecha, $turno, $excludeId]);
        return (bool)$stmt->fetchColumn();
    }

    public function guardar(array $data, array $ingresos, array $egresos): int {
        $id = (int)($data['id'] ?? 0);
        
        $this->pdo->beginTransaction();
        try {
            if ($id > 0) {
                // Update
                $stmt = $this->pdo->prepare("UPDATE flujo_caja SET nota_entrega = ? WHERE id = ?");
                $stmt->execute([$data['nota_entrega'], $id]);
            } else {
                // Insert
                $stmt = $this->pdo->prepare("INSERT INTO flujo_caja (fecha, turno, estado, nota_entrega, usuario_id) VALUES (?, ?, 'borrador', ?, ?)");
                $stmt->execute([$data['fecha'], $data['turno'], $data['nota_entrega'], $data['usuario_id']]);
                $id = (int)$this->pdo->lastInsertId();

                $stmtResp = $this->pdo->prepare("INSERT INTO flujo_caja_responsables (flujo_id, usuario_id, rol_turno) VALUES (?, ?, ?)");
                $stmtResp->execute([$id, $data['usuario_id'], 'Operador Principal']);
            }

            // Clear old movements and insert fresh
            $this->pdo->prepare("DELETE FROM flujo_caja_movimientos WHERE flujo_id = ?")->execute([$id]);

            $stmtMov = $this->pdo->prepare("INSERT INTO flujo_caja_movimientos (flujo_id, categoria_id, categoria, tipo, moneda, monto, medio_pago, observacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            $movs = array_merge($ingresos, $egresos);
            foreach ($movs as $mov) {
                // Ignore empty rows
                if (empty($mov['categoria']) || empty($mov['monto']) || $mov['monto'] <= 0) continue;
                
                $catId = !empty($mov['categoria_id']) ? $mov['categoria_id'] : null;
                $stmtMov->execute([
                    $id, 
                    $catId, 
                    $mov['categoria'], 
                    $mov['tipo'], 
                    $mov['moneda'], 
                    $mov['monto'], 
                    $mov['medio_pago'], 
                    $mov['observacion'] ?? ''
                ]);
            }

            $this->pdo->commit();
            return $id;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function cambiarEstado(int $id, string $estado): bool {
        $stmt = $this->pdo->prepare("UPDATE flujo_caja SET estado = ? WHERE id = ?");
        return $stmt->execute([$estado, $id]);
    }

    public function getResumenDia(string $fecha): array {
        $sql = "
            SELECT 
                f.turno,
                COALESCE(SUM(CASE WHEN m.tipo='Ingreso' THEN 
                    (CASE WHEN m.moneda='USD' THEN m.monto * COALESCE((SELECT factor FROM tipos_cambio WHERE fecha=f.fecha AND moneda_origen='USD' LIMIT 1), 3.7)
                          WHEN m.moneda='CLP' THEN m.monto * COALESCE((SELECT factor FROM tipos_cambio WHERE fecha=f.fecha AND moneda_origen='CLP' LIMIT 1), 0.0039)
                          ELSE m.monto END) ELSE 0 END), 0) AS total_ingresos,
                COALESCE(SUM(CASE WHEN m.tipo='Egreso' THEN 
                    (CASE WHEN m.moneda='USD' THEN m.monto * COALESCE((SELECT factor FROM tipos_cambio WHERE fecha=f.fecha AND moneda_origen='USD' LIMIT 1), 3.7)
                          WHEN m.moneda='CLP' THEN m.monto * COALESCE((SELECT factor FROM tipos_cambio WHERE fecha=f.fecha AND moneda_origen='CLP' LIMIT 1), 0.0039)
                          ELSE m.monto END) ELSE 0 END), 0) AS total_egresos,
                COALESCE(SUM(CASE WHEN m.medio_pago='EFECTIVO' AND m.tipo='Ingreso' AND m.moneda='PEN' THEN m.monto ELSE 0 END), 0) 
              - COALESCE(SUM(CASE WHEN m.medio_pago='EFECTIVO' AND m.tipo='Egreso'  AND m.moneda='PEN' THEN m.monto ELSE 0 END), 0) AS efectivo_pen,
                COALESCE(SUM(CASE WHEN m.medio_pago='EFECTIVO' AND m.tipo='Ingreso' AND m.moneda='USD' THEN m.monto ELSE 0 END), 0) 
              - COALESCE(SUM(CASE WHEN m.medio_pago='EFECTIVO' AND m.tipo='Egreso'  AND m.moneda='USD' THEN m.monto ELSE 0 END), 0) AS efectivo_usd,
                COALESCE(SUM(CASE WHEN m.medio_pago='EFECTIVO' AND m.tipo='Ingreso' AND m.moneda='CLP' THEN m.monto ELSE 0 END), 0) 
              - COALESCE(SUM(CASE WHEN m.medio_pago='EFECTIVO' AND m.tipo='Egreso'  AND m.moneda='CLP' THEN m.monto ELSE 0 END), 0) AS efectivo_clp
            FROM flujo_caja f
            LEFT JOIN flujo_caja_movimientos m ON f.id = m.flujo_id
            WHERE f.fecha = :fecha AND f.estado != 'borrador'
            GROUP BY f.turno
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':fecha' => $fecha]);
        $turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resumen = [
            'fecha' => $fecha,
            'turnos' => $turnos,
            'total_dia_ingresos' => 0,
            'total_dia_egresos'  => 0,
            'efectivo_pen' => 0,
            'efectivo_usd' => 0,
            'efectivo_clp' => 0
        ];

        foreach ($turnos as $t) {
            $resumen['total_dia_ingresos'] += $t['total_ingresos'];
            $resumen['total_dia_egresos']  += $t['total_egresos'];
            $resumen['efectivo_pen'] += $t['efectivo_pen'];
            $resumen['efectivo_usd'] += $t['efectivo_usd'];
            $resumen['efectivo_clp'] += $t['efectivo_clp'];
        }

        return $resumen;
    }
}
