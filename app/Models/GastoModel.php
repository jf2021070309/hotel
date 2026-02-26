<?php
// ============================================================
// app/Models/GastoModel.php
// ============================================================
class GastoModel {
    private mysqli $db;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    /** Todos los gastos, opcionalmente filtrados por fecha */
    public function getAll(string $fecha = ''): array {
        if ($fecha !== '') {
            $stmt = $this->db->prepare(
                "SELECT * FROM gastos WHERE fecha = ? ORDER BY id DESC"
            );
            $stmt->bind_param('s', $fecha);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        }
        return $this->db->query(
            "SELECT * FROM gastos ORDER BY fecha DESC, id DESC"
        )->fetch_all(MYSQLI_ASSOC);
    }

    /** Crear gasto */
    public function create(array $d): int {
        $stmt = $this->db->prepare(
            "INSERT INTO gastos (descripcion, monto, fecha) VALUES (?,?,?)"
        );
        $stmt->bind_param('sds', $d['descripcion'], $d['monto'], $d['fecha']);
        $stmt->execute();
        $id = $this->db->insert_id;
        $stmt->close();
        return $id;
    }

    /** Eliminar gasto */
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM gastos WHERE id = ?");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
