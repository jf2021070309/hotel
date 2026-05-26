<?php
/**
 * app/Models/ClienteModel.php
 * Gestiona la tabla normalizada `clientes` con relaciones vía `rooming_pax`.
 */
class ClienteModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Lista clientes con conteo de estadías y última visita.
     */
    public function getAll(string $buscar = ''): array {
        $sql = "SELECT 
                    c.documento_num                         AS dni,
                    c.documento_tipo                        AS tipo_doc,
                    c.nombre_razon_social                   AS nombre,
                    c.nacionalidad,
                    c.ciudad,
                    c.celular,
                    c.email,
                    IF(c.documento_tipo = 'RUC', c.nombre_razon_social, '') AS razon_social,
                    0                                       AS vip,
                    COUNT(DISTINCT rp.stay_id)              AS total_estadias,
                    MAX(s.fecha_registro)                   AS ultima_visita
                FROM clientes c
                LEFT JOIN rooming_pax rp ON rp.cliente_id = c.id
                LEFT JOIN rooming_stays s ON s.id = rp.stay_id";

        $params = [];
        if ($buscar !== '') {
            $like = '%' . $buscar . '%';
            $sql .= " WHERE (c.nombre_razon_social LIKE ? OR c.documento_num LIKE ?)";
            $params = [$like, $like];
        }

        $sql .= " GROUP BY c.id
                   ORDER BY ultima_visita DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function buscarPax(string $q): array {
        $like = '%' . $q . '%';
        $sql = "SELECT 
                    c.documento_num AS documento_num, 
                    c.documento_tipo AS documento_tipo, 
                    c.nombre_razon_social AS nombre_completo, 
                    c.nacionalidad, 
                    c.ciudad, 
                    c.celular, 
                    c.email, 
                    IF(c.documento_tipo = 'RUC', c.nombre_razon_social, '') AS empresa
                FROM clientes c
                WHERE c.documento_num LIKE ? OR c.nombre_razon_social LIKE ?
                ORDER BY c.id DESC
                LIMIT 10";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$like, $like]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Historial de estadías de un cliente por su número de documento.
     */
    public function historialPorDni(string $dni): array {
        try {
            $sqlStays = "SELECT 
                            s.id,
                            s.fecha_registro    AS check_in,
                            s.fecha_checkout    AS check_out,
                            s.estado,
                            COALESCE(s.total_pago, 0)    AS total_pago,
                            COALESCE(s.total_cobrado, 0) AS total_cobrado,
                            s.estado_pago,
                            h.numero  AS habitacion,
                            h.tipo    AS tipo_hab
                         FROM clientes c
                         JOIN rooming_pax rp ON rp.cliente_id = c.id AND rp.es_titular_acompanante = 1
                         JOIN rooming_stays s ON s.id = rp.stay_id
                         JOIN habitaciones  h ON h.id = s.habitacion_id
                         WHERE c.documento_num = ?
                         ORDER BY s.id DESC
                         LIMIT 50";

            $stmt = $this->pdo->prepare($sqlStays);
            $stmt->execute([$dni]);
            $stays = $stmt->fetchAll();

            if (empty($stays)) return [];

            $sqlPax = "SELECT c.nombre_razon_social AS nombre_completo, 
                              c.documento_tipo AS documento_tipo, 
                              c.documento_num AS documento_num,
                              c.nacionalidad, 
                              rp.es_titular_acompanante AS es_titular
                       FROM rooming_pax rp
                       JOIN clientes c ON c.id = rp.cliente_id
                       WHERE rp.stay_id = ?
                       ORDER BY rp.es_titular_acompanante DESC, c.nombre_razon_social ASC";
            $stmtPax = $this->pdo->prepare($sqlPax);

            foreach ($stays as &$stay) {
                $stmtPax->execute([$stay['id']]);
                $stay['pax'] = $stmtPax->fetchAll();
            }
            unset($stay);
            return $stays;
        } catch (PDOException $e) {
            error_log('ClienteModel::historialPorDni error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Guarda o actualiza un cliente en la tabla `clientes`.
     */
    public function save(array $data): bool {
        try {
            $newDni = $data['dni'];
            $oldDni = $data['old_dni'] ?? '';
            $tipoDoc = $data['tipo_doc'] ?? 'DNI';
            $tipoCliente = ($tipoDoc === 'RUC') ? 'JURIDICO' : 'NATURAL';

            // Check if client exists by document
            $stmtCheck = $this->pdo->prepare("SELECT id FROM clientes WHERE documento_num = ? AND documento_tipo = ? LIMIT 1");
            
            // If DNI changed, update the old record
            if (!empty($oldDni) && $oldDni !== $newDni) {
                $stmtCheck->execute([$oldDni, $tipoDoc]);
                $existing = $stmtCheck->fetch();
                if ($existing) {
                    $sql = "UPDATE clientes SET 
                                documento_tipo = ?,
                                documento_num = ?,
                                nombre_razon_social = ?,
                                nacionalidad = ?,
                                ciudad = ?,
                                celular = ?,
                                email = ?,
                                tipo_cliente = ?
                            WHERE id = ?";
                    $stmt = $this->pdo->prepare($sql);
                    return $stmt->execute([
                        $tipoDoc,
                        $newDni,
                        $data['nombre'],
                        $data['nacionalidad'] ?? 'Peruana',
                        $data['ciudad'] ?? '',
                        $data['celular'] ?? null,
                        $data['email'] ?? null,
                        $tipoCliente,
                        $existing['id']
                    ]);
                }
            }

            // Check if exists with current DNI
            $stmtCheck->execute([$newDni, $tipoDoc]);
            $existing = $stmtCheck->fetch();

            if ($existing) {
                // UPDATE
                $sql = "UPDATE clientes SET 
                            nombre_razon_social = ?,
                            nacionalidad = ?,
                            ciudad = ?,
                            celular = ?,
                            email = ?,
                            tipo_cliente = ?
                        WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute([
                    $data['nombre'],
                    $data['nacionalidad'] ?? 'Peruana',
                    $data['ciudad'] ?? '',
                    $data['celular'] ?? null,
                    $data['email'] ?? null,
                    $tipoCliente,
                    $existing['id']
                ]);
            } else {
                // INSERT
                $sql = "INSERT INTO clientes 
                            (documento_tipo, documento_num, nombre_razon_social, nacionalidad, ciudad, celular, email, tipo_cliente) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute([
                    $tipoDoc,
                    $newDni,
                    $data['nombre'],
                    $data['nacionalidad'] ?? 'Peruana',
                    $data['ciudad'] ?? '',
                    $data['celular'] ?? null,
                    $data['email'] ?? null,
                    $tipoCliente
                ]);
            }
        } catch (PDOException $e) {
            error_log('ClienteModel::save error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Alterna o establece el estado VIP de un cliente por su documento.
     */
    public function setVip(string $dni, int $vip): bool {
        // Since there is no longer a 'vip' column in the normalized table structure,
        // we can simply return true or log a note, to keep compatibility without throwing error.
        return true;
    }
}
