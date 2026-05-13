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
                    p.documento_tipo                        AS tipo_doc,
                    p.nombre_completo                       AS nombre,
                    p.nacionalidad,
                    p.ciudad,
                    p.celular,
                    p.ruc,
                    p.empresa                               AS razon_social,
                    COUNT(DISTINCT p.stay_id)               AS total_estadias,
                    MAX(p.created_at)                       AS ultima_visita
                FROM rooming_pax p
                WHERE p.es_titular = 1 AND p.stay_id IS NULL";

        $params = [];
        if ($buscar !== '') {
            $like = '%' . $buscar . '%';
            $sql .= " AND (p.nombre_completo LIKE ? OR p.documento_num LIKE ? OR p.ruc LIKE ? OR p.empresa LIKE ?)";
            $params = [$like, $like, $like, $like];
        }

        $sql .= " GROUP BY p.documento_num, p.documento_tipo, p.nombre_completo, p.nacionalidad, p.ciudad, p.celular, p.ruc, p.empresa
                  ORDER BY ultima_visita DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function buscarPax(string $q): array {
        $like = '%' . $q . '%';
        $sql = "SELECT 
                    p.documento_num, p.documento_tipo, p.nombre_completo, 
                    p.nacionalidad, p.ciudad, p.celular, 
                    p.email, p.empresa, p.es_corporativo, p.ruc,
                    s.ruc_factura, s.razon_social
                FROM rooming_pax p
                LEFT JOIN rooming_stays s ON p.stay_id = s.id
                WHERE p.documento_num LIKE ? OR p.nombre_completo LIKE ?
                GROUP BY p.documento_num, p.documento_tipo, p.nombre_completo, p.nacionalidad, p.ciudad, p.celular, p.email, p.empresa, p.es_corporativo, p.ruc, s.ruc_factura, s.razon_social
                ORDER BY MAX(p.stay_id) DESC
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
            // 1. Evitar duplicados por DNI
            $checkDni = $this->pdo->prepare("SELECT id FROM rooming_pax WHERE documento_num = ? AND stay_id IS NULL LIMIT 1");
            $checkDni->execute([$data['dni']]);
            if ($checkDni->fetch()) {
                return false; // Ya existe por DNI
            }

            // 2. Evitar duplicados por RUC (si es empresa)
            if ($data['es_empresa'] && !empty($data['ruc'])) {
                $checkRuc = $this->pdo->prepare("SELECT id FROM rooming_pax WHERE ruc = ? AND stay_id IS NULL LIMIT 1");
                $checkRuc->execute([$data['ruc']]);
                if ($checkRuc->fetch()) {
                    return false; // Ya existe por RUC
                }
            }

            $sql = "INSERT INTO rooming_pax 
                        (stay_id, documento_tipo, documento_num, ruc, nombre_completo, nacionalidad, celular, empresa, es_titular, es_corporativo) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)";
            
            $esCorp = $data['es_empresa'] ? 1 : 0;
            $ruc = $data['es_empresa'] ? ($data['ruc'] ?? '') : '';
            $empresa = $data['es_empresa'] ? ($data['razon_social'] ?? '') : '';
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                null, // NULL stay_id for manual registration
                $data['tipo_doc'],
                $data['dni'],
                $ruc,
                $data['nombre'],
                $data['nacionalidad'],
                $data['celular'],
                $empresa,
                $esCorp
            ]);
        } catch (PDOException $e) {
            error_log('ClienteModel::save error: ' . $e->getMessage());
            return false;
        }
    }
}
