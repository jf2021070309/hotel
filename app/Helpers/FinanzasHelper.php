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
     * Busca el turno de flujo de caja activo para hoy y el usuario actual.
     */
    public function getFlujoIdActivo(int $usuarioId): ?int {
        $fechaHoy = date('Y-m-d');
        // El turno depende de la hora actual: Mañana (6am-2pm), Tarde (2pm-10pm)
        $hora = (int)date('H');
        $turno = ($hora >= 6 && $hora < 14) ? 'MAÑANA' : 'TARDE';

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
        
        // Mapeo dinámico: Intentar buscar si el medio_pago ya coincide con una categoría activa
        $stmtCat = $this->pdo->prepare("SELECT nombre FROM finanzas_categorias WHERE modulo = 'Flujo' AND tipo = 'Ingreso' AND nombre = ? AND activo = 1 LIMIT 1");
        $stmtCat->execute([$medioTxt]);
        $categoriaBD = $stmtCat->fetchColumn();

        if ($categoriaBD) {
            $categoria = $categoriaBD;
        } else if ($tipo === 'Ingreso') {
            // Legacy / Fallback mapping
            if ($medioTxt === 'YAPE' || $medioTxt === 'PLIN' || strpos($medioTxt, 'YAPE') !== false) {
                $categoria = 'YAPE O PLIN';
            } elseif ($medioTxt === 'POS' || strpos($medioTxt, 'POS') !== false) {
                $categoria = ($moneda === 'USD') ? 'POS DOLARES' : 'POS SOLES';
            } elseif ($medioTxt === 'TRANSFERENCIA' || $medioTxt === 'DEPOSITO' || $medioTxt === 'TRANSF') {
                $categoria = 'DEPOS/TRANS.'; // Nombre exacto en SQL original
            } elseif ($medioTxt === 'EFECTIVO') {
                if ($moneda === 'USD') $categoria = 'DOLARES EFECTIVO';
                elseif ($moneda === 'CLP') $categoria = 'PESOS EFECTIVO';
                else $categoria = 'SOLES EFECTIVO';
            } else {
                $categoria = 'OTROS INGRESOS';
            }
        }

        $medioFinal = ($medioTxt === 'EFECTIVO') ? 'EFECTIVO' : 'NO EFECTIVO';

        $sql = "INSERT INTO flujo_caja_movimientos 
                (flujo_id, categoria, tipo, moneda, monto, medio_pago, observacion) 
                VALUES (:flujo_id, :categoria, :tipo, :moneda, :monto, :medio, :obs)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':flujo_id'  => $flujoId,
            ':categoria' => $categoria,
            ':tipo'      => $tipo,
            ':moneda'    => $moneda,
            ':monto'     => $data['monto'],
            ':medio'     => $medioFinal,
            ':obs'       => $data['observacion'] ?? ''
        ]);
    }
}
