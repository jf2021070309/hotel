<?php
// ============================================================
// app/Views/layouts/head.php — Head compartido con Bootstrap 5 + Vue 3
// ============================================================
$page_title      = $page_title      ?? 'Hotel Manager';
$base            = $base            ?? '';   // Prefijo del filesystem — lo define cada vista
$export_enabled  = $export_enabled  ?? false;
$chartjs_enabled = $chartjs_enabled ?? false;

require_once dirname(__DIR__, 3) . '/app/Helpers/url.php';
$_root          = project_base_url(); // URL raíz del proyecto — SOLO para assets web
$view_base_href = view_base_href_for_request();
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
  <link rel="icon" type="image/png" href="<?= $_root ?>assets/img/icono.png">
  <link rel="shortcut icon" type="image/png" href="<?= $_root ?>assets/img/icono.png">

  <!-- Bootstrap 5 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Estilos personalizados -->
  <link rel="stylesheet" href="<?= $_root ?>public/assets/css/style.css?v=<?= time() ?>">
  <!-- Vue 3 CDN (Production Build) -->
  <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
  <!-- Axios CDN -->
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<?php if ($export_enabled): ?>
  <!-- Exportación PDF -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
  <!-- Exportación Excel con Estilos -->
  <script src="https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js"></script>
  <!-- Utilidades de exportación -->
  <script src="<?= $_root ?>public/assets/js/exportar.js"></script>
<?php endif; ?>
<?php if ($chartjs_enabled): ?>
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<?php endif; ?>
</head>
<body>
