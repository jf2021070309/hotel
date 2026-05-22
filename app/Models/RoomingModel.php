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
     * Valida si una habitación está disponible para un rango de fechas.
     */
    public function validarDisponibilidad(int $habId, string $fechaIn, string $fechaOut, ?int $excludeStayId = null): bool {
        $sql = "SELECT COUNT(*) FROM rooming_stays 
                WHERE habitacion_id = ? 
                AND estado IN ('activo', 'reservado', 'late_checkout')
                AND fecha_registro < ? 
                AND fecha_checkout > ?";
        
        $params = [$habId, $fechaOut, $fechaIn];
        if ($excludeStayId) {
            $sql .= " AND id != ?";
            $params[] = $excludeStayId;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() == 0;
    }

    /**
     * Obtiene todas las estadías activas (Ocupado, Reservado, Late Checkout).
     * Incluye datos de habitación y el nombre del titular.
     * 
     * @return array Lista de registros activos.
     */
    public function getStaysActivos(): array {
        // Auto-cancelar reservas vencidas (donde hoy es posterior a la fecha de checkout y siguen como 'reservado')
        $this->pdo->query("UPDATE rooming_stays SET estado = 'cancelado' WHERE estado = 'reservado' AND fecha_checkout < CURRENT_DATE");

        $sql = "SELECT s.*, h.numero as hab_numero, h.tipo as hab_tipo,
                (SELECT nombre_completo FROM rooming_pax WHERE stay_id = s.id AND es_titular = 1 LIMIT 1) as titular_nombre,
                (SELECT COUNT(DISTINCT moneda) FROM anticipos WHERE stay_id = s.id) as divisas_count,
                (SELECT COALESCE(SUM(total), 0) FROM rooming_consumos WHERE stay_id = s.id) as total_consumos
                FROM rooming_stays s 
                JOIN habitaciones h ON s.habitacion_id = h.id 
                WHERE s.estado IN ('activo', 'reservado', 'late_checkout', 'cancelado', 'finalizado') 
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

        $stmtCons = $this->pdo->prepare("SELECT SUM(total) FROM rooming_consumos WHERE stay_id = ?");
        $stmtCons->execute([$id]);
        $stay['total_consumos'] = (float)$stmtCons->fetchColumn();

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
            $stmtNum = $this->pdo->prepare("SELECT numero FROM habitaciones WHERE id = ?");
            $stmtNum->execute([$habId]);
            $numReal = $stmtNum->fetchColumn() ?: $habId;
            throw new Exception("La habitación $numReal aún no ha sido marcada como 'LISTA' por el personal de limpieza.");
        }

        $mustCommit = !$this->pdo->inTransaction();
        if ($mustCommit) $this->pdo->beginTransaction();
        try {
            $sql = "INSERT INTO rooming_stays (
                operador, fecha_registro, fecha_checkout, fecha_checkout_original, hora_checkin, medio_reserva, 
                habitacion_id, tipo_hab_declarado, noches, pax_total, total_pago, 
                moneda_pago, monto_original, tc_aplicado, recargo_tarjeta, metodo_pago, 
                tipo_comprobante, num_comprobante, ruc_factura, razon_social, cobrador, procedencia, 
                carro, observaciones, usuario_id, checkin_realizado, total_cobrado, total_cobrado_orig, estado_pago
            ) VALUES (
                :operador, :fecha_reg, :fecha_out, :fecha_out_original, :hora_in, :medio, 
                :hab_id, :tipo_hab, :noches, :pax_total, :total, 
                :moneda, :monto_orig, :tc, :recargo, :metodo, 
                :comprobante, :num_comp, :ruc, :razon_social, :cobrador, :procedencia, 
                :carro, :obs, :uid, 1, :cobrado, :cobrado_orig, :est_pago
            )";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'operador'      => $data['operador'],
                'fecha_reg'     => $data['fecha_reg'],
                'fecha_out'     => $data['fecha_out'],
                'fecha_out_original' => $data['fecha_out'],
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
                'razon_social'  => $data['razon_social'] ?? '',
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
            $sqlPax = "INSERT INTO rooming_pax (stay_id, nombre_completo, documento_tipo, documento_num, nacionalidad, ciudad, celular, email, empresa, es_titular, es_corporativo) 
                       VALUES (:stay_id, :nombre_completo, :documento_tipo, :documento_num, :nacionalidad, :ciudad, :celular, :email, :empresa, :es_titular, :es_corporativo)";
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
                    'celular'         => $pax['celular'] ?? '',
                    'email'           => $pax['email'] ?? '',
                    'empresa'         => $pax['empresa'] ?? '',
                    'es_titular'      => $pax['es_titular'] ? 1 : 0,
                    'es_corporativo'  => !empty($pax['es_corporativo']) ? 1 : 0
                ]);
            }

            // Actualizar habitación a ocupado
            $stmtHab = $this->pdo->prepare("UPDATE habitaciones SET estado = 'ocupado' WHERE id = ?");
            $stmtHab->execute([$data['hab_id']]);

            // No crear tarea de limpieza al registrar el check-in.
            // La tarea de limpieza debe generarse únicamente en el checkout (finalizarStay)
            // o desde reservas programadas. Evitamos llamadas automáticas aquí.

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
            $sql = "UPDATE rooming_stays SET 
                fecha_registro = :fecha_reg, 
                fecha_checkout = :fecha_out, 
                fecha_checkout_original = IF(estado != 'late_checkout', :fecha_out_original, fecha_checkout_original),
                hora_checkin = :hora_in, medio_reserva = :medio, 
                habitacion_id = :hab_id, tipo_hab_declarado = :tipo_hab, 
                noches = :noches, pax_total = :pax_total, total_pago = :total, 
                moneda_pago = :moneda, monto_original = :monto_orig, 
                tc_aplicado = :tc, metodo_pago = :metodo, 
                tipo_comprobante = :comprobante, num_comprobante = :num_comp, 
                ruc_factura = :ruc, razon_social = :razon_social, observaciones = :obs, 
                procedencia = :procedencia, carro = :carro,
                estado = :estado
                WHERE id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            $data['id'] = $id;
            // Remove operator/uid from update or keep them? Keep them as provided in $data.
            // For simplicity, I'll pass the whole mapped array.
            $stmt->execute([
                'fecha_reg'   => $data['fecha_reg'],
                'fecha_out'   => $data['fecha_out'],
                'fecha_out_original' => $data['fecha_out'],
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
                'razon_social'=> $data['razon_social'] ?? '',
                'obs'         => $data['obs'],
                'procedencia' => $data['procedencia'] ?? '',
                'carro'       => $data['carro'] ?? 'NO',
                'estado'      => $data['estado'] ?? 'activo',
                'id'          => $id
            ]);

            // Update room to 'ocupado'
            $stmtHab = $this->pdo->prepare("UPDATE habitaciones SET estado = 'ocupado' WHERE id = ?");
            $stmtHab->execute([$data['hab_id']]);

            // Replace PAX
            $this->pdo->prepare("DELETE FROM rooming_pax WHERE stay_id = ?")->execute([$id]);
            $stmtPax = $this->pdo->prepare("INSERT INTO rooming_pax (stay_id, nombre_completo, documento_tipo, documento_num, nacionalidad, ciudad, celular, email, empresa, es_titular, es_corporativo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($paxList as $p) {
                $stmtPax->execute([
                    $id, 
                    $p['nombre_completo'], 
                    $p['documento_tipo'], 
                    $p['documento_num'],
                    $p['nacionalidad'] ?? '',
                    $p['ciudad'] ?? '',
                    $p['celular'] ?? null,
                    $p['email'] ?? null,
                    $p['empresa'] ?? null,
                    $p['es_titular'] ? 1 : 0,
                    !empty($p['es_corporativo']) ? 1 : 0
                ]);
            }

            // Recalcular y actualizar totales cobrados dinámicamente según la moneda actual y TC de la estadía
            $this->actualizarResumenPagos($id);

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
            $stmtLimpieza->execute([date('Y-m-d'), $hab_id, $numHab, $_SESSION['auth_id'] ?? 1]);

            if ($mustCommit) $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($mustCommit) $this->pdo->rollBack();
            return false;
        }
    }

    public function actualizarResumenPagos(int $stay_id) {
        // 1. Obtener la moneda y TC del stay para poder reconvertir pagos en divisa distinta
        $stmtStay = $this->pdo->prepare("SELECT moneda_pago, tc_aplicado, total_pago FROM rooming_stays WHERE id = ?");
        $stmtStay->execute([$stay_id]);
        $stay = $stmtStay->fetch(PDO::FETCH_ASSOC);

        if (!$stay) return;

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
                    WHEN moneda = :m1 THEN monto
                    ELSE 
                        CASE 
                            WHEN :m2 = 'CLP' THEN monto_pen * :tc1
                            ELSE monto_pen / NULLIF(:tc2, 0)
                        END
                END
            ) FROM anticipos WHERE stay_id = :sid
        ");
        $stmt->execute([
            'm1'  => $monedaStay, 
            'm2'  => $monedaStay, 
            'tc1' => $tcStay, 
            'tc2' => $tcStay, 
            'sid' => $stay_id
        ]);
        $totalCobradoOrig = (float)$stmt->fetchColumn();

        // 4. Obtener total de consumos cargados
        $stmtCons = $this->pdo->prepare("SELECT SUM(total) FROM rooming_consumos WHERE stay_id = ?");
        $stmtCons->execute([$stay_id]);
        $totalConsumos = (float)$stmtCons->fetchColumn();

        $grandTotal = $totalPago + $totalConsumos;

        // 5. Estado de pago (basado en PEN, que es la moneda contable base)
        $estadoPago = 'pendiente';
        if ($totalCobrado > 0) {
            if ($totalCobrado >= $grandTotal - 0.05) $estadoPago = 'pagado';
            else $estadoPago = 'parcial';
        }

        $stmt = $this->pdo->prepare("UPDATE rooming_stays SET total_cobrado = ?, total_cobrado_orig = ?, estado_pago = ? WHERE id = ?");
        $stmt->execute([$totalCobrado, $totalCobradoOrig, $estadoPago, $stay_id]);
    }

    public function incrementarTotal(int $stayId, float $montoPen): bool {
        $stmt = $this->pdo->prepare("UPDATE rooming_stays SET total_pago = total_pago + ? WHERE id = ?");
        $res = $stmt->execute([$montoPen, $stayId]);
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
        $uid = $_SESSION['auth_id'] ?? null;
        if ($uid) {
            $chk = $this->pdo->prepare("SELECT id FROM usuarios WHERE id = ? LIMIT 1");
            $chk->execute([$uid]);
            if (!$chk->fetchColumn()) $uid = null;
        }
        if (!$uid) {
            $uid = (int)$this->pdo->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn() ?: 1;
        }
        $stmt->execute([date('Y-m-d'), $hab_id, $numHab, $uid]);
    }

    /**
     * Genera el reporte PAX mensual.
     * Retorna una fila por cada huésped (PAX) de los check-ins del mes/año indicados.
     * Los campos del stay (hab, fechas, pago, etc.) se incluyen en todas las filas
     * pero el frontend solo las muestra en la fila del titular (es_titular = 1).
     *
     * @param int $mes  Mes (1-12)
     * @param int $anio Año (ej. 2025)
     * @return array
     */
    public function getReportePax(int $mes, int $anio): array {
        $sql = "
            SELECT
                s.id            AS stay_id,
                s.operador,
                s.fecha_registro,
                s.fecha_checkout,
                s.fecha_checkout_original,
                s.hora_checkin,
                h.numero        AS hab_numero,
                s.tipo_hab_declarado,
                s.pax_total,
                s.medio_reserva,
                s.total_pago,
                s.moneda_pago,
                s.monto_original,
                s.estado,
                s.metodo_pago,
                s.tipo_comprobante,
                s.num_comprobante,
                s.cobrador,
                s.carro,
                s.observaciones,
                p.nombre_completo,
                p.documento_tipo,
                p.documento_num,
                p.nacionalidad,
                p.ciudad,
                p.celular,
                p.email,
                p.es_titular,
                p.id            AS pax_id
            FROM rooming_stays s
            JOIN habitaciones h  ON h.id = s.habitacion_id
            JOIN rooming_pax p   ON p.stay_id = s.id
            WHERE MONTH(s.fecha_registro) = :mes
              AND YEAR(s.fecha_registro)  = :anio
              AND s.checkin_realizado = 1
            ORDER BY s.fecha_registro ASC, s.id ASC, p.es_titular DESC, p.id ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['mes' => $mes, 'anio' => $anio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Actualiza masivamente los datos editados desde la vista del Reporte PAX (excel editable).
     */
    public function updateReportePax(array $rows): array {
        try {
            $this->pdo->beginTransaction();

            // Preparar consultas para actualización
            $stmtHab = $this->pdo->prepare("SELECT id FROM habitaciones WHERE numero = ? LIMIT 1");

            $stmtStay = $this->pdo->prepare("
                UPDATE rooming_stays SET
                    operador = :operador,
                    fecha_registro = :fecha_registro,
                    fecha_checkout = :fecha_checkout,
                    fecha_checkout_original = IF(estado != 'late_checkout', :fecha_checkout_original, fecha_checkout_original),
                    hora_checkin = :hora_checkin,
                    habitacion_id = COALESCE(:habitacion_id, habitacion_id),
                    tipo_hab_declarado = :tipo_hab_declarado,
                    pax_total = :pax_total,
                    medio_reserva = :medio_reserva,
                    total_pago = :total_pago,
                    moneda_pago = :moneda_pago,
                    monto_original = :monto_original,
                    estado = :estado,
                    metodo_pago = :metodo_pago,
                    tipo_comprobante = :tipo_comprobante,
                    num_comprobante = :num_comprobante,
                    cobrador = :cobrador,
                    carro = :carro,
                    observaciones = :observaciones
                WHERE id = :stay_id
            ");

            $stmtPax = $this->pdo->prepare("
                UPDATE rooming_pax SET
                    nombre_completo = :nombre_completo,
                    documento_tipo = :documento_tipo,
                    documento_num = :documento_num,
                    nacionalidad = :nacionalidad,
                    ciudad = :ciudad,
                    celular = :celular,
                    email = :email
                WHERE id = :pax_id
            ");

            $updatedStays = [];
            foreach ($rows as $row) {
                $stayId = isset($row['stay_id']) ? (int)$row['stay_id'] : null;
                $paxId  = isset($row['pax_id'])  ? (int)$row['pax_id']  : null;

                if (!$stayId || !$paxId) {
                    continue;
                }

                if (!in_array($stayId, $updatedStays)) {
                    $habId = null;
                    if (!empty($row['hab_numero'])) {
                        $numHab = ltrim($row['hab_numero'], '#');
                        $stmtHab->execute([$numHab]);
                        $habId = $stmtHab->fetchColumn() ?: null;
                    }

                    $fechaReg = $this->parseFechaParaDB($row['fecha_registro'] ?? null);
                    $fechaOut = $this->parseFechaParaDB($row['fecha_checkout'] ?? null);

                    $estado = ($row['estado'] ?? '') === 'late_checkout' ? 'late_checkout' : 'activo';
                    if (isset($row['estado'])) {
                        if ($row['estado'] === 'SI' || $row['estado'] === 'late_checkout' || $row['estado'] === 'late') {
                            $estado = 'late_checkout';
                        } else if ($row['estado'] === 'NO') {
                            $estado = 'activo';
                        } else {
                            $estado = $row['estado'];
                        }
                    }

                    $stmtStay->execute([
                        'operador'           => $row['operador'] ?? '',
                        'fecha_registro'     => $fechaReg,
                        'fecha_checkout'     => $fechaOut,
                        'fecha_checkout_original' => $fechaOut,
                        'hora_checkin'       => $row['hora_checkin'] ?? '',
                        'habitacion_id'      => $habId,
                        'tipo_hab_declarado' => $row['tipo_hab_declarado'] ?? '',
                        'pax_total'          => isset($row['pax_total']) ? (int)$row['pax_total'] : 1,
                        'medio_reserva'      => $row['medio_reserva'] ?? '',
                        'total_pago'         => isset($row['total_pago']) ? (float)$row['total_pago'] : 0.0,
                        'moneda_pago'        => $row['moneda_pago'] ?? 'PEN',
                        'monto_original'     => isset($row['monto_original']) ? (float)$row['monto_original'] : 0.0,
                        'estado'             => $estado,
                        'metodo_pago'        => $row['metodo_pago'] ?? '',
                        'tipo_comprobante'   => $row['tipo_comprobante'] ?? '',
                        'num_comprobante'    => $row['num_comprobante'] ?? '',
                        'cobrador'           => $row['cobrador'] ?? '',
                        'carro'              => $row['carro'] ?? '',
                        'observaciones'      => $row['observaciones'] ?? '',
                        'stay_id'            => $stayId
                    ]);

                    $updatedStays[] = $stayId;
                }

                $stmtPax->execute([
                    'nombre_completo' => $row['nombre_completo'] ?? '',
                    'documento_tipo'  => $row['documento_tipo'] ?? '',
                    'documento_num'   => $row['documento_num'] ?? '',
                    'nacionalidad'    => $row['nacionalidad'] ?? '',
                    'ciudad'          => $row['ciudad'] ?? '',
                    'celular'         => $row['celular'] ?? '',
                    'email'           => $row['email'] ?? '',
                    'pax_id'          => $paxId
                ]);
            }

            $this->pdo->commit();
            return ['ok' => true, 'msg' => 'Reporte guardado exitosamente'];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    private function parseFechaParaDB(?string $fecha): ?string {
        if (!$fecha) return null;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return $fecha;
        }
        // Soporta dd/mm/yy y dd/mm/yyyy
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})$/', $fecha, $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = $matches[3];
            if (strlen($year) === 2) {
                $year = '20' . $year;
            }
            return "$year-$month-$day";
        }
        return $fecha;
    }
}
