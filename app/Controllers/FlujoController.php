<?php
/**
 * app/Controllers/FlujoController.php
 * 
 * Controlador encargado de gestionar el Flujo de Caja diario del hotel.
 * Orquesta la apertura, cierre y auditoría de los turnos de caja, 
 * además de la gestión multidivisa y sincronización con otros módulos financieros.
 */
class FlujoController {
    /** @var FlujoModel Instancia del modelo de flujo de caja. */
    private FlujoModel $model;
    
    /** @var AuditoriaModel Instancia del modelo de auditoría para registro de eventos. */
    private AuditoriaModel $audit;

    /**
     * Constructor del controlador.
     * 
     * @param PDO $pdo Conexión a la base de datos.
     */
    public function __construct(PDO $pdo) {
        require_once __DIR__ . '/../Models/FlujoModel.php';
        require_once __DIR__ . '/../Models/AuditoriaModel.php';
        
        $this->model = new FlujoModel($pdo);
        $this->audit = new AuditoriaModel($pdo);
    }

    /**
     * Obtiene las categorías configuradas para el flujo de caja.
     * 
     * @return array Lista de categorías activas (id, tipo, nombre).
     */
    public function categorias(): array {
        return $this->model->getCategorias();
    }

    /**
     * Lista los turnos de caja filtrados por mes, año y estado.
     * 
     * @param array $filtros Arreglo con 'mes', 'anio' y 'estado'.
     * @return array Lista de turnos con totales calculados en moneda base.
     */
    public function listar(array $filtros): array {
        $params = [
            'mes'    => $filtros['mes'] ?? date('n'),
            'anio'   => $filtros['anio'] ?? date('Y'),
            'estado' => $filtros['estado'] ?? 'todos'
        ];
        return $this->model->listar($params);
    }

    /**
     * Obtiene el detalle completo de un turno específico, incluyendo sus movimientos de ingreso y egreso.
     * 
     * @param int $id ID del flujo de caja.
     * @return array Resultado con 'ok' y 'data' (detalle + movimientos).
     */
    public function detalle(int $id): array {
        if ($id <= 0) return ['ok' => false, 'msg' => 'ID de flujo inválido'];
        
        $detalle = $this->model->getDetalle($id);
        if (!$detalle) return ['ok' => false, 'msg' => 'Flujo no encontrado o no existe'];

        return ['ok' => true, 'data' => $detalle];
    }

    /**
     * Guarda (crea o actualiza) un turno de caja y sus movimientos asociados.
     * Realiza validaciones de permisos y estados (borrador vs cerrado).
     * 
     * @param array $input Datos del encabezado del flujo y arreglos de 'ingresos' y 'egresos'.
     * @return array Resultado de la operación con el ID generado o mensaje de error.
     */
    public function guardar(array $input): array {
        $id    = (int)($input['id'] ?? 0);
        $fecha = $input['fecha'] ?? date('Y-m-d');
        $turno = $input['turno'] ?? '';

        if (empty($fecha) || empty($turno)) {
            return ['ok' => false, 'msg' => 'Fecha y Turno son requeridos'];
        }

        // Si ya existe un turno para esa fecha/turno (abierto o cerrado), redirigir directamente
        if ($id === 0 && $this->model->checkExisteTurno($fecha, $turno, 0)) {
            $existente = $this->model->getIdExistente($fecha, $turno);
            return ['ok' => true, 'msg' => 'El turno ya existe para esta fecha. Redirigiendo al registro...', 'data' => ['id' => $existente, 'existente' => true]];
        }

        // Si es edición, evaluar si está cerrado/depositado
        if ($id > 0) {
            $actual = $this->model->getDetalle($id);
            if ($actual && $actual['estado'] !== 'borrador') {
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

    /**
     * Cierra oficialmente un turno de caja, bloqueando ediciones posteriores para cajeras.
     * 
     * @param int $id ID del flujo de caja.
     * @return array Mensaje de éxito o error.
     */
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

    /**
     * Marca un flujo cerrado como "depositado", indicando que el efectivo ya fue entregado a administración/banco.
     * 
     * @param int $id ID del flujo de caja.
     * @return array Mensaje de éxito o error.
     */
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

    /**
     * Reabre un turno cerrado a estado "borrador" para permitir ediciones. Solo permitido para Administradores.
     * 
     * @param int $id ID del flujo de caja.
     * @return array Mensaje de éxito o error.
     */
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

    /**
     * Obtiene un resumen consolidado financiero de todos los turnos cerrados en una fecha específica.
     * 
     * @param string $fecha Formato YYYY-MM-DD.
     * @return array Resumen con totales por moneda y desglose de turnos.
     */
    /**
     * Obtiene el resumen consolidado específico para la liquidación de sobres de Alex.
     * 
     * @param string $fecha Formato YYYY-MM-DD.
     * @return array Resumen con totales por turno y desglose de egresos.
     */
    public function resumenDia(string $fecha): array {
        return $this->model->getResumenDia($fecha);
    }

    /**
     * Obtiene el resumen consolidado específico para la liquidación de sobres de Alex.
     * 
     * @param string $fecha Formato YYYY-MM-DD.
     * @return array Resumen con totales por turno y desglose de egresos.
     */
    public function resumenAlex(string $fecha): array {
        return $this->model->getReporteAlexDiario($fecha);
    }
}
