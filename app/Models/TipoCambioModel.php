<?php
/**
 * app/Models/TipoCambioModel.php
 * Modelo principal para tipos de cambio del módulo Calculadora.
 * Expone getActual() como método estático para uso en Rooming, Flujo de Caja, etc.
 */
class TipoCambioModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Devuelve el TC más reciente (fecha <= hoy) para USD y CLP.
     * Accesible como método estático pasando $pdo.
     *
     * @return array ['tc_usd' => float, 'tc_clp' => float, 'fecha' => string]
     */
    public function getActual(): array {
        return self::obtenerActual($this->pdo);
    }

    /**
     * Método estático para acceso desde otros módulos sin instanciar.
     * Uso: TipoCambioModel::obtenerActual($pdo)
     */
    public static function obtenerActual(PDO $pdo): array {
        $sql = "SELECT moneda_origen, factor, fecha
                FROM tipos_cambio
                WHERE fecha <= CURDATE()
                  AND moneda_destino = 'PEN'
                ORDER BY fecha DESC
                LIMIT 10";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $tc_usd = null;
        $tc_clp = null;
        $fecha  = null;

        foreach ($rows as $row) {
            if ($row['moneda_origen'] === 'USD' && $tc_usd === null) {
                $tc_usd = (float)$row['factor'];
                $fecha  = $fecha ?? $row['fecha'];
            }
            if ($row['moneda_origen'] === 'CLP' && $tc_clp === null) {
                $tc_clp = (float)$row['factor'];
                $fecha  = $fecha ?? $row['fecha'];
            }
            if ($tc_usd !== null && $tc_clp !== null) break;
        }

        return [
            'tc_usd' => $tc_usd ?? 3.75,
            'tc_clp' => $tc_clp ?? 277.0,
            'fecha'  => $fecha  ?? date('Y-m-d'),
        ];
    }

    /**
     * Retorna el historial de tipos de cambio agrupado por fecha DESC.
     * Cada fila tiene: fecha, tc_usd, tc_clp, created_at (del primer registro de la fecha).
     *
     * @return array[]
     */
    public function getHistorial(): array {
        $sql = "SELECT
                    fecha,
                    MAX(CASE WHEN moneda_origen = 'USD' THEN factor END) AS tc_usd,
                    MAX(CASE WHEN moneda_origen = 'CLP' THEN factor END) AS tc_clp,
                    MIN(created_at) AS created_at
                FROM tipos_cambio
                WHERE moneda_destino = 'PEN'
                GROUP BY fecha
                ORDER BY fecha DESC
                LIMIT 90";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Guarda o actualiza los TC del día indicado (ON DUPLICATE KEY UPDATE).
     * Inserta DOS filas: USD→PEN y CLP→PEN.
     *
     * @param string $fecha   formato 'Y-m-d'
     * @param float  $tc_usd  valor 1 USD en PEN
     * @param float  $tc_clp  valor 1 SOL en CLP (1 PEN = X CLP)
     */
    public function guardar(string $fecha, float $tc_usd, float $tc_clp): void {
        $sql = "INSERT INTO tipos_cambio (moneda_origen, moneda_destino, factor, fecha)
                VALUES (:origen, 'PEN', :factor, :fecha)
                ON DUPLICATE KEY UPDATE factor = VALUES(factor), created_at = created_at";

        $stmt = $this->pdo->prepare($sql);

        // USD → PEN
        $stmt->execute([
            'origen' => 'USD',
            'factor' => $tc_usd,
            'fecha'  => $fecha,
        ]);

        // CLP → PEN  (aquí guardamos cuántos CLP equivale 1 SOL)
        $stmt->execute([
            'origen' => 'CLP',
            'factor' => $tc_clp,
            'fecha'  => $fecha,
        ]);
    }

    /**
     * Lee parámetros de la tabla configuracion.
     *
     * @param array $parametros Lista de nombres de parámetro a leer.
     * @return array Mapa [parametro => valor]
     */
    public function getConfiguracion(array $parametros): array {
        if (empty($parametros)) return [];
        $placeholders = implode(',', array_fill(0, count($parametros), '?'));
        $sql  = "SELECT parametro, valor FROM configuracion WHERE parametro IN ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Guarda (INSERT IGNORE + UPDATE) uno o varios parámetros de configuración.
     *
     * @param array $params Mapa [parametro => valor]
     */
    public function guardarConfiguracion(array $params): void {
        $sql = "INSERT INTO configuracion (parametro, valor)
                VALUES (:p, :v)
                ON DUPLICATE KEY UPDATE valor = VALUES(valor)";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $p => $v) {
            $stmt->execute(['p' => $p, 'v' => $v]);
        }
    }
}
