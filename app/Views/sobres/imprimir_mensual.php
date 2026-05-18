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
$dataAlex = $model->getReporteAlexMensual($mes, $anio);
$reporte = $dataAlex['dias'];
$totalesMes = $dataAlex['totales'];

$meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$tituloMes = $meses[$mes] . " " . $anio;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reporte Mensual Sobres — <?= $tituloMes ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
    
    body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; color: #212529; }
    .report-container { max-width: 960px; margin: 40px auto; background: #fff; padding: 45px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border-radius: 16px; border: 1px solid rgba(0,0,0,0.05); }
    .header-report { border-bottom: 3px solid #1b5e20; padding-bottom: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
    .hotel-brand { font-weight: 900; font-size: 26px; color: #1b5e20; letter-spacing: -1px; }
    .report-title h2 { font-weight: 800; color: #111; margin-bottom: 5px; font-size: 24px; letter-spacing: -0.5px; }

    /* Summary Cards */
    .summary-card { border-radius: 12px; padding: 16px; text-align: center; border: 1px solid rgba(0,0,0,0.08); }
    .summary-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; margin-bottom: 4px; }
    .summary-val { font-size: 20px; font-weight: 800; color: #111; }
    
    .green-table { width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid #1b5e20; border-radius: 10px; overflow: hidden; margin-bottom: 35px; }
    .green-table thead tr { background-color: #1b5e20; color: white; }
    .green-table th { padding: 12px; font-weight: 700; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; text-align: center; border-bottom: 1px solid #1b5e20; }
    .green-table td { padding: 10px 12px; border-bottom: 1px solid #e9ecef; font-size: 12.5px; vertical-align: middle; text-align: center; font-weight: 500; }
    .green-table tr:hover td { background-color: #f8fbf8; }
    .green-table tr:last-child td { border-bottom: none; }
    
    .amount-cell { font-weight: 700; text-align: right !important; font-family: monospace; font-size: 13.5px; }
    .total-col { background-color: #e8f5e9; font-weight: 800; color: #1b5e20; }
    
    .signatures { margin-top: 70px; display: flex; justify-content: space-around; text-align: center; }
    .sig-box { width: 240px; border-top: 2px solid #212529; padding-top: 10px; }
    .sig-name { font-weight: 800; font-size: 13px; letter-spacing: 0.5px; }
    .sig-role { font-size: 11px; color: #6c757d; }

    @media print {
      body { background-color: #fff; margin: 0; padding: 0; }
      .report-container { box-shadow: none; margin: 0; padding: 15px; max-width: 100%; border: none; }
      .no-print { display: none !important; }
      .green-table { border: 1px solid #1b5e20; }
    }
  </style>
</head>
<body>

  <div class="container no-print mt-4 mb-2 d-flex justify-content-between align-items-center" style="max-width: 960px;">
    <a href="./index.php" class="btn btn-outline-dark btn-sm px-3 fw-bold">
      <i class="bi bi-arrow-left me-1"></i> Volver al Sistema
    </a>
    <button class="btn btn-success btn-sm px-4 fw-bold shadow-sm" onclick="window.print()">
      <i class="bi bi-printer-fill me-1"></i> Imprimir Documento Oficial
    </button>
  </div>

  <div class="report-container">
    <div class="header-report">
      <div>
        <div class="hotel-brand">PLATINIUM <span style="color: #d4af37;">HOTEL</span></div>
        <div class="text-muted small fw-semibold">Control Oficial de Tesorería</div>
      </div>
      <div class="text-end">
        <div class="fw-bold fs-5 text-dark" style="letter-spacing: -0.5px;">REPORTE MENSUAL ALEX</div>
        <div class="badge bg-success-subtle text-success fw-bold px-3 py-1 border border-success-subtle mt-1" style="font-size: 12px;">Auditoría de Sobres</div>
      </div>
    </div>

    <div class="report-title mb-4">
      <h2>Resumen Consolidado de Efectivo en Sobres</h2>
      <div class="text-muted fs-6"><i class="bi bi-calendar-check-fill me-1 text-success"></i> Periodo Fiscal: <b class="text-dark"><?= $tituloMes ?></b></div>
    </div>

    <!-- TARJETAS DE MANDO SUPERIORES -->
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="summary-card bg-light border-0" style="border-left: 4px solid #0d6efd !important;">
          <div class="summary-title text-primary">Ingresos en Efectivo (Mes)</div>
          <div class="summary-val text-primary mt-1">S/ <?= number_format($totalesMes['ingresos']['PEN'], 2) ?></div>
          <div class="small text-muted mt-1 fw-semibold" style="font-size: 11px;">
            $ <?= number_format($totalesMes['ingresos']['USD'], 2) ?> USD | $ <?= number_format($totalesMes['ingresos']['CLP'], 0) ?> CLP
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="summary-card bg-light border-0" style="border-left: 4px solid #dc3545 !important;">
          <div class="summary-title text-danger">Retiros y Extracciones (Mes)</div>
          <div class="summary-val text-danger mt-1">- S/ <?= number_format($totalesMes['egresos']['PEN'], 2) ?></div>
          <div class="small text-muted mt-1 fw-semibold" style="font-size: 11px;">
            - $ <?= number_format($totalesMes['egresos']['USD'], 2) ?> USD | - $ <?= number_format($totalesMes['egresos']['CLP'], 0) ?> CLP
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="summary-card text-white" style="background: linear-gradient(135deg, #1b5e20, #2e7d32); border: none;">
          <div class="summary-title text-light opacity-75">Fondo Neto Acumulado (Caja)</div>
          <div class="summary-val text-warning mt-1 fs-4">S/ <?= number_format($totalesMes['neto']['PEN'], 2) ?></div>
          <div class="small text-light mt-1 fw-semibold opacity-75" style="font-size: 11px;">
            $ <?= number_format($totalesMes['neto']['USD'], 2) ?> USD | $ <?= number_format($totalesMes['neto']['CLP'], 0) ?> CLP
          </div>
        </div>
      </div>
    </div>

    <!-- TABLA DE DETALLE DIARIO -->
    <table class="green-table">
      <thead>
        <tr>
          <th rowspan="2" style="width: 13%; vertical-align: middle;">Fecha</th>
          <th colspan="3" class="border-end">Turno Mañana</th>
          <th colspan="3" class="border-end">Turno Tarde</th>
          <th colspan="3" style="background-color: #144517;">Consolidado Diario</th>
        </tr>
        <tr style="font-size: 10px; background-color: #256b2b;">
          <th>PEN</th><th>USD</th><th class="border-end">CLP</th>
          <th>PEN</th><th>USD</th><th class="border-end">CLP</th>
          <th style="background-color: #1e5922;">PEN</th>
          <th style="background-color: #1e5922;">USD</th>
          <th style="background-color: #1e5922;">CLP</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reporte as $fecha => $dia): ?>
        <tr>
          <td class="fw-bold text-dark"><?= date('d/m/Y', strtotime($fecha)) ?></td>
          
          <td class="amount-cell"><?= number_format($dia['MAÑANA']['PEN'], 2) ?></td>
          <td class="amount-cell text-primary"><?= number_format($dia['MAÑANA']['USD'], 2) ?></td>
          <td class="amount-cell text-muted border-end"><?= number_format($dia['MAÑANA']['CLP'], 0) ?></td>

          <td class="amount-cell"><?= number_format($dia['TARDE']['PEN'], 2) ?></td>
          <td class="amount-cell text-primary"><?= number_format($dia['TARDE']['USD'], 2) ?></td>
          <td class="amount-cell text-muted border-end"><?= number_format($dia['TARDE']['CLP'], 0) ?></td>

          <td class="amount-cell total-col fw-bold">S/ <?= number_format($dia['TOTAL']['PEN'], 2) ?></td>
          <td class="amount-cell total-col text-primary fw-bold">$ <?= number_format($dia['TOTAL']['USD'], 2) ?></td>
          <td class="amount-cell total-col text-success fw-bold">$ <?= number_format($dia['TOTAL']['CLP'], 0) ?></td>
        </tr>
        <?php endforeach; ?>
        
        <?php if (empty($reporte)): ?>
        <tr>
          <td colspan="10" class="py-5 text-muted text-center fw-semibold">No se encontraron registros en este periodo fiscal.</td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <div class="row mt-4 align-items-center">
      <div class="col-md-6 mb-3 mb-md-0">
        <div class="card border-0 bg-light shadow-sm" style="border-radius: 12px;">
          <div class="card-body p-4">
            <h6 class="fw-bold mb-3 text-dark fs-6"><i class="bi bi-shield-check text-success me-2"></i>Auditoría de Saldos Netos:</h6>
            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom border-secondary-subtle">
              <span class="text-muted fw-semibold">Efectivo Soles (PEN):</span>
              <span class="fw-bold fs-6 text-dark">S/ <?= number_format($totalesMes['neto']['PEN'], 2) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom border-secondary-subtle">
              <span class="text-muted fw-semibold">Efectivo Dólares (USD):</span>
              <span class="fw-bold fs-6 text-primary">$ <?= number_format($totalesMes['neto']['USD'], 2) ?></span>
            </div>
            <div class="d-flex justify-content-between">
              <span class="text-muted fw-semibold">Efectivo Pesos (CLP):</span>
              <span class="fw-bold fs-6 text-success">$ <?= number_format($totalesMes['neto']['CLP'], 0) ?></span>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="alert alert-success border-0 small h-100 p-4 mb-0 d-flex align-items-center shadow-sm" style="border-radius: 12px; background-color: #e8f5e9; color: #1b5e20;">
          <i class="bi bi-info-circle-fill fs-3 me-3 flex-shrink-0"></i>
          <div style="font-size: 12.5px; line-height: 1.5;">
            <strong>Validación Oficial:</strong> Este documento representa la conciliación auditada del efectivo físico en sobres de tesorería, descontando las extracciones autorizadas del mes.
          </div>
        </div>
      </div>
    </div>

    <div class="signatures">
      <div class="sig-box">
        <div class="sig-name">DEPARTAMENTO DE RECEPCIÓN</div>
        <div class="sig-role">Entrega y Cierre Conforme</div>
      </div>
      <div class="sig-box">
        <div class="sig-name">SEÑOR ALEX</div>
        <div class="sig-role">Revisión y Conformidad</div>
      </div>
    </div>

    <div class="mt-5 text-center small text-muted fst-italic pt-3 border-top border-secondary-subtle" style="font-size: 11px;">
      Documento Generado el <?= date('d/m/Y H:i:s') ?> — Sistema de Gestión Platinium Hotel
    </div>
  </div>

</body>
</html>
