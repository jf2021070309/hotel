<?php
// ============================================================
// app/Controllers/HabitacionController.php
// ============================================================
require_once __DIR__ . '/../Models/HabitacionModel.php';

class HabitacionController {
    private HabitacionModel $model;
    private AuditoriaModel $audit;

    public function __construct(mysqli $db) {
        $this->model = new HabitacionModel($db);
        global $pdo; 
        require_once __DIR__ . '/../Models/AuditoriaModel.php';
        $this->audit = new AuditoriaModel($pdo);
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
        if ($id) {
            $detalle = json_encode([
                'mensaje' => "Registró nueva habitación #$numero",
                'cambios' => [
                    'N°'     => ['antes' => '-', 'despues' => $numero],
                    'Tipo'   => ['antes' => '-', 'despues' => $tipo],
                    'Precio' => ['antes' => 'S/ 0.00', 'despues' => 'S/ ' . number_format($precio_base, 2)]
                ]
            ], JSON_UNESCAPED_UNICODE);

            $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'], 'CREAR_HABITACION', 'HABITACIONES', $detalle);
            json_response(true, ['id' => $id], 201, 'Habitación creada');
        } else {
            json_response(false, null, 500, 'Error al crear habitación');
        }
    }

    /** PUT actualizar habitación */
    public function update(int $id, array $body): void {
        if ($id <= 0) json_response(false, null, 400, 'ID inválido');
        $numero      = trim($body['numero'] ?? '');
        $tipo        = $body['tipo']        ?? 'Simple';
        $piso        = (int)($body['piso']  ?? 1);
        $precio_base = (float)($body['precio_base'] ?? 0);

        if ($numero === '') json_response(false, null, 422, 'El número es obligatorio');

        $original = $this->model->getById($id);
        $ok = $this->model->update($id, compact('numero','tipo','piso','precio_base'));
        
        if ($ok) {
            $cambios = [];
            $labels = ['numero' => 'N°', 'tipo' => 'Tipo', 'piso' => 'Piso', 'precio_base' => 'Precio'];
            foreach (['numero', 'tipo', 'piso', 'precio_base'] as $key) {
                if ($original[$key] != $body[$key]) {
                    $cambios[$labels[$key]] = ['antes' => $original[$key], 'despues' => $body[$key]];
                }
            }
            $detalle = json_encode([
                'mensaje' => "Actualizó datos de Habitación #$numero",
                'cambios' => !empty($cambios) ? $cambios : null
            ], JSON_UNESCAPED_UNICODE);

            $this->audit->registrar($_SESSION['auth_id'], $_SESSION['auth_nombre'], 'ACTUALIZAR_HABITACION', 'HABITACIONES', $detalle);
            json_response(true, null, 200, 'Habitación actualizada');
        } else {
            json_response(false, null, 500, 'Error al actualizar');
        }
    }
}
