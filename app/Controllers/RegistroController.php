<?php
// ============================================================
// app/Controllers/RegistroController.php
// ============================================================
require_once __DIR__ . '/../Models/RegistroModel.php';
require_once __DIR__ . '/../Models/ClienteModel.php';

class RegistroController {
    private RegistroModel $model;
    private ClienteModel  $clienteModel;

    public function __construct(mysqli $db) {
        $this->model        = new RegistroModel($db);
        $this->clienteModel = new ClienteModel($db);
    }

    /** GET: lista o activos */
    public function index(bool $soloActivos = false): void {
        $data = $soloActivos ? $this->model->getActivos() : $this->model->getAll();
        json_response(true, $data);
    }

    /** POST: check-in */
    public function checkin(array $body): void {
        $habitacion_id = (int)($body['habitacion_id'] ?? 0);
        $fecha_ingreso = $body['fecha_ingreso'] ?? date('Y-m-d');
        $precio        = (float)($body['precio'] ?? 0);

        // Puede venir cliente_id o datos de cliente nuevo
        $cliente_id = (int)($body['cliente_id'] ?? 0);
        if ($cliente_id === 0) {
            // Crear cliente inline
            $nombre   = trim($body['nombre']   ?? '');
            $dni      = trim($body['dni']      ?? '');
            $telefono = trim($body['telefono'] ?? '');
            if ($nombre === '') json_response(false, null, 422, 'Nombre del cliente requerido');
            if ($dni    === '') json_response(false, null, 422, 'DNI del cliente requerido');
            $result     = $this->clienteModel->create(compact('nombre','dni','telefono'));
            $cliente_id = $result['id'];
        }

        if ($habitacion_id <= 0) json_response(false, null, 422, 'Habitación inválida');
        if ($precio <= 0)        json_response(false, null, 422, 'El precio debe ser mayor a 0');

        try {
            $regId = $this->model->checkin(compact('habitacion_id','cliente_id','fecha_ingreso','precio'));
            json_response(true, ['id' => $regId], 201, 'Ingreso registrado');
        } catch (RuntimeException $e) {
            json_response(false, null, 409, $e->getMessage());
        }
    }

    /** PUT: checkout */
    public function checkout(int $id, array $body): void {
        if ($id <= 0) json_response(false, null, 400, 'ID inválido');
        $fecha_salida = $body['fecha_salida'] ?? date('Y-m-d');

        try {
            $this->model->checkout($id, $fecha_salida);
            json_response(true, null, 200, 'Salida registrada');
        } catch (RuntimeException $e) {
            json_response(false, null, 409, $e->getMessage());
        }
    }

    /** GET: por ID */
    public function show(int $id): void {
        $row = $this->model->getById($id);
        $row
            ? json_response(true, $row)
            : json_response(false, null, 404, 'Registro no encontrado');
    }
}
