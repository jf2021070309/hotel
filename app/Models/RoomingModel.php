<?php
/**
 * app/Models/RoomingModel.php
 */
class RoomingModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getStaysActivos(): array {
        $sql = "SELECT s.*, h.numero as hab_numero, h.tipo as hab_tipo,
                (SELECT nombre_completo FROM rooming_pax WHERE stay_id = s.id AND es_titular = 1 LIMIT 1) as titular_nombre
                FROM rooming_stays s 
                JOIN habitaciones h ON s.habitacion_id = h.id 
                WHERE s.estado = 'activo' 
                ORDER BY s.id DESC";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function getStayDetail(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT s.*, h.numero as hab_numero FROM rooming_stays s JOIN habitaciones h ON s.habitacion_id = h.id WHERE s.id = ?");
        $stmt->execute([$id]);
        $stay = $stmt->fetch();
        if (!$stay) return null;

        $stmt = $this->pdo->prepare("SELECT * FROM rooming_pax WHERE stay_id = ?");
        $stmt->execute([$id]);
        $stay['pax'] = $stmt->fetchAll();

        $stmt = $this->pdo->prepare("SELECT * FROM anticipos WHERE stay_id = ?");
        $stmt->execute([$id]);
        $stay['pagos'] = $stmt->fetchAll();

        return $stay;
    }

    public function registrarStay(array $data, array $paxList): int {
        // Regla de Negocio: Bloqueo de check-in si limpieza no está 'lista'
        $fechaHoy = date('Y-m-d');
        $habId = $data['habitacion_id'];
        $stmtClean = $this->pdo->prepare("SELECT estado FROM limpieza_registros WHERE fecha = ? AND habitacion_id = ?");
        $stmtClean->execute([$fechaHoy, $habId]);
        $limpieza = $stmtClean->fetchColumn();

        if ($limpieza && $limpieza !== 'lista') {
            throw new Exception("La habitación {$data['hab_id']} aún no ha sido marcada como 'LISTA' por el personal de limpieza.");
        }

        $this->pdo->beginTransaction();
        try {
            $sql = "INSERT INTO rooming_stays (
                operador, fecha_registro, fecha_checkout, hora_checkin, medio_reserva, 
                habitacion_id, tipo_hab_declarado, noches, pax_total, total_pago, 
                moneda_pago, monto_original, tc_aplicado, recargo_tarjeta, metodo_pago, 
                tipo_comprobante, num_comprobante, ruc_factura, cobrador, procedencia, 
                observaciones, usuario_id, checkin_realizado, total_cobrado, estado_pago
            ) VALUES (
                :operador, :fecha_reg, :fecha_out, :hora_in, :medio, 
                :hab_id, :tipo_hab, :noches, :pax_total, :total, 
                :moneda, :monto_orig, :tc, :recargo, :metodo, 
                :comprobante, :num_comp, :ruc, :cobrador, :procedencia, 
                :obs, :uid, 1, :cobrado, :est_pago
            )";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            $stay_id = (int)$this->pdo->lastInsertId();

            // Insertar PAX
            $sqlPax = "INSERT INTO rooming_pax (stay_id, nombre_completo, documento_tipo, documento_num, nacionalidad, ciudad, es_titular) 
                       VALUES (:stay_id, :nombre_completo, :documento_tipo, :documento_num, :nacionalidad, :ciudad, :es_titular)";
            $stmtPax = $this->pdo->prepare($sqlPax);
            foreach ($paxList as $pax) {
                // Asegurar que stay_id esté presente
                $pax['stay_id'] = $stay_id;
                // Filtrar solo las llaves necesarias para evitar errores de PDO
                $stmtPax->execute([
                    'stay_id'         => $stay_id,
                    'nombre_completo' => $pax['nombre_completo'] ?? '',
                    'documento_tipo'  => $pax['documento_tipo'] ?? 'DNI',
                    'documento_num'   => $pax['documento_num'] ?? '',
                    'nacionalidad'    => $pax['nacionalidad'] ?? '',
                    'ciudad'          => $pax['ciudad'] ?? '',
                    'es_titular'      => $pax['es_titular'] ? 1 : 0
                ]);
            }

            // Actualizar habitación a ocupado
            $stmtHab = $this->pdo->prepare("UPDATE habitaciones SET estado = 'ocupado' WHERE id = ?");
            $stmtHab->execute([$data['hab_id']]);

            $this->pdo->commit();
            return $stay_id;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function registrarPago(array $pago): bool {
        $sql = "INSERT INTO anticipos (stay_id, monto, moneda, monto_pen, tc_aplicado, tipo_pago, recibo, fecha, usuario_id) 
                VALUES (:stay_id, :monto, :moneda, :monto_pen, :tc, :tipo, :recibo, :fecha, :uid)";
        $stmt = $this->pdo->prepare($sql);
        $res = $stmt->execute($pago);
        
        if ($res) {
            // Actualizar total_cobrado y estado_pago del stay
            $this->actualizarResumenPagos($pago['stay_id']);
        }
        return $res;
    }

    public function finalizarStay(int $id, string $fechaOut): bool {
        $this->pdo->beginTransaction();
        try {
            // Obtener hab ID
            $stmt = $this->pdo->prepare("SELECT habitacion_id FROM rooming_stays WHERE id = ?");
            $stmt->execute([$id]);
            $hab_id = $stmt->fetchColumn();

            $stmt = $this->pdo->prepare("UPDATE rooming_stays SET estado = 'finalizado', fecha_checkout = ? WHERE id = ?");
            $stmt->execute([$fechaOut, $id]);

            $stmt = $this->pdo->prepare("UPDATE habitaciones SET estado = 'limpieza' WHERE id = ?");
            $stmt->execute([$hab_id]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function actualizarResumenPagos(int $stay_id): void {
        // Calcular total cobrado (convertido a soles si es necesario o en moneda base)
        // Por simplificación, sumamos montos PEN de los anticipos
        $stmt = $this->pdo->prepare("SELECT SUM(monto_pen) FROM anticipos WHERE stay_id = ?");
        $stmt->execute([$stay_id]);
        $totalCobrado = (float)$stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT total_pago FROM rooming_stays WHERE id = ?");
        $stmt->execute([$stay_id]);
        $totalPuntual = (float)$stmt->fetchColumn();

        $estadoPago = 'pendiente';
        if ($totalCobrado > 0) {
            if ($totalCobrado >= $totalPuntual) $estadoPago = 'pagado';
            else $estadoPago = 'parcial';
        }

        $stmt = $this->pdo->prepare("UPDATE rooming_stays SET total_cobrado = ?, estado_pago = ? WHERE id = ?");
        $stmt->execute([$totalCobrado, $estadoPago, $stay_id]);
    }
}
