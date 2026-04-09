<?php
/**
 * app/Models/TiposCambioModel.php
 */
class TiposCambioModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getToday(string $moneda): ?float {
        $stmt = $this->pdo->prepare("SELECT factor FROM tipos_cambio WHERE moneda_origen = ? AND fecha = CURDATE() LIMIT 1");
        $stmt->execute([$moneda]);
        $val = $stmt->fetchColumn();
        if ($val === false) {
            // Fallback robusto si no hay registro hoy
            require_once __DIR__ . '/TipoCambioModel.php';
            $tc = TipoCambioModel::obtenerActual($this->pdo);
            return $moneda === 'USD' ? $tc['tc_usd'] : $tc['tc_clp'];
        }
        return (float)$val;
    }

    public function setTC(string $moneda, float $factor): bool {
        $sql = "INSERT INTO tipos_cambio (moneda_origen, factor, fecha) 
                VALUES (:moneda, :factor, CURDATE()) 
                ON DUPLICATE KEY UPDATE factor = VALUES(factor)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['moneda' => $moneda, 'factor' => $factor]);
    }
}
