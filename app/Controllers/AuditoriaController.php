<?php
/**
 * app/Controllers/AuditoriaController.php
 */
class AuditoriaController {
    private PDO $pdo;
    private AuditoriaModel $model;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        require_once __DIR__ . '/../Models/AuditoriaModel.php';
        $this->model = new AuditoriaModel($pdo);
    }

    /**
     * Listar logs de auditoría
     */
    public function index() {
        return $this->model->getAll(200); // Últimos 200 logs
    }
}
