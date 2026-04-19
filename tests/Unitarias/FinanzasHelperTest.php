<?php
/**
 * tests/Unitarias/FinanzasHelperTest.php
 */

require_once __DIR__ . '/../TestCase.php';
// Nota: Normalmente usaríamos el autoloader de Composer, pero como es el primer test
// y aún no se ha ejecutado 'composer dump-autoload', lo incluimos manualmente.

class FinanzasHelperTest extends TestCase {

    /**
     * @dataProvider horasProvider
     */
    public function testGetTurnoActual(int $hora, string $turnoEsperado) {
        $resultado = FinanzasHelper::getTurnoActual($hora);
        $this->assertEquals($turnoEsperado, $resultado, "Para la hora $hora:00 el turno debería ser $turnoEsperado");
    }

    public function horasProvider() {
        return [
            [6, 'MAÑANA'],
            [10, 'MAÑANA'],
            [13, 'MAÑANA'],
            [14, 'TARDE'],
            [20, 'TARDE'],
            [2, 'TARDE'], // Madrugada
            [5, 'TARDE'],
        ];
    }
}
