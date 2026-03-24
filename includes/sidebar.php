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
        <i class="bi bi-door-open-fill"></i> <span>Habitaciones</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= route('rooming/index.php', $base) ?>" class="<?= isActive('index.php','rooming') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-calendar-check-fill text-primary"></i> <span>Rooming / Check-in</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= route('reservas/index.php', $base) ?>" class="<?= isActive('index.php','reservas') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-grid-3x3-gap-fill text-warning"></i> <span>Cuadro de Reservas</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= route('flujo/index.php', $base) ?>" class="<?= isActive('index.php','flujo') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-cash-stack text-success"></i> <span>Flujo de Caja</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= route('caja_chica/index.php', $base) ?>" class="<?= isActive('index.php','caja_chica') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-box2-heart text-danger"></i> <span>Caja Chica</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= route('yape/index.php', $base) ?>" class="<?= isActive('index.php','yape') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-wallet2" style="color:#7b2cbf"></i> <span>Gastos Yape</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= route('desayunos/index.php', $base) ?>" class="<?= isActive('index.php','desayunos') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-egg-fried text-warning"></i> <span>Desayunos</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= route('limpieza/index.php', $base) ?>" class="<?= isActive('index.php','limpieza') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-stars text-info"></i> <span>Limpieza</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= route('clientes/index.php', $base) ?>" class="<?= isActive('index.php','clientes') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-people-fill"></i> <span>Clientes</span>
      </a>
    </div>

    <?php if (tienePermiso('cajera')): ?>
    <div class="nav-label mt-2">Configuración</div>
    <div class="nav-item">
      <a href="<?= route('admin/usuarios.php', $base) ?>" class="<?= isActive('usuarios.php','admin') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-people-fill text-danger"></i> <span>Gestión Usuarios</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= route('admin/auditoria.php', $base) ?>" class="<?= isActive('auditoria.php','admin') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-journal-text text-warning"></i> <span>Auditoría</span>
      </a>
    </div>
    <?php endif; ?>

    <div class="nav-label mt-2">Reportes (Altogerencia)</div>
    <div class="nav-item">
      <a href="<?= route('app/Views/reportes/mendoza.php', $base) ?>" class="<?= isActive('mendoza.php','reportes') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-file-earmark-bar-graph-fill text-success"></i> <span>Reporte Mendoza</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= route('app/Views/reportes/alex.php', $base) ?>" class="<?= isActive('alex.php','reportes') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-person-badge-fill" style="color:#7b2cbf"></i> <span>Reporte Alex</span>
      </a>
    </div>

    <!-- Reportes removidos por ahora -->
  </nav>

  <div class="sidebar-user px-3 py-3 border-top border-secondary border-opacity-10">
    <div class="d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2 overflow-hidden">
        <div id="sidebarAvatarLetter" class="user-avatar bg-dark text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
             style="width:34px; height:34px; font-weight:700; font-size:13px; border: 1px solid rgba(255,255,255,0.1);">
          <?= strtoupper(substr($_SESSION['auth_nombre'] ?? 'U', 0, 1)) ?>
        </div>
        <div class="user-details overflow-hidden">
          <div id="sidebarUserName" class="text-white fw-bold text-truncate" style="font-size:13px; line-height:1.1;">
            <?= htmlspecialchars($_SESSION['auth_nombre'] ?? 'Usuario') ?>
          </div>
          <div id="sidebarUserLogin" class="text-secondary text-truncate" style="font-size:11px; line-height:1.1; opacity:0.7;">
            <?= htmlspecialchars($_SESSION['auth_usuario'] ?? 'user') ?>
          </div>
          <div id="sidebarUserRole" class="text-secondary fw-bold text-truncate" style="font-size:11px; line-height:1.1; margin-top: 2px;">
            <?= ucwords($_SESSION['auth_rol'] ?? 'Invitado') ?>
          </div>
        </div>
      </div>
      <a href="<?= route('logout.php', $base) ?>" class="text-secondary btn-logout-inline p-1" title="Cerrar Sesión">
        <i class="bi bi-box-arrow-right" style="font-size: 18px;"></i>
      </a>
    </div>
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
