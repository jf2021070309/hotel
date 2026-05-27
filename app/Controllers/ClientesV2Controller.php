<?php
/**
 * app/Controllers/ClientesV2Controller.php
 * Controlador para la grilla plana de Clientes V2
 */
class ClientesV2Controller {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        
        // MIGRACIÓN AUTOMÁTICA
        try {
            $this->pdo->exec("ALTER TABLE `clientes` ADD COLUMN `ruc` VARCHAR(20) DEFAULT NULL AFTER `documento_num`;");
        } catch (Exception $e) {}
        try {
            $this->pdo->exec("ALTER TABLE `clientes` ADD COLUMN `empresa` VARCHAR(255) DEFAULT NULL AFTER `ruc`;");
        } catch (Exception $e) {}
    }

    public function listar(): array {
        $sql = "SELECT 
                    id, 
                    documento_num as dni, 
                    nombre_razon_social as nombre, 
                    ruc, 
                    empresa, 
                    celular 
                FROM clientes 
                ORDER BY id DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardar(array $rows): array {
        $okCount = 0;
        foreach ($rows as $row) {
            $id = $row['id'] ?? null;
            $dni = $row['dni'] ?? '';
            $nombre = $row['nombre'] ?? '';
            $ruc = $row['ruc'] ?? '';
            $empresa = $row['empresa'] ?? '';
            $celular = $row['celular'] ?? '';

            if (empty($dni) && empty($nombre)) continue;

            if (!empty($id)) {
                // UPDATE
                $stmt = $this->pdo->prepare("UPDATE clientes SET 
                    documento_num = ?, 
                    nombre_razon_social = ?, 
                    ruc = ?, 
                    empresa = ?, 
                    celular = ?
                    WHERE id = ?");
                if ($stmt->execute([$dni, $nombre, $ruc, $empresa, $celular, $id])) {
                    $okCount++;
                }
            } else {
                // VERIFY IF DNI EXISTS
                $stmtCheck = $this->pdo->prepare("SELECT id FROM clientes WHERE documento_num = ? AND documento_tipo = 'DNI' LIMIT 1");
                $stmtCheck->execute([$dni]);
                $existing = $stmtCheck->fetch();

                if ($existing) {
                    $stmt = $this->pdo->prepare("UPDATE clientes SET 
                        nombre_razon_social = ?, 
                        ruc = ?, 
                        empresa = ?, 
                        celular = ?
                        WHERE id = ?");
                    if ($stmt->execute([$nombre, $ruc, $empresa, $celular, $existing['id']])) {
                        $okCount++;
                    }
                } else {
                    // INSERT
                    $stmt = $this->pdo->prepare("INSERT INTO clientes (documento_tipo, documento_num, nombre_razon_social, ruc, empresa, celular, tipo_cliente) VALUES ('DNI', ?, ?, ?, ?, ?, 'NATURAL')");
                    if ($stmt->execute([$dni, $nombre, $ruc, $empresa, $celular])) {
                        $okCount++;
                    }
                }
            }
        }
        return ['ok' => true, 'msg' => "$okCount registros guardados"];
    }

    public function eliminar(int $id): array {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM clientes WHERE id = ?");
            $stmt->execute([$id]);
            return ['ok' => true, 'msg' => "Cliente eliminado"];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => "Error al eliminar: " . $e->getMessage()];
        }
    }
}
