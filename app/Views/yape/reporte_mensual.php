<?php
/**
 * app/Views/yape/reporte_mensual.php
 * Reporte de Gastos Yape Mensual — formato impresión.
 */
$base = '../../../';
require_once $base . 'auth/middleware.php';
protegerPorRol('cajera', 'yape');

require_once $base . 'config/db.php';
require_once $base . 'app/Models/YapeModel.php';

$mes = (int)($_GET['mes'] ?? date('n'));
$anio = (int)($_GET['anio'] ?? date('Y'));

$model = new YapeModel($pdo);
$registros = $model->listar(['mes' => $mes, 'anio' => $anio]);

$categoriasConfig = ['MERCADO', 'MOVILIDAD', 'CAFETERÍA/VEA', 'LAVANDERÍA', 'SERV. REPUESTOS', 'OTROS'];

$grupos = [];
$globales = [
    'yape_recibido' => 0, 'total_gastado' => 0, 'vuelto' => 0,
    'rubros' => ['MERCADO' => 0, 'MOVILIDAD' => 0, 'CAFETERÍA/VEA' => 0, 'LAVANDERÍA' => 0, 'SERV. REPUESTOS' => 0, 'OTROS' => 0]
];

foreach ($registros as $r) {
    if (!isset($grupos[$r['fecha']])) {
        $grupos[$r['fecha']] = [
            'fecha' => $r['fecha'],
            'turnos' => [],
            'totales' => [
                'yape_recibido' => 0, 'total_gastado' => 0, 'vuelto' => 0,
                'rubros' => ['MERCADO' => 0, 'MOVILIDAD' => 0, 'CAFETERÍA/VEA' => 0, 'LAVANDERÍA' => 0, 'SERV. REPUESTOS' => 0, 'OTROS' => 0]
            ]
        ];
    }
    $grupos[$r['fecha']]['turnos'][] = $r;
    
    // Locales
    $grupos[$r['fecha']]['totales']['yape_recibido'] += (float)$r['yape_recibido'];
    $grupos[$r['fecha']]['totales']['total_gastado'] += (float)$r['total_gastado'];
    $grupos[$r['fecha']]['totales']['vuelto'] += (float)$r['vuelto'];
    
    // Globales
    $globales['yape_recibido'] += (float)$r['yape_recibido'];
    $globales['total_gastado'] += (float)$r['total_gastado'];
    $globales['vuelto'] += (float)$r['vuelto'];
    
    foreach ($globales['rubros'] as $key => $val) {
        $monto = (float)($r['detalles_montos'][$key] ?? 0);
        $grupos[$r['fecha']]['totales']['rubros'][$key] += $monto;
        $globales['rubros'][$key] += $monto;
    }
}

// Ordenar fechas descendente
uasort($grupos, function($a, $b) {
    return strcmp($b['fecha'], $a['fecha']);
});

$nombresMeses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$nombreMes = $nombresMeses[$mes - 1] ?? '';
$generadoEn = date('d/m/Y H:i');

function fmtDate($dateStr) {
    $p = explode('-', $dateStr);
    return count($p) === 3 ? "{$p[2]}/{$p[1]}/{$p[0]}" : $dateStr;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reporte Yape <?= $nombreMes ?> <?= $anio ?></title>
  <!-- Favicon -->
  <link rel="icon" type="image/jpeg" href="<?= $base ?>assets/img/logo.jpg">
  <link rel="shortcut icon" type="image/jpeg" href="<?= $base ?>assets/img/logo.jpg">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');

    body {
      font-family: 'Inter', sans-serif;
      background: #f0f2f5;
      color: #222;
    }

    .report-container {
      max-width: 1000px;
      margin: 36px auto;
      background: #fff;
      padding: 48px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.10);
      border-radius: 14px;
    }

    .header-report {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      border-bottom: 2px solid #e9ecef;
      padding-bottom: 18px;
      margin-bottom: 28px;
    }

    .hotel-brand { font-weight: 800; font-size: 22px; letter-spacing: -0.8px; color: #111; }
    .hotel-brand span { color: #6366f1; }

    .report-meta { text-align: right; }
    .report-meta .badge-id { font-size: 11px; background: #f3f4f6; border: 1px solid #e5e7eb; padding: 4px 10px; border-radius: 20px; font-weight: 700; }

    h2.title { font-weight: 800; font-size: 20px; color: #1e293b; margin-bottom: 4px; }

    .info-row {
      display: flex; gap: 24px; flex-wrap: wrap;
      margin-bottom: 28px;
      padding: 14px 18px;
      background: #f8fafc;
      border-radius: 10px;
      border: 1px solid #e2e8f0;
    }
    .info-item label { display: block; font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 2px; }
    .info-item span  { font-size: 14px; font-weight: 600; color: #334155; }

    /* Tabla gastos Excel style a la imprimir.php */
    .expense-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    .expense-table thead tr { background: #1e293b; color: #fff; }
    .expense-table th { padding: 11px 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; text-align: center; border: 1px solid #334155;}
    .expense-table td { padding: 6px 8px; font-size: 12px; border: 1px solid #e2e8f0; }
    
    .row-turno { background: #ffffff; }
    .row-total-dia { background: #fef08a !important; font-weight: bold; }
    
    .expense-table .col-centro { text-align: center; }
    .expense-table .col-monto { text-align: right; white-space: nowrap; }

    .row-total-mes { background: #e2e8f0 !important; font-weight: 800; font-size: 13px; color: #0f172a; }
    .row-total-mes td { border-top: 3px solid #94a3b8 !important; }

    /* Firmas */
    .signatures { display: flex; justify-content: space-around; text-align: center; margin-top: 60px; }
    .sig-box { width: 220px; border-top: 1px solid #333; padding-top: 8px; }
    .sig-name { font-weight: 700; font-size: 12px; }
    .sig-role { font-size: 11px; color: #888; }

    .footer-note { text-align: center; font-size: 11px; color: #aaa; margin-top: 36px; font-style: italic; }

    /* Print */
    @media print {
      body { background: #fff; }
      .report-container { box-shadow: none; margin: 0; padding: 10px; max-width: 100%; border-radius: 0; }
      .no-print { display: none !important; }
      .row-total-dia { background: #fef08a !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      .row-total-mes { background: #e2e8f0 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      .expense-table thead tr { background: #1e293b !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
  </style>
</head>
<body>

  <div class="container no-print mt-3 mb-0 text-end" style="max-width:1000px; margin: 0 auto;">
    <a href="index.php" class="btn btn-outline-secondary btn-sm me-2">
      <i class="bi bi-arrow-left"></i> Volver a Gastos Yape
    </a>
    <button class="btn btn-primary btn-sm shadow-sm" onclick="window.print()">
      <i class="bi bi-printer me-1"></i> Imprimir Reporte Mensual
    </button>
  </div>

  <div class="report-container">

    <!-- CABECERA -->
    <div class="header-report">
      <div>
        <div class="hotel-brand">PLATINIUM <span>HOTEL</span></div>
        <div style="font-size:12px; color:#94a3b8; margin-top:2px;">Sistema de Gestión Hotelera</div>
      </div>
      <div class="report-meta">
        <div class="badge-id mb-1">MES: <?= strtoupper($nombreMes) ?> <?= $anio ?></div>
        <div style="font-size:13px; font-weight:700; color:#334155; margin-top:4px;">REPORTE MENSUAL YAPE</div>
        <div style="font-size:11px; color:#94a3b8;">Para: <b>Sr. Mendoza</b></div>
      </div>
    </div>

    <!-- INFO -->
    <div class="info-row">
      <div class="info-item">
        <label>Período</label>
        <span><?= mb_strtoupper($nombreMes, 'UTF-8') ?> - <?= $anio ?></span>
      </div>
      <div class="info-item">
        <label>Generado por</label>
        <span><?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Sistema') ?></span>
      </div>
      <div class="info-item">
        <label>Total Gastado Mes</label>
        <span style="color:#dc2626; font-weight:800;">S/ <?= number_format($globales['total_gastado'], 2) ?></span>
      </div>
      <div class="info-item">
        <label>Balance Vueltos Mes</label>
        <span style="<?= $globales['vuelto'] >= 0 ? 'color:#16a34a' : 'color:#dc2626' ?>; font-weight:800;">
          <?= $globales['vuelto'] < 0 ? '- ' : '' ?>S/ <?= number_format(abs($globales['vuelto']), 2) ?>
        </span>
      </div>
    </div>

    <!-- TABLA DE GASTOS -->
    <h2 class="title mb-3">Resumen Consolidado</h2>
    <table class="expense-table">
      <thead>
        <tr>
          <th>TURNO</th>
          <th>FECHA</th>
          <th>YAPE REC.</th>
          <?php foreach ($categoriasConfig as $cat): ?>
            <th><?= htmlspecialchars($cat) ?></th>
          <?php endforeach; ?>
          <th>TOTAL GASTO</th>
          <th>VUELTOS</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($grupos)): ?>
          <tr><td colspan="<?= 5 + count($categoriasConfig) ?>" class="text-center text-muted py-3">No hay gastos en este mes.</td></tr>
        <?php else: ?>
          <?php foreach ($grupos as $g): ?>
            <?php foreach ($g['turnos'] as $y): ?>
              <tr class="row-turno">
                <td class="col-centro fw-bold" style="color: <?= $y['turno'] == 'MAÑANA' ? '#0284c7' : '#334155' ?>;"><?= $y['turno'] ?></td>
                <td class="col-centro" style="color:#64748b; font-weight:600;"><?= fmtDate($y['fecha']) ?></td>
                <td class="col-monto fw-bold text-primary"><?= (float)$y['yape_recibido'] > 0 ? number_format($y['yape_recibido'], 2) : '' ?></td>
                
                <?php foreach ($categoriasConfig as $cat): ?>
                  <?php $m = (float)($y['detalles_montos'][$cat] ?? 0); ?>
                  <td class="col-monto">
                    <?= $m > 0 ? number_format($m, 2) : '<span style="color:#cbd5e1">-</span>' ?>
                  </td>
                <?php endforeach; ?>
                
                <td class="col-monto fw-bold" style="color:#dc2626;"><?= number_format($y['total_gastado'], 2) ?></td>
                <td class="col-monto fw-bold" style="color:#16a34a;">
                    <?= (float)$y['vuelto'] < 0 ? '- ' . number_format(abs($y['vuelto']), 2) : number_format($y['vuelto'], 2) ?>
                </td>
              </tr>
            <?php endforeach; ?>
            
            <!-- Fila Totales Día -->
            <tr class="row-total-dia">
              <td class="col-centro">TOTAL</td>
              <td class="col-centro"></td>
              <td class="col-monto text-primary"><?= $g['totales']['yape_recibido'] > 0 ? number_format($g['totales']['yape_recibido'], 2) : '' ?></td>
              
              <?php foreach ($categoriasConfig as $cat): ?>
                <?php $m = $g['totales']['rubros'][$cat]; ?>
                <td class="col-monto text-secondary">
                  <?= $m > 0 ? number_format($m, 2) : '<span style="color:#a1a1aa">-</span>' ?>
                </td>
              <?php endforeach; ?>
              
              <td class="col-monto text-danger"><?= number_format($g['totales']['total_gastado'], 2) ?></td>
              <td class="col-monto text-success">
                  <?= $g['totales']['vuelto'] < 0 ? '- ' . number_format(abs($g['totales']['vuelto']), 2) : number_format($g['totales']['vuelto'], 2) ?>
              </td>
            </tr>
          <?php endforeach; ?>
          
          <!-- Fila Totales Global Mes -->
          <tr class="row-total-mes">
            <td class="col-centro" colspan="2" style="letter-spacing:1px;">TOTAL MENSUAL</td>
            <td class="col-monto text-primary"><?= number_format($globales['yape_recibido'], 2) ?></td>
            
            <?php foreach ($categoriasConfig as $cat): ?>
              <?php $m = $globales['rubros'][$cat]; ?>
              <td class="col-monto">
                <?= $m > 0 ? number_format($m, 2) : '<span style="color:#94a3b8">-</span>' ?>
              </td>
            <?php endforeach; ?>
            
            <td class="col-monto text-danger"><?= number_format($globales['total_gastado'], 2) ?></td>
            <td class="col-monto text-success">
                <?= $globales['vuelto'] < 0 ? '- ' . number_format(abs($globales['vuelto']), 2) : number_format($globales['vuelto'], 2) ?>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- FIRMAS -->
    <div class="signatures">
      <div class="sig-box">
        <div class="sig-name">RENDICIÓN COMPROBADA</div>
        <div class="sig-role">Sistema / Tesorería</div>
      </div>
      <div class="sig-box">
        <div class="sig-name">SR. MENDOZA</div>
        <div class="sig-role">Conforme</div>
      </div>
    </div>

    <div class="footer-note">
      Reporte Mensual generado el <?= $generadoEn ?> — Sistema Platinium Hotel
    </div>
  </div>

</body>
</html>
