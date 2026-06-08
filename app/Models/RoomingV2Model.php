<?php
/**
 * app/Models/RoomingV2Model.php
 * Modelo para la grilla Rooming V2 utilizando las tablas relacionales existentes y sincronizando finanzas.
 */
require_once __DIR__ . '/../Helpers/FinanzasHelper.php';

class RoomingV2Model {
    private PDO $pdo;
    private FinanzasHelper $finanzas;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->finanzas = new FinanzasHelper($pdo);
    }

    /**
     * Obtiene los registros del mes/año indicados cruzando las tablas existentes.
     */
    public function getReporte(int $mes, int $anio): array {
        $sql = "
            SELECT
                s.id            AS stay_id,
                GROUP_CONCAT(c.id ORDER BY p.es_titular_acompanante DESC, c.id ASC SEPARATOR ',') AS pax_ids,
                s.operador,
                s.fecha_registro AS fecha,
                h.numero        AS hab,
                s.tipo_hab_declarado AS tipo_hab,
                s.pax_total     AS pax,
                s.medio_reserva,
                DATE_FORMAT(s.fecha_checkin_real, '%H:%i') AS hora_checkin,
                GROUP_CONCAT(c.nombre_razon_social ORDER BY p.es_titular_acompanante DESC, c.id ASC SEPARATOR '\n') AS nombre_apellido,
                GROUP_CONCAT(c.documento_tipo ORDER BY p.es_titular_acompanante DESC, c.id ASC SEPARATOR '\n') AS documento_tipo,
                GROUP_CONCAT(IF(c.documento_num LIKE 'R_%', '', c.documento_num) ORDER BY p.es_titular_acompanante DESC, c.id ASC SEPARATOR '\n') AS documento_num,
                GROUP_CONCAT(COALESCE(c.nacionalidad, '') ORDER BY p.es_titular_acompanante DESC, c.id ASC SEPARATOR '\n') AS nacionalidad,
                GROUP_CONCAT(COALESCE(c.ciudad, '') ORDER BY p.es_titular_acompanante DESC, c.id ASC SEPARATOR '\n') AS ciudad,
                s.fecha_registro AS fecha_checkin,
                (SELECT GROUP_CONCAT(hf.fecha_checkout_pasada ORDER BY hf.id ASC SEPARATOR '\n')
                 FROM rooming_stays_historial_fechas hf
                 WHERE hf.stay_id = s.id) AS fechas_checkout_historial,
                s.fecha_checkout,
                s.total_pago    AS pago_total,
                IF(s.estado = 'late_checkout', 'SI', 'NO') AS late_checkout,
                s.metodo_pago   AS medio_pago,
                s.tipo_comprobante AS comprobante_pago,
                s.num_comprobante AS numero_comprobante,
                s.cobrador      AS quien_cobro,
                s.carro,
                s.estado        AS estado_stay,
                s.observaciones
            FROM rooming_stays s
            LEFT JOIN habitaciones h  ON h.id = s.habitacion_id
            JOIN rooming_pax p        ON p.stay_id = s.id
            JOIN clientes c           ON c.id = p.cliente_id
            WHERE MONTH(s.fecha_registro) = :mes
              AND YEAR(s.fecha_registro)  = :anio
              AND s.checkin_realizado = 1
            GROUP BY s.id
            ORDER BY s.fecha_registro ASC, s.id ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['mes' => $mes, 'anio' => $anio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Guarda en masa:
     * - Si tiene stay_id y pax_id: actualiza el registro en las tablas existentes.
     * - Si no tiene stay_id: inserta como un nuevo check-in en las tablas existentes.
     * Sincroniza automáticamente los pagos en la tabla `anticipos` y `flujo_caja_movimientos`.
     */
    public function guardarReporte(array $rows): array {
        try {
            $this->pdo->beginTransaction();

            // Preparar statements comunes
            $stmtHab = $this->pdo->prepare("SELECT id FROM habitaciones WHERE numero = ? LIMIT 1");

            // Update stays statement
            $stmtUpdateStay = $this->pdo->prepare("
                UPDATE rooming_stays SET
                    operador = :operador,
                    fecha_registro = :fecha_registro,
                    fecha_checkout = :fecha_checkout,
                    fecha_checkin_real = :fecha_checkin_real,
                    habitacion_id = COALESCE(:habitacion_id, habitacion_id),
                    tipo_hab_declarado = :tipo_hab_declarado,
                    pax_total = :pax_total,
                    medio_reserva = :medio_reserva,
                    total_pago = :total_pago,
                    estado = :estado,
                    metodo_pago = :metodo_pago,
                    tipo_comprobante = :tipo_comprobante,
                    num_comprobante = :num_comprobante,
                    cobrador = :cobrador,
                    carro = :carro,
                    observaciones = :observaciones
                WHERE id = :stay_id
            ");

            // Update clientes statement
            $stmtUpdateCliente = $this->pdo->prepare("
                UPDATE clientes SET
                    nombre_razon_social = :nombre,
                    documento_tipo = :doc_tipo,
                    documento_num = :doc_num,
                    nacionalidad = :nacionalidad,
                    ciudad = :ciudad
                WHERE id = :cliente_id
            ");

            // Insert new stay statement
            $stmtInsertStay = $this->pdo->prepare("
                INSERT INTO rooming_stays (
                    operador, fecha_registro, fecha_checkout, fecha_checkin_real,
                    habitacion_id, tipo_hab_declarado, pax_total, medio_reserva,
                    total_pago, moneda_pago, monto_original, estado, metodo_pago,
                    tipo_comprobante, num_comprobante, cobrador, carro, observaciones,
                    checkin_realizado, estado_pago, usuario_id, cliente_titular_id
                ) VALUES (
                    :operador, :fecha_registro, :fecha_checkout, :fecha_checkin_real,
                    :habitacion_id, :tipo_hab_declarado, :pax_total, :medio_reserva,
                    :total_pago, 'PEN', :monto_original, :estado, :metodo_pago,
                    :tipo_comprobante, :num_comprobante, :cobrador, :carro, :observaciones,
                    1, 'pagado', :usuario_id, :cliente_titular_id
                )
            ");

            // Insert intermediates pax statement
            $stmtInsertPax = $this->pdo->prepare("
                INSERT INTO rooming_pax (stay_id, cliente_id, es_titular_acompanante)
                VALUES (?, ?, ?)
            ");

            // Sentencias para sincronización de anticipos
            $stmtSumAnticipos = $this->pdo->prepare("SELECT SUM(monto) FROM anticipos WHERE stay_id = ?");
            $stmtDelAnticipos = $this->pdo->prepare("DELETE FROM anticipos WHERE stay_id = ?");
            $stmtDelFlujoMovs = $this->pdo->prepare("DELETE FROM flujo_caja_movimientos WHERE stay_id = ?");
            $stmtInsertAnticipo = $this->pdo->prepare("
                INSERT INTO anticipos (stay_id, monto, moneda, monto_pen, tc_aplicado, tipo_pago, recibo, fecha, usuario_id) 
                VALUES (:stay_id, :monto, 'PEN', :monto_pen, 1, :tipo, '', :fecha, :uid)
            ");

            foreach ($rows as $row) {
                $stayId = isset($row['stay_id']) ? (int)$row['stay_id'] : null;

                // Formatear fechas
                $fechaReg  = $this->parseFecha($row['fecha'] ?? null) ?: date('Y-m-d');
                $fechasCheckoutArr = isset($row['fechas_checkout_all']) ? explode("\n", str_replace("\r", "", $row['fechas_checkout_all'])) : [];
                $fechaOutRaw = !empty($fechasCheckoutArr) ? end($fechasCheckoutArr) : ($row['fecha_checkout'] ?? null);
                $fechaOut  = $this->parseFecha($fechaOutRaw) ?: date('Y-m-d', strtotime('+1 day'));
                $estado    = ($row['late_checkout'] ?? 'NO') === 'SI' ? 'late_checkout' : 'activo';

                // Crear fecha_checkin_real a partir de la fecha y hora
                $horaInput = trim($row['hora_checkin'] ?? '');
                $horaReal = !empty($horaInput) && preg_match('/^\d{1,2}:\d{2}/', $horaInput) ? $horaInput . ':00' : date('H:i:s');
                $fechaCheckinReal = $fechaReg . ' ' . $horaReal;

                // Buscar ID de la habitación por número
                $habId = null;
                if (!empty($row['hab'])) {
                    $stmtHab->execute([trim($row['hab'])]);
                    $habId = $stmtHab->fetchColumn() ?: null;
                    if (!$habId) {
                        throw new Exception("La habitación #" . $row['hab'] . " no existe.");
                    }
                }

                if ($stayId) {
                    // --- ACTUALIZACIÓN DE EXISTENTE ---
                    
                    // 1. Separar los pasajeros por salto de línea
                    $names = isset($row['nombre_apellido']) ? explode("\n", str_replace("\r", "", $row['nombre_apellido'])) : [];
                    $docTypes = isset($row['documento_tipo']) ? explode("\n", str_replace("\r", "", $row['documento_tipo'])) : [];
                    $docNums = isset($row['documento_num']) ? explode("\n", str_replace("\r", "", $row['documento_num'])) : [];
                    $nacs = isset($row['nacionalidad']) ? explode("\n", str_replace("\r", "", $row['nacionalidad'])) : [];
                    $cities = isset($row['ciudad']) ? explode("\n", str_replace("\r", "", $row['ciudad'])) : [];
                    
                    $paxIds = [];
                    if (!empty($row['pax_ids'])) {
                        $paxIds = explode(",", $row['pax_ids']);
                    }

                    $maxPax = max(count($names), count($docNums));
                    
                    // Asegurar al menos 1 pasajero titular
                    if ($maxPax === 0) {
                        $maxPax = 1;
                        $names = ['HUESPED'];
                    }

                    $titularClienteId = null;
                    $actualPaxIds = [];

                    for ($i = 0; $i < $maxPax; $i++) {
                        $name = trim($names[$i] ?? '');
                        if (empty($name) && $i > 0) continue; // Ignorar líneas en blanco secundarias
                        if (empty($name)) $name = 'HUESPED';

                        $docType = trim($docTypes[$i] ?? ($docTypes[0] ?? 'DNI'));
                        if (empty($docType)) $docType = 'DNI';

                        $docNum = trim($docNums[$i] ?? '');
                        $nac = trim($nacs[$i] ?? 'Peruana');
                        $city = trim($cities[$i] ?? '');

                        $paxId = $paxIds[$i] ?? null;

                        if ($paxId) {
                            if (empty($docNum)) {
                                $stmtGet = $this->pdo->prepare("SELECT documento_num FROM clientes WHERE id = ?");
                                $stmtGet->execute([$paxId]);
                                $existingDoc = $stmtGet->fetchColumn();
                                if ($existingDoc && str_starts_with($existingDoc, 'R_')) {
                                    $docNum = $existingDoc;
                                } else {
                                    $docNum = uniqid('R_');
                                }
                            }
                            // Actualizar cliente existente
                            $stmtUpdateCliente->execute([
                                'nombre'       => $name,
                                'doc_tipo'     => $docType,
                                'doc_num'      => $docNum,
                                'nacionalidad' => $nac,
                                'ciudad'       => $city,
                                'cliente_id'   => $paxId
                            ]);
                            $actualPaxIds[] = (int)$paxId;
                            if ($i === 0) {
                                $titularClienteId = (int)$paxId;
                            }
                        } else {
                            // Insertar nuevo cliente
                            $newPaxId = $this->upsertCliente([
                                'nombre_apellido' => $name,
                                'documento_tipo'  => $docType,
                                'documento_num'   => $docNum,
                                'nacionalidad'    => $nac,
                                'ciudad'          => $city
                            ]);
                            // Vincular relación
                            $stmtInsertPax->execute([$stayId, $newPaxId, ($i === 0 ? 1 : 0)]);
                            $actualPaxIds[] = $newPaxId;
                            if ($i === 0) {
                                $titularClienteId = $newPaxId;
                            }
                        }
                    }

                    // Eliminar pasajeros removidos
                    if (count($paxIds) > count($actualPaxIds)) {
                        $stmtDeletePax = $this->pdo->prepare("DELETE FROM rooming_pax WHERE stay_id = ? AND cliente_id = ?");
                        foreach ($paxIds as $oldId) {
                            if (!in_array((int)$oldId, $actualPaxIds)) {
                                $stmtDeletePax->execute([$stayId, (int)$oldId]);
                            }
                        }
                    }

                    // Verificar si la fecha de checkout cambió para registrar en el historial
                    $stmtCheck = $this->pdo->prepare("SELECT fecha_checkout FROM rooming_stays WHERE id = ?");
                    $stmtCheck->execute([$stayId]);
                    $oldFechaOut = $stmtCheck->fetchColumn();

                    if ($oldFechaOut && $oldFechaOut !== $fechaOut) {
                        $stmtHist = $this->pdo->prepare("INSERT INTO rooming_stays_historial_fechas (stay_id, fecha_checkout_pasada, motivo, usuario_id) VALUES (?, ?, ?, ?)");
                        $stmtHist->execute([$stayId, $oldFechaOut, 'Ampliación de estadía desde grilla V2', $_SESSION['auth_id'] ?? 1]);
                    }

                    // Actualizar stay principal
                    $stmtUpdateStay->execute([
                        'operador'       => $row['operador'] ?? '',
                        'fecha_registro' => $fechaReg,
                        'fecha_checkout' => $fechaOut,
                        'fecha_checkin_real' => $fechaCheckinReal,
                        'habitacion_id'  => $habId,
                        'tipo_hab_declarado' => $row['tipo_hab'] ?? 'SIMPLE',
                        'pax_total'      => count($actualPaxIds),
                        'medio_reserva'  => $row['medio_reserva'] ?? 'DIRECTO',
                        'total_pago'     => isset($row['pago_total']) ? (float)$row['pago_total'] : 0.00,
                        'estado'         => $estado,
                        'metodo_pago'    => $row['medio_pago'] ?? '',
                        'tipo_comprobante' => $row['comprobante_pago'] ?? '',
                        'num_comprobante' => $row['numero_comprobante'] ?? '',
                        'cobrador'       => $row['quien_cobro'] ?? '',
                        'carro'          => $row['carro'] ?? 'NO',
                        'observaciones'  => $row['observaciones'] ?? '',
                        'stay_id'        => $stayId
                    ]);

                    if ($titularClienteId) {
                        $this->pdo->prepare("UPDATE rooming_stays SET cliente_titular_id = ? WHERE id = ?")->execute([$titularClienteId, $stayId]);
                    }

                    // Marcar habitación como ocupada
                    if ($habId) {
                        $this->pdo->prepare("UPDATE habitaciones SET estado = 'ocupado' WHERE id = ?")->execute([$habId]);
                    }

                    // --- SINCRONIZACIÓN DE PAGOS/FINANZAS PARA EDICIONES ---
                    $stmtSumAnticipos->execute([$stayId]);
                    $sumaActual = (float)$stmtSumAnticipos->fetchColumn();
                    $nuevoMonto = (float)($row['pago_total'] ?? 0.00);

                    // Si se modificó el monto total de pago
                    if (abs($nuevoMonto - $sumaActual) > 0.01) {
                        // Limpiar registros antiguos para evitar duplicados/descalces
                        $stmtDelAnticipos->execute([$stayId]);
                        $stmtDelFlujoMovs->execute([$stayId]);

                        if ($nuevoMonto > 0) {
                            // Insertar anticipo nuevo
                            $stmtInsertAnticipo->execute([
                                'stay_id'   => $stayId,
                                'monto'     => $nuevoMonto,
                                'monto_pen' => $nuevoMonto,
                                'tipo'      => $row['medio_pago'] ?? 'SOLES EFECTIVO',
                                'fecha'     => $fechaReg,
                                'uid'       => $_SESSION['auth_id'] ?? 1
                            ]);

                            // Sincronizar directo a flujo_caja_movimientos
                            $horaCheckinVal = !empty($row['hora_checkin']) ? (int)explode(':', $row['hora_checkin'])[0] : (int)date('H');
                            $turnoRegVal = FinanzasHelper::getTurnoActual($horaCheckinVal);
                            
                            $this->finanzas->registrarMovimientoAutomatico([
                                'usuario_id'  => $_SESSION['auth_id'] ?? 1,
                                'stay_id'     => $stayId,
                                'categoria'   => 'Alojamiento', 
                                'monto'       => $nuevoMonto, 
                                'moneda'      => 'PEN',
                                'medio_pago'  => $row['medio_pago'] ?? 'EFECTIVO',
                                'observacion' => "HOSPEDAJE: " . (explode("\n", $row['nombre_apellido'])[0] ?? 'Huésped') . " (Modificado en grilla V2) - Registro #$stayId (Hab #" . ($row['hab'] ?? 'N/A') . ")",
                                'fecha'       => $fechaReg,
                                'turno'       => $turnoRegVal
                            ]);
                        }
                    }

                    $this->actualizarResumenPagos($stayId);

                } else {
                    // --- INSERCIÓN DE NUEVO ---
                    if (empty($row['nombre_apellido']) && empty($row['hab'])) {
                        continue;
                    }

                    // 1. Separar acompañantes por salto de línea
                    $names = isset($row['nombre_apellido']) ? explode("\n", str_replace("\r", "", $row['nombre_apellido'])) : [];
                    $docTypes = isset($row['documento_tipo']) ? explode("\n", str_replace("\r", "", $row['documento_tipo'])) : [];
                    $docNums = isset($row['documento_num']) ? explode("\n", str_replace("\r", "", $row['documento_num'])) : [];
                    $nacs = isset($row['nacionalidad']) ? explode("\n", str_replace("\r", "", $row['nacionalidad'])) : [];
                    $cities = isset($row['ciudad']) ? explode("\n", str_replace("\r", "", $row['ciudad'])) : [];

                    $maxPax = max(count($names), count($docNums));
                    if ($maxPax === 0) {
                        $maxPax = 1;
                        $names = ['HUESPED'];
                    }

                    $titularId = null;
                    $insertedPaxIds = [];

                    for ($i = 0; $i < $maxPax; $i++) {
                        $name = trim($names[$i] ?? '');
                        if (empty($name) && $i > 0) continue;
                        if (empty($name)) $name = 'HUESPED';

                        $docType = trim($docTypes[$i] ?? ($docTypes[0] ?? 'DNI'));
                        if (empty($docType)) $docType = 'DNI';

                        $docNum = trim($docNums[$i] ?? '');
                        $nac = trim($nacs[$i] ?? 'Peruana');
                        $city = trim($cities[$i] ?? '');

                        $paxId = $this->upsertCliente([
                            'nombre_apellido' => $name,
                            'documento_tipo'  => $docType,
                            'documento_num'   => $docNum,
                            'nacionalidad'    => $nac,
                            'ciudad'          => $city
                        ]);

                        if ($i === 0) {
                            $titularId = $paxId;
                        }
                        $insertedPaxIds[] = $paxId;
                    }

                    if (!$titularId) {
                        $titularId = $this->upsertCliente(['nombre_apellido' => 'HUESPED']);
                        $insertedPaxIds[] = $titularId;
                    }

                    // 2. Insertar stay
                    $stmtInsertStay->execute([
                        'operador'           => $row['operador'] ?? '',
                        'fecha_registro'     => $fechaReg,
                        'fecha_checkout'     => $fechaOut,
                        'fecha_checkin_real' => $fechaCheckinReal,
                        'habitacion_id'      => $habId,
                        'tipo_hab_declarado' => $row['tipo_hab'] ?? 'SIMPLE',
                        'pax_total'          => count($insertedPaxIds),
                        'medio_reserva'      => $row['medio_reserva'] ?? 'DIRECTO',
                        'total_pago'         => isset($row['pago_total']) ? (float)$row['pago_total'] : 0.00,
                        'monto_original'     => isset($row['pago_total']) ? (float)$row['pago_total'] : 0.00,
                        'estado'             => $estado,
                        'metodo_pago'        => $row['medio_pago'] ?? '',
                        'tipo_comprobante'   => $row['comprobante_pago'] ?? '',
                        'num_comprobante'    => $row['numero_comprobante'] ?? '',
                        'cobrador'           => $row['quien_cobro'] ?? '',
                        'carro'              => $row['carro'] ?? 'NO',
                        'observaciones'      => $row['observaciones'] ?? '',
                        'usuario_id'         => $_SESSION['auth_id'] ?? 1,
                        'cliente_titular_id' => $titularId
                    ]);

                    $newStayId = (int)$this->pdo->lastInsertId();

                    // 3. Crear relación intermedia pax
                    foreach ($insertedPaxIds as $pId) {
                        $stmtInsertPax->execute([$newStayId, $pId, ($pId === $titularId ? 1 : 0)]);
                    }

                    // Marcar habitación como ocupada
                    if ($habId) {
                        $this->pdo->prepare("UPDATE habitaciones SET estado = 'ocupado' WHERE id = ?")->execute([$habId]);
                    }

                    // 4. Registrar anticipo y flujo automático
                    $nuevoMonto = (float)($row['pago_total'] ?? 0.00);
                    if ($nuevoMonto > 0) {
                        $stmtInsertAnticipo->execute([
                            'stay_id'   => $newStayId,
                            'monto'     => $nuevoMonto,
                            'monto_pen' => $nuevoMonto,
                            'tipo'      => $row['medio_pago'] ?? 'SOLES EFECTIVO',
                            'fecha'     => $fechaReg,
                            'uid'       => $_SESSION['auth_id'] ?? 1
                        ]);

                        // Registrar en flujo_caja_movimientos
                        $horaCheckinVal = !empty($row['hora_checkin']) ? (int)explode(':', $row['hora_checkin'])[0] : (int)date('H');
                        $turnoRegVal = FinanzasHelper::getTurnoActual($horaCheckinVal);
                        
                        $this->finanzas->registrarMovimientoAutomatico([
                            'usuario_id'  => $_SESSION['auth_id'] ?? 1,
                            'stay_id'     => $newStayId,
                            'categoria'   => 'Alojamiento', 
                            'monto'       => $nuevoMonto, 
                            'moneda'      => 'PEN',
                            'medio_pago'  => $row['medio_pago'] ?? 'EFECTIVO',
                            'observacion' => "HOSPEDAJE: " . (explode("\n", $row['nombre_apellido'])[0] ?? 'Huésped') . " - Registro #$newStayId (Hab #" . ($row['hab'] ?? 'N/A') . ")",
                            'fecha'       => $fechaReg,
                            'turno'       => $turnoRegVal
                        ]);
                    }

                    $this->actualizarResumenPagos($newStayId);
                }
            }

            $this->pdo->commit();
            return ['ok' => true, 'msg' => 'Cambios guardados con éxito en la base de datos central.'];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * Elimina el check-in (stay) de la base de datos relacional.
     * Libera automáticamente la habitación.
     */
    public function eliminarRegistro(int $stayId): bool {
        try {
            $this->pdo->beginTransaction();
            
            // Eliminar la relación de pasajeros
            $stmt1 = $this->pdo->prepare("DELETE FROM rooming_pax WHERE stay_id = ?");
            $stmt1->execute([$stayId]);

            // Eliminar los anticipos
            $stmtAnt = $this->pdo->prepare("DELETE FROM anticipos WHERE stay_id = ?");
            $stmtAnt->execute([$stayId]);

            // Eliminar los movimientos del flujo
            $stmtFl = $this->pdo->prepare("DELETE FROM flujo_caja_movimientos WHERE stay_id = ?");
            $stmtFl->execute([$stayId]);

            // Eliminar los consumos asociados
            $stmt2 = $this->pdo->prepare("DELETE FROM rooming_consumos WHERE stay_id = ?");
            $stmt2->execute([$stayId]);

            // Obtener el hab_id para volver a marcarlo como disponible (libre)
            $stmtHabId = $this->pdo->prepare("SELECT habitacion_id FROM rooming_stays WHERE id = ?");
            $stmtHabId->execute([$stayId]);
            $habId = $stmtHabId->fetchColumn();

            // Eliminar el stay principal
            $stmt3 = $this->pdo->prepare("DELETE FROM rooming_stays WHERE id = ?");
            $res = $stmt3->execute([$stayId]);

            if ($habId) {
                // Volver a marcar la habitación como disponible (libre)
                $this->pdo->prepare("UPDATE habitaciones SET estado = 'libre' WHERE id = ?")->execute([$habId]);
            }

            $this->pdo->commit();
            return $res;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    /**
     * Recalcula el total cobrado y actualiza el estado de pago del check-in.
     */
    public function actualizarResumenPagos(int $stay_id): void {
        $stmtStay = $this->pdo->prepare("SELECT moneda_pago, tc_aplicado, total_pago FROM rooming_stays WHERE id = ?");
        $stmtStay->execute([$stay_id]);
        $stay = $stmtStay->fetch(PDO::FETCH_ASSOC);

        if (!$stay) return;

        $monedaStay = $stay['moneda_pago'] ?? 'PEN';
        $tcStay     = (float)($stay['tc_aplicado'] ?? 1);
        $totalPago  = (float)($stay['total_pago'] ?? 0);

        // Total cobrado en PEN
        $stmt = $this->pdo->prepare("SELECT SUM(monto_pen) FROM anticipos WHERE stay_id = ?");
        $stmt->execute([$stay_id]);
        $totalCobrado = (float)$stmt->fetchColumn();

        // Total cobrado en la moneda original del stay
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

        // Obtener total de consumos cargados
        $stmtCons = $this->pdo->prepare("SELECT SUM(total) FROM rooming_consumos WHERE stay_id = ?");
        $stmtCons->execute([$stay_id]);
        $totalConsumos = (float)$stmtCons->fetchColumn();

        $grandTotal = $totalPago + $totalConsumos;

        $estadoPago = 'pendiente';
        if ($totalCobrado > 0) {
            if ($totalCobrado >= $grandTotal - 0.05) $estadoPago = 'pagado';
            else $estadoPago = 'parcial';
        }

        $stmt = $this->pdo->prepare("UPDATE rooming_stays SET total_cobrado = ?, total_cobrado_orig = ?, estado_pago = ? WHERE id = ?");
        $stmt->execute([$totalCobrado, $totalCobradoOrig, $estadoPago, $stay_id]);
    }

    private function upsertCliente(array $pax): int {
        $docTipo = $pax['documento_tipo'] ?? 'DNI';
        $docNum  = trim($pax['documento_num'] ?? '');
        $nombre  = trim($pax['nombre_apellido'] ?? 'HUÉSPED');
        $tipoCliente = ($docTipo === 'RUC') ? 'JURIDICO' : 'NATURAL';

        if (empty($docNum)) {
            $docNum = uniqid('R_');
        } else {
            $stmt = $this->pdo->prepare("SELECT id FROM clientes WHERE documento_tipo = ? AND documento_num = ? LIMIT 1");
            $stmt->execute([$docTipo, $docNum]);
            $existingId = $stmt->fetchColumn();
            if ($existingId) {
                // Actualizar datos del cliente
                $stmtUp = $this->pdo->prepare("UPDATE clientes SET nombre_razon_social = ?, nacionalidad = ?, ciudad = ?, tipo_cliente = ? WHERE id = ?");
                $stmtUp->execute([
                    $nombre,
                    $pax['nacionalidad'] ?? 'Peruana',
                    $pax['ciudad'] ?? '',
                    $tipoCliente,
                    $existingId
                ]);
                return (int)$existingId;
            }
        }

        // Crear nuevo
        $stmt = $this->pdo->prepare("INSERT INTO clientes (nombre_razon_social, documento_tipo, documento_num, nacionalidad, ciudad, tipo_cliente) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $nombre,
            $docTipo,
            $docNum,
            $pax['nacionalidad'] ?? 'Peruana',
            $pax['ciudad'] ?? '',
            $tipoCliente
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    private function parseFecha(?string $fecha): ?string {
        if (!$fecha) return null;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return $fecha;
        }
        if (preg_match('/^^(\d{1,2})\/(\d{1,2})\/(\d{2,4})$/', $fecha, $matches)) {
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
