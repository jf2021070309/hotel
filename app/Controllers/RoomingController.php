<?php
/**
 * app/Controllers/RoomingController.php
 */
class RoomingController {
    /**
     * @var PDO Conexión a la base de datos
     */
    private PDO $pdo;
    private RoomingModel $model;
    private AuditoriaModel $audit;
    
    /**
     * @var FinanzasHelper Helper para registro automático en flujo de caja
     */
    private FinanzasHelper $finanzas;

    /**
     * Constructor del modelo.
     * @param PDO $pdo Conexión activa.
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        require_once __DIR__ . '/../Models/RoomingModel.php';
        require_once __DIR__ . '/../Models/AuditoriaModel.php';
        $this->model = new RoomingModel($pdo);
        $this->audit = new AuditoriaModel($pdo);
    }

    /**
     * Obtiene la lista de estadías actualmente activas en el hotel.
     * @return array Resumen de huéspedes hospedados.
     */
    public function listarActivos() {
        return $this->model->getStaysActivos();
    }

    /**
     * Obtiene el detalle completo de una estadía (datos, pasajeros y pagos).
     * @param int $id ID de la estadía.
     * @return array|null Información detallada o null si no existe.
     */
    public function detalle(int $id) {
        return $this->model->getStayDetail($id);
    }

    /**
     * Procesa el proceso de Check-in o Activación de Reserva.
     * Valida datos, registra pasajeros y procesa el pago inicial si existe.
     * 
     * @param array $input Datos de la estadía y lista de pasajeros.
     * @return array Respuesta de éxito o error con mensaje descriptivo.
     */
    public function checkin(array $input) {
        $stayData = $input['stay'];
        $paxList = $input['pax'];
        
        // Mapeo manual de campos para coincidir con los placeholders del Modelo
        $mapped = [
            'operador'     => $_SESSION['auth_nombre'] ?? 'Administrador',
            'fecha_reg'    => $stayData['fecha_registro'],
            'fecha_out'    => $stayData['fecha_checkout'],
            'hora_in'      => $stayData['hora_checkin'],
            'medio'        => $stayData['medio_reserva'],
            'hab_id'       => $stayData['habitacion_id'],
            'tipo_hab'     => $stayData['tipo_hab_declarado'] ?? 'ESTANDAR',
            'noches'       => $stayData['noches'],
            'pax_total'    => count($paxList),
            'total'        => $stayData['total_pago'],
            'moneda'       => $stayData['moneda_pago'],
            'monto_orig'   => $stayData['monto_original'],
            'tc'           => $stayData['tc_aplicado'] ?? 1,
            'recargo'      => !empty($stayData['recargo_pos']) ? ((float)$stayData['total_pago'] * 0.05 / 1.05) : ($stayData['recargo_tarjeta'] ?? 0),
            'metodo'       => $stayData['metodo_pago'],
            'comprobante'  => $stayData['tipo_comprobante'],
            'num_comp'     => $stayData['num_comprobante'] ?? '',
            'ruc'          => $stayData['ruc_factura'] ?? '',
            'cobrador'     => $_SESSION['auth_nombre'] ?? '',
            'procedencia'  => $stayData['procedencia'] ?? '',
            'carro'        => $stayData['carro'] ?? 'NO',
            'obs'          => $stayData['observaciones'] ?? '',
            'uid'          => $_SESSION['auth_id'],
            'cobrado'      => $stayData['total_cobrado'] ?? 0,
            'cobrado_orig' => isset($input['adelanto']) ? (float)$input['adelanto'] : (float)($stayData['monto_original'] ?? 0),
            'est_pago'     => $stayData['estado_pago'] ?? 'pendiente',
            'estado'       => $stayData['estado'] ?? 'activo'
        ];
        
        $this->pdo->beginTransaction();
        try {
            if (!empty($stayData['id'])) {
                $stay_id = (int)$stayData['id'];
                
                // --- CAPTURA DE DATOS PARA AUDITORÍA DE EDICIÓN ---
                $original = $this->model->getStayDetail($stay_id);
                
                $this->model->actualizarStay($stay_id, $mapped, $paxList);
                $msg = "Registro actualizado correctamente";

                // Comparar cambios (solo campos principales de la estadía)
                $cambios = [];
                $labels = [
                    'habitacion_id'       => 'Habitación',
                    'fecha_checkout'      => 'Fecha Out',
                    'noches'              => 'Noches',
                    'total_pago'          => 'Total Pago',
                    'observaciones'       => 'Observaciones',
                    'tipo_hab_declarado'  => 'Tipo Hab.',
                    'metodo_pago'         => 'Método Pago'
                ];

                $mapeoOriginal = [
                    'habitacion_id'      => $original['habitacion_id'],
                    'fecha_checkout'     => $original['fecha_checkout'],
                    'noches'             => $original['noches'],
                    'total_pago'         => (float)$original['total_pago'],
                    'observaciones'      => $original['observaciones'],
                    'tipo_hab_declarado' => $original['tipo_hab_declarado'],
                    'metodo_pago'        => $original['metodo_pago']
                ];

                $mapeoNuevo = [
                    'habitacion_id'      => $mapped['hab_id'],
                    'fecha_checkout'     => $mapped['fecha_out'],
                    'noches'             => $mapped['noches'],
                    'total_pago'         => (float)$mapped['total'],
                    'observaciones'      => $mapped['obs'],
                    'tipo_hab_declarado' => $mapped['tipo_hab'],
                    'metodo_pago'        => $mapped['metodo']
                ];
                $labels = [
                    'hab_id' => 'Habitación', 
                    'fecha_out' => 'Salida', 
                    'noches' => 'Noches', 
                    'total' => 'Monto Total', 
                    'obs' => 'Observaciones', 
                    'metodo' => 'Método Pago'
                ];

                $originalBase = [
                    'hab_id' => (string)$original['habitacion_id'],
                    'fecha_out' => (string)$original['fecha_checkout'],
                    'noches' => (string)$original['noches'],
                    'total' => (float)$original['total_pago'],
                    'obs' => trim($original['observaciones']),
                    'metodo' => (string)$original['metodo_pago']
                ];

                $mapeoNuevo = [
                    'hab_id' => (string)$mapped['hab_id'],
                    'fecha_out' => (string)$mapped['fecha_out'],
                    'noches' => (string)$mapped['noches'],
                    'total' => (float)$mapped['total'],
                    'obs' => trim($mapped['obs']),
                    'metodo' => (string)$mapped['metodo']
                ];

                $cambios = [];
                foreach ($originalBase as $key => $oldVal) {
                    if ((string)$oldVal !== (string)$mapeoNuevo[$key]) {
                        $label = $labels[$key] ?? $key;
                        $cambios[$label] = ['antes' => $oldVal, 'despues' => $mapeoNuevo[$key]];
                    }
                }

                // Comparar Huéspedes
                $paxOriginales = implode(", ", array_column($original['pax'] ?? [], 'nombre_completo'));
                $paxNuevos = implode(", ", array_column($paxList, 'nombre_completo'));

                if ($paxOriginales !== $paxNuevos) {
                    $cambios['Huéspedes'] = [
                        'antes' => !empty($paxOriginales) ? $paxOriginales : '(ninguno)',
                        'despues' => !empty($paxNuevos) ? $paxNuevos : '(ninguno)'
                    ];
                }

                $numCambios = count($cambios);
                $detalle = json_encode([
                    'mensaje' => "Actualizó datos de la estadía en Hab #{$mapped['hab_id']} (" . ($numCambios > 0 ? "$numCambios campos modificados" : "Sin cambios detectados") . ")",
                    'cambios' => $numCambios > 0 ? $cambios : null
                ], JSON_UNESCAPED_UNICODE);

                $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'] ?? 'Sistema', 'ACTUALIZAR_STAY', 'ROOMING', $detalle);

            } else {
                $stay_id = $this->model->registrarStay($mapped, $paxList);
                $msg = "Registro creado correctamente";
                $paxTitular = $paxList[0]['nombre_completo'] ?? 'Huésped';
                $detalle = "Realizó el ingreso (Check-in) del Huésped: $paxTitular en la Habitación #{$mapped['hab_id']}";
                $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'] ?? 'Sistema', 'CHECKIN_REGISTRADO', 'ROOMING', $detalle);
            }
            
            // Si hay pago inicial, registrarlo como anticipo
            if ($mapped['cobrado'] > 0) {
                $adelantoVal = isset($input['adelanto']) ? (float)$input['adelanto'] : 0;
                $esPagoCompleto = ($input['tipoPago'] ?? 'completo') === 'completo';
                $monto_pago = $esPagoCompleto ? (float)$mapped['monto_orig'] : $adelantoVal;
                
                $pago = [
                    'stay_id'   => $stay_id,
                    'monto'     => $monto_pago,
                    'moneda'    => $mapped['moneda'],
                    'monto_pen' => $mapped['cobrado'],
                    'tc'        => $mapped['tc'],
                    'tipo'      => $mapped['metodo'],
                    'recibo'    => $mapped['num_comp'],
                    'fecha'     => date('Y-m-d'),
                    'uid'       => $_SESSION['auth_id']
                ];
                // Determinar si es pago completo o adelanto
                $subtipo = ($mapped['cobrado'] >= $mapped['total'] - 0.05) ? 'hospedaje' : 'adelanto';
                
                $this->model->registrarPago($pago, $subtipo);
            }
            
            $this->pdo->commit();
            return ['ok' => true, 'id' => $stay_id, 'msg' => $msg];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['ok' => false, 'msg' => "Error: " . $e->getMessage()];
        }
    }

    public function checkout(int $id, array $pago = []) {
        // VALIDACIÓN: No permitir checkout si hay saldo pendiente
        $stay = $this->model->getStayDetail($id);
        if (!$stay) {
            return ['ok' => false, 'msg' => "Estadía no encontrada"];
        }

        if ($stay['estado_pago'] !== 'pagado' && empty($pago)) {
            return ['ok' => false, 'msg' => "No se puede realizar el checkout. La habitación tiene un saldo pendiente de: " . ($stay['total_pago'] - $stay['total_cobrado'])];
        }

        if ($this->model->finalizarStay($id, date('Y-m-d'), $pago)) {
            $numHab = $stay['nro_habitacion'] ?? $stay['habitacion_id'];
            $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'], 'CHECKOUT_REALIZADO', 'ROOMING', "Check-out realizado (Habitación #$numHab)");
            return ['ok' => true, 'msg' => "Check-out realizado"];
        }
        return ['ok' => false, 'msg' => "No se pudo realizar el checkout"];
    }

    public function lateCheckout(int $id) {
        $stmt = $this->pdo->prepare("UPDATE rooming_stays SET estado = 'late_checkout' WHERE id = ?");
        if ($stmt->execute([$id])) {
            $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'], 'LATE_CHECKOUT', 'ROOMING', "Late checkout stay ID: $id");
            return ['ok' => true, 'msg' => 'Late checkout aplicado'];
        }
        return ['ok' => false, 'msg' => 'No se pudo aplicar late checkout'];
    }

    public function registrarPago(array $input) {
        $input['uid'] = $_SESSION['auth_id'];
        $stayId = (int)($input['stay_id'] ?? 0);
        
        if ($stayId <= 0 || (float)($input['monto'] ?? 0) <= 0) {
            return ['ok' => false, 'msg' => 'ID o monto inválido.'];
        }

        $stmt = $this->pdo->prepare("SELECT total_pago, total_cobrado FROM rooming_stays WHERE id = ?");
        $stmt->execute([$stayId]);
        $stay = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($stay) {
            $saldoPendiente = (float)$stay['total_pago'] - (float)$stay['total_cobrado'];
            $montoIngresado = (float)($input['monto_pen'] ?? $input['monto'] ?? 0);
            
            // Validación estricta para evitar negativos
            if ($montoIngresado > $saldoPendiente + 0.05) {
                return [
                    'ok' => false, 
                    'msg' => "El monto ingresado (S/ ".number_format($montoIngresado, 2).") supera el saldo pendiente (S/ ".number_format($saldoPendiente, 2).")."
                ];
            }
        }

        $subtipo = 'adelanto';
        if ($stay) {
            $saldoPendiente = (float)$stay['total_pago'] - (float)$stay['total_cobrado'];
            // Si hay recargo del POS, el saldo que el usuario DEBE pagar 
            // para completar es Saldo + 5%, ya que el total_pago aumentará en esa proporción.
            if (!empty($input['recargo_pos'])) {
                $saldoPendiente *= 1.05;
            }

            if ((float)$input['monto_pen'] >= $saldoPendiente - 0.05) {
                $subtipo = 'completo';
            }
        }

        if ($this->model->registrarPago($input, $subtipo)) {
            $msgAudit = "Registró pago de S/ " . number_format($input['monto_pen'], 2) . " [{$input['tipo']}] para Estancia #$stayId";
            $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'], 'REGISTRAR_PAGO', 'ROOMING', $msgAudit);
            return ['ok' => true, 'msg' => "Pago registrado"];
        }
        return ['ok' => false, 'msg' => "Error al registrar pago"];
    }
}
