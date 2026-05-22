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
        $this->pdo->prepare("INSERT INTO habitaciones (id, numero, tipo, estado) VALUES (?, '101', 'SIMPLE', 'libre') ON DUPLICATE KEY UPDATE estado = 'libre', numero = '101'")->execute([$habitacionId]);

        // Limpiar cualquier registro de limpieza de hoy para esta hab para que el guard no bloquee el check-in
        $this->pdo->prepare("DELETE FROM limpieza_registros WHERE habitacion_id = ? AND fecha = CURDATE()")->execute([$habitacionId]);

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

    public function testEdicionCambioMoneda() {
        $habitacionId = 1;
        $this->pdo->prepare("INSERT INTO habitaciones (id, numero, tipo, estado) VALUES (?, '101', 'SIMPLE', 'libre') ON DUPLICATE KEY UPDATE estado = 'libre', numero = '101'")->execute([$habitacionId]);
        $this->pdo->prepare("DELETE FROM limpieza_registros WHERE habitacion_id = ? AND fecha = CURDATE()")->execute([$habitacionId]);

        // 1. Checkin inicial en PEN
        $input = [
            'stay' => [
                'fecha_registro' => date('Y-m-d'),
                'fecha_checkout' => date('Y-m-d', strtotime('+1 days')),
                'hora_checkin'   => '12:00',
                'medio_reserva'  => 'DIRECTO',
                'habitacion_id'  => $habitacionId,
                'noches'         => 1,
                'total_pago'     => 300.00,
                'moneda_pago'    => 'PEN',
                'monto_original' => 300.00,
                'tc_aplicado'    => 1.0,
                'metodo_pago'    => 'EFECTIVO',
                'tipo_comprobante' => 'TICKET',
                'total_cobrado'  => 150.00,
                'estado_pago'    => 'parcial'
            ],
            'pax' => [
                ['nombre_completo' => 'HUÉSPED MULTIMONEDA', 'documento_tipo' => 'DNI', 'documento_num' => '87654321', 'es_titular' => 1]
            ],
            'adelanto' => 150.00,
            'tipoPago' => 'adelanto'
        ];

        $resCheckin = $this->controller->checkin($input);
        $this->assertTrue($resCheckin['ok']);
        $stayId = $resCheckin['id'];

        // Verificar datos en bd
        $stayDb = $this->pdo->query("SELECT * FROM rooming_stays WHERE id = $stayId")->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(300.00, (float)$stayDb['total_pago']);
        $this->assertEquals(150.00, (float)$stayDb['total_cobrado']);
        $this->assertEquals(150.00, (float)$stayDb['total_cobrado_orig']);
        $this->assertEquals('PEN', $stayDb['moneda_pago']);

        // 2. Editar stay: cambiar moneda a USD y TC = 3.0
        $inputEdit = [
            'stay' => [
                'id'             => $stayId,
                'fecha_registro' => date('Y-m-d'),
                'fecha_checkout' => date('Y-m-d', strtotime('+1 days')),
                'hora_checkin'   => '12:00',
                'medio_reserva'  => 'DIRECTO',
                'habitacion_id'  => $habitacionId,
                'noches'         => 1,
                'total_pago'     => 300.00,
                'moneda_pago'    => 'USD',
                'monto_original' => 100.00,
                'tc_aplicado'    => 3.0,
                'metodo_pago'    => 'EFECTIVO',
                'tipo_comprobante' => 'TICKET',
                'total_cobrado'  => 150.00,
                'estado_pago'    => 'parcial'
            ],
            'pax' => [
                ['nombre_completo' => 'HUÉSPED MULTIMONEDA', 'documento_tipo' => 'DNI', 'documento_num' => '87654321', 'es_titular' => 1]
            ]
        ];

        $resEdit = $this->controller->checkin($inputEdit);
        $this->assertTrue($resEdit['ok'], "Edit checkin failed: " . ($resEdit['msg'] ?? ''));

        // Verificar que total_cobrado_orig cambió a 50 USD (150 PEN / 3.0 TC)
        $stayDb2 = $this->pdo->query("SELECT * FROM rooming_stays WHERE id = $stayId")->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('USD', $stayDb2['moneda_pago']);
        $this->assertEquals(300.00, (float)$stayDb2['total_pago']);
        $this->assertEquals(100.00, (float)$stayDb2['monto_original']);
        $this->assertEquals(150.00, (float)$stayDb2['total_cobrado']);
        $this->assertEquals(50.00, (float)$stayDb2['total_cobrado_orig']);

        // 3. Incrementar deuda con consumo de S/ 30
        $this->pdo->query("DELETE FROM inventario_productos WHERE id = 9999");
        $this->pdo->query("INSERT INTO inventario_productos (id, nombre, precio_venta, stock_actual, categoria, refrigeradora, activo) VALUES (9999, 'Producto Test', 15.00, 10, 'TEST', 0, 1)");
        
        $consumoController = new ConsumoController($this->pdo);
        $resCons = $consumoController->registrar([
            'stay_id'     => $stayId,
            'producto_id' => 9999,
            'cantidad'    => 2,
            'precio'      => 15.00,
            'metodo_pago' => null // A la habitación
        ]);
        $this->assertTrue($resCons['ok'], "Registrar consumo falló: " . ($resCons['msg'] ?? ''));

        // Verificar en BD: total_pago permanece en 300.00 (decouplado). monto_original no se altera -> 100.
        // El consumo se registra por separado en la tabla rooming_consumos.
        $stayDb3 = $this->pdo->query("SELECT * FROM rooming_stays WHERE id = $stayId")->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(300.00, (float)$stayDb3['total_pago']);
        $this->assertEquals(100.00, (float)$stayDb3['monto_original']);
        $this->assertEquals(150.00, (float)$stayDb3['total_cobrado']);
        $this->assertEquals(50.00, (float)$stayDb3['total_cobrado_orig']);

        // Verificar que el consumo se haya registrado correctamente en rooming_consumos por 30 PEN
        $totalConsumos = (float)$this->pdo->query("SELECT SUM(total) FROM rooming_consumos WHERE stay_id = $stayId")->fetchColumn();
        $this->assertEquals(30.00, $totalConsumos);
    }
}
