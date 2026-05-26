<?php
/**
 * app/Views/flujo/reporte_sobre.php
 */
$base = '../../../';
require_once $base . 'app/Middleware/auth.php';
require_once $base . 'config/db.php';
require_once $base . 'app/Models/FlujoModel.php';

protegerPorRol('cajera', 'flujo');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ID de flujo inválido.");
}

$model = new FlujoModel($pdo);
$flujo = $model->getDetalle($id);

if (!$flujo) {
    die("Flujo no encontrado.");
}

// Variables de cálculo para el reporte
$totalIngresos = 0;
$totalEgresos = 0;

$ingresoEfectivo = 0;
$ingresoPosYape = 0;
$ingresoYape = 0;

$egresosMonto = 0;
$efectivoEnSobre = 0;

// Aquí hacemos el sumario en soles o en original si quieren todo en la moneda original
// Para mantener el reporte ordenado asumiremos todo convertido a la moneda base (S/) como muestra el requerimiento.
$tc = $flujo['tc'];

// Función de conversión
$convertir = function($monto, $moneda) use ($tc) {
    if ($moneda === 'USD') return $monto * ($tc['USD'] ?? 3.7);
    if ($moneda === 'CLP') return $monto * ($tc['CLP'] ?? 0.0039);
    return $monto;
};

// Procesar Ingresos
$desgloseIngresos = [];
foreach ($flujo['ingresos'] as $mov) {
    $montoSol = $convertir($mov['monto'], $mov['moneda']);
    $totalIngresos += $montoSol;
    
    $esEfectivo = ($mov['medio_pago'] === 'EFECTIVO' || $mov['medio_pago'] === 'SOLES EFECTIVO' || $mov['medio_pago'] === 'DOLARES EFECTIVO' || $mov['medio_pago'] === 'PESOS EFECTIVO');
    $esYape = (strpos(strtoupper($mov['medio_pago']), 'YAPE') !== false || strpos(strtoupper($mov['categoria']), 'YAPE') !== false);
    
    if ($esEfectivo) {
        $ingresoEfectivo += $montoSol;
    } else {
        $ingresoPosYape += $montoSol;
    }
    
    if ($esYape) {
        $ingresoYape += $montoSol;
    }

    $desgloseIngresos[] = [
        'categoria' => $mov['categoria'],
        'observacion' => $mov['observacion'] ?? '',
        'monto' => $montoSol,
        'medio' => $mov['medio_pago']
    ];
}

// Procesar Egresos
$desgloseEgresos = [];
foreach ($flujo['egresos'] as $mov) {
    $montoSol = $convertir($mov['monto'], $mov['moneda']);
    $totalEgresos += $montoSol;
    
    $desgloseEgresos[] = [
        'categoria' => $mov['categoria'],
        'observacion' => $mov['observacion'] ?? '',
        'monto' => $montoSol,
        'medio' => $mov['medio_pago']
    ];
}

// Efectivo a entregar del turno (Solo Ingresos EFECTIVO)
$efectivoEnSobre = $ingresoEfectivo;

// Acumulado Mensual Neto
$parts = explode('-', $flujo['fecha']);
$anio = (int)($parts[0] ?? date('Y'));
$mes  = (int)($parts[1] ?? date('n'));
$resumenMensual = $model->getReporteAlexMensual($mes, $anio);
$mensualSolesNeto = 0;
$mensualDolaresNeto = 0;
$mensualPesosNeto = 0;
foreach ($resumenMensual as $diaItem) {
    if (isset($diaItem['TOTAL'])) {
        $mensualSolesNeto += (float)($diaItem['TOTAL']['PEN'] ?? 0);
        $mensualDolaresNeto += (float)($diaItem['TOTAL']['USD'] ?? 0);
        $mensualPesosNeto += (float)($diaItem['TOTAL']['CLP'] ?? 0);
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Turno #<?= $id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Arial', sans-serif; }
        .a4-container {
            background-color: #fff;
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            padding: 20mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
        }
        .report-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .report-title { font-size: 24px; font-weight: bold; text-transform: uppercase; margin: 0; }
        .report-meta { display: flex; justify-content: space-between; margin-top: 15px; font-size: 14px; }
        .section-title { font-size: 16px; font-weight: bold; background: #eee; padding: 5px 10px; margin-top: 20px; margin-bottom: 15px; }
        .line-item { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 14px; }
        .line-item .desc { flex: 1; padding-right: 15px; }
        .total-line { display: flex; justify-content: space-between; margin-top: 10px; font-size: 15px; font-weight: bold; border-top: 1px dashed #000; padding-top: 5px; }
        
        .caja-final { background: #f0f8ff; border: 2px solid #0056b3; padding: 15px; margin-top: 30px; border-radius: 8px; }
        .caja-final .monto-grande { font-size: 24px; font-weight: bold; color: #0056b3; }
        
        .firmas-area { display: flex; justify-content: space-around; margin-top: 80px; }
        .firma-box { text-align: center; width: 250px; }
        .firma-linea { border-top: 1px solid #000; margin-top: 50px; padding-top: 5px; font-weight: bold; }
        .firma-label { font-size: 12px; color: #555; }

        @media print {
            body { background-color: #fff; margin: 0; padding: 0; }
            .a4-container { width: 100%; min-height: auto; margin: 0; padding: 0; box-shadow: none; border: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="text-center mt-3 no-print">
    <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-2"></i>Imprimir Reporte (A4)</button>
    <button class="btn btn-outline-secondary" onclick="window.close()">Cerrar</button>
</div>

<div class="a4-container">
    <div class="report-header">
        <h1 class="report-title">Reporte de Cierre de Turno (Sobre)</h1>
        <div class="report-meta">
            <div>
                <strong>Turno:</strong> <?= htmlspecialchars($flujo['turno']) ?><br>
                <strong>Fecha:</strong> <?= htmlspecialchars($flujo['fecha']) ?>
            </div>
            <div style="text-align: right;">
                <strong>Operador/a:</strong> <?= htmlspecialchars($flujo['operador'] ?? 'No especificado') ?><br>
                <strong>ID Flujo:</strong> #<?= $id ?>
            </div>
        </div>
    </div>

    <!-- INGRESOS -->
    <div class="section-title">DETALLE DE INGRESOS</div>
    <?php foreach($desgloseIngresos as $ing): ?>
        <div class="line-item">
            <div class="desc">
                <?= htmlspecialchars($ing['categoria']) ?> 
                <span class="text-danger fw-bold"><?= !empty($ing['observacion']) ? ' ('.htmlspecialchars($ing['observacion']).')' : '' ?></span> 
                <small class="text-muted">(<?= $ing['medio'] ?>)</small>
            </div>
            <div class="monto">S/ <?= number_format($ing['monto'], 2) ?></div>
        </div>
    <?php endforeach; ?>
    <?php if(empty($desgloseIngresos)): ?>
        <div class="text-muted text-center small">No hay ingresos registrados.</div>
    <?php endif; ?>
    
    <div class="total-line">
        <div>TOTAL INGRESOS (Todas las vías):</div>
        <div>S/ <?= number_format($totalIngresos, 2) ?></div>
    </div>
    
    <div class="mt-2 text-end text-muted small" style="padding-right: 5px;">
        Efectivo cobrado: S/ <?= number_format($ingresoEfectivo, 2) ?> <br>
        Digital / POS / Transferencia: S/ <?= number_format($ingresoPosYape, 2) ?>
    </div>

    <!-- EGRESOS -->
    <div class="section-title">DETALLE DE EGRESOS</div>
    <?php foreach($desgloseEgresos as $egr): ?>
        <div class="line-item">
            <div class="desc">
                <?= htmlspecialchars($egr['categoria']) ?> 
                <span class="text-secondary opacity-75 small italic"><?= !empty($egr['observacion']) ? ' ('.htmlspecialchars($egr['observacion']).')' : '' ?></span>
                <small class="text-muted">(<?= $egr['medio'] ?>)</small>
            </div>
            <div class="monto text-danger">- S/ <?= number_format($egr['monto'], 2) ?></div>
        </div>
    <?php endforeach; ?>
    <?php if(empty($desgloseEgresos)): ?>
        <div class="text-muted text-center small">No hay egresos registrados.</div>
    <?php endif; ?>

    <div class="total-line">
        <div>TOTAL EGRESOS:</div>
        <div class="text-danger">- S/ <?= number_format($totalEgresos, 2) ?></div>
    </div>

    <!-- NOTAS DE ENTREGA -->
    <?php if(!empty($flujo['nota_entrega'])): ?>
    <div class="section-title">NOTA DE ENTREGA</div>
    <div style="font-size: 14px; font-style: italic; border-left: 3px solid #ccc; padding-left: 10px; color: #555;">
        <?= nl2br(htmlspecialchars($flujo['nota_entrega'])) ?>
    </div>
    <?php endif; ?>

    <!-- SOBRE FÍSICO (TURNO Y ACUMULADO MENSUAL) -->
    <div class="caja-final mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div style="font-weight: bold; letter-spacing: 1px; color: #0056b3;">ENTREGA DE EFECTIVO DEL TURNO</div>
                <div class="small mt-1" style="color: #666;">Ingresos cobrados en efectivo durante el turno</div>
            </div>
            <div class="monto-grande">S/ <?= number_format($efectivoEnSobre, 2) ?></div>
        </div>
    </div>

    <div style="background: #fffbeb; border: 2px solid #f59e0b; padding: 15px; border-radius: 8px;">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div style="font-weight: bold; letter-spacing: 1px; color: #b45309;">FONDO ACUMULADO MENSUAL (SOBRE NETO)</div>
                <div class="small mt-1" style="color: #d97706;">Ingresos Efectivo del mes - Egresos Efectivo del mes</div>
            </div>
            <div style="font-size: 22px; font-weight: bold; color: #b45309;">S/ <?= number_format($mensualSolesNeto, 2) ?></div>
        </div>
        <div class="d-flex justify-content-between mt-2 pt-2 border-top border-warning small text-muted">
            <span>Dólares (USD): $ <?= number_format($mensualDolaresNeto, 2) ?></span>
            <span>Pesos (CLP): $ <?= number_format($mensualPesosNeto, 0) ?></span>
        </div>
    </div>

    <!-- ESPACIO DE FIRMAS -->
    <div class="firmas-area">
        <div class="firma-box">
            <div class="firma-linea">
                <?= htmlspecialchars($flujo['operador'] ?? 'Cajera(o)') ?>
            </div>
            <div class="firma-label">ENTREGUÉ CONFORME</div>
        </div>
        <div class="firma-box">
            <div class="firma-linea">
                Administración / Alex
            </div>
            <div class="firma-label">RECIBÍ CONFORME</div>
        </div>
    </div>

</div>

<script>
    // Autofocus en impresion al abrir en prod
    // window.onload = function() { window.print(); }
</script>
</body>
</html>
