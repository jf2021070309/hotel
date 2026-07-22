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
                    c.nombre_razon_social, 
                    c.documento_tipo AS tipo_documento, 
                    c.documento_num AS numero_documento, 
                    c.celular,
                    rs.num_comprobante, rs.tipo_comprobante,
                    rs.total_pago, rs.moneda_pago, rs.fecha_registro, rs.estado
                FROM rooming_stays rs
                INNER JOIN rooming_pax rp ON rp.stay_id = rs.id AND rp.es_titular_acompanante = 1
                INNER JOIN clientes c ON c.id = rp.cliente_id
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
     * Reporte SUNAT (Excluye opción 'Ninguna' / 'NINGUNO' / '-')
     */
    public function sunat($desde, $hasta) {
        $sql = "SELECT 
                    IFNULL(c.nombre_razon_social, 'General') AS nombre_razon_social, 
                    IFNULL(c.documento_tipo, 'DNI') AS tipo_documento, 
                    IFNULL(c.documento_num, '-') AS numero_documento, 
                    c.celular,
                    rs.id AS stay_id,
                    rs.num_comprobante, 
                    rs.tipo_comprobante,
                    rs.total_pago, 
                    rs.moneda_pago, 
                    rs.fecha_registro, 
                    rs.estado,
                    h.numero AS habitacion_numero
                FROM rooming_stays rs
                LEFT JOIN rooming_pax rp ON rp.stay_id = rs.id AND rp.es_titular_acompanante = 1
                LEFT JOIN clientes c ON c.id = rp.cliente_id
                LEFT JOIN habitaciones h ON h.id = rs.habitacion_id
                WHERE rs.tipo_comprobante IS NOT NULL 
                  AND UPPER(rs.tipo_comprobante) NOT IN ('NINGUNO', 'NINGUNA', 'NINGUN', '-', '')
                  AND (rs.fecha_registro BETWEEN ? AND ?)
                ORDER BY rs.fecha_registro DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$desde, $hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Reporte de Corporativas Extranjeras
     */
    public function corporativasExtranjeras() {
        $sql = "SELECT 
                    c.nombre_razon_social AS empresa, c.nacionalidad, c.nombre_razon_social AS contacto_referencia,
                    c.celular, c.email,
                    COUNT(DISTINCT rs.id) AS total_estadias,
                    MAX(rs.fecha_registro) AS ultima_visita,
                    MIN(rs.fecha_registro) AS primera_visita
                FROM clientes c
                INNER JOIN rooming_pax rp ON rp.cliente_id = c.id
                INNER JOIN rooming_stays rs ON rp.stay_id = rs.id
                WHERE c.tipo_cliente = 'JURIDICO' OR c.documento_tipo = 'RUC'
                GROUP BY c.id, c.nombre_razon_social, c.nacionalidad, c.celular, c.email
                ORDER BY total_estadias DESC";
        
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Reporte de Pasajeros Extranjeros Recurrentes
     */
    public function extranjerosRecurrentes($minVisitas = 2) {
        $sql = "SELECT 
                    c.nombre_razon_social AS nombre_completo, c.documento_num AS pasaporte,
                    c.nacionalidad, c.celular, c.email,
                    COUNT(DISTINCT rs.id) AS total_visitas,
                    MIN(rs.fecha_registro) AS primera_visita,
                    MAX(rs.fecha_registro) AS ultima_visita
                FROM clientes c
                INNER JOIN rooming_pax rp ON rp.cliente_id = c.id AND rp.es_titular_acompanante = 1
                INNER JOIN rooming_stays rs ON rp.stay_id = rs.id
                GROUP BY c.id, c.documento_num, c.nacionalidad, c.nombre_razon_social, c.celular, c.email
                HAVING total_visitas >= ?
                ORDER BY total_visitas DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$minVisitas]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Reporte Sr. Mendoza
     */
    public function mendoza(int $mes, int $anio) {
        require_once __DIR__ . '/../Models/ReporteModel.php';
        $model = new ReporteModel($this->pdo);
        return [
            'data' => $model->getVentaHospedaje($mes, $anio),
            'consumos' => $model->getConsumosDetail($mes, $anio),
            'resumen' => $model->getResumenP_L($mes, $anio),
            'resumen_desglosado' => $model->getResumenDesglosado($mes, $anio),
            'egresos' => $model->getEgresosMendoza($mes, $anio)
        ];
    }
}
