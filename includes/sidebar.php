<?php
// ============================================================
// includes/sidebar.php — Barra lateral de navegación
// ============================================================

// Determine la página activa
$current = basename($_SERVER['PHP_SELF']);
$folder  = basename(dirname($_SERVER['PHP_SELF']));

function isActive(string $page, string $folder_): string {
    global $current, $folder;
    // Módulos normales (registros, pagos, etc.)
    if ($folder_ !== '' && $folder === $folder_ && $current === $page) return 'active';
    // Dashboard: en XAMPP $folder='hotel', en Railway $folder=''
    if ($folder_ === '' && ($folder === 'hotel' || $folder === '' || $folder === 'app') && $current === $page) return 'active';
    return '';
}

// Calcular $base: cuántos niveles subir para llegar a la raíz del proyecto
// Funciona en XAMPP (/hotel/modulo/archivo.php) y Railway (/modulo/archivo.php)
$_selfPath  = str_replace('\\', '/', $_SERVER['PHP_SELF']);
$_dirParts  = array_filter(explode('/', dirname($_selfPath)), 'strlen');
$_dirParts  = array_values($_dirParts);
// Quitar 'hotel' si existe (instalación XAMPP en subdirectorio)
if (!empty($_dirParts) && $_dirParts[0] === 'hotel') {
    array_shift($_dirParts);
}
$base = str_repeat('../', count($_dirParts));
?>
<!-- Sidebar overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="mainSidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><i class="bi bi-building"></i></div>
    <div class="brand-text">
      <h6>Hotel Manager</h6>
      <small>Sistema de Gestión</small>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-label">Menú Principal</div>

    <div class="nav-item">
      <a href="<?= $base ?>index.php" class="<?= isActive('index.php','') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-grid-1x2-fill"></i> Dashboard
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= $base ?>habitaciones/index.php" class="<?= isActive('index.php','habitaciones') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-building"></i> Habitaciones
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= $base ?>clientes/index.php" class="<?= isActive('index.php','clientes') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-people-fill"></i> Clientes
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= $base ?>registros/crear.php" class="<?= isActive('crear.php','registros') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-person-plus-fill"></i> Registrar Ingreso
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= $base ?>registros/salida.php" class="<?= isActive('salida.php','registros') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-box-arrow-right"></i> Registrar Salida
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= $base ?>pagos/index.php" class="<?= isActive('index.php','pagos') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-credit-card-fill"></i> Pagos
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= $base ?>gastos/index.php" class="<?= isActive('index.php','gastos') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-receipt-cutoff"></i> Gastos
      </a>
    </div>

    <div class="nav-label mt-2">Reportes</div>
    <div class="nav-item">
      <a href="<?= $base ?>reportes/cuadre_diario.php" class="<?= isActive('cuadre_diario.php','reportes') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-bar-chart-line-fill"></i> Cuadre Diario
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= $base ?>reportes/mensual.php" class="<?= isActive('mensual.php','reportes') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-calendar-month-fill"></i> Reporte Mensual
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= $base ?>reportes/graficos.php" class="<?= isActive('graficos.php','reportes') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-graph-up"></i> Gráficos
      </a>
    </div>
  </nav>

  <div class="sidebar-footer">
    <a href="<?= $base ?>logout.php">
      <i class="bi bi-box-arrow-left"></i> Cerrar Sesión
    </a>
  </div>
</aside>

<script>
function openSidebar() {
  document.getElementById('mainSidebar').classList.add('open');
  document.getElementById('sidebarOverlay').classList.add('active');
  document.body.style.overflow = 'hidden';
}
function closeSidebar() {
  document.getElementById('mainSidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('active');
  document.body.style.overflow = '';
}
function closeSidebarOnMobile() {
  if (window.innerWidth <= 768) closeSidebar();
}
</script>
