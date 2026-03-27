<?php
/**
 * app/Controllers/RoomingController.php
 */
class RoomingController {
    private PDO $pdo;
    private RoomingModel $model;
    private AuditoriaModel $audit;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        require_once __DIR__ . '/../Models/RoomingModel.php';
        require_once __DIR__ . '/../Models/AuditoriaModel.php';
        $this->model = new RoomingModel($pdo);
        $this->audit = new AuditoriaModel($pdo);
    }

    public function listarActivos() {
        return $this->model->getStaysActivos();
    }

    public function detalle(int $id) {
        return $this->model->getStayDetail($id);
    }

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
            file_put_contents(__DIR__ . '/../../tmp/debug_checkin.log', "Mapped: " . json_encode($mapped) . "\n", FILE_APPEND);
            $stay_id = $this->model->registrarStay($mapped, $paxList);
            
            // Si hay pago inicial, registrarlo como anticipo
            if ($mapped['cobrado'] > 0) {
                $pago = [
                    'stay_id'   => $stay_id,
                    'monto'     => $input['adelanto'] ?? $mapped['cobrado'],
                    'moneda'    => $mapped['moneda'],
                    'monto_pen' => $mapped['cobrado'],
                    'tc'        => $mapped['tc'],
                    'tipo'      => $mapped['metodo'],
                    'recibo'    => $mapped['num_comp'],
                    'fecha'     => date('Y-m-d'),
                    'uid'       => $_SESSION['auth_id']
                ];
                $this->model->registrarPago($pago);
            }

            $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'], 'CHECKIN_REGISTRADO', 'ROOMING', "Check-in hab #{$mapped['hab_id']}, ID Stay: $stay_id");
            
            return ['ok' => true, 'id' => $stay_id, 'msg' => "Check-in realizado correctamente"];
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
        if ($this->model->registrarPago($input)) {
            return ['ok' => true, 'msg' => "Pago registrado"];
        }
        return ['ok' => false, 'msg' => "Error al registrar pago"];
    }
}
