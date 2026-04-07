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
            'operador'     => $_SESSION['auth_nombre'],
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
            'recargo'      => $stayData['recargo_tarjeta'] ?? 0,
            'metodo'       => $stayData['metodo_pago'],
            'comprobante'  => $stayData['tipo_comprobante'],
            'num_comp'     => $stayData['num_comprobante'] ?? '',
            'ruc'          => $stayData['ruc_factura'] ?? '',
            'cobrador'     => $_SESSION['auth_nombre'],
            'procedencia'  => $stayData['procedencia'] ?? '',
            'carro'        => $stayData['carro'] ?? 'NO',
            'obs'          => $stayData['observaciones'] ?? '',
            'uid'          => $_SESSION['auth_id'],
            'cobrado'      => $stayData['total_cobrado'] ?? 0,
            'est_pago'     => $stayData['estado_pago'] ?? 'pendiente'
        ];
        
        try {
            if (!empty($stayData['id'])) {
                $stay_id = (int)$stayData['id'];
                $this->model->actualizarStay($stay_id, $mapped, $paxList);
                $msg = "Reserva activada correctamente";
            } else {
                $stay_id = $this->model->registrarStay($mapped, $paxList);
                $msg = "Check-in realizado correctamente";
            }
            
            // Si hay pago inicial, registrarlo como anticipo
            if ($mapped['cobrado'] > 0) {
                $adelantoVal = isset($input['adelanto']) ? (float)$input['adelanto'] : 0;
                $monto_pago = ($adelantoVal > 0) ? $adelantoVal : $mapped['monto_orig'];
                
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

            $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'], 'CHECKIN_REGISTRADO', 'ROOMING', "Check-in hab #{$mapped['hab_id']}, ID Stay: $stay_id");
            
            return ['ok' => true, 'id' => $stay_id, 'msg' => $msg];
        } catch (Exception $e) {
            file_put_contents(__DIR__ . '/../../tmp/debug_checkin.log', "Error: " . $e->getMessage() . "\n", FILE_APPEND);
            return ['ok' => false, 'msg' => "Error: " . $e->getMessage()];
        }
    }

    public function checkout(int $id, array $pago = []) {
        if ($this->model->finalizarStay($id, date('Y-m-d'), $pago)) {
            $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'], 'CHECKOUT_REALIZADO', 'ROOMING', "Check-out stay ID: $id");
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

        // Detectar si este pago salda la deuda completa
        $stayId = (int)($input['stay_id'] ?? 0);
        $montoPen = (float)($input['monto_pen'] ?? $input['monto'] ?? 0);

        $stmt = $this->pdo->prepare("SELECT total_pago, total_cobrado, moneda_pago FROM rooming_stays WHERE id = ?");
        $stmt->execute([$stayId]);
        $stay = $stmt->fetch(PDO::FETCH_ASSOC);

        $subtipo = 'adelanto'; // Por defecto: todavía queda saldo
        if ($stay) {
            $saldoPendiente = (float)$stay['total_pago'] - (float)$stay['total_cobrado'];
            // Si el monto en PEN cubre el saldo pendiente restante, es pago completo
            if ($montoPen >= $saldoPendiente - 0.01) {
                $subtipo = 'completo';
            }
        }

        if ($this->model->registrarPago($input, $subtipo)) {
            return ['ok' => true, 'msg' => "Pago registrado"];
        }
        return ['ok' => false, 'msg' => "Error al registrar pago"];
    }
}
