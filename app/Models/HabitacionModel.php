<?php
/**
 * app/Models/HabitacionModel.php
 */
class HabitacionModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAll(): array {
        return $this->pdo->query("SELECT * FROM habitaciones ORDER BY numero ASC")->fetchAll();
    }

    public function getLibres(): array {
        return $this->pdo->query("SELECT * FROM habitaciones WHERE estado = 'libre' AND activa = 1 ORDER BY numero ASC")->fetchAll();
    }

    /**
     * Obtiene habitaciones que no tengan cruces de fechas con estadías activas o reservas.
     */
    public function getLibresParaFechas(string $fechaIn, string $fechaOut, ?int $excludeStayId = null): array {
        $sql = "SELECT h.* 
                FROM habitaciones h
                WHERE h.activa = 1 
                AND h.id NOT IN (
                    SELECT habitacion_id 
                    FROM rooming_stays 
                    WHERE estado IN ('activo', 'reservado', 'late_checkout')
                    AND fecha_registro < :fecha_out 
                    AND fecha_checkout > :fecha_in
                ";
        
        if ($excludeStayId) {
            $sql .= " AND id != :exclude_id ";
        }
        
        $sql .= ") ORDER BY h.numero ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $params = [
            'fecha_in'  => $fechaIn,
            'fecha_out' => $fechaOut
        ];
        if ($excludeStayId) {
            $params['exclude_id'] = $excludeStayId;
        }
        
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM habitaciones WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function actualizarEstado(int $id, string $estado): bool {
        $stmt = $this->pdo->prepare("UPDATE habitaciones SET estado = ? WHERE id = ?");
        $res = $stmt->execute([$estado, $id]);
        if ($res && $estado === 'libre') {
            $this->sincronizarLimpieza($id);
        }
        return $res;
    }

    public function crear(array $data): bool {
        $sql = "INSERT INTO habitaciones (numero, tipo, piso, precio_base, estado, activa) VALUES (?, ?, ?, ?, ?, 1)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['numero'], $data['tipo'], $data['piso'], $data['precio_base'], $data['estado'] ?? 'libre'
        ]);
    }

    public function actualizar(int $id, array $data): bool {
        $sql = "UPDATE habitaciones SET numero = ?, tipo = ?, piso = ?, precio_base = ?, estado = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $res = $stmt->execute([
            $data['numero'], $data['tipo'], $data['piso'], $data['precio_base'], $data['estado'], $id
        ]);
        if ($res && $data['estado'] === 'libre') {
            $this->sincronizarLimpieza($id);
        }
        return $res;
    }

    /**
     * Si la habitación se pone LIBRE manualmente, sincroniza el log de limpieza para que no bloquee el check-in.
     */
    private function sincronizarLimpieza(int $habId): void {
        $fecha = date('Y-m-d');
        $stmt = $this->pdo->prepare("UPDATE limpieza_registros SET estado = 'lista' WHERE habitacion_id = ? AND fecha = ? AND estado != 'lista'");
        $stmt->execute([$habId, $fecha]);
    }
}
