<?php
// ============================================================
// app/Controllers/PagoController.php
// ============================================================
require_once __DIR__ . '/../Models/PagoModel.php';

class PagoController {
    private PagoModel $model;

    public function __construct(mysqli $db) {
        $this->model = new PagoModel($db);
    }

    public function index(): void {
        json_response(true, $this->model->getAll());
    }

    public function store(array $body): void {
        $registro_id = (int)($body['registro_id'] ?? 0);
        $monto       = (float)($body['monto']      ?? 0);
        $metodo      = $body['metodo'] ?? 'efectivo';
        $fecha       = $body['fecha']  ?? date('Y-m-d');

        if ($registro_id <= 0)   json_response(false, null, 422, 'Seleccione un huésped activo');
        if ($monto <= 0)         json_response(false, null, 422, 'El monto debe ser mayor a 0');
        if (!in_array($metodo, ['efectivo','tarjeta']))
            json_response(false, null, 422, 'Método de pago inválido');

        $id = $this->model->create(compact('registro_id','monto','metodo','fecha'));
        json_response(true, ['id' => $id], 201, 'Pago registrado');
    }
}
