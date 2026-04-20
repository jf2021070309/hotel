<?php
require_once __DIR__ . '/../Helpers/FinanzasHelper.php';

/**
 * Modelo de Rooming (Hospedajes).
 * 
 * Gestiona la persistencia de datos relacionados con estadías (stays),
 * pasajeros (pax) y pagos anticipados. Implementa reglas de negocio como
 * el bloqueo de habitaciones sucias y la automatización de tareas de limpieza.
 * 
 * @package App\Models
 */
class RoomingModel {
    private PDO $pdo;
    private FinanzasHelper $finanzas;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->finanzas = new FinanzasHelper($pdo);
    }

    /**
     * Obtiene todas las estadías activas (Ocupado, Reservado, Late Checkout).
     * Incluye datos de habitación y el nombre del titular.
     * 
     * @return array Lista de registros activos.
     */
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
        $stmt = $this->pdo->prepare("SELECT s.*, h.numero as hab_numero, h.tipo as hab_tipo, h.precio_base as hab_precio FROM rooming_stays s JOIN habitaciones h ON s.habitacion_id = h.id WHERE s.id = ?");
        $stmt->execute([$id]);
        $stay = $stmt->fetch();
        if (!$stay) return null;

        $stmt = $this->pdo->prepare("SELECT * FROM rooming_pax WHERE stay_id = ?");
        $stmt->execute([$id]);
        $stay['pax'] = $stmt->fetchAll();

        $stmt = $this->pdo->prepare("
            SELECT a.*, u.nombre as cajero_nom 
            FROM anticipos a 
            LEFT JOIN usuarios u ON a.usuario_id = u.id 
            WHERE a.stay_id = ? 
            ORDER BY a.id ASC
        ");
        $stmt->execute([$id]);
        $stay['pagos'] = $stmt->fetchAll();

        return $stay;
    }

    /**
     * Registra un nuevo hospedaje (Check-in).
     * 
     * @param array $data Mapeo de campos para rooming_stays.
     * @param array $paxList Lista de objetos pasajero.
     * @throws Exception Si la habitación no está marcada como 'lista' por el personal de limpieza.
     * @return int ID de la estadía generada.
     */
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

        $mustCommit = !$this->pdo->inTransaction();
        if ($mustCommit) $this->pdo->beginTransaction();
        try {
            $sql = "INSERT INTO rooming_stays (
                operador, fecha_registro, fecha_checkout, hora_checkin, medio_reserva, 
                habitacion_id, tipo_hab_declarado, noches, pax_total, total_pago, 
                moneda_pago, monto_original, tc_aplicado, recargo_tarjeta, metodo_pago, 
                tipo_comprobante, num_comprobante, ruc_factura, cobrador, procedencia, 
                carro, observaciones, usuario_id, checkin_realizado, total_cobrado, total_cobrado_orig, estado_pago
            ) VALUES (
                :operador, :fecha_reg, :fecha_out, :hora_in, :medio, 
                :hab_id, :tipo_hab, :noches, :pax_total, :total, 
                :moneda, :monto_orig, :tc, :recargo, :metodo, 
                :comprobante, :num_comp, :ruc, :cobrador, :procedencia, 
                :carro, :obs, :uid, 1, :cobrado, :cobrado_orig, :est_pago
            )";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'operador'      => $data['operador'],
                'fecha_reg'     => $data['fecha_reg'],
                'fecha_out'     => $data['fecha_out'],
                'hora_in'       => $data['hora_in'],
                'medio'         => $data['medio'],
                'hab_id'        => $data['hab_id'],
                'tipo_hab'      => $data['tipo_hab'],
                'noches'        => $data['noches'],
                'pax_total'     => $data['pax_total'],
                'total'         => $data['total'],
                'moneda'        => $data['moneda'],
                'monto_orig'    => $data['monto_orig'],
                'tc'            => $data['tc'],
                'recargo'       => $data['recargo'] ?? 0,
                'metodo'        => $data['metodo'],
                'comprobante'   => $data['comprobante'],
                'num_comp'      => $data['num_comp'],
                'ruc'           => $data['ruc'],
                'cobrador'      => $data['cobrador'],
                'procedencia'   => $data['procedencia'],
                'carro'         => $data['carro'],
                'obs'           => $data['obs'],
                'uid'           => $data['uid'],
                'cobrado'       => $data['cobrado'],
                'cobrado_orig'  => $data['cobrado_orig'],
                'est_pago'      => $data['est_pago']
            ]);
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

            $this->upsertLimpiezaCheckout((int)$data['hab_id'], $data['fecha_out']);

            if ($mustCommit) $this->pdo->commit();
            return $stay_id;
        } catch (Exception $e) {
            if ($mustCommit) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function activarReserva(int $id, int $hab_id): bool {
        $mustCommit = !$this->pdo->inTransaction();
        if ($mustCommit) $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("UPDATE rooming_stays SET estado = 'activo', habitacion_id = ? WHERE id = ?");
            $stmt->execute([$hab_id, $id]);
            $stmtHab = $this->pdo->prepare("UPDATE habitaciones SET estado = 'ocupado' WHERE id = ?");
            $stmtHab->execute([$hab_id]);
            if ($mustCommit) $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($mustCommit) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function actualizarStay(int $id, array $data, array $paxList): bool {
        $mustCommit = !$this->pdo->inTransaction();
        if ($mustCommit) $this->pdo->beginTransaction();
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
                estado = :estado
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
                'estado'      => $data['estado'] ?? 'activo',
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

            $this->upsertLimpiezaCheckout((int)$data['hab_id'], $data['fecha_out']);

            if ($mustCommit) $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($mustCommit) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function registrarPago(array $pago, string $subtipo = 'hospedaje'): bool {
        // Opción 1: Si hay recargo POS, incrementamos el total del stay para que el pago calce
        if (!empty($pago['recargo_pos'])) {
            $montoPen = (float)($pago['monto_pen'] ?? $pago['monto'] ?? 0);
            $surcharge = $montoPen * 0.05 / 1.05;
            $this->incrementarTotal((int)$pago['stay_id'], $surcharge);
        }

        $sql = "INSERT INTO anticipos (stay_id, monto, moneda, monto_pen, tc_aplicado, tipo_pago, recibo, fecha, usuario_id) 
                VALUES (:stay_id, :monto, :moneda, :monto_pen, :tc, :tipo, :recibo, :fecha, :uid)";
        
        $stmt = $this->pdo->prepare($sql);
        $res = $stmt->execute([
            'stay_id'   => $pago['stay_id'],
            'monto'     => $pago['monto'],
            'moneda'    => $pago['moneda'],
            'monto_pen' => $pago['monto_pen'],
            'tc'        => $pago['tc'],
            'tipo'      => $pago['tipo'],
            'recibo'    => $pago['recibo'] ?? '',
            'fecha'     => $pago['fecha'] ?? date('Y-m-d'),
            'uid'       => $pago['uid']
        ]);

        if ($res) {
            // Actualizar total_cobrado y estado_pago del stay
            $this->actualizarResumenPagos($pago['stay_id']);

            // Obtener info básica para mejor observación
            $stmtInfo = $this->pdo->prepare("
                SELECT s.id, h.numero as hab_num, 
                       (SELECT nombre_completo FROM rooming_pax WHERE stay_id = s.id AND es_titular = 1 LIMIT 1) as titular
                FROM rooming_stays s
                JOIN habitaciones h ON s.habitacion_id = h.id
                WHERE s.id = ?
            ");
            $stmtInfo->execute([$pago['stay_id']]);
            $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

            $habNum = $info['hab_num'] ?? 'N/A';
            $titular = $info['titular'] ?? 'Huésped';

            // Etiqueta legible según el tipo de pago
            $etiquetas = [
                'adelanto'  => 'pagó un ADELANTO del hospedaje',
                'hospedaje' => 'pagó el hospedaje',
                'completo'  => 'completó el pago del hospedaje',
                'saldo'     => 'abonó el saldo pendiente',
            ];
            $etiqueta = $etiquetas[$subtipo] ?? "pagó $subtipo";

            // SINCRONIZACIÓN: Registrar el ingreso en el Flujo de Caja
            $syncRes = $this->finanzas->registrarMovimientoAutomatico([
                'usuario_id'  => $pago['uid'],
                'categoria'   => $pago['tipo'] ?? 'Alojamiento', 
                'monto'       => $pago['monto'], 
                'moneda'      => $pago['moneda'] ?? 'PEN',
                'medio_pago'  => $pago['tipo'] ?? 'EFECTIVO',
                'observacion' => "HOSPEDAJE: $titular ($etiqueta) - Registro #{$pago['stay_id']} (Hab #$habNum)"
            ]);

            if (!$syncRes) {
                throw new Exception("Error de sincronización: No se encontró un TURNO DE CAJA abierto para registrar este pago. Por favor, abra su turno antes de continuar.");
            }
        }
        return $res;
    }

    public function finalizarStay(int $id, string $fechaOut, array $pago = []): bool {
        $mustCommit = !$this->pdo->inTransaction();
        if ($mustCommit) $this->pdo->beginTransaction();
        try {
            // 1. Registrar pago si se proporciona (Saldo pendiente)
            if (!empty($pago) && (float)($pago['monto'] ?? 0) > 0) {
                $pago['stay_id'] = $id;
                $pago['fecha'] = $fechaOut;
                $pago['uid'] = $_SESSION['auth_id'] ?? 1;
                $pago['monto_pen'] = $pago['monto_pen'] ?? $pago['monto'];
                $this->registrarPago($pago, 'completo'); // Marcamos como pago completo
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

            if ($mustCommit) $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($mustCommit) $this->pdo->rollBack();
            return false;
        }
    }

    public function actualizarResumenPagos(int $stay_id): void {
        // 1. Obtener la moneda y TC del stay para poder reconvertir pagos en divisa distinta
        $stmtStay = $this->pdo->prepare("SELECT moneda_pago, tc_aplicado, total_pago FROM rooming_stays WHERE id = ?");
        $stmtStay->execute([$stay_id]);
        $stay = $stmtStay->fetch(PDO::FETCH_ASSOC);
        $monedaStay = $stay['moneda_pago'] ?? 'PEN';
        $tcStay     = (float)($stay['tc_aplicado'] ?? 1);
        $totalPago  = (float)($stay['total_pago'] ?? 0);

        // 2. Total cobrado en PEN (base contable)
        $stmt = $this->pdo->prepare("SELECT SUM(monto_pen) FROM anticipos WHERE stay_id = ?");
        $stmt->execute([$stay_id]);
        $totalCobrado = (float)$stmt->fetchColumn();

        // 3. Total cobrado en la moneda original del stay
        //    - Si el pago es en la misma moneda: usar monto directamente
        //    - Si es en otra moneda: reconvertir vía tc_aplicado del stay
        $stmt = $this->pdo->prepare("
            SELECT SUM(
                CASE
                    WHEN moneda = :moneda THEN monto
                    ELSE monto_pen / NULLIF(:tc, 0)
                END
            ) FROM anticipos WHERE stay_id = :stay_id
        ");
        $stmt->execute(['moneda' => $monedaStay, 'tc' => $tcStay, 'stay_id' => $stay_id]);
        $totalCobradoOrig = (float)$stmt->fetchColumn();

        // 4. Estado de pago (basado en PEN, que es la moneda contable base)
        $estadoPago = 'pendiente';
        if ($totalCobrado > 0) {
            if ($totalCobrado >= $totalPago) $estadoPago = 'pagado';
            else $estadoPago = 'parcial';
        }

        $stmt = $this->pdo->prepare("UPDATE rooming_stays SET total_cobrado = ?, total_cobrado_orig = ?, estado_pago = ? WHERE id = ?");
        $stmt->execute([$totalCobrado, $totalCobradoOrig, $estadoPago, $stay_id]);
    }

    public function incrementarTotal(int $stayId, float $monto): bool {
        $stmt = $this->pdo->prepare("UPDATE rooming_stays SET total_pago = total_pago + ? WHERE id = ?");
        $res = $stmt->execute([$monto, $stayId]);
        if ($res) {
            $this->actualizarResumenPagos($stayId);
        }
        return $res;
    }

    private function upsertLimpiezaCheckout(int $hab_id, string $fecha_out): void {
        $stmtHab = $this->pdo->prepare("SELECT numero FROM habitaciones WHERE id = ?");
        $stmtHab->execute([$hab_id]);
        $numHab = $stmtHab->fetchColumn();

        $sql = "INSERT INTO limpieza_registros 
                (fecha, habitacion_id, habitacion, tipo_limpieza, prioridad, estado, usuario_id) 
                VALUES (?, ?, ?, 'salida', 'alta', 'pendiente', ?)
                ON DUPLICATE KEY UPDATE tipo_limpieza = 'salida', prioridad = 'alta'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$fecha_out, $hab_id, $numHab, $_SESSION['auth_id'] ?? 1]);
    }
}
