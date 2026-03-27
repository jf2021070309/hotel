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
     * Muestra el último nombre registrado y cuenta cuántas estadías tiene.
     */
    public function getAll(string $buscar = ''): array {
        $sql = "SELECT 
                    p.documento_num                         AS dni,
                    p.documento_tipo                        AS tipo_doc,
                    p.nombre_completo                       AS nombre,
                    p.nacionalidad,
                    p.ciudad,
                    COUNT(DISTINCT p.stay_id)               AS total_estadias,
                    MAX(p.created_at)                       AS ultima_visita
                FROM rooming_pax p
                WHERE p.es_titular = 1";

        $params = [];
        if ($buscar !== '') {
            $like = '%' . $buscar . '%';
            $sql .= " AND (p.nombre_completo LIKE ? OR p.documento_num LIKE ?)";
            $params = [$like, $like];
        }

        $sql .= " GROUP BY p.documento_num, p.documento_tipo, p.nombre_completo, p.nacionalidad, p.ciudad
                  ORDER BY p.nombre_completo ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Historial de estadías de un titular por su documento_num
     */
    public function historialPorDni(string $dni): array {
        try {
            // 1. Estadías donde este DNI es titular
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

            // 2. Para cada estadía cargar TODOS los pax (titular + acompañantes)
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
}
