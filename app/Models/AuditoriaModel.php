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
        $sql = "INSERT INTO auditoria (usuario_id, usuario_nombre, accion, modulo, detalle, ip) 
                VALUES (:uid, :unombre, :accion, :modulo, :detalle, :ip)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'uid'     => $usuario_id,
            'unombre' => $usuario_nombre,
            'accion'  => $accion,
            'modulo'  => $modulo,
            'detalle' => $detalle,
            'ip'      => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
    }

    /**
     * Obtiene los logs de auditoría ordenados por fecha descendente.
     */
    public function getAll(int $limit = 100): array {
        $sql = "SELECT * FROM auditoria ORDER BY fecha_hora DESC LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
