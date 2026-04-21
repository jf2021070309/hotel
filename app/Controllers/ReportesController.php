<?php
/**
 * app/Controllers/ReportesController.php
 */
class ReportesController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Reporte de Solicitudes de Factura
     */
    public function facturas($desde, $hasta, $estado = null) {
        $sql = "SELECT 
                    rp.nombre_completo, rp.documento_tipo, rp.documento_num, rp.celular,
                    rs.ruc_factura, rs.razon_social, rs.num_comprobante, rs.tipo_comprobante,
                    rs.total_pago, rs.moneda_pago, rs.fecha_registro, rs.estado
                FROM rooming_stays rs
                INNER JOIN rooming_pax rp ON rp.stay_id = rs.id AND rp.es_titular = 1
                WHERE rs.tipo_comprobante = 'FACTURA'
                  AND (rs.fecha_registro BETWEEN ? AND ?)";
        
        $params = [$desde, $hasta];
        if ($estado) {
            $sql .= " AND rs.estado = ?";
            $params[] = $estado;
        }
        $sql .= " ORDER BY rs.fecha_registro DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Reporte de Corporativas Extranjeras
     */
    public function corporativasExtranjeras() {
        $sql = "SELECT 
                    rp.empresa, rp.pais_origen, rp.nombre_completo AS contacto_referencia,
                    rp.celular, rp.email,
                    COUNT(DISTINCT rs.id) AS total_estadias,
                    MAX(rs.fecha_registro) AS ultima_visita,
                    MIN(rs.fecha_registro) AS primera_visita
                FROM rooming_pax rp
                INNER JOIN rooming_stays rs ON rp.stay_id = rs.id
                WHERE rp.es_corporativo = 1
                  AND rp.nacionalidad != 'Peruana'
                  AND rp.empresa IS NOT NULL
                GROUP BY rp.empresa, rp.pais_origen
                ORDER BY total_estadias DESC";
        
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Reporte de Pasajeros Extranjeros Recurrentes
     */
    public function extranjerosRecurrentes($minVisitas = 2) {
        $sql = "SELECT 
                    rp.nombre_completo, rp.documento_num AS pasaporte, rp.pais_origen,
                    rp.nacionalidad, rp.celular, rp.email,
                    COUNT(DISTINCT rs.id) AS total_visitas,
                    MIN(rs.fecha_registro) AS primera_visita,
                    MAX(rs.fecha_registro) AS ultima_visita
                FROM rooming_pax rp
                INNER JOIN rooming_stays rs ON rp.stay_id = rs.id
                WHERE rp.nacionalidad != 'Peruana'
                  AND rp.es_corporativo = 0
                  AND rp.es_titular = 1
                GROUP BY rp.documento_num
                HAVING total_visitas >= ?
                ORDER BY total_visitas DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$minVisitas]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
