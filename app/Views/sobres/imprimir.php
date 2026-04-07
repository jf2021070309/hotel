<?php
/**
 * app/Views/sobres/imprimir.php
 * Reporte Consolidado de Sobres (Tabla Verde) para el señor Alex.
 */
$base = '../../../';
require_once $base . 'auth/middleware.php';
protegerPorRol('cajera', 'sobres');

require_once $base . 'config/db.php';
require_once $base . 'app/Models/FlujoModel.php';

$fecha = $_GET['fecha'] ?? date('Y-m-d');
$model = new FlujoModel($pdo);
$reporte = $model->getReporteAlexDiario($fecha);

// Formatear fecha para mostrar
$fechaFmt = date('d/m/Y', strtotime($fecha));

// Calcular totales finales (Soles)
$totalManana = $reporte['MAÑANA']['PEN'];
$totalTarde  = $reporte['TARDE']['PEN'];
$totalGeneral = $totalManana + $totalTarde;

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reporte de Sobres — <?= $fechaFmt ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
    
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f0f2f5;
      color: #333;
    }

    .report-container {
      max-width: 800px;
      margin: 40px auto;
      background: #fff;
      padding: 50px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      border-radius: 12px;
    }

    .header-report {
      border-bottom: 2px solid #e9ecef;
      padding-bottom: 20px;
      margin-bottom: 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .hotel-brand {
      font-weight: 800;
      font-size: 24px;
      color: #1a1a1a;
      letter-spacing: -1px;
    }

    .report-title {
      text-align: center;
      margin-bottom: 40px;
    }

    .report-title h2 {
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 5px;
    }

    .green-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      border: 2px solid #2e7d32;
      border-radius: 8px;
      overflow: hidden;
      margin-bottom: 30px;
    }

    .green-table thead tr {
      background-color: #2e7d32;
      color: white;
    }

    .green-table th {
      padding: 15px;
      font-weight: 700;
      text-transform: uppercase;
      font-size: 14px;
      letter-spacing: 0.5px;
      text-align: center;
    }

    .green-table td {
      padding: 15px;
      border-bottom: 1px solid #e8f5e9;
      font-size: 15px;
      vertical-align: middle;
    }

    .green-table tr:last-child td {
      border-bottom: none;
    }

    .bg-light-green {
      background-color: #f1f8e9;
    }

    .turn-badge {
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
    }

    .badge-manana { background-color: #e3f2fd; color: #0d47a1; }
    .badge-tarde { background-color: #fff3e0; color: #e65100; }

    .amount-cell {
      font-weight: 700;
      font-size: 18px;
      text-align: right;
      color: #2e7d32;
    }

    .egresos-detalle {
      font-size: 12px;
      color: #666;
      font-style: italic;
      max-width: 300px;
    }

    .total-row {
      background-color: #e8f5e9;
      font-weight: 800;
    }

    .signatures {
      margin-top: 80px;
      display: flex;
      justify-content: space-around;
      text-align: center;
    }

    .sig-box {
      width: 200px;
      border-top: 1px solid #333;
      padding-top: 10px;
    }

    .sig-name {
      font-weight: 700;
      font-size: 13px;
      margin-bottom: 2px;
    }

    .sig-role {
      font-size: 11px;
      color: #777;
    }

    @media print {
      body { background-color: #fff; }
      .report-container { 
        box-shadow: none; 
        margin: 0; 
        padding: 0; 
        max-width: 100%;
      }
      .no-print { display: none !important; }
      .green-table { border: 1px solid #2e7d32; }
    }
  </style>
</head>
<body>

  <div class="container no-print mt-3 text-end">
    <button class="btn btn-primary shadow-sm" onclick="window.print()">
      <i class="bi bi-printer me-2"></i>Imprimir Reporte A4
    </button>
  </div>

  <div class="report-container">
    <div class="header-report">
      <div class="hotel-brand">PLATINIUM <span class="text-primary">HOTEL</span></div>
      <div class="text-end">
        <div class="fw-bold fs-5">REPORTE ALEX</div>
        <div class="text-muted small">Liquidación Diaria de Efectivo</div>
      </div>
    </div>

    <div class="report-title">
      <h2>Resumen de Sobres Físicos</h2>
      <div class="text-muted"><i class="bi bi-calendar3 me-1"></i>Fecha: <b><?= $fechaFmt ?></b></div>
    </div>

    <table class="green-table">
      <thead>
        <tr>
          <th>Origen (Turno)</th>
          <th>Detalle de Extracciones</th>
          <th class="text-end">Soles (PEN)</th>
          <th class="text-end">Dólares (USD)</th>
          <th class="text-end">Pesos (CLP)</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <div class="turn-badge badge-manana mb-1">TURNO MAÑANA</div>
          </td>
          <td>
            <div class="egresos-detalle">
              <?= $reporte['MAÑANA']['egresos_detalle'] ?: '<span class="text-muted">Ninguna extracción</span>' ?>
            </div>
          </td>
          <td class="amount-cell">
            S/ <?= number_format($reporte['MAÑANA']['PEN'], 2) ?>
          </td>
          <td class="amount-cell text-primary">
            $ <?= number_format($reporte['MAÑANA']['USD'], 2) ?>
          </td>
          <td class="amount-cell text-success">
            $ <?= number_format($reporte['MAÑANA']['CLP'], 0) ?>
          </td>
        </tr>
        <tr class="bg-light-green">
          <td>
            <div class="turn-badge badge-tarde mb-1">TURNO TARDE</div>
          </td>
          <td>
            <div class="egresos-detalle">
              <?= $reporte['TARDE']['egresos_detalle'] ?: '<span class="text-muted">Ninguna extracción</span>' ?>
            </div>
          </td>
          <td class="amount-cell">
            S/ <?= number_format($reporte['TARDE']['PEN'], 2) ?>
          </td>
          <td class="amount-cell text-primary">
            $ <?= number_format($reporte['TARDE']['USD'], 2) ?>
          </td>
          <td class="amount-cell text-success">
            $ <?= number_format($reporte['TARDE']['CLP'], 0) ?>
          </td>
        </tr>
        <tr class="total-row">
          <td colspan="2" class="text-end py-3">TOTALES A ENTREGAR:</td>
          <td class="amount-cell py-3">
            S/ <?= number_format($reporte['MAÑANA']['PEN'] + $reporte['TARDE']['PEN'], 2) ?>
          </td>
          <td class="amount-cell text-primary py-3">
            $ <?= number_format($reporte['MAÑANA']['USD'] + $reporte['TARDE']['USD'], 2) ?>
          </td>
          <td class="amount-cell text-success py-3">
            $ <?= number_format($reporte['MAÑANA']['CLP'] + $reporte['TARDE']['CLP'], 0) ?>
          </td>
        </tr>
      </tbody>
    </table>

    <div class="alert alert-secondary border-0 small">
      <i class="bi bi-info-circle-fill me-2"></i>
      Este reporte detalla únicamente el <b>efectivo físico</b> contenido en los sobres. Los pagos electrónicos (Yape, POS, Transferencias) no se incluyen en esta liquidación y son auditados por separado.
    </div>

    <?php if ($reporte['MAÑANA']['USD'] + $reporte['TARDE']['USD'] > 0 || $reporte['MAÑANA']['CLP'] + $reporte['TARDE']['CLP'] > 0): ?>
    <div class="mt-4">
      <h6 class="fw-bold mb-2">Otras Monedas en Sobre:</h6>
      <ul class="list-group list-group-flush small">
        <?php if ($reporte['MAÑANA']['USD'] + $reporte['TARDE']['USD'] > 0): ?>
        <li class="list-group-item d-flex justify-content-between">
          <span>Dólares Americanos (USD)</span>
          <span class="fw-bold">$ <?= number_format($reporte['MAÑANA']['USD'] + $reporte['TARDE']['USD'], 2) ?></span>
        </li>
        <?php endif; ?>
        <?php if ($reporte['MAÑANA']['CLP'] + $reporte['TARDE']['CLP'] > 0): ?>
        <li class="list-group-item d-flex justify-content-between">
          <span>Pesos Chilenos (CLP)</span>
          <span class="fw-bold">$ <?= number_format($reporte['MAÑANA']['CLP'] + $reporte['TARDE']['CLP'], 0, '.', ',') ?></span>
        </li>
        <?php endif; ?>
      </ul>
    </div>
    <?php endif; ?>

    <div class="signatures">
      <div class="sig-box">
        <div class="sig-name">REPEPCIÓN</div>
        <div class="sig-role">Entregué Conforme</div>
      </div>
      <div class="sig-box">
        <div class="sig-name">ALEX</div>
        <div class="sig-role">Recibí Conforme</div>
      </div>
    </div>

    <div class="mt-5 text-center small text-muted fst-italic">
      Generado el <?= date('d/m/Y H:i:s') ?> por el Sistema Platinium
    </div>
  </div>

</body>
</html>
