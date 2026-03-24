<?php
/**
 * api/desayunos.php
 * Router for Breakfasts module actions.
 */
require_once '../config/db.php';
require_once '../auth/session.php';
require_once '../app/Controllers/DesayunoController.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'hoy';
$controller = new DesayunoController($pdo);

switch ($action) {
    case 'hoy':
        echo json_encode($controller->getHoy());
        break;
    
    case 'listar':
        echo json_encode($controller->listar());
        break;

    case 'guardar':
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input) {
            $input['usuario_id'] = $_SESSION['auth_id'] ?? 1;
            echo json_encode($controller->guardar($input));
        } else {
            echo json_encode(['ok' => false, 'msg' => 'Datos inválidos']);
        }
        break;

    case 'imprimir':
        // Esta acción devuelve HTML para imprimir
        $id = $_GET['id'] ?? 0;
        $stmt = $pdo->prepare("SELECT * FROM desayunos WHERE id = ?");
        $stmt->execute([$id]);
        $d = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$d) die("Registro no encontrado.");
        
        $model = new DesayunoModel($pdo);
        $detalles = $model->getDetalle($id);
        $fecha = date('d/m/Y', strtotime($d['fecha']));
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Reporte Desayunos - <?= $fecha ?></title>
            <style>
                body { font-family: sans-serif; padding: 30px; color: #333; }
                .header { text-align: center; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
                .resumen { display: flex; justify-content: space-around; background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
                .kpi { text-align: center; }
                .kpi span { display: block; font-size: 24px; font-weight: bold; color: #d97706; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
                th { background: #f4f4f4; }
                .obs { margin-top: 30px; padding: 15px; border: 1px dashed #ccc; background: #fffbeb; }
                @media print { .btn-print { display: none; } }
            </style>
        </head>
        <body>
            <button class="btn-print" onclick="window.print()">Imprimir Reporte</button>
            <div class="header">
                <h1>REPORTE DE DESAYUNOS</h1>
                <p>Hotel Platinium - Fecha: <?= $fecha ?></p>
            </div>
            <div class="resumen">
                <div class="kpi"><small>PAX CALCULADO</small><span><?= $d['pax_calculado'] ?></span></div>
                <div class="kpi"><small>PAX AJUSTADO</small><span><?= $d['pax_ajustado'] ?></span></div>
                <div class="kpi"><small>TOTAL FINAL</small><span><?= $d['pax_ajustado'] ?: $d['pax_calculado'] ?></span></div>
            </div>
            <table>
                <thead><tr><th>HAB.</th><th>TITULAR</th><th>PAX</th><th>¿INCLUYE?</th></tr></thead>
                <tbody>
                    <?php foreach ($detalles as $it): ?>
                    <tr style="<?= !$it['incluye_desayuno'] ? 'color: #999; text-decoration: line-through;' : '' ?>">
                        <td><strong><?= $it['habitacion'] ?></strong></td>
                        <td><?= $it['titular'] ?></td>
                        <td><?= $it['pax'] ?> pax</td>
                        <td><?= $it['incluye_desayuno'] ? 'SÍ' : 'NO' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="obs"><strong>OBSERVACIONES:</strong><br><?= nl2br(htmlspecialchars($d['observacion'] ?? 'Ninguna')) ?></div>
        </body>
        </html>
        <?php
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => 'Acción no reconocida']);
        break;
}
