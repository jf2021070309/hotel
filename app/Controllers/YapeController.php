<?php
/**
 * app/Controllers/YapeController.php
 */
class YapeController {
    private YapeModel $model;
    private $audit;

    public function __construct(PDO $pdo) {
        require_once __DIR__ . '/../Models/YapeModel.php';
        require_once __DIR__ . '/../Models/AuditoriaModel.php';
        
        $this->model = new YapeModel($pdo);
        $this->audit = new AuditoriaModel($pdo);
        $this->pdo = $pdo;
    }

    public function listar(): array {
        $mes = $_GET['mes'] ?? date('n');
        $anio = $_GET['anio'] ?? date('Y');
        
        $filtros = ['mes' => $mes, 'anio' => $anio];
        return ['ok' => true, 'data' => $this->model->listar($filtros)];
    }

    public function detalle(int $id): array {
        $data = $this->model->getDetalle($id);
        if ($data) return ['ok' => true, 'data' => $data];
        return ['ok' => false, 'msg' => 'Registro Yape no encontrado.'];
    }

    public function guardar(array $input): array {
        $id = (int)($input['id'] ?? 0);
        $fecha = $_POST['fecha'] ?? $input['fecha'] ?? date('Y-m-d');
        $turno = mb_strtoupper(trim($_POST['turno'] ?? $input['turno'] ?? ''));
        
        if (!in_array($turno, ['MAÑANA', 'TARDE'])) {
            return ['ok' => false, 'msg' => 'Turno inválido.'];
        }

        // Check if there is already a Yape registry for this date+shift
        if ($this->model->verificarUnico($fecha, $turno, $id)) {
            return ['ok' => false, 'msg' => "Ya existe un registro Yape para el turno $turno de hoy. Edita el registro existente en lugar de crear uno nuevo."];
        }

        // Avoid editing if closed
        if ($id > 0) {
            $exist = $this->model->getDetalle($id);
            if (!$exist) return ['ok' => false, 'msg' => 'Registro no encontrado.'];
            if ($exist['estado'] !== 'borrador') return ['ok' => false, 'msg' => 'No puedes editar un registro que ya ha sido cerrado.'];
        }

        $yape_recibido = (float)($input['yape_recibido'] ?? 0);
        $observacion   = mb_strtoupper(trim($input['observacion'] ?? ''));
        $detalles      = $input['detalles'] ?? [];

        // Sum spent
        $total_gastado = 0;
        foreach ($detalles as $det) {
            if (!empty($det['rubro']) && (float)($det['monto'] ?? 0) > 0) {
                $total_gastado += (float)$det['monto'];
            }
        }

        $vuelto = $yape_recibido - $total_gastado;

        if ($vuelto < 0) {
            return ['ok' => false, 'msg' => 'El total gastado supera al Yape recibido. Revisa los montos.'];
        }

        $data = [
            'id' => $id,
            'fecha' => $fecha,
            'turno' => $turno,
            'yape_recibido' => $yape_recibido,
            'total_gastado' => $total_gastado,
            'vuelto' => $vuelto,
            'observacion' => $observacion,
            'usuario_id' => $_SESSION['auth_id']
        ];

        try {
            $newId = $this->model->guardar($data, $detalles);
            $msg = $id > 0 ? "Registro Yape $newId actualizado." : "Registro Yape $newId creado.";
            if ($id == 0) $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'], 'YAPE_CREADO', 'FINANZAS', $msg);
            
            // Re-fetch to return latest updated info
            return ['ok' => true, 'msg' => 'Borrador guardado', 'data' => $this->model->getDetalle($newId)];
        } catch (Exception $e) {
            // Check for duplicate key error on uk_fecha_turno_yape safely mapped
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return ['ok' => false, 'msg' => 'Ya existe un registro para esta fecha y turno debido a colisión en la base de datos.'];
            }
            return ['ok' => false, 'msg' => 'Error BD: ' . $e->getMessage()];
        }
    }

    public function cerrar(array $input): array {
        $id = (int)($input['id'] ?? 0);
        $registro = $this->model->getDetalle($id);

        if (!$registro) return ['ok' => false, 'msg' => 'Registro Yape no encontrado.'];
        if ($registro['estado'] !== 'borrador') return ['ok' => false, 'msg' => 'El registro ya se encuentra cerrado.'];

        $vuelto = (float)$registro['vuelto'];

        try {
            return $this->model->ejecutarTransaccionCierre(function($pdo) use ($id, $registro, $vuelto) {
                // 1. Cerrar registro
                $this->model->cambiarEstado($id, 'cerrado');
                $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'], 'YAPE_CERRADO', 'FINANZAS', "Gastos Yape $id cerrado. Vuelto sobrante = S/$vuelto");

                // 2. Si hay vuelto, mandarlo al Flujo de Caja
                if ($vuelto > 0) {
                    require_once __DIR__ . '/../Models/FlujoModel.php';
                    $flujoModel = new FlujoModel($pdo);
                    
                    // Buscar el flujo de caja que corresponda a esa misma FECHA y TURNO y que siga en Borrador.
                    $flujosActivos = $flujoModel->listar(['estado' => 'borrador', 'mes' => date('n', strtotime($registro['fecha'])), 'anio' => date('Y', strtotime($registro['fecha']))]);
                    
                    $flujoTarget = null;
                    foreach ($flujosActivos as $f) {
                        if ($f['fecha'] === $registro['fecha'] && $f['turno'] === $registro['turno']) {
                            $flujoTarget = $f; break;
                        }
                    }

                    if (!$flujoTarget) {
                        throw new Exception("No hay ningún turno de Flujo de Caja ABIERTO para la fecha {$registro['fecha']} y turno {$registro['turno']} donde depositar el vuelto de Yape (S/ $vuelto). Por favor, abre el turno en Flujo de Caja primero, o el turno ya fue cerrado y no puede recibir más ingresos.");
                    }

                    // Insertar ingreso en flujo_caja_movimientos
                     $stmtF = $pdo->prepare("
                        INSERT INTO flujo_caja_movimientos 
                        (flujo_id, tipo, monto, categoria, moneda, medio_pago, observacion) 
                        VALUES (?, 'Ingreso', ?, 'VUELTO YAPE', 'PEN', 'EFECTIVO', 'Vuelto Yape de Alex (Registro #$id)')
                    ");
                    $stmtF->execute([$flujoTarget['id'], $vuelto]);
                }

                return ['ok' => true, 'msg' => $vuelto > 0 ? 'Registro cerrado. El vuelto ha sido transferido automáticamente a la caja de este turno.' : 'Registro cerrado con éxito. (Vuelto 0)'];
            });
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }
}
