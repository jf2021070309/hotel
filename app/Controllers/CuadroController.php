<?php
/**
 * app/Controllers/CuadroController.php
 */
class CuadroController {
    private CuadroModel $model;

    public function __construct(PDO $pdo) {
        require_once __DIR__ . '/../Models/CuadroModel.php';
        $this->model = new CuadroModel($pdo);
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
}
