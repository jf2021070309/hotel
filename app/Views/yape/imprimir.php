<?php
/**
 * app/Views/yape/imprimir.php
 * Reporte de Rendición de Gastos Yape — para enviar al Sr. Mendoza.
 */
$base = '../../../';
require_once $base . 'app/Middleware/auth.php';
protegerPorRol('cajera', 'yape');

require_once $base . 'config/db.php';
require_once $base . 'app/Models/YapeModel.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

$model  = new YapeModel($pdo);
$yape   = $model->getDetalle($id);
if (!$yape) { echo "Registro no encontrado."; exit; }

// Mapa de rubros a etiquetas legibles
$rubroLabels = [
    'MERCADO'          => 'MERCADO',
    'MOVILIDAD'        => 'MOVILIDAD',
    'CAFETERÍA/VEA'    => 'CAFETERÍA / VEA - GENOVESA',
    'CAFETERIA'        => 'CAFETERÍA / VEA - GENOVESA',  // compatibilidad v. anterior
    'LAVANDERÍA'       => 'LAVANDERÍA',
    'LAVANDERIA'       => 'LAVANDERÍA',                   // compatibilidad v. anterior
    'SERV. REPUESTOS'  => 'SERV. / REPUESTOS',
    'SERV_REPUESTOS'   => 'SERV. / REPUESTOS',            // compatibilidad v. anterior
    'OTROS'            => 'OTROS',
];

$fechaFmt   = date('d/m/Y', strtotime($yape['fecha']));
$generadoEn = date('d/m/Y H:i');
$vuelto     = (float)$yape['vuelto'];
$gastado    = (float)$yape['total_gastado'];
$recibido   = (float)$yape['yape_recibido'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Rendición Gastos Yape #<?= $id ?> — <?= $fechaFmt ?></title>
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
      max-width: 760px;
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

    /* Tabla gastos */
    .expense-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    .expense-table thead tr { background: #1e293b; color: #fff; }
    .expense-table th { padding: 11px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
    .expense-table td { padding: 11px 14px; font-size: 13px; border-bottom: 1px solid #f1f5f9; }
    .expense-table tbody tr:last-child td { border-bottom: none; }
    .expense-table tbody tr:nth-child(even) { background: #f8fafc; }
    .expense-table .col-monto { text-align: right; font-weight: 700; white-space: nowrap; }
    .expense-table .col-rubro { font-weight: 600; }
    .expense-table .col-doc   { color: #64748b; font-size: 12px; }
    .expense-table .col-obs   { color: #64748b; font-size: 12px; font-style: italic; }

    /* Resumen */
    .summary-box {
      border-radius: 12px;
      padding: 20px 24px;
      margin-bottom: 28px;
    }
    .summary-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 14px; }
    .summary-row.total { font-size: 17px; font-weight: 800; border-top: 2px solid #e2e8f0; padding-top: 12px; margin-top: 4px; }
    .summary-row label { color: #64748b; font-weight: 600; }
    .lbl-recibido { color: #2563eb !important; }
    .lbl-gastado  { color: #dc2626 !important; }
    .val-recibido { color: #1d4ed8; font-weight: 700; }
    .val-gastado  { color: #dc2626; font-weight: 700; }
    .val-vuelto   { font-size: 22px; font-weight: 800; }
    .val-vuelto.ok      { color: #16a34a; }
    .val-vuelto.danger  { color: #dc2626; }

    .obs-box { background: #fffbeb; border-left: 4px solid #f59e0b; border-radius: 4px; padding: 12px 16px; font-size: 13px; color: #78350f; margin-bottom: 28px; }

    /* Firmas */
    .signatures { display: flex; justify-content: space-around; text-align: center; margin-top: 60px; }
    .sig-box { width: 180px; border-top: 1px solid #333; padding-top: 8px; }
    .sig-name { font-weight: 700; font-size: 12px; }
    .sig-role { font-size: 11px; color: #888; }

    .footer-note { text-align: center; font-size: 11px; color: #aaa; margin-top: 36px; font-style: italic; }

    /* Print */
    @media print {
      body { background: #fff; }
      .report-container { box-shadow: none; margin: 0; padding: 20px; max-width: 100%; border-radius: 0; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>

  <div class="container no-print mt-3 mb-0 text-end" style="max-width:760px; margin: 0 auto;">
    <a href="form.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm me-2">
      <i class="bi bi-arrow-left"></i> Volver
    </a>
    <button class="btn btn-primary btn-sm shadow-sm" onclick="window.print()">
      <i class="bi bi-printer me-1"></i> Imprimir / Guardar PDF
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
        <div class="badge-id mb-1">RENDICIÓN #<?= $id ?></div>
        <div style="font-size:13px; font-weight:700; color:#334155; margin-top:4px;">GASTOS YAPE</div>
        <div style="font-size:11px; color:#94a3b8;">Para: <b>Sr. Mendoza</b></div>
      </div>
    </div>

    <!-- INFO -->
    <div class="info-row">
      <div class="info-item">
        <label>Fecha</label>
        <span><?= $fechaFmt ?></span>
      </div>
      <div class="info-item">
        <label>Turno</label>
        <span><?= htmlspecialchars($yape['turno']) ?></span>
      </div>
      <div class="info-item">
        <label>Operador / Cajera</label>
        <span><?= htmlspecialchars($yape['operador'] ?? '—') ?></span>
      </div>
      <div class="info-item">
        <label>Estado</label>
        <span style="<?= $yape['estado'] === 'cerrado' ? 'color:#16a34a' : 'color:#d97706' ?>; text-transform:uppercase; font-weight:800;">
          <?= $yape['estado'] === 'cerrado' ? '✓ Cerrado' : '⏳ Borrador' ?>
        </span>
      </div>
    </div>

    <!-- TABLA DE GASTOS -->
    <h2 class="title mb-3">Detalle de Compras Realizadas</h2>
    <table class="expense-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Rubro</th>
          <th>Doc.</th>
          <th>Observación</th>
          <th class="text-end">Monto (S/)</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($yape['detalles'])): ?>
          <tr><td colspan="5" class="text-center text-muted py-3">Sin gastos registrados.</td></tr>
        <?php else: ?>
          <?php foreach ($yape['detalles'] as $i => $d): ?>
            <tr>
              <td style="color:#94a3b8; font-size:12px;"><?= $i + 1 ?></td>
              <td class="col-rubro"><?= htmlspecialchars($rubroLabels[$d['rubro']] ?? $d['rubro']) ?></td>
              <td class="col-doc"><?= htmlspecialchars($d['documento'] ?? '—') ?></td>
              <td class="col-obs"><?= htmlspecialchars($d['observacion'] ?? '—') ?></td>
              <td class="col-monto">S/ <?= number_format((float)$d['monto'], 2) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <?php if (!empty($yape['observacion'])): ?>
    <div class="obs-box">
      <strong>Observación del turno:</strong> <?= htmlspecialchars($yape['observacion']) ?>
    </div>
    <?php endif; ?>

    <!-- RESUMEN -->
    <div class="summary-box" style="background:#f8fafc; border:1px solid #e2e8f0;">
      <div class="summary-row">
        <label class="lbl-recibido">Yape Recibido de Sr. Mendoza</label>
        <span class="val-recibido">+ S/ <?= number_format($recibido, 2) ?></span>
      </div>
      <div class="summary-row">
        <label class="lbl-gastado">Total Gastado en Compras</label>
        <span class="val-gastado">− S/ <?= number_format($gastado, 2) ?></span>
      </div>
      <div class="summary-row total">
        <label style="color:#1e293b;"><?= $vuelto >= 0 ? 'Vuelto a Efectivo' : 'FALTANTE ⚠️' ?></label>
        <span class="val-vuelto <?= $vuelto >= 0 ? 'ok' : 'danger' ?>">
          S/ <?= number_format(abs($vuelto), 2) ?>
        </span>
      </div>
    </div>

    <!-- FIRMAS -->
    <div class="signatures">
      <div class="sig-box">
        <div class="sig-name">RECEPCIÓN</div>
        <div class="sig-role">Quien rindió cuentas</div>
      </div>
      <div class="sig-box">
        <div class="sig-name">SR. MENDOZA</div>
        <div class="sig-role">Conforme</div>
      </div>
    </div>

    <div class="footer-note">
      Generado el <?= $generadoEn ?> — Sistema Platinium Hotel
    </div>
  </div>

</body>
</html>
