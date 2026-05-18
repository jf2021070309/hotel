<?php
// ============================================================
// includes/sidebar.php — Barra lateral de navegación
// ============================================================

// Determinar la raíz del proyecto para inclusiones de backend
$_projectRoot = dirname(__DIR__);
require_once $_projectRoot . '/rutas.php';

// Determine la ruta activa considerando URLs limpias y rutas físicas
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$projectBase = project_base_url();
if (strpos($requestPath, $projectBase) === 0) {
    $requestPath = substr($requestPath, strlen($projectBase));
}
$requestPath = trim((string)$requestPath, '/');

function isActive(string $page, string $folder_): string {
    global $requestPath;

    $path = ltrim($page, '/');
    if ($folder_ !== '' && $path === 'index.php') {
        $path = $folder_ . '/index.php';
    }

    $map = clean_route_map();
    $clean = $map[$path] ?? null;

    if ($clean === null) {
        if ($folder_ === '' && ($requestPath === '' || $requestPath === 'index.php')) {
            return 'active';
        }
        return '';
    }

    $clean = trim($clean, '/');
    if ($clean === '') {
        return $requestPath === '' ? 'active' : '';
    }

    if ($requestPath === $clean || strpos($requestPath, $clean . '/') === 0) {
        return 'active';
    }

    return '';
}
?>
<!-- Sidebar overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="mainSidebar">
  <div class="sidebar-brand">
    <div class="d-flex align-items-center gap-3 overflow-hidden">
      <!-- Icono lujoso para el logo PLATINIUM -->
      <div class="brand-icon" style="background: linear-gradient(135deg, #111, #333); border: 1px solid #d4af37; color: #d4af37; box-shadow: 0 4px 10px rgba(212, 175, 55, 0.2);">
        <i class="bi bi-star-fill" style="font-size: 16px;"></i>
      </div>
      <div class="brand-text">
        <h6 style="letter-spacing: 1px; font-weight: 800; text-transform: uppercase;">PLATINIUM</h6>
        <small style="letter-spacing: 2px; color: #d4af37; font-weight: 600;">HOTEL ★★★</small>
      </div>
    </div>
    <button id="btnToggleSidebar" class="btn-toggle-sidebar" onclick="toggleSidebar()" title="Alternar menú">
      <i class="bi bi-chevron-left"></i>
    </button>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-label">Menú Principal</div>

    <?php if (tieneAccesoModulo('dashboard')): ?>
    <div class="nav-item">
      <a href="<?= route('index.php', $base) ?>" class="<?= isActive('index.php','') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-grid-1x2-fill"></i> <span>Dashboard</span>
      </a>
    </div>
    <?php endif; ?>

    <?php if (tieneAccesoModulo('flujo')): ?>
    <div class="nav-item">
      <a href="<?= route('flujo/index.php', $base) ?>" class="<?= isActive('index.php','flujo') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-cash-stack text-success"></i> <span>Flujo de Caja</span>
      </a>
    </div>
    <?php endif; ?>

    <?php if (tieneAccesoModulo('rooming')): ?>
    <div class="nav-item">
      <a href="<?= route('rooming/index.php', $base) ?>" class="<?= isActive('index.php','rooming') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-calendar-check-fill text-primary"></i> <span>Rooming / Check-in</span>
      </a>
    </div>
    <?php endif; ?>

    <?php if (tieneAccesoModulo('reservas')): ?>
    <div class="nav-item">
      <a href="<?= route('reservas/index.php', $base) ?>" class="<?= isActive('index.php','reservas') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-grid-3x3-gap-fill text-warning"></i> <span>Cuadro de Reservas</span>
      </a>
    </div>
    <?php endif; ?>

    <?php if (tieneAccesoModulo('caja_chica')): ?>
    <div class="nav-item">
      <a href="<?= route('caja_chica/index.php', $base) ?>" class="<?= isActive('index.php','caja_chica') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-box2-heart text-danger"></i> <span>Caja Chica</span>
      </a>
    </div>
    <?php endif; ?>

    <?php if (tieneAccesoModulo('habitaciones')): ?>
    <div class="nav-item">
      <a href="<?= route('habitaciones/index.php', $base) ?>" class="<?= isActive('index.php','habitaciones') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-door-open-fill"></i> <span>Habitaciones</span>
      </a>
    </div>
    <?php endif; ?>

    <?php if (tieneAccesoModulo('sobres')): ?>
    <div class="nav-item">
      <a href="<?= route('sobres/index.php', $base) ?>" class="<?= isActive('index.php','sobres') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-envelope-paper-fill text-success"></i> <span>Sobre de Alex</span>
      </a>
    </div>
    <?php endif; ?>

    <?php if (tieneAccesoModulo('calculadora')): ?>
    <div class="nav-item">
      <a href="<?= route('calculadora/index.php', $base) ?>" class="<?= isActive('index.php','calculadora') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-calculator-fill" style="color:#d4af37;"></i> <span>Calculadora</span>
      </a>
    </div>
    <?php endif; ?>

    <?php if (tieneAccesoModulo('yape')): ?>
    <div class="nav-item">
      <a href="<?= route('yape/index.php', $base) ?>" class="<?= isActive('index.php','yape') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-wallet2" style="color:#7b2cbf"></i> <span>Gastos Yape</span>
      </a>
    </div>
    <?php endif; ?>

    <?php if (tieneAccesoModulo('inventario')): ?>
    <div class="nav-item">
      <a href="<?= route('inventario/index.php', $base) ?>" class="<?= isActive('index.php','inventario') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-box-seam-fill text-warning"></i> <span>Inventario de Bebidas</span>
      </a>
    </div>
    <?php endif; ?>

    <?php if (tieneAccesoModulo('desayunos')): ?>
    <div class="nav-item">
      <a href="<?= route('desayunos/index.php', $base) ?>" class="<?= isActive('index.php','desayunos') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-egg-fried text-warning"></i> <span>Desayunos</span>
      </a>
    </div>
    <?php endif; ?>

    <?php if (tieneAccesoModulo('limpieza')): ?>
    <div class="nav-item">
      <a href="<?= route('limpieza/index.php', $base) ?>" class="<?= isActive('index.php','limpieza') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-stars text-info"></i> <span>Limpieza</span>
      </a>
    </div>
    <?php endif; ?>

    <?php if (tieneAccesoModulo('clientes')): ?>
    <div class="nav-item">
      <a href="<?= route('clientes/index.php', $base) ?>" class="<?= isActive('index.php','clientes') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-people-fill"></i> <span>Clientes</span>
      </a>
    </div>
    <?php endif; ?>

      <?php if (tieneAccesoModulo('gestion_usuarios') || tieneAccesoModulo('medios_pago') || tieneAccesoModulo('auditoria')): ?>
      <div class="nav-label mt-2">Configuración</div>
      <?php endif; ?>

      <?php if (tieneAccesoModulo('gestion_usuarios')): ?>
      <div class="nav-item">
        <a href="<?= route('admin/usuarios.php', $base) ?>" class="<?= isActive('usuarios.php','admin') ?>" onclick="closeSidebarOnMobile()">
          <i class="bi bi-people-fill text-danger"></i> <span>Gestión Usuarios</span>
        </a>
      </div>
      <?php endif; ?>

      <?php if (tieneAccesoModulo('medios_pago')): ?>
      <div class="nav-item">
        <a href="<?= route('admin/medios_pago.php', $base) ?>" class="<?= isActive('medios_pago.php','admin') ?>" onclick="closeSidebarOnMobile()">
          <i class="bi bi-credit-card-2-back-fill text-primary"></i> <span>Medios de Pago</span>
        </a>
      </div>
      <?php endif; ?>

      <?php if (tieneAccesoModulo('auditoria')): ?>
      <div class="nav-item">
        <a href="<?= route('admin/auditoria.php', $base) ?>" class="<?= isActive('auditoria.php','admin') ?>" onclick="closeSidebarOnMobile()">
          <i class="bi bi-journal-text text-warning"></i> <span>Auditoría</span>
        </a>
      </div>
      <?php endif; ?>

    <?php if (tieneAccesoModulo('reporte_mendoza')): ?>
    <div class="nav-label mt-2">Reportes Avanzados</div>
    <?php endif; ?>

    <!-- Reportes Comerciales eliminado del sidebar -->

    <?php if (tieneAccesoModulo('reporte_mendoza')): ?>
    <div class="nav-item">
      <a href="<?= route('app/Views/reportes/mendoza.php', $base) ?>" class="<?= isActive('mendoza.php','reportes') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-file-earmark-bar-graph-fill text-success"></i> <span>Reporte Mendoza</span>
      </a>
    </div>
    <?php endif; ?>

  </nav>

  <div class="sidebar-user px-3 py-3 border-top" style="border-color: rgba(255,255,255,0.05) !important;">
    <div class="d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2 overflow-hidden">
        <div id="sidebarAvatarLetter" class="user-avatar text-dark rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
             style="width:34px; height:34px; font-weight:800; font-size:14px; background: linear-gradient(135deg, #d4af37, #f3e5ab); box-shadow: 0 0 10px rgba(212,175,55,0.2);">
          <?= strtoupper(substr($_SESSION['auth_nombre'] ?? 'U', 0, 1)) ?>
        </div>
        <div class="user-details overflow-hidden">
          <div id="sidebarUserName" class="text-white fw-bold text-truncate" style="font-size:13px; line-height:1.2; letter-spacing: 0.5px;">
            <?= htmlspecialchars($_SESSION['auth_nombre'] ?? 'Usuario') ?>
          </div>
          <div id="sidebarUserLogin" class="text-truncate" style="color: #94a3b8; font-size:11px; line-height:1.1;">
            <?= htmlspecialchars($_SESSION['auth_usuario'] ?? 'user') ?>
          </div>
          <div id="sidebarUserRole" class="fw-bold text-truncate" style="color: #d4af37; font-size:10px; line-height:1.1; margin-top: 2px; text-transform: uppercase; letter-spacing: 1px;">
            <?= ucwords($_SESSION['auth_rol'] ?? 'Invitado') ?>
          </div>
        </div>
      </div>
      <a href="<?= route('logout.php', $base) ?>" class="btn-logout-inline p-1" title="Cerrar Sesión" style="color: #ef4444; opacity: 0.8; transition: all 0.3s;">
        <i class="bi bi-box-arrow-right" style="font-size: 18px;"></i>
      </a>
    </div>
  </div>

</aside>

<script>
// Exponer funciones al ámbito global para asegurar acceso desde onclick
window.handleMenuClick = function() {
  if (window.innerWidth <= 768) {
    window.openSidebar();
  } else {
    window.toggleSidebar();
  }
};

window.openSidebar = function() {
  const sidebar = document.getElementById('mainSidebar');
  const overlay = document.getElementById('sidebarOverlay');
  if (sidebar) sidebar.classList.add('open');
  if (overlay) overlay.classList.add('active');
  document.body.style.overflow = 'hidden';
};

window.closeSidebar = function() {
  const sidebar = document.getElementById('mainSidebar');
  const overlay = document.getElementById('sidebarOverlay');
  if (sidebar) sidebar.classList.remove('open');
  if (overlay) overlay.classList.remove('active');
  document.body.style.overflow = '';
};

window.closeSidebarOnMobile = function() {
  if (window.innerWidth <= 768) window.closeSidebar();
};

// Listener automático para asegurar que cualquier botón burger funcione
document.addEventListener('click', (e) => {
  const btn = e.target.closest('.btn-burger');
  if (btn) {
    e.preventDefault();
    window.handleMenuClick();
  }
});

window.toggleSidebar = function() {
  const sidebar = document.getElementById('mainSidebar');
  const mainContent = document.querySelector('.main-content');
  const btn = document.getElementById('btnToggleSidebar');

  if (!sidebar) return;
  
  sidebar.classList.toggle('collapsed');
  if (mainContent) mainContent.classList.toggle('sidebar-collapsed');

  const isCollapsed = sidebar.classList.contains('collapsed');
  localStorage.setItem('sidebar_collapsed', isCollapsed ? 'true' : 'false');
  
  if (btn) {
    const icon = btn.querySelector('i');
    if (icon) icon.className = isCollapsed ? 'bi bi-chevron-right' : 'bi bi-chevron-left';
  }
};

// --- SIDEBAR SCROLL PERSISTENCE ---
(function() {
  const sidebarNav = document.querySelector('.sidebar-nav');
  if (!sidebarNav) return;

  const savedScroll = sessionStorage.getItem('sidebar_scroll');
  if (savedScroll) {
    sidebarNav.scrollTop = parseInt(savedScroll, 10);
  }

  sidebarNav.addEventListener('click', (e) => {
    if (e.target.closest('a')) {
      sessionStorage.setItem('sidebar_scroll', sidebarNav.scrollTop);
    }
  });

  sidebarNav.addEventListener('scroll', () => {
    sessionStorage.setItem('sidebar_scroll', sidebarNav.scrollTop);
  }, { passive: true });
})();

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
      if (btn) {
        const icon = btn.querySelector('i');
        if (icon) icon.className = 'bi bi-chevron-right';
      }
    });
  }
})();
</script>
