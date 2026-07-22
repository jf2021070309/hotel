<?php
/**
 * app/Models/ReporteModel.php
 */
class ReporteModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }


    public function asegurarTablaVouchers(): void {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `rooming_vouchers` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `referencia_tipo` VARCHAR(30) NOT NULL,
              `referencia_id` INT NOT NULL,
              `comprobante_b64` LONGTEXT NOT NULL,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              UNIQUE KEY `uk_ref` (`referencia_tipo`, `referencia_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    public function guardarVoucherB64(string $tipo, int $id, string $b64): bool {
        $this->asegurarTablaVouchers();
        $sql = "INSERT INTO rooming_vouchers (referencia_tipo, referencia_id, comprobante_b64)
                VALUES (:tipo, :id, :b64)
                ON DUPLICATE KEY UPDATE comprobante_b64 = :b64_upd";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':tipo' => $tipo,
            ':id' => $id,
            ':b64' => $b64,
            ':b64_upd' => $b64
        ]);
    }

    /**
     * Reporte Sr. Mendoza: Venta Detallada por Habitación
     * Incluye todos los pagos (Efectivo, POS, Yape) asociados a estadías del mes.
     */
    public function getVentaHospedaje(int $mes, int $anio): array {
        $this->asegurarTablaVouchers();
        $sql = "
            SELECT 
                a.id AS pago_id,
                a.stay_id,
                h.numero AS habitacion,
                h.tipo AS tipo_hab,
                s.pax_total AS pax,
                s.fecha_registro AS check_in,
                s.fecha_checkout AS check_out,
                DATEDIFF(s.fecha_checkout, s.fecha_registro) AS noches,
                s.medio_reserva AS canal,
                a.fecha AS pago_fecha,
                CASE 
                    -- Sincronizado con FinanzasHelper::getTurnoActual()
                    WHEN HOUR(COALESCE(s.fecha_checkin_real, a.created_at)) >= 6 AND HOUR(COALESCE(s.fecha_checkin_real, a.created_at)) < 14 THEN 'MAÑANA' 
                    ELSE 'TARDE' 
                END AS turno,
                a.moneda,
                a.monto,
                a.tipo_pago,
                CASE 
                    WHEN a.tipo_pago IN ('TRANSFERENCIA', 'DEPOSITO', 'TRANSF', 'DEPOS/TRANS.') THEN 'TRANSFER.'
                    WHEN a.tipo_pago IN ('YAPE', 'PLIN', 'YAPE O PLIN', 'YAPE/PLIN') THEN 'YAPE/PLIN'
                    WHEN a.tipo_pago LIKE '%POS%' AND a.moneda = 'USD' THEN 'POS $'
                    WHEN a.tipo_pago LIKE '%POS%' AND a.moneda = 'PEN' THEN 'POS S/'
                    WHEN a.tipo_pago LIKE '%POS%' AND a.moneda = 'CLP' THEN 'POS CLP'
                    WHEN a.tipo_pago LIKE '%EFECTIVO%' AND a.moneda = 'CLP' THEN 'EFEC CLP'
                    WHEN a.tipo_pago LIKE '%EFECTIVO%' AND a.moneda = 'USD' THEN 'EFEC $'
                    WHEN a.tipo_pago LIKE '%EFECTIVO%' AND a.moneda = 'PEN' THEN 'EFEC S/'
                    ELSE a.tipo_pago
                END AS medio_label,
                a.monto AS total_fila,
                CONCAT(s.tipo_comprobante, ' ', IFNULL(s.num_comprobante, '')) AS comprobante,
                v.comprobante_b64
            FROM anticipos a
            JOIN rooming_stays s ON a.stay_id = s.id
            JOIN habitaciones h ON s.habitacion_id = h.id
            LEFT JOIN rooming_vouchers v ON (v.referencia_tipo = 'hospedaje' AND v.referencia_id = a.id)
            WHERE MONTH(a.fecha) = :mes AND YEAR(a.fecha) = :anio
              AND s.estado != 'anulado'
            ORDER BY a.fecha DESC, turno DESC, h.piso, h.numero
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':mes' => $mes, ':anio' => $anio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Resumen por Moneda y Método (Mendoza Footer)
     * Consolidado de Hospedaje (anticipos) + Consumos Extras (rooming_consumos)
     */
    public function getResumenDesglosado(int $mes, int $anio): array {
        $res = [
            'POS' => ['PEN' => 0, 'USD' => 0, 'CLP' => 0],
            'EFECTIVO' => ['PEN' => 0, 'USD' => 0, 'CLP' => 0],
            'YAPE' => 0,
            'TRANSFERENCIA' => 0
        ];

        // 1. Pagos de Hospedaje
        $hospedaje = $this->getVentaHospedaje($mes, $anio);
        foreach ($hospedaje as $row) {
            $monto  = (float)$row['monto'];
            $moneda = $row['moneda'];
            $medio  = $row['medio_label'];

            if ($medio === 'YAPE/PLIN') {
                $res['YAPE'] += $monto;
            } elseif ($medio === 'TRANSFER.') {
                $res['TRANSFERENCIA'] += $monto;
            } elseif (strpos($medio, 'POS') !== false) {
                if (isset($res['POS'][$moneda])) $res['POS'][$moneda] += $monto;
            } elseif (strpos($medio, 'EFEC') !== false) {
                if (isset($res['EFECTIVO'][$moneda])) $res['EFECTIVO'][$moneda] += $monto;
            }
        }

        // 2. Consumos Extras Directos
        $consumos = $this->getConsumosDetail($mes, $anio);
        foreach ($consumos as $c) {
            $monto = (float)$c['total'];
            $metodo = strtoupper($c['metodo_pago'] ?? '');
            if (strpos($metodo, 'YAPE') !== false || strpos($metodo, 'PLIN') !== false) {
                $res['YAPE'] += $monto;
            } elseif (strpos($metodo, 'TRANSF') !== false) {
                $res['TRANSFERENCIA'] += $monto;
            } elseif (strpos($metodo, 'POS') !== false) {
                $res['POS']['PEN'] += $monto;
            } else {
                $res['EFECTIVO']['PEN'] += $monto;
            }
        }

        return $res;
    }

    /**
     * Mendoza: Detalle de consumos realizados en el mes
     */
    public function getConsumosDetail(int $mes, int $anio): array {
        $this->asegurarTablaVouchers();
        $sql = "
            SELECT 
                c.id,
                c.stay_id,
                h.numero AS habitacion,
                ip.nombre AS producto,
                c.cantidad,
                c.total,
                c.metodo_pago,
                CASE 
                    WHEN c.metodo_pago LIKE '%USD%' OR c.metodo_pago LIKE '%DOLAR%' THEN 'USD'
                    WHEN c.metodo_pago LIKE '%CLP%' OR c.metodo_pago LIKE '%PESOS%' THEN 'CLP'
                    ELSE 'PEN'
                END as moneda,
                DATE(c.created_at) AS fecha,
                CASE 
                    WHEN HOUR(c.created_at) >= 6 AND HOUR(c.created_at) < 14 THEN 'MAÑANA' 
                    ELSE 'TARDE' 
                END AS turno,
                v.comprobante_b64
            FROM rooming_consumos c
            LEFT JOIN inventario_productos ip ON c.producto_id = ip.id
            LEFT JOIN rooming_stays s ON c.stay_id = s.id
            LEFT JOIN habitaciones h ON s.habitacion_id = h.id
            LEFT JOIN rooming_vouchers v ON (v.referencia_tipo = 'consumo' AND v.referencia_id = c.id)
            WHERE MONTH(c.created_at) = :mes AND YEAR(c.created_at) = :anio
            ORDER BY c.created_at DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['mes' => $mes, 'anio' => $anio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEgresosMendoza(int $mes, int $anio): array {
        $sql = "
            SELECT 
                f.fecha, 
                f.turno,
                fc.nombre as categoria,
                m.monto,
                fc.id as cat_id
            FROM flujo_caja f
            JOIN flujo_caja_movimientos m ON f.id = m.flujo_id
            JOIN finanzas_categorias fc ON m.categoria_id = fc.id
            WHERE MONTH(f.fecha) = :mes AND YEAR(f.fecha) = :anio
              AND m.tipo = 'Egreso'
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':mes' => $mes, ':anio' => $anio]);
        
        $egresos = [];
        $diasEnMes = date('t', strtotime("$anio-$mes-01"));
        for ($d = 1; $d <= $diasEnMes; $d++) {
            $f = sprintf("%04d-%02d-%02d", $anio, $mes, $d);
            $egresos[$f] = [
                'MAÑANA' => ['MERCADO'=>0, 'MOVILIDAD'=>0, 'CAFETERÍA'=>0, 'LAVANDERÍA'=>0, 'ÚTILES ESCR.'=>0, 'RECEPCIÓN CC'=>0, 'REPUESTOS'=>0, 'PERSONAL'=>0, 'OTROS'=>0],
                'TARDE' => ['MERCADO'=>0, 'MOVILIDAD'=>0, 'CAFETERÍA'=>0, 'LAVANDERÍA'=>0, 'ÚTILES ESCR.'=>0, 'RECEPCIÓN CC'=>0, 'REPUESTOS'=>0, 'PERSONAL'=>0, 'OTROS'=>0]
            ];
        }

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $f = $row['fecha'];
            $t = strtoupper($row['turno']);
            $cat = strtoupper($row['categoria']);
            $catId = $row['cat_id'];
            $monto = (float)$row['monto'];
            
            if (!isset($egresos[$f][$t])) continue;

            $key = 'OTROS';
            if (strpos($cat, 'MERCADO') !== false || $catId == 9) $key = 'MERCADO';
            else if (strpos($cat, 'MOVIL') !== false || $catId == 10) $key = 'MOVILIDAD';
            else if (strpos($cat, 'CAFE') !== false || strpos($cat, 'VEA') !== false || strpos($cat, 'GENOV') !== false || $catId == 11) $key = 'CAFETERÍA';
            else if (strpos($cat, 'LAVAN') !== false || $catId == 12) $key = 'LAVANDERÍA';
            else if (strpos($cat, 'ESCRIT') !== false || strpos($cat, 'UTIL') !== false || $catId == 13) $key = 'ÚTILES ESCR.';
            else if (strpos($cat, 'RECEP') !== false || strpos($cat, 'CHICA') !== false || $catId == 14) $key = 'RECEPCIÓN CC';
            else if (strpos($cat, 'REPUEST') !== false || strpos($cat, 'SERV') !== false || $catId == 15) $key = 'REPUESTOS';
            else if (strpos($cat, 'PERSO') !== false || strpos($cat, 'PAGO') !== false || $catId == 16) $key = 'PERSONAL';

            $egresos[$f][$t][$key] += $monto;
        }

        return $egresos;
    }

    /**
     * Resumen Mensual Consolidado (P&L)
     */
    public function getResumenP_L(int $mes, int $anio): array {
        // 1. Ingresos Rooming (Lodging) desde ANTICIPOS
        $sqlIng = "SELECT SUM(monto_pen) FROM anticipos WHERE MONTH(fecha) = :mes AND YEAR(fecha) = :anio";
        $stmt = $this->pdo->prepare($sqlIng);
        $stmt->execute([':mes' => $mes, ':anio' => $anio]);
        $ingHosting = (float)$stmt->fetchColumn();

        // 2. Otros Ingresos (Venta productos, early checkin, etc en Flujo)
        $sqlOtros = "
            SELECT SUM(m.monto) 
            FROM flujo_caja_movimientos m
            LEFT JOIN finanzas_categorias c ON m.categoria_id = c.id
            WHERE m.tipo = 'Ingreso' 
              AND (c.nombre IS NULL OR c.nombre NOT IN ('HABITACIÓN', 'YAPE O PLIN')) 
              AND m.flujo_id IN (
                  SELECT id FROM flujo_caja WHERE MONTH(fecha) = :mes AND YEAR(fecha) = :anio
              )
        ";
        $stmt = $this->pdo->prepare($sqlOtros);
        $stmt->execute([':mes' => $mes, ':anio' => $anio]);
        $otrosIng = (float)$stmt->fetchColumn();

        // 3. Egresos Operativos (Flujo) - EXCLUIMOS reposición de Caja Chica para no duplicar si sumamos sus gastos reales
        $sqlEgr = "
            SELECT SUM(m.monto) 
            FROM flujo_caja_movimientos m
            LEFT JOIN finanzas_categorias c ON m.categoria_id = c.id
            WHERE m.tipo = 'Egreso' 
              AND (c.nombre IS NULL OR c.nombre NOT IN ('RECEPCIÓN C.CH.', 'REPOSICIÓN C.CH.'))
              AND m.flujo_id IN (
                  SELECT id FROM flujo_caja WHERE MONTH(fecha) = :mes AND YEAR(fecha) = :anio
              )
        ";
        $stmt = $this->pdo->prepare($sqlEgr);
        $stmt->execute(['mes' => $mes, 'anio' => $anio]);
        $egresosOp = (float)$stmt->fetchColumn();

        // 4. Caja Chica (Gastos Reales)
        $sqlCch = "SELECT SUM(monto) FROM caja_chica_movimientos 
                   WHERE tipo='egreso' AND (anulado=0 OR anulado IS NULL)
                   AND MONTH(fecha) = :mes AND YEAR(fecha) = :anio";
        $stmt = $this->pdo->prepare($sqlCch);
        $stmt->execute(['mes' => $mes, 'anio' => $anio]);
        $gastosCch = (float)$stmt->fetchColumn();

        // 5. Gastos Yape (Reporte Alex)
        $sqlYape = "SELECT SUM(d.monto) 
                    FROM gastos_yape_detalle d
                    JOIN gastos_yape y ON d.gasto_yape_id = y.id
                    WHERE MONTH(y.fecha) = :mes AND YEAR(y.fecha) = :anio";
        $stmt = $this->pdo->prepare($sqlYape);
        $stmt->execute(['mes' => $mes, 'anio' => $anio]);
        $gastosYape = (float)$stmt->fetchColumn();

        return [
            'ingresos_hospedaje' => $ingHosting,
            'otros_ingresos' => $otrosIng,
            'egresos_operativos' => $egresosOp,
            'gastos_caja_chica' => $gastosCch,
            'gastos_yape' => $gastosYape,
            'total_ingresos' => ($ingHosting + $otrosIng),
            'total_gastos' => ($egresosOp + $gastosCch + $gastosYape),
            'ganancia_mes' => ($ingHosting + $otrosIng) - ($egresosOp + $gastosCch + $gastosYape),
            'utilidad_neta' => ($ingHosting + $otrosIng) - ($egresosOp + $gastosCch + $gastosYape)
        ];
    }
}
