<?php
// ============================================================
// app/Models/ClienteModel.php
// ============================================================
class ClienteModel {
    private mysqli $db;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    /** Lista todos (o filtra por nombre/DNI) */
    public function getAll(string $buscar = ''): array {
        if ($buscar !== '') {
            $like = '%' . $buscar . '%';
            $stmt = $this->db->prepare(
                "SELECT * FROM clientes WHERE nombre LIKE ? OR dni LIKE ? ORDER BY nombre"
            );
            $stmt->bind_param('ss', $like, $like);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        }
        return $this->db->query(
            "SELECT * FROM clientes ORDER BY nombre"
        )->fetch_all(MYSQLI_ASSOC);
    }

    /** Por ID */
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM clientes WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /**
     * Crear cliente. Retorna el ID o, si DNI ya existe, el ID del existente.
     */
    public function create(array $d): array {
        $stmt = $this->db->prepare(
            "INSERT INTO clientes (nombre, dni, telefono) VALUES (?,?,?)"
        );
        $stmt->bind_param('sss', $d['nombre'], $d['dni'], $d['telefono']);
        $ok = $stmt->execute();

        if ($ok) {
            $id = $this->db->insert_id;
            $stmt->close();
            return ['id' => $id, 'duplicado' => false];
        }

        // DNI duplicado (errno 1062) → buscar id existente
        if ($this->db->errno === 1062) {
            $stmt->close();
            $st2 = $this->db->prepare("SELECT id FROM clientes WHERE dni = ?");
            $st2->bind_param('s', $d['dni']);
            $st2->execute();
            $st2->bind_result($existingId);
            $st2->fetch();
            $st2->close();
            return ['id' => $existingId, 'duplicado' => true];
        }

        $stmt->close();
        throw new RuntimeException('Error al crear cliente: ' . $this->db->error);
    }
}
