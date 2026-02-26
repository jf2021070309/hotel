<?php
// ============================================================
// app/Controllers/GastoController.php
// ============================================================
require_once __DIR__ . '/../Models/GastoModel.php';

class GastoController {
    private GastoModel $model;

    public function __construct(mysqli $db) {
        $this->model = new GastoModel($db);
    }

    public function index(string $fecha = ''): void {
        json_response(true, $this->model->getAll($fecha));
    }

    public function store(array $body): void {
        $descripcion = trim($body['descripcion'] ?? '');
        $monto       = (float)($body['monto']    ?? 0);
        $fecha       = $body['fecha'] ?? date('Y-m-d');

        if ($descripcion === '') json_response(false, null, 422, 'La descripción es obligatoria');
        if ($monto <= 0)         json_response(false, null, 422, 'El monto debe ser mayor a 0');

        $id = $this->model->create(compact('descripcion','monto','fecha'));
        json_response(true, ['id' => $id], 201, 'Gasto registrado');
    }

    public function destroy(int $id): void {
        if ($id <= 0) json_response(false, null, 400, 'ID inválido');
        $ok = $this->model->delete($id);
        $ok
            ? json_response(true, null, 200, 'Gasto eliminado')
            : json_response(false, null, 500, 'Error al eliminar');
    }
}
