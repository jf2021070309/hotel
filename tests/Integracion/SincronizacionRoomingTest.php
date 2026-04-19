<?php
/**
 * tests/Integracion/SincronizacionRoomingTest.php
 * Verifica que el registro de un pago en Rooming impacte correctamente en Finanzas.
 */

require_once __DIR__ . '/../TestCase.php';

class SincronizacionRoomingTest extends TestCase {
    private RoomingModel $rooming;
    private FinanzasHelper $finanzas;
    private int $uid;
    private int $stayId;

    protected function setUp(): void {
        parent::setUp();
        
        $this->rooming = new RoomingModel($this->pdo);
        $this->finanzas = new FinanzasHelper($this->pdo);

        // Obtener un usuario y una estadía reales para el test
        $this->uid = $this->pdo->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn() ?: 1;
        
        // Crear una estadía mínima si no hay una para el test
        $this->stayId = $this->pdo->query("SELECT id FROM rooming_stays LIMIT 1")->fetchColumn() ?: 0;
        if (!$this->stayId) {
            $this->pdo->prepare("INSERT INTO rooming_stays (operador, fecha_registro, habitacion_id, usuario_id, estado) VALUES ('TEST', CURDATE(), 1, ?, 'activo')")->execute([$this->uid]);
            $this->stayId = $this->pdo->lastInsertId();
        }
    }

    public function testPagoSincronizaConFlujoCaja() {
        // 1. Asegurar que haya un turno abierto para el test
        $turno = FinanzasHelper::getTurnoActual();
        $this->pdo->prepare("INSERT IGNORE INTO flujo_caja (fecha, turno, usuario_id, estado) VALUES (CURDATE(), ?, ?, 'borrador')")
                  ->execute([$turno, $this->uid]);
        $this->pdo->prepare("UPDATE flujo_caja SET estado = 'borrador' WHERE fecha = CURDATE() AND turno = ? AND usuario_id = ?")
                  ->execute([$turno, $this->uid]);
        
        $flujoId = $this->finanzas->getFlujoIdActivo($this->uid);
        $this->assertNotNull($flujoId, "Debe haber un flujo de caja activo para el test");

        // 2. Registrar un pago a través del modelo
        $montoTest = rand(50, 200);
        $pago = [
            'stay_id'   => $this->stayId,
            'monto'     => $montoTest,
            'moneda'    => 'PEN',
            'monto_pen' => $montoTest,
            'tc'        => 1.0,
            'tipo'      => 'EFECTIVO',
            'recibo'    => 'INTEG-TEST-'.rand(100,999),
            'fecha'     => date('Y-m-d'),
            'uid'       => $this->uid
        ];

        $res = $this->rooming->registrarPago($pago, 'hospedaje');
        $this->assertTrue($res, "El registro del pago en Rooming falló");

        // 3. Verificar que se creó el movimiento en el flujo de caja correcto
        $stmt = $this->pdo->prepare("SELECT monto, observacion 
                                    FROM flujo_caja_movimientos 
                                    WHERE flujo_id = ? 
                                    ORDER BY id DESC LIMIT 1");
        $stmt->execute([$flujoId]);
        $movimiento = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($movimiento, "No se encontró el movimiento en el flujo de caja");
        $this->assertEquals($montoTest, $movimiento['monto'], "El monto sincronizado no coincide");
        $this->assertStringContainsString('HOSPEDAJE:', $movimiento['observacion'], "La observación no tiene el formato esperado");
    }
}
