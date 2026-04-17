<?php
/**
 * app/Models/AuditoriaModel.php
 */
class AuditoriaModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Registra un evento en la tabla de auditoría.
     */
    public function registrar(
        ?int $usuario_id,
        ?string $usuario_nombre,
        string $accion,
        string $modulo,
        ?string $detalle = null
    ): void {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $dispositivo = $this->parseUA($ua);

        $sql = "INSERT INTO auditoria (usuario_id, usuario_nombre, accion, modulo, detalle, dispositivo, ip) 
                VALUES (:uid, :unombre, :accion, :modulo, :detalle, :dispositivo, :ip)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'uid'         => $usuario_id,
            'unombre'     => $usuario_nombre,
            'accion'      => $accion,
            'modulo'      => $modulo,
            'detalle'     => $detalle,
            'dispositivo' => $dispositivo,
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
    }

    /**
     * Detecta de forma básica el dispositivo y navegador.
     */
    private function parseUA(string $ua): string {
        $os = "Escritorio";
        if (preg_match('/mobile/i', $ua)) $os = "Móvil";
        if (preg_match('/tablet/i', $ua)) $os = "Tablet";
        if (preg_match('/iphone|ipad/i', $ua)) $os = "iOS";
        if (preg_match('/android/i', $ua)) $os = "Android";

        $browser = "Navegador";
        if (preg_match('/chrome/i', $ua)) $browser = "Chrome";
        elseif (preg_match('/safari/i', $ua)) $browser = "Safari";
        elseif (preg_match('/firefox/i', $ua)) $browser = "Firefox";
        elseif (preg_match('/edge/i', $ua)) $browser = "Edge";

        return "$os — $browser";
    }

    /**
     * Obtiene los logs de auditoría ordenados por fecha descendente con filtros.
     */
    public function getAll(array $filters = [], int $limit = 250): array {
        $sql = "SELECT a.*, u.rol as rol_usuario 
                FROM auditoria a 
                LEFT JOIN usuarios u ON a.usuario_id = u.id 
                WHERE 1=1";
        
        $params = [];

        // Filtro por nombre de usuario (nombre real o username)
        if (!empty($filters['nombre'])) {
            $sql .= " AND (a.usuario_nombre LIKE :nombre OR u.usuario LIKE :nombre)";
            $params['nombre'] = "%" . $filters['nombre'] . "%";
        }

        // Filtro por rol
        if (!empty($filters['rol']) && $filters['rol'] !== 'TODOS') {
            $sql .= " AND u.rol = :rol";
            $params['rol'] = $filters['rol'];
        }

        // Filtro por fechas
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
