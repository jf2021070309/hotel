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
        
        // Agregar info de sesión
        $stayData['usuario_id'] = $_SESSION['auth_id'];
        $stayData['operador']   = $_SESSION['auth_nombre'];
        $stayData['cobrador']   = $_SESSION['auth_nombre'];
        
        try {
            $stay_id = $this->model->registrarStay($stayData, $paxList);
            
            // Si hay pago inicial, registrarlo como anticipo
            if ($stayData['total_cobrado'] > 0) {
                $pago = [
                    'stay_id'   => $stay_id,
                    'monto'     => $stayData['monto_original'] ?? $stayData['total_cobrado'],
                    'moneda'    => $stayData['moneda_pago'],
                    'monto_pen' => $stayData['total_cobrado'],
                    'tc'        => $stayData['tc_aplicado'] ?? 1,
                    'tipo'      => $stayData['metodo_pago'],
                    'recibo'    => $stayData['num_comprobante'],
                    'fecha'     => date('Y-m-d'),
                    'uid'       => $_SESSION['auth_id']
                ];
                $this->model->registrarPago($pago);
            }

            $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'], 'CHECKIN_REGISTRADO', 'ROOMING', "Check-in hab #{$stayData['habitacion_id']}, ID Stay: $stay_id");
            
            return ['ok' => true, 'id' => $stay_id, 'msg' => "Check-in realizado correctamente"];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => "Error: " . $e->getMessage()];
        }
    }

    public function checkout(int $id) {
        if ($this->model->finalizarStay($id, date('Y-m-d'))) {
            $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'], 'CHECKOUT_REALIZADO', 'ROOMING', "Check-out stay ID: $id");
            return ['ok' => true, 'msg' => "Check-out realizado"];
        }
        return ['ok' => false, 'msg' => "No se pudo realizar el checkout"];
    }

    public function registrarPago(array $input) {
        $input['uid'] = $_SESSION['auth_id'];
        if ($this->model->registrarPago($input)) {
            return ['ok' => true, 'msg' => "Pago registrado"];
        }
        return ['ok' => false, 'msg' => "Error al registrar pago"];
    }
}
