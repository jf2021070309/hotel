<?php
/**
 * app/Controllers/CajaChicaController.php
 */
class CajaChicaController {
    private CajaChicaModel $model;
    private $audit;
    private $pdo;

    public function __construct(PDO $pdo) {
        require_once __DIR__ . '/../Models/CajaChicaModel.php';
        require_once __DIR__ . '/../Models/AuditoriaModel.php';

        $this->model = new CajaChicaModel($pdo);
        $this->audit = new AuditoriaModel($pdo);
        $this->pdo = $pdo;
    }

    public function categorias(): array {
        return $this->model->getCategorias();
    }

    public function cicloActivo(): array {
        $ciclo = $this->model->getCicloActivo();
        return ['ok' => true, 'data' => $ciclo];
    }

    public function listar(): array {
        return ['ok' => true, 'data' => $this->model->listarCiclos()];
    }

    public function abrir(array $input): array {
        $nombre = mb_strtoupper(trim($input['nombre'] ?? ''));
        $saldo  = (float)($input['saldo_inicial'] ?? 100);

        if (empty($nombre) || $saldo <= 0) {
            return ['ok' => false, 'msg' => 'Datos invalidos. El nombre y saldo son obligatorios.'];
        }

        if ($this->model->getCicloActivo()) {
            return ['ok' => false, 'msg' => 'Ya existe un ciclo de caja chica abierto. Cierrelo primero.'];
        }

        try {
            return $this->model->ejecutarTransaccionCierreRepocision(function($pdo) use ($nombre, $saldo, $input) {
                $id = $this->model->abrirCiclo($nombre, $saldo, $_SESSION['auth_id'], [
                    'sobre_fecha' => $input['sobre_fecha'] ?? null,
                    'sobre_turno' => $input['sobre_turno'] ?? null
                ]);
                $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'], 'CAJA_CHICA_ABIERTA', 'FINANZAS', "Ciclo de C.Chica abierto: $nombre con S/$saldo.");
                return ['ok' => true, 'msg' => 'Ciclo abierto correctamente', 'data' => ['id' => $id]];
            });
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => 'Error de BD: ' . $e->getMessage()];
        }
    }

    public function registrarGasto(array $input): array {
        $caja_id = (int)($input['caja_id'] ?? 0);
        $documento = mb_strtoupper(trim($input['documento'] ?? ''));
        $rubro = mb_strtoupper(trim($input['rubro'] ?? $documento));
        $monto = (float)($input['monto'] ?? 0);
        $obs = mb_strtoupper(trim($input['observacion'] ?? ''));

        if ($caja_id <= 0 || empty($documento) || $monto <= 0) {
            return ['ok' => false, 'msg' => 'El Documento y Monto son obligatorios.'];
        }

        $ciclo = $this->model->getCicloActivo();
        if (!$ciclo || $ciclo['id'] !== $caja_id) {
            return ['ok' => false, 'msg' => 'El ciclo indicado ya no esta abierto o no coincide.'];
        }

        if ($ciclo['saldo_actual'] < $monto) {
            return ['ok' => false, 'msg' => 'Saldo insuficiente para este gasto.'];
        }

        try {
            $this->model->registrarGasto([
                'caja_id' => $caja_id,
                'rubro' => $rubro,
                'monto' => $monto,
                'documento' => $documento,
                'observacion' => $obs,
                'usuario_id' => $_SESSION['auth_id']
            ]);

            $detalle = json_encode([
                'mensaje' => 'Registro un GASTO en Caja Chica',
                'cambios' => [
                    'Documento' => ['antes' => '-', 'despues' => $documento],
                    'Monto' => ['antes' => 'S/ 0.00', 'despues' => 'S/ ' . number_format($monto, 2)],
                    'Obs'   => ['antes' => '-', 'despues' => $obs ?: 'Ninguna']
                ]
            ], JSON_UNESCAPED_UNICODE);

            $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'], 'GASTO_CAJA_CHICA', 'FINANZAS', $detalle);

            return ['ok' => true, 'msg' => 'Gasto registrado correctamente'];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => 'Error: ' . $e->getMessage()];
        }
    }

    public function anularGasto(array $input): array {
        $mov_id = (int)($input['mov_id'] ?? 0);
        $motivo = mb_strtoupper(trim($input['motivo'] ?? ''));

        if ($mov_id <= 0 || empty($motivo)) {
            return ['ok' => false, 'msg' => 'El motivo de anulacion es obligatorio.'];
        }

        $ciclo = $this->model->getCicloActivo();
        if (!$ciclo) {
            return ['ok' => false, 'msg' => 'No hay caja abierta para anular movimientos.'];
        }

        try {
            if ($this->model->anularGasto($mov_id, $motivo, $_SESSION['auth_id'])) {
                $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'], 'CAJA_CHICA_ANULADA', 'FINANZAS', "Movimiento $mov_id anulado: $motivo");
                return ['ok' => true, 'msg' => 'Gasto anulado. El monto regreso al saldo.'];
            }
            return ['ok' => false, 'msg' => 'No se pudo anular el gasto.'];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => 'Error: ' . $e->getMessage()];
        }
    }

    public function cerrar(array $input): array {
        $caja_id = (int)($input['caja_id'] ?? 0);
        $reponer = filter_var($input['reponer'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $ciclo = $this->model->getCicloActivo();
        if (!$ciclo || $ciclo['id'] !== $caja_id) {
            return ['ok' => false, 'msg' => 'Ciclo no encontrado o ya cerrado.'];
        }

        try {
            return $this->model->ejecutarTransaccionCierreRepocision(function($pdo) use ($caja_id, $ciclo, $reponer, $input) {
                $this->model->cerrarCiclo($caja_id, $ciclo['saldo_actual'], $_SESSION['auth_id']);
                $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'], 'CAJA_CHICA_CERRADA', 'FINANZAS', "Caja Chica $caja_id cerrada. Saldo Final: {$ciclo['saldo_actual']}");

                if ($reponer) {
                    $montoReposicion = 100.00;
                    $sFecha = !empty($input['sobre_fecha']) ? $input['sobre_fecha'] : date('Y-m-d');
                    $sTurno = !empty($input['sobre_turno']) ? $input['sobre_turno'] : 'MAÑANA';

                    $nuevoNombre = !empty($input['nombre_reposicion']) ? mb_strtoupper(trim($input['nombre_reposicion'])) : ("FONDO FIJO S/ 100 - " . date('d/m/Y'));
                    $this->model->abrirCiclo($nuevoNombre, $montoReposicion, $_SESSION['auth_id'], [
                        'sobre_fecha' => $sFecha,
                        'sobre_turno' => $sTurno
                    ]);
                }

                return ['ok' => true, 'msg' => $reponer ? 'Ciclo cerrado, reintegro descontado del sobre y nuevo ciclo creado.' : 'Ciclo cerrado con exito.'];
            });
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }
}
