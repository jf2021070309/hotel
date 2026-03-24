<?php
/**
 * app/Controllers/CajaChicaController.php
 */
class CajaChicaController {
    private CajaChicaModel $model;
    private $audit;

    public function __construct(PDO $pdo) {
        require_once __DIR__ . '/../Models/CajaChicaModel.php';
        require_once __DIR__ . '/../Models/AuditoriaModel.php';
        
        $this->model = new CajaChicaModel($pdo);
        $this->audit = new AuditoriaModel($pdo);
        $this->pdo = $pdo; // Need it to intantiate FlujoModel during Reposición if needed, or we just pass it.
    }

    public function categorias(): array {
        return $this->model->getCategorias();
    }

    public function cicloActivo(): array {
        $ciclo = $this->model->getCicloActivo();
        return ['ok' => true, 'data' => $ciclo]; // If null, means no active cycle
    }

    public function listar(): array {
        return ['ok' => true, 'data' => $this->model->listarCiclos()];
    }

    public function abrir(array $input): array {
        $nombre = mb_strtoupper(trim($input['nombre'] ?? ''));
        $saldo  = (float)($input['saldo_inicial'] ?? 100);

        if (empty($nombre) || $saldo <= 0) {
            return ['ok' => false, 'msg' => 'Datos inválidos. El nombre y saldo son obligatorios.'];
        }

        // Verificamos si ya hay uno abierto
        if ($this->model->getCicloActivo()) {
            return ['ok' => false, 'msg' => 'Ya existe un ciclo de caja chica abierto. Ciérrelo primero.'];
        }

        try {
            $id = $this->model->abrirCiclo($nombre, $saldo, $_SESSION['auth_id']);
            $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'], 'CAJA_CHICA_ABIERTA', 'FINANZAS', "Ciclo de C.Chica abierto: $nombre con S/$saldo.");
            return ['ok' => true, 'msg' => 'Ciclo abierto correctamente', 'data' => ['id' => $id]];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => 'Error de BD: ' . $e->getMessage()];
        }
    }

    public function registrarGasto(array $input): array {
        $caja_id = (int)($input['caja_id'] ?? 0);
        $rubro = mb_strtoupper(trim($input['rubro'] ?? ''));
        $monto = (float)($input['monto'] ?? 0);
        $documento = mb_strtoupper(trim($input['documento'] ?? ''));
        $obs = mb_strtoupper(trim($input['observacion'] ?? ''));

        if ($caja_id <= 0 || empty($rubro) || $monto <= 0) {
            return ['ok' => false, 'msg' => 'Rubro y Monto son obligatorios.'];
        }

        $ciclo = $this->model->getCicloActivo();
        if (!$ciclo || $ciclo['id'] !== $caja_id) {
            return ['ok' => false, 'msg' => 'El ciclo indicado ya no está abierto o no coincide.'];
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
            return ['ok' => true, 'msg' => 'Gasto registrado correctamente'];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => 'Error: ' . $e->getMessage()];
        }
    }

    public function anularGasto(array $input): array {
        $mov_id = (int)($input['mov_id'] ?? 0);
        $motivo = mb_strtoupper(trim($input['motivo'] ?? ''));

        if ($mov_id <= 0 || empty($motivo)) {
            return ['ok' => false, 'msg' => 'El motivo de anulación es obligatorio.'];
        }

        // Verify that the cycle is still open to annul expenses
        $ciclo = $this->model->getCicloActivo();
        if (!$ciclo) {
            return ['ok' => false, 'msg' => 'No hay caja abierta para anular movimientos.'];
        }

        try {
            if ($this->model->anularGasto($mov_id, $motivo, $_SESSION['auth_id'])) {
                $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'], 'CAJA_CHICA_ANULADA', 'FINANZAS', "Movimiento $mov_id anulado: $motivo");
                return ['ok' => true, 'msg' => 'Gasto anulado. El monto regresó al saldo.'];
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
            return $this->model->ejecutarTransaccionCierreRepocision(function($pdo) use ($caja_id, $ciclo, $reponer) {
                // 1. Cerrar ciclo actual
                $this->model->cerrarCiclo($caja_id, $ciclo['saldo_actual'], $_SESSION['auth_id']);
                $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'], 'CAJA_CHICA_CERRADA', 'FINANZAS', "Caja Chica $caja_id cerrada. Saldo Final: {$ciclo['saldo_actual']}");

                // 2. Si reponer es verdadero, intentar sacar dinero de Flujo de Caja
                if ($reponer) {
                    $montoReposicion = 100.00; // Podría ser dinámico (lo gastado) pero el req. dice que siempre arranca en 100.
                    // Si el usuario dijo "Reponer 100 del sobre", se debita 100 cerrado.
                    // O se debita solo lo gastado? req: "se repone sacando S/. 100 del sobre de efectivo". Interpretamos fondo variable que arranca fijo en 100.
                    
                    // Buscar flujo de caja abierto (Mañana o Tarde del dia actual)
                    require_once __DIR__ . '/../Models/FlujoModel.php';
                    $flujoModel = new FlujoModel($pdo);
                    $flujosActivos = $flujoModel->listar(['estado' => 'borrador', 'mes' => date('n'), 'anio' => date('Y')]);
                    
                    // Solo tomamos el primer flujo en borrador del día (en teoría solo hay uno activo por día)
                    // Para ser exactos, tomamos el que tenga fecha = HOY
                    $flujoHoy = null;
                    foreach ($flujosActivos as $f) {
                        if ($f['fecha'] === date('Y-m-d')) {
                            $flujoHoy = $f; break;
                        }
                    }

                    if (!$flujoHoy) {
                        throw new Exception("No hay ningún turno de Flujo de Caja Físico ABIERTO el día de hoy para sacar el dinero de reposición.");
                    }

                    // Insertar egreso en flujo_caja_movimientos
                    $stmtF = $pdo->prepare("
                        INSERT INTO flujo_caja_movimientos 
                        (flujo_id, tipo, monto, categoria, moneda, medio_pago, observacion) 
                        VALUES (?, 'Egreso', ?, 'RECEPCIÓN C.CH.', 'PEN', 'EFECTIVO', 'Reposición automática de Caja Chica')
                    ");
                    $stmtF->execute([$flujoHoy['id'], $montoReposicion]);

                    // Crear nuevo ciclo de Caja Chica
                    $nuevoNombre = "CICLO REPOSICIÓN " . date('d/m/Y H:i');
                    $this->model->abrirCiclo($nuevoNombre, $montoReposicion, $_SESSION['auth_id']);
                }

                return ['ok' => true, 'msg' => $reponer ? 'Ciclo cerrado, reintegro descontado de Caja Principal y Nuevo Ciclo creado.' : 'Ciclo cerrado con éxito.'];
            });
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }
}
