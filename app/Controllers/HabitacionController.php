<?php
// ============================================================
// app/Controllers/HabitacionController.php
// ============================================================
require_once __DIR__ . '/../Models/HabitacionModel.php';

class HabitacionController {
    private HabitacionModel $model;

    public function __construct(mysqli $db) {
        $this->model = new HabitacionModel($db);
    }

    /** GET habitaciones — lista todas o una por id */
    public function index(int $id = 0): void {
        if ($id > 0) {
            $row = $this->model->getById($id);
            $row
                ? json_response(true, $row)
                : json_response(false, null, 404, 'Habitación no encontrada');
        } else {
            json_response(true, $this->model->getAll());
        }
    }

    /** GET habitaciones libres */
    public function libres(): void {
        json_response(true, $this->model->getLibres());
    }

    /** POST crear habitación */
    public function store(array $body): void {
        $numero      = trim($body['numero'] ?? '');
        $tipo        = $body['tipo']        ?? 'Simple';
        $piso        = (int)($body['piso']  ?? 1);
        $precio_base = (float)($body['precio_base'] ?? 0);

        if ($numero === '')   json_response(false, null, 422, 'El número es obligatorio');
        if ($precio_base <= 0) json_response(false, null, 422, 'El precio debe ser mayor a 0');
        if (!in_array($tipo, ['Simple','Doble','Suite']))
            json_response(false, null, 422, 'Tipo inválido');

        $id = $this->model->create(compact('numero','tipo','piso','precio_base'));
        $id
            ? json_response(true, ['id' => $id], 201, 'Habitación creada')
            : json_response(false, null, 500, 'Error al crear habitación');
    }

    /** PUT actualizar habitación */
    public function update(int $id, array $body): void {
        if ($id <= 0) json_response(false, null, 400, 'ID inválido');
        $numero      = trim($body['numero'] ?? '');
        $tipo        = $body['tipo']        ?? 'Simple';
        $piso        = (int)($body['piso']  ?? 1);
        $precio_base = (float)($body['precio_base'] ?? 0);

        if ($numero === '') json_response(false, null, 422, 'El número es obligatorio');

        $ok = $this->model->update($id, compact('numero','tipo','piso','precio_base'));
        $ok
            ? json_response(true, null, 200, 'Habitación actualizada')
            : json_response(false, null, 500, 'Error al actualizar');
    }
}
