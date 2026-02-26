<?php
// ============================================================
// includes/head.php — Head compartido con Bootstrap 5 + Vue 3
// Requerir: $base ('' | '../' | '../../') y $page_title antes de incluir
// ============================================================
$page_title = $page_title ?? 'Hotel Manager';
$base       = $base       ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></title>

  <!-- Bootstrap 5 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Estilos personalizados -->
  <link rel="stylesheet" href="<?= $base ?>style.css">
  <!-- Vue 3 CDN (Global Build) -->
  <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
</head>
<body>
