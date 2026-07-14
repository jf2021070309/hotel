<?php
/**
 * app/Models/ReservasModel.php
 */
class ReservasModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Returns all rooms with their stays for the given month.
     * Single optimized JOIN — no N+1 queries.
     */
    public function getDatosAnio(int $anio): array {
        // Auto-cancelar reservas vencidas (donde hoy es posterior a la fecha de checkout y siguen como 'reservado')
        $this->pdo->query("UPDATE rooming_stays SET estado = 'cancelado' WHERE estado = 'reservado' AND fecha_checkout < CURRENT_DATE");

        $primerDia  = sprintf('%04d-01-01', $anio);
        $ultimoDia  = sprintf('%04d-12-31', $anio);
        $diasEnAnio = (int)date('z', strtotime($ultimoDia)) + 1;

        // 1. All rooms
        $stmtHab = $this->pdo->query(
            "SELECT id, numero, tipo, estado,
                    CAST(SUBSTRING(numero, 1, 1) AS UNSIGNED) AS piso
             FROM habitaciones
             ORDER BY piso ASC, numero ASC"
        );
        $habitacionesRaw = $stmtHab->fetchAll(PDO::FETCH_ASSOC);

        // 2. All stays overlapping the year (single query)
        $stmt = $this->pdo->prepare(
            "SELECT
                 s.id,
                 s.habitacion_id,
                 s.fecha_registro,
                 s.fecha_checkout,
                 DATEDIFF(s.fecha_checkout, s.fecha_registro) AS noches,
                 s.pax_total,
                 s.estado_pago,
                 s.total_pago,
                 s.total_cobrado,
                 s.moneda_pago,
                 s.medio_reserva  AS canal,
                 s.estado,
                 s.metodo_pago,
                 s.observaciones,
                 c.nombre_razon_social AS titular
             FROM rooming_stays s
             LEFT JOIN rooming_pax p ON p.stay_id = s.id AND p.es_titular_acompanante = 1
             LEFT JOIN clientes c ON c.id = p.cliente_id
             WHERE s.estado IN ('activo','late_checkout','reservado','finalizado')
               AND s.fecha_registro <= :ultimo
               AND s.fecha_checkout  > :primero"
        );
        $stmt->execute([':ultimo' => $ultimoDia, ':primero' => $primerDia]);
        $staysRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Index by room id
        $staysByRoom = [];
        foreach ($staysRaw as $s) {
            $diaIni  = (int)date('z', strtotime(max($s['fecha_registro'], $primerDia))) + 1;
            
            $dtFin   = strtotime($s['fecha_checkout']);
            $dtLimit = strtotime($ultimoDia);

            if ($dtFin > $dtLimit) {
                $diaFin = $diasEnAnio + 1;
            } else {
                $diaFin = (int)date('z', $dtFin) + 1;
            }

            $cols = max(1, $diaFin - $diaIni);

            $staysByRoom[$s['habitacion_id']][] = [
                'id'            => (int)$s['id'],
                'dia_inicio'    => $diaIni,
                'dia_fin'       => $diaFin,
                'cols'          => $cols,
                'fecha_inicio'  => $s['fecha_registro'],
                'fecha_fin'     => $s['fecha_checkout'],
                'noches'        => (int)$s['noches'],
                'titular'       => $s['titular'] ?? '---',
                'pax'           => (int)$s['pax_total'],
                'estado_pago'   => $s['estado_pago'],
                'total_pago'    => (float)$s['total_pago'],
                'total_cobrado' => (float)$s['total_cobrado'],
                'moneda_pago'   => $s['moneda_pago'],
                'canal'         => $s['canal'],
                'estado'        => $s['estado'],
                'metodo_pago'   => $s['metodo_pago'],
                'observaciones' => $s['observaciones'],
            ];
        }

        // 4. Build result
        $habitaciones = [];
        foreach ($habitacionesRaw as $h) {
            $habitaciones[] = [
                'id'     => (int)$h['id'],
                'numero' => $h['numero'],
                'tipo'   => $h['tipo'],
                'estado' => $h['estado'],
                'piso'   => (int)$h['piso'],
                'stays'  => $staysByRoom[$h['id']] ?? [],
            ];
        }

        return ['habitaciones' => $habitaciones, 'dias_en_anio' => $diasEnAnio];
    }

    /**
     * Today's summary panel.
     */
    public function getResumenDia(string $fecha): array {
        // Sincronizar vencimientos antes del resumen
        $this->pdo->query("UPDATE rooming_stays SET estado = 'cancelado' WHERE estado = 'reservado' AND fecha_checkout < CURRENT_DATE");

        $stmt = $this->pdo->prepare(
            "SELECT
                 COUNT(DISTINCT s.id)               AS ocupadas,
                 (SELECT COUNT(*) FROM habitaciones) AS total,
                 COALESCE(SUM(s.pax_total), 0)       AS pax_total,
                 COALESCE(SUM(s.total_cobrado), 0)   AS ingresos_hoy,
                 SUM(s.estado_pago != 'pagado')      AS pendientes,
                 SUM(s.estado_pago = 'pendiente')    AS cnt_pendiente,
                 SUM(s.estado_pago = 'adelanto')     AS cnt_adelanto,
                 SUM(s.estado_pago = 'parcial')      AS cnt_parcial,
                 SUM(s.estado_pago = 'pagado')       AS cnt_pagado
             FROM rooming_stays s
             WHERE s.estado IN ('activo','late_checkout')
               AND s.fecha_registro <= :fecha
               AND s.fecha_checkout  > :fecha2"
        );
        $stmt->execute([':fecha' => $fecha, ':fecha2' => $fecha]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'ocupadas'      => (int)($row['ocupadas']      ?? 0),
            'total'         => (int)($row['total']         ?? 0),
            'pax_total'     => (int)($row['pax_total']     ?? 0),
            'ingresos_hoy'  => (float)($row['ingresos_hoy'] ?? 0),
            'pendientes'    => (int)($row['pendientes']    ?? 0),
            'cnt_pendiente' => (int)($row['cnt_pendiente'] ?? 0),
            'cnt_adelanto'  => (int)($row['cnt_adelanto']  ?? 0),
            'cnt_parcial'   => (int)($row['cnt_parcial']   ?? 0),
            'cnt_pagado'    => (int)($row['cnt_pagado']    ?? 0),
        ];
    }

    /**
     * Register a quick payment and recalculate estado_pago.
     */
    public function pagoRapido(int $stay_id, float $monto, string $moneda, string $metodo, float $tc, int $uid): array {
        $monto_pen = $moneda === 'PEN' ? $monto : round($monto * $tc, 2);

        $mustCommit = !$this->pdo->inTransaction();
        if ($mustCommit) {
            $this->pdo->beginTransaction();
        }
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO anticipos (stay_id, monto, moneda, monto_pen, tc_aplicado, tipo_pago, recibo, fecha, usuario_id)
                 VALUES (?, ?, ?, ?, ?, ?, '', NOW(), ?)"
            );
            $stmt->execute([$stay_id, $monto, $moneda, $monto_pen, $tc, $metodo, $uid]);

            // Recalculate
            $stmt = $this->pdo->prepare("SELECT SUM(monto_pen) FROM anticipos WHERE stay_id = ?");
            $stmt->execute([$stay_id]);
            $totalCobrado = (float)$stmt->fetchColumn();

            $stmt = $this->pdo->prepare("SELECT total_pago FROM rooming_stays WHERE id = ?");
            $stmt->execute([$stay_id]);
            $totalPago = (float)$stmt->fetchColumn();

            $estadoPago = 'pendiente';
            if ($totalCobrado >= $totalPago)               $estadoPago = 'pagado';
            elseif ($totalCobrado >= $totalPago * 0.5)     $estadoPago = 'parcial';
            elseif ($totalCobrado > 0)                     $estadoPago = 'adelanto';

            $stmt = $this->pdo->prepare(
                "UPDATE rooming_stays SET total_cobrado = ?, estado_pago = ? WHERE id = ?"
            );
            $stmt->execute([$totalCobrado, $estadoPago, $stay_id]);

            $this->pdo->commit();
            return ['ok' => true, 'total_cobrado' => $totalCobrado, 'estado_pago' => $estadoPago];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Mark a stay as late_checkout.
     */
    public function lateCheckout(int $id): bool {
        $stmt = $this->pdo->prepare(
            "UPDATE rooming_stays SET estado = 'late_checkout' WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }

    /**
     * Create a brief reservation (Quick Reservation).
     */
    public function registrarReservaRapida(array $data): int {
        $fecha_fin = date('Y-m-d', strtotime($data['fecha_inicio'] . " + {$data['noches']} days"));
        
        $mustCommit = !$this->pdo->inTransaction();
        if ($mustCommit) {
            $this->pdo->beginTransaction();
        }
        try {
            // First insert a placeholder client (Unique dummy doc to avoid UNIQUE constraint conflicts)
            $stmtCli = $this->pdo->prepare("INSERT INTO clientes (nombre_razon_social, documento_tipo, documento_num, tipo_cliente) VALUES (?, 'DNI', ?, 'NATURAL')");
            $stmtCli->execute([$data['titular'], uniqid('R_')]);
            $clienteId = (int)$this->pdo->lastInsertId();

            $sql = "INSERT INTO rooming_stays (
                operador, fecha_registro, fecha_checkout, medio_reserva, 
                habitacion_id, tipo_hab_declarado, pax_total, total_pago, 
                moneda_pago, metodo_pago, tipo_comprobante, cobrador, 
                observaciones, usuario_id, estado, estado_pago, cliente_titular_id
            ) VALUES (
                :operador, :fecha_reg, :fecha_out, :medio, 
                :hab_id, 'RESERVA', 1, 0, 
                'PEN', 'EFECTIVO', 'TICKET', :cobrador, 
                :obs, :uid, 'reservado', 'pendiente', :cliente_titular_id
            )";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'operador' => $_SESSION['auth_nombre'] ?? 'Admin',
                'fecha_reg' => $data['fecha_inicio'],
                'fecha_out' => $fecha_fin,
                'hab_id'    => $data['hab_id'],
                'cobrador'  => $_SESSION['auth_nombre'] ?? 'Admin',
                'obs'       => $data['observaciones'] ?? '',
                'uid'       => $data['usuario_id'],
                'medio'     => $data['canal'] ?? 'DIRECTO',
                'cliente_titular_id' => $clienteId
            ]);
            
            $stay_id = (int)$this->pdo->lastInsertId();
            
            $stmtPax = $this->pdo->prepare("INSERT INTO rooming_pax (stay_id, cliente_id, es_titular_acompanante) VALUES (?, ?, 1)");
            $stmtPax->execute([$stay_id, $clienteId]);
            
            if ($mustCommit) {
                $this->pdo->commit();
            }
            return $stay_id;
        } catch (Exception $e) {
            if ($mustCommit && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Activate a reservation (Check-in).
     * Changes status to 'activo' and marks room as 'ocupado'.
     */
    public function activarStay(int $id): bool {
        $mustCommit = !$this->pdo->inTransaction();
        if ($mustCommit) {
            $this->pdo->beginTransaction();
        }
        try {
            // 1. Update stay status + mark as checked-in
            $stmt = $this->pdo->prepare(
                "UPDATE rooming_stays SET estado = 'activo', checkin_realizado = 1, fecha_checkin_real = NOW() WHERE id = ?"
            );
            $stmt->execute([$id]);

            // 2. Get hab_id
            $stmtHabId = $this->pdo->prepare("SELECT habitacion_id FROM rooming_stays WHERE id = ?");
            $stmtHabId->execute([$id]);
            $habId = $stmtHabId->fetchColumn();

            if ($habId) {
                // 3. Update room status to 'ocupado'
                $stmtRoom = $this->pdo->prepare("UPDATE habitaciones SET estado = 'ocupado' WHERE id = ?");
                $stmtRoom->execute([$habId]);
            }

            if ($mustCommit) {
                $this->pdo->commit();
            }
            return true;
        } catch (Exception $e) {
            if ($mustCommit && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Update a quick reservation while it is still reserved.
     */
    public function actualizarReservaRapida(int $id, array $data): bool {
        $fecha_fin = date('Y-m-d', strtotime($data['fecha_inicio'] . " + {$data['noches']} days"));

        $mustCommit = !$this->pdo->inTransaction();
        if ($mustCommit) {
            $this->pdo->beginTransaction();
        }

        try {
            $stmtStay = $this->pdo->prepare("SELECT id FROM rooming_stays WHERE id = ? AND estado = 'reservado' FOR UPDATE");
            $stmtStay->execute([$id]);

            if (!$stmtStay->fetchColumn()) {
                throw new Exception('Solo se pueden editar reservas en estado reservado');
            }

            $stmt = $this->pdo->prepare("
                UPDATE rooming_stays
                SET fecha_registro = ?,
                    fecha_checkout = ?,
                    medio_reserva = ?,
                    observaciones = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['fecha_inicio'],
                $fecha_fin,
                $data['canal'],
                $data['observaciones'] ?? '',
                $id
            ]);

            // Update titular name in clientes table via junction
            $stmtGetCliente = $this->pdo->prepare("
                SELECT rp.cliente_id FROM rooming_pax rp
                WHERE rp.stay_id = ? AND rp.es_titular_acompanante = 1 LIMIT 1
            ");
            $stmtGetCliente->execute([$id]);
            $clienteId = $stmtGetCliente->fetchColumn();

            if ($clienteId) {
                $stmtUpCli = $this->pdo->prepare("UPDATE clientes SET nombre_razon_social = ? WHERE id = ?");
                $stmtUpCli->execute([$data['titular'], $clienteId]);
            } else {
                // Create new client + pax link (Unique dummy doc to avoid UNIQUE constraint conflicts)
                $stmtCli = $this->pdo->prepare("INSERT INTO clientes (nombre_razon_social, documento_tipo, documento_num, tipo_cliente) VALUES (?, 'DNI', ?, 'NATURAL')");
                $stmtCli->execute([$data['titular'], uniqid('R_')]);
                $newCliId = (int)$this->pdo->lastInsertId();
                
                $stmtInsPax = $this->pdo->prepare("INSERT INTO rooming_pax (stay_id, cliente_id, es_titular_acompanante) VALUES (?, ?, 1)");
                $stmtInsPax->execute([$id, $newCliId]);

                // Also update stays titular
                $stmtUpStayTit = $this->pdo->prepare("UPDATE rooming_stays SET cliente_titular_id = ? WHERE id = ?");
                $stmtUpStayTit->execute([$newCliId, $id]);
            }

            if ($mustCommit) {
                $this->pdo->commit();
            }
            return true;
        } catch (Exception $e) {
            if ($mustCommit && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Reject a reservation and free the room if it was still blocked as reserved.
     */
    public function rechazarStay(int $id): bool {
        $mustCommit = !$this->pdo->inTransaction();
        if ($mustCommit) {
            $this->pdo->beginTransaction();
        }
        try {
            $stmtStay = $this->pdo->prepare("SELECT habitacion_id, estado FROM rooming_stays WHERE id = ? FOR UPDATE");
            $stmtStay->execute([$id]);
            $stay = $stmtStay->fetch(PDO::FETCH_ASSOC);

            if (!$stay) {
                throw new Exception('Reserva no encontrada');
            }

            if ($stay['estado'] !== 'reservado') {
                throw new Exception('Solo se pueden rechazar reservas en estado reservado');
            }

            $stmt = $this->pdo->prepare("UPDATE rooming_stays SET estado = 'cancelado' WHERE id = ?");
            $stmt->execute([$id]);

            $stmtRoom = $this->pdo->prepare("UPDATE habitaciones SET estado = 'libre' WHERE id = ? AND estado = 'reservado'");
            $stmtRoom->execute([(int)$stay['habitacion_id']]);

            if ($mustCommit) {
                $this->pdo->commit();
            }
            return true;
        } catch (Exception $e) {
            if ($mustCommit && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Cambiar estado de una habitación desde el cuadro de reservas.
     */
    public function cambiarEstadoHabitacion(int $hab_id, string $estado): bool {
        require_once __DIR__ . '/HabitacionModel.php';
        $habModel = new HabitacionModel($this->pdo);

        if ($estado === 'libre') {
            try {
                $stmt = $this->pdo->prepare("
                    UPDATE rooming_stays s
                    JOIN rooming_pax p ON p.stay_id = s.id AND p.es_titular_acompanante = 1
                    JOIN clientes c ON c.id = p.cliente_id
                    SET s.estado = 'cancelado'
                    WHERE s.habitacion_id = ? 
                      AND s.estado IN ('reservado', 'activo', 'inhouse')
                      AND c.nombre_razon_social IN ('[SUCIO]', '[MANTENIMIENTO]')
                ");
                $stmt->execute([$hab_id]);
            } catch (Exception $e) {}
        }

        return $habModel->actualizarEstado($hab_id, $estado);
    }
}
