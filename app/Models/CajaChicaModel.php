require_once __DIR__ . '/../Helpers/FinanzasHelper.php';

class CajaChicaModel {
    private PDO $pdo;
    private FinanzasHelper $finanzas;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->finanzas = new FinanzasHelper($pdo);
    }

    public function getCategorias(): array {
        $stmt = $this->pdo->query("SELECT id, tipo, nombre FROM finanzas_categorias WHERE modulo='C.Chica' AND activo=1 ORDER BY orden, nombre");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCicloActivo(): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM caja_chica WHERE estado = 'abierta' ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $ciclo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ciclo) return null;

        $stmtMovs = $this->pdo->prepare("SELECT m.*, u.nombre AS operador FROM caja_chica_movimientos m LEFT JOIN usuarios u ON m.usuario_id = u.id WHERE m.caja_id = ? ORDER BY m.id DESC");
        $stmtMovs->execute([$ciclo['id']]);
        $ciclo['movimientos'] = $stmtMovs->fetchAll(PDO::FETCH_ASSOC);

        // Calcular saldo actual
        $gastado = 0;
        foreach ($ciclo['movimientos'] as $m) {
            if ($m['tipo'] === 'egreso' && !$m['anulado']) {
                $gastado += (float)$m['monto'];
            }
        }
        $ciclo['total_gastado'] = $gastado;
        $ciclo['saldo_actual']  = (float)$ciclo['saldo_inicial'] - $gastado;

        return $ciclo;
    }

    public function listarCiclos(): array {
        $sql = "
            SELECT 
                c.id, c.nombre, c.saldo_inicial, c.saldo_final, 
                c.fecha_apertura, c.fecha_cierre, c.estado,
                COALESCE((SELECT SUM(monto) FROM caja_chica_movimientos WHERE caja_id=c.id AND tipo='egreso' AND anulado=0), 0) AS total_gastado,
                u1.nombre AS usuario_apertura,
                u2.nombre AS usuario_cierre
            FROM caja_chica c
            LEFT JOIN usuarios u1 ON c.usuario_apertura = u1.id
            LEFT JOIN usuarios u2 ON c.usuario_cierre = u2.id
            ORDER BY c.id DESC
        ";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function abrirCiclo(string $nombre, float $saldoInicial, int $usuarioId): int {
        $stmt = $this->pdo->prepare("INSERT INTO caja_chica (nombre, saldo_inicial, fecha_apertura, estado, usuario_apertura) VALUES (?, ?, CURDATE(), 'abierta', ?)");
        $stmt->execute([$nombre, $saldoInicial, $usuarioId]);
        $id = (int)$this->pdo->lastInsertId();

        // SINCRONIZACIÓN: La apertura de caja chica es un EGRESO del flujo principal
        $this->finanzas->registrarMovimientoAutomatico([
            'usuario_id'  => $usuarioId,
            'categoria'   => 'Reposición Caja Chica',
            'tipo'        => 'Egreso',
            'monto'       => $saldoInicial,
            'moneda'      => 'PEN',
            'medio_pago'  => 'EFECTIVO',
            'observacion' => "Apertura Ciclo #$id: $nombre"
        ]);

        return $id;
    }

    public function registrarGasto(array $data): int {
        return $this->registrarMovimiento($data, 'egreso');
    }

    public function registrarIngreso(array $data): int {
        return $this->registrarMovimiento($data, 'ingreso');
    }

    private function registrarMovimiento(array $data, string $tipo): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO caja_chica_movimientos 
            (caja_id, tipo, monto, rubro, documento, fecha, observacion, usuario_id) 
            VALUES (?, ?, ?, ?, ?, CURDATE(), ?, ?)
        ");
        $stmt->execute([
            $data['caja_id'],
            $tipo,
            $data['monto'],
            $data['rubro'],
            $data['documento'] ?? '',
            $data['observacion'] ?? '',
            $data['usuario_id']
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function anularGasto(int $movId, string $motivo, int $userId): bool {
        $stmt = $this->pdo->prepare("UPDATE caja_chica_movimientos SET anulado = 1, motivo_anulacion = ?, usuario_id = ? WHERE id = ?");
        return $stmt->execute([$motivo, $userId, $movId]);
    }

    public function cerrarCiclo(int $cajaId, float $saldoFinal, int $usuarioId): bool {
        $stmt = $this->pdo->prepare("UPDATE caja_chica SET estado = 'cerrada', fecha_cierre = CURDATE(), saldo_final = ?, usuario_cierre = ? WHERE id = ?");
        return $stmt->execute([$saldoFinal, $usuarioId, $cajaId]);
    }

    // Helper to run transaction across Flujo and CajaChica
    public function ejecutarTransaccionCierreRepocision(callable $callback) {
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
}
