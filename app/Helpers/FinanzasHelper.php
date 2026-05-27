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
     * Determina el turno actual sugerido basado en la hora.
     * Mañana: 06:00 - 13:59
     * Tarde: 14:00 - 05:59
     */
    public static function getTurnoActual(?int $horaManual = null): string {
        $hora = $horaManual ?? (int)date('H');
        return ($hora >= 6 && $hora < 14) ? 'MAÑANA' : 'TARDE';
    }

    /**
     * Busca el turno de flujo de caja activo (borrador).
     * Horario abierto: Prioriza el turno abierto del usuario actual. Si no tiene uno,
     * busca cualquier otra caja abierta (borrador) sin restricción estricta de hora.
     */
    public function getFlujoIdActivo(int $usuarioId): ?int {
        // 1. Buscar si el usuario actual tiene un turno abierto (estado = 'borrador')
        $stmt = $this->pdo->prepare("
            SELECT id FROM flujo_caja 
            WHERE usuario_id = ? AND estado = 'borrador'
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$usuarioId]);
        $id = $stmt->fetchColumn();

        if ($id) return (int)$id;

        // 2. Fallback: Si no tiene turno propio, buscar si hay algún otro turno abierto en general
        $stmtAll = $this->pdo->prepare("
            SELECT id FROM flujo_caja 
            WHERE estado = 'borrador'
            ORDER BY id DESC LIMIT 1
        ");
        $stmtAll->execute();
        $idGen = $stmtAll->fetchColumn();

        return $idGen ? (int)$idGen : null;
    }

    /**
     * Registra un movimiento automático en el flujo de caja.
     * $data incluye: usuario_id, monto, moneda, medio_pago, categoria (opcional), observacion
     */
    public function registrarMovimientoAutomatico(array $data): bool {
        $flujoId = $data['flujo_id'] ?? null;
        
        if (!$flujoId) {
            $fechaTrans = $data['fecha'] ?? null;
            $turnoTrans = $data['turno'] ?? null;
            
            if ($fechaTrans && $turnoTrans) {
                // Buscar si existe un turno de caja para esta fecha y turno
                $stmt = $this->pdo->prepare("SELECT id FROM flujo_caja WHERE fecha = ? AND turno = ? LIMIT 1");
                $stmt->execute([$fechaTrans, $turnoTrans]);
                $flujoId = $stmt->fetchColumn() ?: null;
                
                if (!$flujoId) {
                    // Si no existe, lo creamos automáticamente (en estado 'borrador')
                    $stmtInsert = $this->pdo->prepare("
                        INSERT INTO flujo_caja (fecha, turno, estado, usuario_id, nota_entrega)
                        VALUES (?, ?, 'borrador', ?, 'Apertura automática desde Rooming V2')
                    ");
                    $stmtInsert->execute([$fechaTrans, $turnoTrans, $data['usuario_id'] ?? 1]);
                    $flujoId = (int)$this->pdo->lastInsertId();
                }
            } else {
                // Fallback clásico al flujo activo de la cajera
                $flujoId = $this->getFlujoIdActivo($data['usuario_id'] ?? 1);
                
                if (!$flujoId) {
                    // Si aun así no hay ninguna caja activa, creamos una para hoy automáticamente
                    $fechaTrans = date('Y-m-d');
                    $turnoTrans = self::getTurnoActual();
                    
                    $stmt = $this->pdo->prepare("SELECT id FROM flujo_caja WHERE fecha = ? AND turno = ? LIMIT 1");
                    $stmt->execute([$fechaTrans, $turnoTrans]);
                    $flujoId = $stmt->fetchColumn() ?: null;
                    
                    if (!$flujoId) {
                        $stmtInsert = $this->pdo->prepare("
                            INSERT INTO flujo_caja (fecha, turno, estado, usuario_id, nota_entrega)
                            VALUES (?, ?, 'borrador', ?, 'Apertura automática de emergencia')
                        ");
                        $stmtInsert->execute([$fechaTrans, $turnoTrans, $data['usuario_id'] ?? 1]);
                        $flujoId = (int)$this->pdo->lastInsertId();
                    }
                }
            }
        }
        
        if (!$flujoId) {
            error_log("FINANZAS_DEBUG: No se pudo resolver flujo para registrar movimiento.");
            return false; 
        }

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

        $medioFinal = 'EFECTIVO';
        if (strpos($medioTxt, 'YAPE') !== false) {
            $medioFinal = 'YAPE_CAJA';
        } elseif (strpos($medioTxt, 'PLIN') !== false) {
            $medioFinal = 'PLIN';
        } elseif (strpos($medioTxt, 'POS_SOLES') !== false) {
            $medioFinal = 'POS_SOLES';
        } elseif (strpos($medioTxt, 'POS_DOLARES') !== false) {
            $medioFinal = 'POS_DOLARES';
        } elseif (strpos($medioTxt, 'POS') !== false) {
            $medioFinal = ($moneda === 'USD') ? 'POS_DOLARES' : 'POS_SOLES';
        } elseif (strpos($medioTxt, 'TRANSFERENCIA') !== false || strpos($medioTxt, 'DEPOSITO') !== false || strpos($medioTxt, 'NO EFECTIVO') !== false) {
            $medioFinal = 'TRANSFERENCIA';
        }

        $stayId = $data['stay_id'] ?? null;

        $sql = "INSERT INTO flujo_caja_movimientos 
                (flujo_id, categoria_id, stay_id, tipo, moneda, monto, medio_pago, observacion) 
                VALUES (:flujo_id, :cat_id, :stay_id, :tipo, :moneda, :monto, :medio, :obs)";
        
        $stmt = $this->pdo->prepare($sql);
        $montoFinal = (float)($data['monto'] ?? 0);
        return $stmt->execute([
            ':flujo_id'  => $flujoId,
            ':cat_id'    => $categoriaId,
            ':stay_id'   => $stayId,
            ':tipo'      => $tipo,
            ':moneda'    => $moneda,
            ':monto'     => $montoFinal,
            ':medio'     => $medioFinal,
            ':obs'       => $data['observacion'] ?? ''
        ]);
    }
}
