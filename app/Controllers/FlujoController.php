<?php
/**
 * app/Controllers/FlujoController.php
 */
class FlujoController {
    private FlujoModel $model;

    public function __construct(PDO $pdo) {
        require_once __DIR__ . '/../Models/FlujoModel.php';
        require_once __DIR__ . '/../Models/AuditoriaModel.php';
        
        $this->model = new FlujoModel($pdo);
        $this->audit = new AuditoriaModel($pdo);
    }

    public function categorias(): array {
        return $this->model->getCategorias();
    }

    public function listar(array $filtros): array {
        $params = [
            'mes'    => $filtros['mes'] ?? date('n'),
            'anio'   => $filtros['anio'] ?? date('Y'),
            'estado' => $filtros['estado'] ?? 'todos'
        ];
        return $this->model->listar($params);
    }

    public function detalle(int $id): array {
        if ($id <= 0) return ['ok' => false, 'msg' => 'ID de flujo inválido'];
        
        $detalle = $this->model->getDetalle($id);
        if (!$detalle) return ['ok' => false, 'msg' => 'Flujo no encontrado o no existe'];

        return ['ok' => true, 'data' => $detalle];
    }

    public function guardar(array $input): array {
        $id    = (int)($input['id'] ?? 0);
        $fecha = $input['fecha'] ?? date('Y-m-d');
        $turno = $input['turno'] ?? '';

        if (empty($fecha) || empty($turno)) {
            return ['ok' => false, 'msg' => 'Fecha y Turno son requeridos'];
        }

        // Si es nuevo, chequear que no exista ya ese turno o editar el actual
        if ($this->model->checkExisteTurno($fecha, $turno, $id)) {
            return ['ok' => false, 'msg' => "Ya existe un flujo para el turno $turno del $fecha"];
        }

        // Si es edición, evaluar si está cerrado/depositado
        if ($id > 0) {
            $actual = $this->model->getDetalle($id);
            if ($actual && $actual['estado'] !== 'borrador') {
                // EXCEPCIÓN: Admin/Supervisor sí pueden editar aunque esté cerrado
                if (!in_array($_SESSION['auth_rol'] ?? '', ['admin', 'supervisor'])) {
                    return ['ok' => false, 'msg' => 'No tienes permisos para editar un turno cerrado o depositado'];
                }
            }
        }

        $data = [
            'id'           => $id,
            'fecha'        => $fecha,
            'turno'        => $turno,
            'nota_entrega' => $input['nota_entrega'] ?? '',
            'usuario_id'   => $_SESSION['auth_id']
        ];

        try {
            $newId = $this->model->guardar($data, $input['ingresos'] ?? [], $input['egresos'] ?? []);
            return ['ok' => true, 'msg' => 'Turno guardado correctamente', 'data' => ['id' => $newId]];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => 'Error al guardar el flujo: ' . $e->getMessage()];
        }
    }

    public function cerrar(int $id): array {
        if ($id <= 0) return ['ok' => false, 'msg' => 'ID inválido'];
        
        $actual = $this->model->getDetalle($id);
        if (!$actual) return ['ok' => false, 'msg' => 'Flujo no encontrado'];
        if ($actual['estado'] !== 'borrador') return ['ok' => false, 'msg' => 'El flujo ya no está en borrador'];

        if ($this->model->cambiarEstado($id, 'cerrado')) {
            $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'], 'FLUJO_CERRADO', 'FINANZAS', "Flujo ID $id cerrado.");
            return ['ok' => true, 'msg' => 'Turno cerrado correctamente'];
        }
        return ['ok' => false, 'msg' => 'No se pudo cerrar el turno'];
    }

    public function depositar(int $id): array {
        if ($id <= 0) return ['ok' => false, 'msg' => 'ID inválido'];
        
        $actual = $this->model->getDetalle($id);
        if (!$actual) return ['ok' => false, 'msg' => 'Flujo no encontrado'];
        if ($actual['estado'] !== 'cerrado') return ['ok' => false, 'msg' => 'Solo flujos cerrados se pueden depositar'];

        if ($this->model->cambiarEstado($id, 'depositado')) {
            $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'], 'FLUJO_DEPOSITADO', 'FINANZAS', "Flujo ID $id marcado depositado.");
            return ['ok' => true, 'msg' => 'Dinero del turno depositado correctamente'];
        }
        return ['ok' => false, 'msg' => 'No se pudo depositar el turno'];
    }

    public function reabrir(int $id): array {
        if ($id <= 0) return ['ok' => false, 'msg' => 'ID de flujo inválido'];
        
        // Solo Admin/Supervisor pueden reabrir
        if (!in_array($_SESSION['auth_rol'] ?? '', ['admin', 'supervisor'])) {
            return ['ok' => false, 'msg' => 'No tienes permisos para reabrir turnos'];
        }

        if ($this->model->cambiarEstado($id, 'borrador')) {
            $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'], 'FLUJO_REABIERTO', 'FINANZAS', "Flujo ID $id reabierto a borrador.");
            return ['ok' => true, 'msg' => 'Turno reabierto correctamente (ahora es editable)'];
        }
        return ['ok' => false, 'msg' => 'No se pudo reabrir el turno'];
    }

    public function resumenDia(string $fecha): array {
        return $this->model->getResumenDia($fecha);
    }
}
