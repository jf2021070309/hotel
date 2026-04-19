<?php
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase {
    protected ?PDO $pdo = null;

    protected function setUp(): void {
        parent::setUp();
        
        // Incluir la conexión real para los tests de integración
        global $pdo; 
        if (!$pdo) {
            require_once __DIR__ . '/../config/db.php';
        }
        $this->pdo = $pdo;
        
        // Iniciar transacción para no ensuciar la base de datos
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void {
        // Deshacer todos los cambios realizados durante el test
        if ($this->pdo && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        parent::tearDown();
    }
}
