<?php
// ============================================================
// app/Models/RegistroModel.php
// ============================================================
class RegistroModel {
    private mysqli $db;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    /** Todos los registros (historial) */
    public function getAll(): array {
        $sql = "SELECT r.id, r.fecha_ingreso, r.fecha_salida, r.precio, r.estado,
                       h.numero hab_num, h.tipo hab_tipo,
                       c.nombre cliente, c.dni
                FROM registros r
                JOIN habitaciones h ON h.id = r.habitacion_id
                JOIN clientes     c ON c.id = r.cliente_id
                ORDER BY r.estado ASC, r.fecha_ingreso DESC";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    /** Solo registros activos (para salida y select de pagos) */
    public function getActivos(): array {
        $sql = "SELECT r.id, r.fecha_ingreso, r.precio,
                       h.numero hab_num, h.tipo hab_tipo, h.id hab_id,
                       c.nombre cliente, c.dni, c.telefono
                FROM registros r
                JOIN habitaciones h ON h.id = r.habitacion_id
                JOIN clientes     c ON c.id = r.cliente_id
                WHERE r.estado = 'activo'
                ORDER BY r.fecha_ingreso";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    /** Por ID */
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT r.*, h.numero hab_num, h.tipo hab_tipo, h.id hab_id,
                    c.nombre cliente, c.dni
             FROM registros r
             JOIN habitaciones h ON h.id = r.habitacion_id
             JOIN clientes     c ON c.id = r.cliente_id
             WHERE r.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /**
     * Check-in — inserta registro y cambia habitación a OCUPADO (transacción).
     * @return int ID del nuevo registro
     */
    public function checkin(array $d): int {
        $this->db->begin_transaction();
        try {
            // Verificar que la habitación siga libre
            $st = $this->db->prepare(
                "SELECT estado FROM habitaciones WHERE id = ? FOR UPDATE"
            );
            $st->bind_param('i', $d['habitacion_id']);
            $st->execute();
            $st->bind_result($estado);
            $st->fetch();
            $st->close();

            if ($estado !== 'libre') {
                throw new RuntimeException('La habitación ya no está disponible.');
            }

            // Insertar registro
            $st2 = $this->db->prepare(
                "INSERT INTO registros (habitacion_id, cliente_id, fecha_ingreso, precio)
                 VALUES (?,?,?,?)"
            );
            $st2->bind_param('iisd',
                $d['habitacion_id'], $d['cliente_id'],
                $d['fecha_ingreso'], $d['precio']
            );
            $st2->execute();
            $regId = $this->db->insert_id;
            $st2->close();

            // Marcar habitación como ocupada
            $st3 = $this->db->prepare(
                "UPDATE habitaciones SET estado='ocupado' WHERE id=?"
            );
            $st3->bind_param('i', $d['habitacion_id']);
            $st3->execute();
            $st3->close();

            $this->db->commit();
            return $regId;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Check-out — finaliza registro y libera habitación (transacción).
     */
    public function checkout(int $regId, string $fechaSalida): void {
        $this->db->begin_transaction();
        try {
            // Obtener habitacion_id
            $st = $this->db->prepare(
                "SELECT habitacion_id FROM registros WHERE id = ? AND estado = 'activo'"
            );
            $st->bind_param('i', $regId);
            $st->execute();
            $st->bind_result($habId);
            if (!$st->fetch()) {
                $st->close();
                throw new RuntimeException('Registro no encontrado o ya finalizado.');
            }
            $st->close();

            // Finalizar registro
            $st2 = $this->db->prepare(
                "UPDATE registros SET estado='finalizado', fecha_salida=?
                 WHERE id=? AND estado='activo'"
            );
            $st2->bind_param('si', $fechaSalida, $regId);
            $st2->execute();
            $st2->close();

            // Liberar habitación
            $st3 = $this->db->prepare(
                "UPDATE habitaciones SET estado='libre' WHERE id=?"
            );
            $st3->bind_param('i', $habId);
            $st3->execute();
            $st3->close();

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
