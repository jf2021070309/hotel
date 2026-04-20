<?php
// ============================================================
// includes/head.php — Head compartido con Bootstrap 5 + Vue 3
// Requerir: $base ('' | '../' | '../../') y $page_title antes de incluir
// ============================================================
$page_title      = $page_title      ?? 'Hotel Manager';
$base            = $base            ?? '';
$export_enabled  = $export_enabled  ?? false;
$chartjs_enabled = $chartjs_enabled ?? false;

require_once dirname(__DIR__) . '/rutas.php';
$view_base_href = view_base_href_for_request();
$_root = project_base_url();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></title>
<?php if ($view_base_href): ?>
  <base href="<?= htmlspecialchars($view_base_href, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>

  <!-- Favicon -->
  <link rel="icon" type="image/jpeg" href="<?= $_root ?>assets/img/logo.jpg">
  <link rel="shortcut icon" type="image/jpeg" href="<?= $_root ?>assets/img/logo.jpg">

  <!-- Bootstrap 5 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Estilos personalizados -->
  <link rel="stylesheet" href="<?= $_root ?>style.css?v=<?= time() ?>">
  <!-- Vue 3 CDN (Global Build) -->
  <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
  <!-- Axios CDN -->
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<?php if ($export_enabled): ?>
  <!-- Exportación PDF -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
  <!-- Exportación Excel -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <!-- Utilidades de exportación -->
  <script src="<?= $_root ?>app/exportar.js"></script>
<?php endif; ?>
<?php if ($chartjs_enabled): ?>
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<?php endif; ?>
</head>
<body>
