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

// Incluir el sistema de rutas
require_once $base . 'rutas.php';
?>
<!-- Sidebar overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="mainSidebar">
  <div class="sidebar-brand">
    <div class="d-flex align-items-center gap-3 overflow-hidden">
      <div class="brand-icon"><i class="bi bi-building"></i></div>
      <div class="brand-text">
        <h6>Hotel Manager</h6>
        <small>Sistema de Gestión</small>
      </div>
    </div>
    <button id="btnToggleSidebar" class="btn-toggle-sidebar" onclick="toggleSidebar()" title="Alternar menú">
      <i class="bi bi-chevron-left"></i>
    </button>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-label">Menú Principal</div>

    <div class="nav-item">
      <a href="<?= route('index.php', $base) ?>" class="<?= isActive('index.php','') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-grid-1x2-fill"></i> <span>Dashboard</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= route('habitaciones/index.php', $base) ?>" class="<?= isActive('index.php','habitaciones') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-building"></i> <span>Habitaciones</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= route('clientes/index.php', $base) ?>" class="<?= isActive('index.php','clientes') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-people-fill"></i> <span>Clientes</span>
      </a>
    </div>

    <?php if (tienePermiso('admin')): ?>
    <div class="nav-label mt-2">Configuración</div>
    <div class="nav-item">
      <a href="<?= route('admin/usuarios.php', $base) ?>" class="<?= isActive('usuarios.php','admin') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-people-fill text-danger"></i> <span>Gestión Usuarios</span>
      </a>
    </div>
    <?php endif; ?>

    <!-- Reportes removidos por ahora -->
  </nav>

  <div class="sidebar-footer">
    <a href="<?= route('logout.php', $base) ?>">
      <i class="bi bi-box-arrow-left"></i> <span>Cerrar Sesión</span>
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

// --- COLLAPSIBLE LOGIC ---
function toggleSidebar() {
  const sidebar = document.getElementById('mainSidebar');
  const mainContent = document.querySelector('.main-content');
  const btn = document.getElementById('btnToggleSidebar');

  sidebar.classList.toggle('collapsed');
  if (mainContent) mainContent.classList.toggle('sidebar-collapsed');

  const isCollapsed = sidebar.classList.contains('collapsed');
  localStorage.setItem('sidebar_collapsed', isCollapsed ? 'true' : 'false');
  
  const icon = btn.querySelector('i');
  icon.className = isCollapsed ? 'bi bi-chevron-right' : 'bi bi-chevron-left';
}

// Restore state on load
(function() {
  const isCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
  if (isCollapsed && window.innerWidth > 768) {
    document.addEventListener('DOMContentLoaded', () => {
      const sidebar = document.getElementById('mainSidebar');
      const main = document.querySelector('.main-content');
      const btn = document.getElementById('btnToggleSidebar');
      
      if (sidebar) sidebar.classList.add('collapsed');
      if (main) main.classList.add('sidebar-collapsed');
      if (btn) btn.querySelector('i').className = 'bi bi-chevron-right';
    });
  }
})();
</script>
