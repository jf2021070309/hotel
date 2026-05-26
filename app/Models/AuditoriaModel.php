<?php
/**
 * app/Models/AuditoriaModel.php
 */
class AuditoriaModel {
    private PDO $pdo;
    private ?array $columnasAuditoria = null;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Registra un evento en la tabla de auditoria.
     */
    public function registrar(
        ?int $usuario_id,
        string $accion,
        string $modulo,
        ?string $detalle = null
    ): void {
        $datos = [
            'usuario_id' => $usuario_id,
            'accion' => $accion,
            'modulo' => $modulo,
            'detalle' => $detalle,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ];

        if ($this->tieneColumna('dispositivo')) {
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $datos['dispositivo'] = $this->parseUA($ua);
        }

        $columnas = array_keys($datos);
        $placeholders = array_map(fn(string $columna): string => ':' . $columna, $columnas);

        $sql = sprintf(
            'INSERT INTO auditoria (%s) VALUES (%s)',
            implode(', ', $columnas),
            implode(', ', $placeholders)
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($datos);
    }

    /**
     * Detecta de forma basica el dispositivo y navegador.
     */
    private function parseUA(string $ua): string {
        $os = 'Escritorio';
        if (preg_match('/mobile/i', $ua)) $os = 'Movil';
        if (preg_match('/tablet/i', $ua)) $os = 'Tablet';
        if (preg_match('/iphone|ipad/i', $ua)) $os = 'iOS';
        if (preg_match('/android/i', $ua)) $os = 'Android';

        $browser = 'Navegador';
        if (preg_match('/chrome/i', $ua)) $browser = 'Chrome';
        elseif (preg_match('/safari/i', $ua)) $browser = 'Safari';
        elseif (preg_match('/firefox/i', $ua)) $browser = 'Firefox';
        elseif (preg_match('/edge/i', $ua)) $browser = 'Edge';

        return $os . ' - ' . $browser;
    }

    private function tieneColumna(string $columna): bool {
        if ($this->columnasAuditoria === null) {
            $stmt = $this->pdo->query('SHOW COLUMNS FROM auditoria');
            $this->columnasAuditoria = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        return in_array($columna, $this->columnasAuditoria, true);
    }

    /**
     * Obtiene los logs de auditoria ordenados por fecha descendente con filtros.
     */
    public function getAll(array $filters = [], int $limit = 250): array {
        $sql = "SELECT a.*, u.nombre as usuario_nombre, u.rol as rol_usuario
                FROM auditoria a
                LEFT JOIN usuarios u ON a.usuario_id = u.id
                WHERE 1=1";

        $params = [];

        if (!empty($filters['nombre'])) {
            $sql .= " AND (u.nombre LIKE :nombre OR u.usuario LIKE :nombre)";
            $params['nombre'] = '%' . $filters['nombre'] . '%';
        }

        if (!empty($filters['rol']) && $filters['rol'] !== 'TODOS') {
            $sql .= " AND u.rol = :rol";
            $params['rol'] = $filters['rol'];
        }

        if (!empty($filters['desde'])) {
            $sql .= " AND DATE(a.fecha_hora) >= :desde";
            $params['desde'] = $filters['desde'];
        }
        if (!empty($filters['hasta'])) {
            $sql .= " AND DATE(a.fecha_hora) <= :hasta";
            $params['hasta'] = $filters['hasta'];
        }

        $sql .= " ORDER BY a.fecha_hora DESC LIMIT :limit";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
