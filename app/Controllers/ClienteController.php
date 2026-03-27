<?php
/**
 * app/Controllers/ClienteController.php
 */
require_once __DIR__ . '/../Models/ClienteModel.php';

class ClienteController {
    private ClienteModel $model;

    public function __construct(PDO $pdo) {
        $this->model = new ClienteModel($pdo);
    }

    public function index(string $buscar = ''): array {
        return $this->model->getAll($buscar);
    }

    public function historial(string $dni): array {
        return $this->model->historialPorDni($dni);
    }
}
