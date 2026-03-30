<?php
require_once __DIR__ . '/../Helpers/FinanzasHelper.php';

class RoomingModel {
    private PDO $pdo;
    private FinanzasHelper $finanzas;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->finanzas = new FinanzasHelper($pdo);
    }

    public function getStaysActivos(): array {
        $sql = "SELECT s.*, h.numero as hab_numero, h.tipo as hab_tipo,
                (SELECT nombre_completo FROM rooming_pax WHERE stay_id = s.id AND es_titular = 1 LIMIT 1) as titular_nombre
                FROM rooming_stays s 
                JOIN habitaciones h ON s.habitacion_id = h.id 
                WHERE s.estado IN ('activo', 'reservado', 'late_checkout') 
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
        $habId = $data['hab_id'];
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
                carro, observaciones, usuario_id, checkin_realizado, total_cobrado, estado_pago
            ) VALUES (
                :operador, :fecha_reg, :fecha_out, :hora_in, :medio, 
                :hab_id, :tipo_hab, :noches, :pax_total, :total, 
                :moneda, :monto_orig, :tc, :recargo, :metodo, 
                :comprobante, :num_comp, :ruc, :cobrador, :procedencia, 
                :carro, :obs, :uid, 1, :cobrado, :est_pago
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

            // SINCRONIZACIÓN: Si hay un pago inicial (total_cobrado), registrar en Anticipos + Flujo
            if ((float)$data['cobrado'] > 0) {
                $this->registrarPago([
                    'stay_id'   => $stay_id,
                    'monto'     => $data['monto_original'],
                    'moneda'    => $data['moneda'] ?? 'PEN',
                    'monto_pen' => $data['cobrado'],
                    'tc'        => $data['tc_aplicado'] ?? 1.0,
                    'tipo'      => $data['metodo'] ?? 'EFECTIVO',
                    'recibo'    => $data['num_comp'] ?? '',
                    'fecha'     => $data['fecha_reg'],
                    'uid'       => $data['uid']
                ]);
            }

            $this->pdo->commit();
            return $stay_id;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function activarReserva(int $id, int $hab_id): bool {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("UPDATE rooming_stays SET estado = 'activo', habitacion_id = ? WHERE id = ?");
            $stmt->execute([$hab_id, $id]);
            $stmtHab = $this->pdo->prepare("UPDATE habitaciones SET estado = 'ocupado' WHERE id = ?");
            $stmtHab->execute([$hab_id]);
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function actualizarStay(int $id, array $data, array $paxList): bool {
        $this->pdo->beginTransaction();
        try {
            // Update stay info
            $sql = "UPDATE rooming_stays SET 
                fecha_registro = :fecha_reg, fecha_checkout = :fecha_out, 
                hora_checkin = :hora_in, medio_reserva = :medio, 
                habitacion_id = :hab_id, tipo_hab_declarado = :tipo_hab, 
                noches = :noches, pax_total = :pax_total, total_pago = :total, 
                moneda_pago = :moneda, monto_original = :monto_orig, 
                tc_aplicado = :tc, metodo_pago = :metodo, 
                tipo_comprobante = :comprobante, num_comprobante = :num_comp, 
                ruc_factura = :ruc, observaciones = :obs, 
                total_cobrado = :cobrado, estado_pago = :est_pago,
                estado = 'activo'
                WHERE id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            $data['id'] = $id;
            // Remove operator/uid from update or keep them? Keep them as provided in $data.
            // For simplicity, I'll pass the whole mapped array.
            $stmt->execute([
                'fecha_reg'   => $data['fecha_reg'],
                'fecha_out'   => $data['fecha_out'],
                'hora_in'     => $data['hora_in'],
                'medio'       => $data['medio'],
                'hab_id'      => $data['hab_id'],
                'tipo_hab'    => $data['tipo_hab'],
                'noches'      => $data['noches'],
                'pax_total'   => $data['pax_total'],
                'total'       => $data['total'],
                'moneda'      => $data['moneda'],
                'monto_orig'  => $data['monto_orig'],
                'tc'          => $data['tc'],
                'metodo'      => $data['metodo'],
                'comprobante' => $data['comprobante'],
                'num_comp'    => $data['num_comp'],
                'ruc'         => $data['ruc'],
                'obs'         => $data['obs'],
                'cobrado'     => $data['cobrado'],
                'est_pago'    => $data['est_pago'],
                'id'          => $id
            ]);

            // Update room to 'ocupado'
            $stmtHab = $this->pdo->prepare("UPDATE habitaciones SET estado = 'ocupado' WHERE id = ?");
            $stmtHab->execute([$data['hab_id']]);

            // Replace PAX
            $this->pdo->prepare("DELETE FROM rooming_pax WHERE stay_id = ?")->execute([$id]);
            $stmtPax = $this->pdo->prepare("INSERT INTO rooming_pax (stay_id, nombre_completo, documento_tipo, documento_num, es_titular) VALUES (?, ?, ?, ?, ?)");
            foreach ($paxList as $p) {
                $stmtPax->execute([$id, $p['nombre_completo'], $p['documento_tipo'], $p['documento_num'], $p['es_titular'] ? 1 : 0]);
            }

            $this->pdo->commit();
            return true;
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

            // SINCRONIZACIÓN: Registrar el ingreso en el Flujo de Caja
            $this->finanzas->registrarMovimientoAutomatico([
                'usuario_id'  => $pago['uid'],
                'categoria'   => 'Alojamiento / Pago extra',
                'monto'       => $pago['monto'], // FIX: Usar monto original con la moneda original
                'moneda'      => $pago['moneda'] ?? 'PEN',
                'medio_pago'  => $pago['tipo_pago'] ?? 'EFECTIVO',
                'observacion' => "PAGO Stay #" . $pago['stay_id'] . ". Recibo: " . ($pago['recibo'] ?? 'N/A')
            ]);
        }
        return $res;
    }

    public function finalizarStay(int $id, string $fechaOut, array $pago = []): bool {
        $this->pdo->beginTransaction();
        try {
            // 1. Registrar pago si se proporciona (Saldo pendiente)
            if (!empty($pago) && (float)($pago['monto'] ?? 0) > 0) {
                $pago['stay_id'] = $id;
                $pago['fecha'] = $fechaOut;
                $pago['uid'] = $_SESSION['auth_id'] ?? 1;
                $pago['monto_pen'] = $pago['monto_pen'] ?? $pago['monto'];
                $this->registrarPago($pago); // Esto ya sincroniza con Flujo de Caja
            }

            // 2. Obtener hab ID
            $stmt = $this->pdo->prepare("SELECT habitacion_id FROM rooming_stays WHERE id = ?");
            $stmt->execute([$id]);
            $hab_id = $stmt->fetchColumn();

            // 3. Finalizar Stay
            $stmt = $this->pdo->prepare("UPDATE rooming_stays SET estado = 'finalizado', fecha_checkout = ? WHERE id = ?");
            $stmt->execute([$fechaOut, $id]);

            // 4. Pasar habitación a estado 'Sucia' (DB: limpieza)
            $stmt = $this->pdo->prepare("UPDATE habitaciones SET estado = 'limpieza' WHERE id = ?");
            $stmt->execute([$hab_id]);

            // 5. Automatizar tarea de LIMPIEZA TIPO SALIDA (Prioridad ALTA)
            $stmtHab = $this->pdo->prepare("SELECT numero FROM habitaciones WHERE id = ?");
            $stmtHab->execute([$hab_id]);
            $numHab = $stmtHab->fetchColumn();

            $stmtLimpieza = $this->pdo->prepare("
                INSERT INTO limpieza_registros 
                (fecha, habitacion_id, habitacion, tipo_limpieza, prioridad, estado, usuario_id) 
                VALUES (?, ?, ?, 'salida', 'alta', 'pendiente', ?)
                ON DUPLICATE KEY UPDATE tipo_limpieza = 'salida', prioridad = 'alta', estado = 'pendiente'
            ");
            $stmtLimpieza->execute([$fechaOut, $hab_id, $numHab, $_SESSION['auth_id'] ?? 1]);

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

    public function incrementarTotal(int $stayId, float $monto): bool {
        $stmt = $this->pdo->prepare("UPDATE rooming_stays SET total_pago = total_pago + ? WHERE id = ?");
        $res = $stmt->execute([$monto, $stayId]);
        if ($res) {
            $this->actualizarResumenPagos($stayId);
        }
        return $res;
    }
}
