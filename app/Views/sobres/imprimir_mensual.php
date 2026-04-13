<?php
/**
 * app/Views/sobres/imprimir_mensual.php
 * Reporte Consolidado Mensual de Sobres para el señor Alex.
 */
$base = '../../../';
require_once $base . 'auth/middleware.php';
protegerPorRol('cajera', 'sobres');

require_once $base . 'config/db.php';
require_once $base . 'app/Models/FlujoModel.php';

$mes = (int)($_GET['mes'] ?? date('n'));
$anio = (int)($_GET['anio'] ?? date('Y'));

$model = new FlujoModel($pdo);
$reporte = $model->getReporteAlexMensual($mes, $anio);

$meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$tituloMes = $meses[$mes] . " " . $anio;

// Totales generales del mes
$totalPEN = 0;
$totalUSD = 0;
$totalCLP = 0;

foreach ($reporte as $dia) {
    $totalPEN += $dia['TOTAL']['PEN'];
    $totalUSD += $dia['TOTAL']['USD'];
    $totalCLP += $dia['TOTAL']['CLP'];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reporte Mensual Sobres — <?= $tituloMes ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
    
    body { font-family: 'Inter', sans-serif; background-color: #f0f2f5; color: #333; }
    .report-container { max-width: 900px; margin: 40px auto; background: #fff; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-radius: 12px; }
    .header-report { border-bottom: 2px solid #e9ecef; padding-bottom: 15px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
    .hotel-brand { font-weight: 800; font-size: 22px; color: #1a1a1a; letter-spacing: -1px; }
    .report-title { text-align: center; margin-bottom: 30px; }
    .report-title h2 { font-weight: 700; color: #2c3e50; margin-bottom: 5px; }

    .green-table { width: 100%; border-collapse: separate; border-spacing: 0; border: 2px solid #2e7d32; border-radius: 8px; overflow: hidden; margin-bottom: 30px; }
    .green-table thead tr { background-color: #2e7d32; color: white; }
    .green-table th { padding: 12px; font-weight: 700; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; text-align: center; }
    .green-table td { padding: 10px; border-bottom: 1px solid #e8f5e9; font-size: 13px; vertical-align: middle; text-align: center; }
    .green-table tr:last-child td { border-bottom: none; }
    
    .amount-cell { font-weight: 700; text-align: right !important; }
    .total-row { background-color: #e8f5e9; font-weight: 800; }
    .signatures { margin-top: 60px; display: flex; justify-content: space-around; text-align: center; }
    .sig-box { width: 220px; border-top: 1px solid #333; padding-top: 8px; }
    .sig-name { font-weight: 700; font-size: 12px; }
    .sig-role { font-size: 10px; color: #777; }

    @media print {
      body { background-color: #fff; }
      .report-container { box-shadow: none; margin: 0; padding: 20px; max-width: 100%; }
      .no-print { display: none !important; }
      .green-table { border: 1px solid #2e7d32; }
    }
  </style>
</head>
<body>

  <div class="container no-print mt-3 d-flex justify-content-between">
    <a href="./index.php" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left me-2"></i>Volver
    </a>
    <button class="btn btn-primary btn-sm" onclick="window.print()">
      <i class="bi bi-printer me-2"></i>Imprimir Mensual A4
    </button>
  </div>

  <div class="report-container">
    <div class="header-report">
      <div class="hotel-brand">PLATINIUM <span class="text-primary">HOTEL</span></div>
      <div class="text-end">
        <div class="fw-bold fs-5">REPORTE MENSUAL ALEX</div>
        <div class="text-muted small">Consolidado de Efectivo Físico</div>
      </div>
    </div>

    <div class="report-title">
      <h2>Resumen Mensual de Sobres</h2>
      <div class="text-muted"><i class="bi bi-calendar3 me-1"></i>Periodo: <b><?= $tituloMes ?></b></div>
    </div>

    <table class="green-table">
      <thead>
        <tr>
          <th rowspan="2">Fecha</th>
          <th colspan="3">Turno Mañana</th>
          <th colspan="3">Turno Tarde</th>
          <th colspan="3" class="bg-success bg-opacity-10 text-dark">Total Día</th>
        </tr>
        <tr>
          <th>PEN</th><th>USD</th><th>CLP</th>
          <th>PEN</th><th>USD</th><th>CLP</th>
          <th class="bg-success bg-opacity-10 text-dark">PEN</th>
          <th class="bg-success bg-opacity-10 text-dark">USD</th>
          <th class="bg-success bg-opacity-10 text-dark">CLP</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reporte as $fecha => $dia): ?>
        <tr>
          <td class="fw-bold"><?= date('d/m/Y', strtotime($fecha)) ?></td>
          
          <td class="amount-cell"><?= number_format($dia['MAÑANA']['PEN'], 1) ?></td>
          <td class="amount-cell text-primary"><?= number_format($dia['MAÑANA']['USD'], 1) ?></td>
          <td class="amount-cell text-muted"><?= number_format($dia['MAÑANA']['CLP'], 0) ?></td>

          <td class="amount-cell"><?= number_format($dia['TARDE']['PEN'], 1) ?></td>
          <td class="amount-cell text-primary"><?= number_format($dia['TARDE']['USD'], 1) ?></td>
          <td class="amount-cell text-muted"><?= number_format($dia['TARDE']['CLP'], 0) ?></td>

          <td class="amount-cell total-row">S/ <?= number_format($dia['TOTAL']['PEN'], 1) ?></td>
          <td class="amount-cell total-row text-primary">$ <?= number_format($dia['TOTAL']['USD'], 1) ?></td>
          <td class="amount-cell total-row text-success">$ <?= number_format($dia['TOTAL']['CLP'], 0) ?></td>
        </tr>
        <?php endforeach; ?>
        
        <?php if (empty($reporte)): ?>
        <tr>
          <td colspan="10" class="py-5 text-muted">No se encontraron movimientos de sobres en este periodo.</td>
        </tr>
        <?php else: ?>
        <tr class="total-row bg-dark text-white">
          <td class="py-3">TOTAL MES</td>
          <td colspan="3" class="text-center">Mañana: S/ <?= number_format(array_sum(array_column(array_column($reporte, 'MAÑANA'), 'PEN')), 2) ?></td>
          <td colspan="3" class="text-center">Tarde: S/ <?= number_format(array_sum(array_column(array_column($reporte, 'TARDE'), 'PEN')), 2) ?></td>
          <td class="amount-cell py-3">S/ <?= number_format($totalPEN, 2) ?></td>
          <td class="amount-cell py-3">$ <?= number_format($totalUSD, 2) ?></td>
          <td class="amount-cell py-3">$ <?= number_format($totalCLP, 0) ?></td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <div class="row mt-4">
      <div class="col-6">
        <div class="card border-0 bg-light">
          <div class="card-body p-3">
            <h6 class="fw-bold small mb-2">Desglose por Moneda:</h6>
            <div class="d-flex justify-content-between mb-1 small">
              <span>Efectivo Soles (PEN):</span>
              <span class="fw-bold">S/ <?= number_format($totalPEN, 2) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-1 small">
              <span>Efectivo Dólares (USD):</span>
              <span class="fw-bold">$ <?= number_format($totalUSD, 2) ?></span>
            </div>
            <div class="d-flex justify-content-between small">
              <span>Efectivo Pesos (CLP):</span>
              <span class="fw-bold">$ <?= number_format($totalCLP, 0) ?></span>
            </div>
          </div>
        </div>
      </div>
      <div class="col-6">
        <div class="alert alert-secondary border-0 small h-100 mb-0">
          <i class="bi bi-info-circle-fill me-2"></i>
          Este documento es un consolidado mensual del efectivo físico entregado en sobres. Debe coincidir con los depósitos realizados.
        </div>
      </div>
    </div>

    <div class="signatures">
      <div class="sig-box">
        <div class="sig-name">RECEPCIÓN</div>
        <div class="sig-role">Entregué Conforme</div>
      </div>
      <div class="sig-box">
        <div class="sig-name">ALEX</div>
        <div class="sig-role">Recibí Conforme</div>
      </div>
    </div>

    <div class="mt-5 text-center small text-muted fst-italic">
      Generado el <?= date('d/m/Y H:i:s') ?> por el Sistema de Control Platinium
    </div>
  </div>

</body>
</html>
