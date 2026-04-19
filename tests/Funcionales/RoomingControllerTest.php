<?php
/**
 * tests/Funcionales/RoomingControllerTest.php
 * Prueba de alto nivel que simula la interacción con el controlador de Rooming.
 */

require_once __DIR__ . '/../TestCase.php';

class RoomingControllerTest extends TestCase {
    private RoomingController $controller;
    private int $uid;

    protected function setUp(): void {
        parent::setUp();
        
        // Cargar el controlador
        require_once __DIR__ . '/../../app/Controllers/RoomingController.php';
        $this->controller = new RoomingController($this->pdo);

        // Simulamos una sesión de usuario
        $this->uid = $this->pdo->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn() ?: 1;
        $_SESSION['auth_id'] = $this->uid;
        $_SESSION['auth_nombre'] = 'Tester Functional';
    }

    public function testFlujoCompletoCheckin() {
        // 1. Preparar datos de entrada simulando el JSON que enviaría el Front-end
        $habitacionId = 1;
        // Asegurar que la habitación existe
        $this->pdo->prepare("INSERT IGNORE INTO habitaciones (id, numero, tipo, estado) VALUES (?, '101', 'SIMPLE', 'libre')")->execute([$habitacionId]);

        $input = [
            'stay' => [
                'fecha_registro' => date('Y-m-d'),
                'fecha_checkout' => date('Y-m-d', strtotime('+2 days')),
                'hora_checkin'   => '14:00',
                'medio_reserva'  => 'DIRECTO',
                'habitacion_id'  => $habitacionId,
                'noches'         => 2,
                'total_pago'     => 200.00,
                'moneda_pago'    => 'PEN',
                'monto_original' => 200.00,
                'tc_aplicado'    => 1.0,
                'metodo_pago'    => 'EFECTIVO',
                'tipo_comprobante' => 'TICKET',
                'total_cobrado'  => 50.00, // Adelanto inicial
                'estado_pago'    => 'parcial'
            ],
            'pax' => [
                ['nombre_completo' => 'HUÉSPED FUNCIONAL', 'documento' => '12345678', 'es_titular' => 1]
            ],
            'adelanto' => 50.00
        ];

        // 2. Ejecutar la acción del controlador
        // Nota: El controlador internamente validará el flujo de caja
        // Abrimos caja para que no falle (Garantizamos que el estado sea 'borrador')
        $turno = FinanzasHelper::getTurnoActual();
        $this->pdo->prepare("
            INSERT INTO flujo_caja (fecha, turno, usuario_id, estado) 
            VALUES (CURDATE(), ?, ?, 'borrador')
            ON DUPLICATE KEY UPDATE estado = 'borrador'
        ")->execute([$turno, $this->uid]);

        $response = $this->controller->checkin($input);

        // 3. Verificaciones (Assertions)
        $this->assertTrue($response['ok'], "El checkin falló: " . ($response['msg'] ?? ''));
        $stayId = $response['id'];

        // Verificar que se creó la auditoría
        $stmtAudit = $this->pdo->prepare("SELECT id FROM auditoria WHERE modulo = 'ROOMING' AND accion = 'CHECKIN_REGISTRADO' ORDER BY id DESC LIMIT 1");
        $stmtAudit->execute();
        $this->assertNotFalse($stmtAudit->fetch(), "No se registró la auditoría del checkin");

        // Verificar que la habitación cambió a 'ocupado' si el modelo lo hace
        $estadoHab = $this->pdo->query("SELECT estado FROM habitaciones WHERE id = $habitacionId")->fetchColumn();
        $this->assertEquals('ocupado', $estadoHab, "La habitación debería estar ocupada");
    }
}
