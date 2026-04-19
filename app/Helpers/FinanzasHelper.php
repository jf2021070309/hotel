<?php
/**
 * app/Helpers/FinanzasHelper.php
 * Centraliza la sincronización entre módulos operativos y el flujo de caja.
 */
class FinanzasHelper {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Determina el turno actual basado en la hora.
     * Mañana: 06:00 - 13:59
     * Tarde: 14:00 - 05:59
     */
    public static function getTurnoActual(): string {
        $hora = (int)date('H');
        return ($hora >= 6 && $hora < 14) ? 'MAÑANA' : 'TARDE';
    }

    /**
     * Busca el turno de flujo de caja activo para hoy y el usuario actual.
     */
    public function getFlujoIdActivo(int $usuarioId): ?int {
        $fechaHoy = date('Y-m-d');
        $turno = self::getTurnoActual();

        $stmt = $this->pdo->prepare("
            SELECT id FROM flujo_caja 
            WHERE fecha = ? AND turno = ? AND usuario_id = ? AND estado != 'borrador_eliminado'
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$fechaHoy, $turno, $usuarioId]);
        return $stmt->fetchColumn() ?: null;
    }

    /**
     * Registra un movimiento automático en el flujo de caja.
     * $data incluye: usuario_id, monto, moneda, medio_pago, categoria (opcional), observacion
     */
    public function registrarMovimientoAutomatico(array $data): bool {
        $flujoId = $data['flujo_id'] ?? $this->getFlujoIdActivo($data['usuario_id']);
        
        if (!$flujoId) return false; 

        $tipo = $data['tipo'] ?? 'Ingreso';
        $moneda = strtoupper($data['moneda'] ?? 'PEN');
        $medioTxt = strtoupper($data['medio_pago'] ?? 'EFECTIVO');
        
        $categoria = $data['categoria'] ?? '';
        $categoriaId = null;

        // Nuevos campos para rastreo de sobre físico
        $sFecha = !empty($data['sobre_fecha']) ? $data['sobre_fecha'] : null;
        $sTurno = !empty($data['sobre_turno']) ? $data['sobre_turno'] : null;

        // Si se pasó un nombre de categoría, intentar buscar su ID para mantener integridad
        // Si no se encuentra con el nombre exacto, intentamos buscar si coincide con un medio de pago común
        if (!empty($categoria)) {
            $stmtCat = $this->pdo->prepare("SELECT id FROM finanzas_categorias WHERE modulo = 'Flujo' AND nombre = ? AND activo = 1 LIMIT 1");
            $stmtCat->execute([$categoria]);
            $resCat = $stmtCat->fetch(PDO::FETCH_ASSOC);
            
            if ($resCat) {
                $categoriaId = $resCat['id'];
            } else {
                // Fallback: si el nombre es YAPE, PLIN, etc. intentar normalizarlo a la categoría de la BD
                $mapping = [
                    'YAPE' => 'YAPE O PLIN',
                    'PLIN' => 'YAPE O PLIN',
                    'EFECTIVO' => ($moneda === 'USD') ? 'DOLARES EFECTIVO' : 'SOLES EFECTIVO',
                    'POS' => ($moneda === 'USD') ? 'POS DOLARES' : 'POS SOLES',
                    'TRANSFERENCIA' => 'DEPOS/TRANS.',
                    'DEPOSITO' => 'DEPOS/TRANS.'
                ];
                $norm = strtoupper($categoria);
                if (isset($mapping[$norm])) {
                    $categoria = $mapping[$norm];
                    $stmtCat->execute([$categoria]);
                    $categoriaId = $stmtCat->fetchColumn() ?: null;
                }
            }
        }

        // Si después de lo anterior no hay categoría, buscar por tipo y medio (legacy matching)
        if (empty($categoriaId)) {
            $stmtCat = $this->pdo->prepare("SELECT id, nombre FROM finanzas_categorias WHERE modulo = 'Flujo' AND tipo = ? AND nombre = ? AND activo = 1 LIMIT 1");
            $stmtCat->execute([$tipo, $medioTxt]);
            $catBD = $stmtCat->fetch(PDO::FETCH_ASSOC);

            if ($catBD) {
                $categoria = $catBD['nombre'];
                $categoriaId = $catBD['id'];
            } else {
                // Fallback manual si no hay nada en la BD
                if (empty($categoria)) {
                    if ($tipo === 'Ingreso') {
                        if ($medioTxt === 'YAPE' || $medioTxt === 'PLIN' || strpos($medioTxt, 'YAPE') !== false) {
                            $categoria = 'YAPE O PLIN';
                        } elseif ($medioTxt === 'POS' || strpos($medioTxt, 'POS') !== false) {
                            $categoria = ($moneda === 'USD') ? 'POS DOLARES' : 'POS SOLES';
                        } elseif ($medioTxt === 'TRANSFERENCIA' || $medioTxt === 'DEPOSITO' || $medioTxt === 'TRANSF') {
                            $categoria = 'DEPOS/TRANS.';
                        } elseif ($medioTxt === 'EFECTIVO') {
                            if ($moneda === 'USD') $categoria = 'DOLARES EFECTIVO';
                            elseif ($moneda === 'CLP') $categoria = 'PESOS EFECTIVO';
                            else $categoria = 'SOLES EFECTIVO';
                        } else {
                            $categoria = 'OTROS INGRESOS';
                        }
                    } else {
                        $categoria = 'OTROS EGRESOS';
                    }
                    
                    // Intentar buscar ID una última vez con el nombre mapeado
                    $stmtCat = $this->pdo->prepare("SELECT id FROM finanzas_categorias WHERE modulo = 'Flujo' AND nombre = ? AND activo = 1 LIMIT 1");
                    $stmtCat->execute([$categoria]);
                    $categoriaId = $stmtCat->fetchColumn() ?: null;
                }
            }
        }

        $medioFinal = (strpos($medioTxt, 'EFECTIVO') !== false) ? 'EFECTIVO' : 'NO EFECTIVO';

        $sql = "INSERT INTO flujo_caja_movimientos 
                (flujo_id, categoria_id, categoria, tipo, moneda, monto, medio_pago, observacion, sobre_fecha, sobre_turno) 
                VALUES (:flujo_id, :cat_id, :categoria, :tipo, :moneda, :monto, :medio, :obs, :s_fecha, :s_turno)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':flujo_id'  => $flujoId,
            ':cat_id'    => $categoriaId,
            ':categoria' => $categoria,
            ':tipo'      => $tipo,
            ':moneda'    => $moneda,
            ':monto'     => $data['monto'],
            ':medio'     => $medioFinal,
            ':obs'       => $data['observacion'] ?? '',
            ':s_fecha'   => $sFecha,
            ':s_turno'   => $sTurno
        ]);
    }
}
