<?php
// ============================================================
// app/Controllers/ClienteController.php
// ============================================================
require_once __DIR__ . '/../Models/ClienteModel.php';

class ClienteController {
    private ClienteModel $model;

    public function __construct(mysqli $db) {
        $this->model = new ClienteModel($db);
    }

    public function index(string $buscar = ''): void {
        json_response(true, $this->model->getAll($buscar));
    }

    public function store(array $body): void {
        $nombre   = trim($body['nombre']   ?? '');
        $dni      = trim($body['dni']      ?? '');
        $telefono = trim($body['telefono'] ?? '');

        if ($nombre === '') json_response(false, null, 422, 'El nombre es obligatorio');
        if ($dni    === '') json_response(false, null, 422, 'El DNI es obligatorio');

        $result = $this->model->create(compact('nombre','dni','telefono'));
        $msg = $result['duplicado'] ? 'Cliente ya existía, reutilizado' : 'Cliente creado';
        json_response(true, ['id' => $result['id'], 'duplicado' => $result['duplicado']], 201, $msg);
    }
}
