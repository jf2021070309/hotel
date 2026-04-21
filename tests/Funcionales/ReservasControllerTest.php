<?php
/**
 * tests/Funcionales/ReservasControllerTest.php
 */

require_once __DIR__ . '/../TestCase.php';

class ReservasControllerTest extends TestCase {
    private ReservasController $controller;
    private int $uid;

    protected function setUp(): void {
        parent::setUp();

        require_once __DIR__ . '/../../app/Controllers/ReservasController.php';
        $this->controller = new ReservasController($this->pdo);

        $this->uid = (int)($this->pdo->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn() ?: 1);
        $_SESSION['auth_id'] = $this->uid;
        $_SESSION['auth_nombre'] = 'Tester Reservas';
    }

    private function crearReservaBase(int $habitacionId, string $estadoHabitacion = 'libre'): int {
        $this->pdo->prepare("
            INSERT INTO habitaciones (id, numero, tipo, estado)
            VALUES (?, '109', 'SIMPLE', ?)
            ON DUPLICATE KEY UPDATE numero = '109', tipo = 'SIMPLE', estado = VALUES(estado)
        ")->execute([$habitacionId, $estadoHabitacion]);

        $this->pdo->prepare("
            INSERT INTO rooming_stays (
                operador, fecha_registro, fecha_checkout, medio_reserva, habitacion_id,
                tipo_hab_declarado, noches, pax_total, total_pago, moneda_pago, metodo_pago,
                tipo_comprobante, cobrador, observaciones, usuario_id, estado, estado_pago
            ) VALUES (
                'TEST', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'DIRECTO', ?,
                'RESERVA', 1, 1, 0, 'PEN', 'EFECTIVO',
                'RECIBO', 'TESTER', 'Reserva funcional', ?, 'reservado', 'pendiente'
            )
        ")->execute([$habitacionId, $this->uid]);

        $stayId = (int)$this->pdo->lastInsertId();

        $this->pdo->prepare("
            INSERT INTO rooming_pax (stay_id, nombre_completo, documento_num, es_titular)
            VALUES (?, 'HUESPED TEST', '00000000', 1)
        ")->execute([$stayId]);

        return $stayId;
    }

    public function testPuedeConfirmarReservaDesdeReservas(): void {
        $stayId = $this->crearReservaBase(109, 'libre');

        $response = $this->controller->checkin(['id' => $stayId]);

        $this->assertTrue($response['ok'], $response['msg'] ?? 'No se pudo confirmar');
        $estadoStay = $this->pdo->query("SELECT estado FROM rooming_stays WHERE id = {$stayId}")->fetchColumn();
        $estadoHab = $this->pdo->query("SELECT estado FROM habitaciones WHERE id = 109")->fetchColumn();

        $this->assertSame('activo', $estadoStay);
        $this->assertSame('ocupado', $estadoHab);
    }

    public function testPuedeRechazarReservaDesdeReservas(): void {
        $stayId = $this->crearReservaBase(110, 'reservado');

        $response = $this->controller->rechazar(['id' => $stayId]);

        $this->assertTrue($response['ok'], $response['msg'] ?? 'No se pudo rechazar');
        $estadoStay = $this->pdo->query("SELECT estado FROM rooming_stays WHERE id = {$stayId}")->fetchColumn();
        $estadoHab = $this->pdo->query("SELECT estado FROM habitaciones WHERE id = 110")->fetchColumn();

        $this->assertSame('cancelado', $estadoStay);
        $this->assertSame('libre', $estadoHab);
    }

    public function testPuedeEditarReservaDesdeReservas(): void {
        $stayId = $this->crearReservaBase(111, 'libre');

        $response = $this->controller->editarQuickReserva([
            'id' => $stayId,
            'fecha' => '2026-04-25',
            'titular' => 'EMPRESA EDITADA SAC',
            'noches' => 3,
            'observaciones' => 'Cliente llega tarde',
            'canal' => 'BOOKING'
        ]);

        $this->assertTrue($response['ok'], $response['msg'] ?? 'No se pudo editar');

        $stay = $this->pdo->query("SELECT fecha_registro, fecha_checkout, noches, medio_reserva, observaciones, estado FROM rooming_stays WHERE id = {$stayId}")
            ->fetch(PDO::FETCH_ASSOC);
        $titular = $this->pdo->query("SELECT nombre_completo FROM rooming_pax WHERE stay_id = {$stayId} AND es_titular = 1 LIMIT 1")
            ->fetchColumn();

        $this->assertSame('2026-04-25', $stay['fecha_registro']);
        $this->assertSame('2026-04-28', $stay['fecha_checkout']);
        $this->assertSame('3', (string)$stay['noches']);
        $this->assertSame('BOOKING', $stay['medio_reserva']);
        $this->assertSame('Cliente llega tarde', $stay['observaciones']);
        $this->assertSame('reservado', $stay['estado']);
        $this->assertSame('EMPRESA EDITADA SAC', $titular);
    }
}
