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
     */
    public function registrarMovimientoAutomatico(array $data): bool {
        $flujoId = $data['flujo_id'] ?? $this->getFlujoIdActivo($data['usuario_id']);
        
        if (!$flujoId) return false; // No hay turno abierto para registrar

        $medio = (strtoupper($data['medio_pago'] ?? '') === 'EFECTIVO') ? 'EFECTIVO' : 'NO EFECTIVO';
        $tipo  = $data['tipo'] ?? 'Ingreso';

        $sql = "INSERT INTO flujo_caja_movimientos 
                (flujo_id, categoria, tipo, moneda, monto, medio_pago, observacion) 
                VALUES (:flujo_id, :categoria, :tipo, :moneda, :monto, :medio, :obs)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':flujo_id'  => $flujoId,
            ':categoria' => $data['categoria'] ?? 'Operación Automática',
            ':tipo'      => $tipo,
            ':moneda'    => $data['moneda'] ?? 'PEN',
            ':monto'     => $data['monto'],
            ':medio'     => $medio,
            ':obs'       => $data['observacion'] ?? ''
        ]);
    }
}
