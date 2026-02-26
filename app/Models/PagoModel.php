<?php
// ============================================================
// app/Models/PagoModel.php
// ============================================================
class PagoModel {
    private mysqli $db;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    /** Todos los pagos con datos de habitación y cliente */
    public function getAll(): array {
        $sql = "SELECT p.id, p.monto, p.metodo, p.fecha,
                       h.numero hab_num,
                       c.nombre cliente,
                       r.id registro_id
                FROM pagos p
                JOIN registros    r ON r.id  = p.registro_id
                JOIN habitaciones h ON h.id  = r.habitacion_id
                JOIN clientes     c ON c.id  = r.cliente_id
                ORDER BY p.fecha DESC, p.id DESC";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    /** Crear pago */
    public function create(array $d): int {
        $stmt = $this->db->prepare(
            "INSERT INTO pagos (registro_id, monto, metodo, fecha) VALUES (?,?,?,?)"
        );
        $stmt->bind_param('idss',
            $d['registro_id'], $d['monto'], $d['metodo'], $d['fecha']
        );
        $stmt->execute();
        $id = $this->db->insert_id;
        $stmt->close();
        return $id;
    }
}
