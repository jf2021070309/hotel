<?php
// ============================================================
// app/Models/HabitacionModel.php
// ============================================================
class HabitacionModel {
    private mysqli $db;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    /** Todas las habitaciones con datos del huésped activo */
    public function getAll(): array {
        $sql = "SELECT h.id, h.numero, h.tipo, h.piso, h.estado, h.precio_base,
                       c.nombre  AS cliente,
                       r.precio  AS precio_actual,
                       r.id      AS registro_id,
                       r.fecha_ingreso
                FROM habitaciones h
                LEFT JOIN registros r ON r.habitacion_id = h.id AND r.estado = 'activo'
                LEFT JOIN clientes  c ON c.id = r.cliente_id
                ORDER BY h.piso, h.numero";
        $res = $this->db->query($sql);
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    /** Solo habitaciones libres (para select de check-in) */
    public function getLibres(): array {
        $res = $this->db->query(
            "SELECT id, numero, tipo, piso, precio_base
             FROM habitaciones WHERE estado = 'libre'
             ORDER BY numero"
        );
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    /** Una habitación por ID */
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM habitaciones WHERE id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /** Crear habitación */
    public function create(array $d): int|false {
        $stmt = $this->db->prepare(
            "INSERT INTO habitaciones (numero, tipo, piso, precio_base) VALUES (?,?,?,?)"
        );
        $stmt->bind_param('ssis', $d['numero'], $d['tipo'], $d['piso'], $d['precio_base']);
        $ok = $stmt->execute();
        $id = $ok ? $this->db->insert_id : false;
        $stmt->close();
        return $id;
    }

    /** Actualizar habitación */
    public function update(int $id, array $d): bool {
        $stmt = $this->db->prepare(
            "UPDATE habitaciones SET numero=?, tipo=?, piso=?, precio_base=? WHERE id=?"
        );
        $stmt->bind_param('ssidi', $d['numero'], $d['tipo'], $d['piso'], $d['precio_base'], $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /** Cambiar estado (libre/ocupado) — usado internamente por RegistroModel */
    public function setEstado(int $id, string $estado): bool {
        $stmt = $this->db->prepare("UPDATE habitaciones SET estado=? WHERE id=?");
        $stmt->bind_param('si', $estado, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
