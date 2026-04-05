<?php
/**
 * app/Controllers/CalculadoraController.php
 */
class CalculadoraController {
    private PDO            $pdo;
    private TipoCambioModel $model;
    private AuditoriaModel  $audit;

    public function __construct(PDO $pdo) {
        $this->pdo   = $pdo;
        require_once __DIR__ . '/../Models/TipoCambioModel.php';
        require_once __DIR__ . '/../Models/AuditoriaModel.php';
        $this->model = new TipoCambioModel($pdo);
        $this->audit = new AuditoriaModel($pdo);
    }

    /**
     * GET: Carga la vista con el TC actual y la configuración desde BD.
     * Devuelve un array listo para incluir en la vista.
     */
    public function index(): array {
        $tc      = $this->model->getActual();
        $config  = $this->model->getConfiguracion([
            'recargo_pos',
            'mostrar_panel_clp',
            'mostrar_panel_usd',
        ]);
        $historial = $this->model->getHistorial();

        return [
            'tc'        => $tc,
            'config'    => [
                'recargo_pos'       => (float)($config['recargo_pos']       ?? 0.05),
                'mostrar_panel_clp' => (int)($config['mostrar_panel_clp']   ?? 1),
                'mostrar_panel_usd' => (int)($config['mostrar_panel_usd']   ?? 1),
            ],
            'historial' => $historial,
        ];
    }

    /**
     * GET AJAX: Devuelve el TC más reciente en JSON.
     * Responde: { ok: true, data: { tc_usd, tc_clp, fecha } }
     */
    public function getTipoCambio(): void {
        $tc = $this->model->getActual();
        json_response(true, $tc);
    }

    /**
     * POST: Inserta o actualiza tipos de cambio (2 filas por fecha).
     * Registra en auditoría.
     */
    public function guardarTC(array $input): array {
        $fecha  = trim($input['fecha']  ?? date('Y-m-d'));
        $tc_usd = (float)($input['tc_usd'] ?? 0);
        $tc_clp = (float)($input['tc_clp'] ?? 0);

        if (!$fecha || $tc_usd <= 0 || $tc_clp <= 0) {
            return ['ok' => false, 'msg' => 'Datos inválidos. Fecha, TC USD y TC CLP son obligatorios.'];
        }

        // Validar formato fecha
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return ['ok' => false, 'msg' => 'Formato de fecha incorrecto.'];
        }

        try {
            $this->model->guardar($fecha, $tc_usd, $tc_clp);

            $detalle = json_encode([
                'fecha'  => $fecha,
                'tc_usd' => $tc_usd,
                'tc_clp' => $tc_clp,
            ]);
            $this->audit->registrar(
                $_SESSION['auth_id'],
                $_SESSION['auth_nombre'],
                'GUARDAR_TC',
                'calculadora',
                $detalle
            );

            return ['ok' => true, 'msg' => "Tipo de cambio del $fecha guardado correctamente."];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => 'Error al guardar: ' . $e->getMessage()];
        }
    }

    /**
     * POST: Actualiza parámetros en la tabla configuracion.
     */
    public function guardarParams(array $input): array {
        $allowed = ['recargo_pos', 'mostrar_panel_clp', 'mostrar_panel_usd'];
        $params  = [];

        foreach ($allowed as $key) {
            if (array_key_exists($key, $input)) {
                $params[$key] = $input[$key];
            }
        }

        if (empty($params)) {
            return ['ok' => false, 'msg' => 'No se recibieron parámetros válidos.'];
        }

        try {
            $this->model->guardarConfiguracion($params);
            return ['ok' => true, 'msg' => 'Parámetros guardados correctamente.'];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => 'Error al guardar parámetros: ' . $e->getMessage()];
        }
    }
}
