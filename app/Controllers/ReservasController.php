<?php
/**
 * app/Controllers/ReservasController.php
 */
class ReservasController {
    private ReservasModel $model;
    private AuditoriaModel $audit;

    public function __construct(PDO $pdo) {
        require_once __DIR__ . '/../Models/ReservasModel.php';
        require_once __DIR__ . '/../Models/AuditoriaModel.php';
        $this->model = new ReservasModel($pdo);
        $this->audit = new AuditoriaModel($pdo);
    }

    /**
     * Returns monthly grid data + daily summary.
     */
    public function datos(): array {
        $mes  = max(1, min(12, (int)($_GET['mes']  ?? date('n'))));
        $anio = max(2020, min(2100, (int)($_GET['anio'] ?? date('Y'))));
        $hoy  = date('Y-m-d');

        $grid    = $this->model->getDatosMes($mes, $anio);
        $resumen = $this->model->getResumenDia($hoy);

        return [
            'habitaciones' => $grid['habitaciones'],
            'dias_en_mes'  => $grid['dias_en_mes'],
            'mes'          => $mes,
            'anio'         => $anio,
            'hoy'          => (int)date('j'),
            'resumen'      => $resumen,
        ];
    }

    /**
     * Register quick payment from the grid modal.
     */
    public function pagoRapido(array $input): array {
        $stay_id = (int)($input['stay_id'] ?? 0);
        $monto   = (float)($input['monto']   ?? 0);
        $moneda  = $input['moneda']  ?? 'PEN';
        $metodo  = $input['metodo']  ?? 'efectivo';
        $tc      = (float)($input['tc'] ?? 1);
        $uid     = (int)($_SESSION['auth_id'] ?? 0);

        if (!$stay_id || $monto <= 0) {
            return ['ok' => false, 'msg' => 'Datos incompletos: stay_id y monto son requeridos'];
        }

        try {
            $result = $this->model->pagoRapido($stay_id, $monto, $moneda, $metodo, $tc, $uid);
            $msg = "Registró PAGO de S/ " . number_format($monto * $tc, 2) . " [$metodo] desde Cuadro de Reservas";
            $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'], 'REGISTRAR_PAGO', 'RESERVAS', $msg);
            return ['ok' => true, 'msg' => 'Pago registrado correctamente', 'data' => $result];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => 'Error al registrar pago: ' . $e->getMessage()];
        }
    }

    /**
     * Apply late checkout state to a stay.
     */
    public function lateCheckout(array $input): array {
        $id = (int)($input['id'] ?? 0);
        if (!$id) return ['ok' => false, 'msg' => 'ID de estadía requerido'];

        if ($this->model->lateCheckout($id)) {
            return ['ok' => true, 'msg' => 'Late checkout aplicado'];
        }
        return ['ok' => false, 'msg' => 'No se pudo aplicar late checkout'];
    }

    /**
     * Create a brief reservation.
     */
    public function quickReserva(array $input): array {
        $data = [
            'hab_id'        => (int)($input['hab_id'] ?? 0),
            'fecha_inicio'  => $input['fecha'] ?? date('Y-m-d'),
            'noches'        => (int)($input['noches'] ?? 1),
            'titular'       => $input['titular'] ?? 'RESERVADO',
            'observaciones' => $input['observaciones'] ?? '',
            'usuario_id'    => (int)($_SESSION['auth_id'] ?? 1),
            'canal'         => $input['canal'] ?? 'DIRECTO'
        ];

        if (!$data['hab_id'] || empty($data['titular'])) {
            return ['ok' => false, 'msg' => 'Datos incompletos'];
        }

        try {
            $id = $this->model->registrarReservaRapida($data);
            $msg = "Creó RESERVA RÁPIDA para: {$data['titular']} (Hab #{$data['hab_id']})";
            $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'], 'NUEVA_RESERVA', 'RESERVAS', $msg);
            return ['ok' => true, 'msg' => 'Reserva registrada', 'id' => $id];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => 'Error: ' . $e->getMessage()];
        }
    }

    public function editarQuickReserva(array $input): array {
        $id = (int)($input['id'] ?? 0);
        $data = [
            'fecha_inicio'  => $input['fecha'] ?? date('Y-m-d'),
            'noches'        => (int)($input['noches'] ?? 1),
            'titular'       => trim($input['titular'] ?? 'RESERVADO'),
            'observaciones' => $input['observaciones'] ?? '',
            'canal'         => $input['canal'] ?? 'DIRECTO'
        ];

        if (!$id || empty($data['titular']) || $data['noches'] < 1) {
            return ['ok' => false, 'msg' => 'Datos incompletos'];
        }

        try {
            $this->model->actualizarReservaRapida($id, $data);
            $msg = "Editó RESERVA RÁPIDA #{$id} para: {$data['titular']}";
            $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'], 'EDITAR_RESERVA', 'RESERVAS', $msg);
            return ['ok' => true, 'msg' => 'Reserva actualizada'];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => 'Error: ' . $e->getMessage()];
        }
    }

    public function checkin(array $input): array {
        $id = (int)($input['id'] ?? 0);
        if (!$id) return ['ok' => false, 'msg' => 'ID no válido'];
        
        try {
            if ($this->model->activarStay($id)) {
                $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'], 'CHECKIN_RESERVA', 'RESERVAS', "Activó llegada de Huésped (Check-in desde Reservas)");
                return ['ok' => true, 'msg' => 'Ingreso (Check-in) registrado'];
            }
            return ['ok' => false, 'msg' => 'No se pudo activar la reserva'];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    public function rechazar(array $input): array {
        $id = (int)($input['id'] ?? 0);
        if (!$id) return ['ok' => false, 'msg' => 'ID no vÃ¡lido'];

        try {
            if ($this->model->rechazarStay($id)) {
                $this->audit->registrar(
                    $_SESSION['auth_id'],
                    $_SESSION['auth_nombre'],
                    'RECHAZAR_RESERVA',
                    'RESERVAS',
                    "RechazÃ³ una reserva desde el cuadro de reservas (Stay #$id)"
                );
                return ['ok' => true, 'msg' => 'Reserva rechazada'];
            }
            return ['ok' => false, 'msg' => 'No se pudo rechazar la reserva'];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }
}
