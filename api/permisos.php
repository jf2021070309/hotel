<?php
// api/permisos.php — Gestión de permisos de módulos por usuario
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';

if (!estaAutenticado()) { json_response(false, null, 401, 'No autorizado'); }

// Solo admin puede modificar
$accion = $_GET['action'] ?? '';

// Lista canónica de módulos del sistema
define('MODULOS', [
    'dashboard'        => ['label' => 'Dashboard',            'icon' => 'bi-grid-1x2-fill'],
    'habitaciones'     => ['label' => 'Habitaciones',          'icon' => 'bi-door-open-fill'],
    'rooming'          => ['label' => 'Rooming / Check-in',    'icon' => 'bi-calendar-check-fill'],
    'reservas'         => ['label' => 'Cuadro de Reservas',    'icon' => 'bi-grid-3x3-gap-fill'],
    'flujo'            => ['label' => 'Flujo de Caja',         'icon' => 'bi-cash-stack'],
    'caja_chica'       => ['label' => 'Caja Chica',            'icon' => 'bi-box2-heart'],
    'yape'             => ['label' => 'Gastos Yape',           'icon' => 'bi-wallet2'],
    'inventario'       => ['label' => 'Inventario de Bebidas', 'icon' => 'bi-box-seam-fill'],
    'desayunos'        => ['label' => 'Desayunos',             'icon' => 'bi-egg-fried'],
    'limpieza'         => ['label' => 'Limpieza',              'icon' => 'bi-stars'],
    'clientes'         => ['label' => 'Clientes',              'icon' => 'bi-people-fill'],
    'gestion_usuarios' => ['label' => 'Gestión Usuarios',      'icon' => 'bi-people-fill'],
    'medios_pago'      => ['label' => 'Medios de Pago',        'icon' => 'bi-credit-card-2-back-fill'],
    'auditoria'        => ['label' => 'Auditoría',             'icon' => 'bi-journal-text'],
    'reporte_mendoza'  => ['label' => 'Reporte Mendoza',       'icon' => 'bi-file-earmark-bar-graph-fill'],
    'reporte_alex'     => ['label' => 'Reporte Alex',          'icon' => 'bi-person-badge-fill'],
]);

switch ($accion) {

    case 'listar':
        // Devuelve los permisos de un usuario específico
        $uid = (int)($_GET['usuario_id'] ?? 0);
        if (!$uid) { json_response(false, null, 400, 'usuario_id requerido'); }

        // Leer permisos guardados
        $stmt = $pdo->prepare("SELECT modulo, activo FROM usuario_permisos WHERE usuario_id = ?");
        $stmt->execute([$uid]);
        $guardados = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // modulo => activo

        // Construir lista con todos los módulos (si no existe = activo por defecto)
        $resultado = [];
        foreach (MODULOS as $key => $info) {
            $resultado[] = [
                'modulo' => $key,
                'label'  => $info['label'],
                'icon'   => $info['icon'],
                'activo' => isset($guardados[$key]) ? (int)$guardados[$key] : 1,
            ];
        }
        json_response(true, $resultado);
        break;

    case 'guardar':
        // Solo admin puede cambiar permisos
        if (($_SESSION['auth_rol'] ?? '') !== 'admin') {
            json_response(false, null, 403, 'Solo el administrador puede modificar permisos');
        }
        $body   = json_decode(file_get_contents('php://input'), true) ?? [];
        $uid    = (int)($body['usuario_id'] ?? 0);
        $perms  = $body['permisos'] ?? [];   // [{ modulo: 'rooming', activo: 1 }, ...]

        if (!$uid || empty($perms)) {
            json_response(false, null, 400, 'Datos incompletos');
        }
        // No se pueden cambiar permisos del admin id=1 ni del propio usuario logueado
        if ($uid === 1) {
            json_response(false, null, 403, 'No se pueden restringir permisos al administrador principal');
        }

        $stmt = $pdo->prepare(
            "INSERT INTO usuario_permisos (usuario_id, modulo, activo)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE activo = VALUES(activo)"
        );
        $pdo->beginTransaction();
        foreach ($perms as $p) {
            $stmt->execute([$uid, $p['modulo'], $p['activo'] ? 1 : 0]);
        }
        $pdo->commit();

        json_response(true, null, 200, 'Permisos actualizados correctamente');
        break;

    default:
        json_response(false, null, 400, 'Acción no válida');
}
