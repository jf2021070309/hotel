<?php
/**
 * app/Models/ClienteModel.php
 * Clientes = titulares de rooming_pax (es_titular = 1), sin duplicados por documento
 */
class ClienteModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Lista únicos titulares (agrupa por documento_num para evitar duplicados)
     */
    public function getAll(string $buscar = ''): array {
        $sql = "SELECT 
                    p.documento_num                         AS dni,
                    MAX(p.documento_tipo)                   AS tipo_doc,
                    MAX(p.nombre_completo)                  AS nombre,
                    MAX(p.nacionalidad)                     AS nacionalidad,
                    MAX(p.ciudad)                           AS ciudad,
                    MAX(p.celular)                          AS celular,
                    MAX(p.email)                            AS email,
                    MAX(p.ruc)                              AS ruc,
                    MAX(p.empresa)                          AS razon_social,
                    COUNT(DISTINCT p.stay_id)               AS total_estadias,
                    MAX(p.vip)                              AS vip,
                    MAX(p.created_at)                       AS ultima_visita
                FROM rooming_pax p
                WHERE p.es_titular = 1";

        $params = [];
        if ($buscar !== '') {
            $like = '%' . $buscar . '%';
            $sql .= " AND (p.nombre_completo LIKE ? OR p.documento_num LIKE ? OR p.ruc LIKE ? OR p.empresa LIKE ?)";
            $params = [$like, $like, $like, $like];
        }

        $sql .= " GROUP BY p.documento_num
                  ORDER BY ultima_visita DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function buscarPax(string $q): array {
        $like = '%' . $q . '%';
        $sql = "SELECT 
                    p.documento_num, 
                    MAX(p.documento_tipo)   AS documento_tipo, 
                    MAX(p.nombre_completo)  AS nombre_completo, 
                    MAX(p.nacionalidad)     AS nacionalidad, 
                    MAX(p.ciudad)           AS ciudad, 
                    MAX(p.celular)          AS celular, 
                    MAX(p.email)            AS email, 
                    MAX(p.empresa)          AS empresa, 
                    MAX(p.es_corporativo)   AS es_corporativo, 
                    MAX(p.ruc)              AS ruc,
                    MAX(s.ruc_factura)      AS ruc_factura, 
                    MAX(s.razon_social)     AS razon_social
                FROM rooming_pax p
                LEFT JOIN rooming_stays s ON p.stay_id = s.id
                WHERE p.documento_num LIKE ? OR p.nombre_completo LIKE ?
                GROUP BY p.documento_num
                ORDER BY MAX(COALESCE(p.stay_id, 0)) DESC, MAX(p.id) DESC
                LIMIT 10";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$like, $like]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Historial de estadías de un titular por su documento_num
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
                         FROM rooming_pax p
                         JOIN rooming_stays s ON s.id = p.stay_id
                         JOIN habitaciones  h ON h.id = s.habitacion_id
                         WHERE p.es_titular = 1
                           AND p.documento_num = ?
                         ORDER BY s.id DESC
                         LIMIT 50";

            $stmt = $this->pdo->prepare($sqlStays);
            $stmt->execute([$dni]);
            $stays = $stmt->fetchAll();

            if (empty($stays)) return [];

            $sqlPax = "SELECT nombre_completo, documento_tipo, documento_num,
                               nacionalidad, es_titular
                       FROM rooming_pax
                       WHERE stay_id = ?
                       ORDER BY es_titular DESC, nombre_completo ASC";
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
     * Guarda un nuevo cliente manualmente en la tabla rooming_pax.
     */
    public function save(array $data): bool {
        try {
            // Check if record exists for this DNI with NULL stay_id
            $checkSql = "SELECT id FROM rooming_pax WHERE documento_num = ? AND stay_id IS NULL LIMIT 1";
            $stmtCheck = $this->pdo->prepare($checkSql);
            $stmtCheck->execute([$data['dni']]);
            $existing = $stmtCheck->fetch();

            $esCorp = (!empty($data['es_empresa'])) ? 1 : 0;
            $ruc = $esCorp ? ($data['ruc'] ?? '') : '';
            $empresa = $esCorp ? ($data['razon_social'] ?? '') : '';
            $vip = (!empty($data['vip'])) ? 1 : 0;

            if ($existing) {
                // UPDATE
                $sql = "UPDATE rooming_pax SET 
                            documento_tipo = ?,
                            ruc = ?,
                            nombre_completo = ?,
                            nacionalidad = ?,
                            ciudad = ?,
                            celular = ?,
                            email = ?,
                            empresa = ?,
                            es_corporativo = ?,
                            vip = ?
                        WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute([
                    $data['tipo_doc'],
                    $ruc,
                    $data['nombre'],
                    $data['nacionalidad'],
                    $data['ciudad'] ?? '',
                    $data['celular'],
                    $data['email'] ?? '',
                    $empresa,
                    $esCorp,
                    $vip,
                    $existing['id']
                ]);
            } else {
                // INSERT
                $sql = "INSERT INTO rooming_pax 
                            (stay_id, documento_tipo, documento_num, ruc, nombre_completo, nacionalidad, ciudad, celular, email, empresa, es_titular, es_corporativo, vip) 
                        VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute([
                    $data['tipo_doc'],
                    $data['dni'],
                    $ruc,
                    $data['nombre'],
                    $data['nacionalidad'],
                    $data['ciudad'] ?? '',
                    $data['celular'],
                    $data['email'] ?? '',
                    $empresa,
                    $esCorp,
                    $vip
                ]);
            }
        } catch (PDOException $e) {
            error_log('ClienteModel::save error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Alterna o establece el estado VIP/Estrella de un titular por su DNI.
     */
    public function setVip(string $dni, int $vip): bool {
        try {
            $sql = "UPDATE rooming_pax SET vip = ? WHERE documento_num = ? AND es_titular = 1";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$vip, $dni]);
        } catch (PDOException $e) {
            error_log('ClienteModel::setVip error: ' . $e->getMessage());
            return false;
        }
    }
}
