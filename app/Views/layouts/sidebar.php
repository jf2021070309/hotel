<?php
// ============================================================
// app/Views/layouts/sidebar.php — Barra lateral de navegación
// ============================================================

// Determinar la raíz del proyecto para inclusiones de backend
$_projectRoot = dirname(__DIR__, 3);
require_once $_projectRoot . '/app/Helpers/url.php';

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

    if ($clean === null && $folder_ !== '') {
        // Intentar con prefijo del folder (ej: admin/usuarios.php)
        $alternativePath = $folder_ . '/' . $path;
        if (isset($map[$alternativePath])) {
            $clean = $map[$alternativePath];
        } else {
            // Intentar con prefijo completo de app/Views/ (ej: app/Views/reportes/mendoza.php)
            $alternativePath2 = 'app/Views/' . $folder_ . '/' . $path;
            if (isset($map[$alternativePath2])) {
                $clean = $map[$alternativePath2];
            }
        }
    }

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
  <div class="sidebar-brand" style="justify-content: center; position: relative;">
    <div class="d-flex align-items-center overflow-hidden w-100 justify-content-center">
      <!-- Logo de la empresa -->
      <div class="brand-image">
        <img src="<?= $projectBase ?>assets/img/logo2.png" alt="Platinium Hotel" style="max-width: 160px; height: auto;">
      </div>
    </div>
    <button id="btnToggleSidebar" class="btn-toggle-sidebar" style="position: absolute; right: 15px;" onclick="toggleSidebar()" title="Alternar menú">
      <i class="bi bi-chevron-left"></i>
    </button>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-label">Menú Principal</div>

    <?php if (tieneAccesoModulo('reservas')): ?>
    <div class="nav-item">
      <a href="<?= route('reservas/index.php', $base) ?>" class="<?= isActive('index.php','reservas') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-grid-3x3-gap-fill text-warning"></i> <span>Cuadro de Reservas</span>
      </a>
    </div>
    <?php endif; ?>

    <?php if (tieneAccesoModulo('rooming')): ?>
    <div class="nav-item">
      <a href="<?= route('rooming/v2.php', $base) ?>" class="<?= isActive('v2.php','rooming') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-calendar-check-fill text-primary"></i> <span>Rooming / Check-in</span>
      </a>
    </div>
    <?php endif; ?>

    <?php if (tieneAccesoModulo('flujo')): ?>
    <div class="nav-item">
      <a href="<?= route('flujo/v2.php', $base) ?>" class="<?= isActive('v2.php','flujo') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-cash-stack text-success"></i> <span>Flujo de Caja</span>
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
      <a href="<?= route('limpieza/v2.php', $base) ?>" class="<?= isActive('v2.php','limpieza') ?>" onclick="closeSidebarOnMobile()">
        <i class="bi bi-stars text-info"></i> <span>Limpieza</span>
      </a>
    </div>
    <?php endif; ?>

    <?php if (tieneAccesoModulo('clientes')): ?>
    <div class="nav-item">
      <a href="<?= route('clientes/v2.php', $base) ?>" class="<?= isActive('v2.php','clientes') ?>" onclick="closeSidebarOnMobile()">
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

    <div class="nav-label mt-2">Alertas</div>
    <div class="nav-item">
      <a href="#" class="" onclick="event.preventDefault(); var offcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasNotifications')); offcanvas.show(); if(window.innerWidth <= 768) closeSidebar();">
        <i class="bi bi-bell-fill text-danger animate__animated animate__swing animate__infinite animate__slower" style="display: inline-block;"></i>
        <span>Notificaciones</span>
        <span id="sidebarNotifBadge" class="badge bg-danger rounded-pill ms-auto" style="display: none; font-size: 10px;">0</span>
      </a>
    </div>

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
  console.debug('[Sidebar] openSidebar called');
};

window.closeSidebar = function() {
  const sidebar = document.getElementById('mainSidebar');
  const overlay = document.getElementById('sidebarOverlay');
  if (sidebar) sidebar.classList.remove('open');
  if (overlay) overlay.classList.remove('active');
  document.body.style.overflow = '';
  console.debug('[Sidebar] closeSidebar called');
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
    console.debug('[Sidebar] btn-burger clicked', {target: e.target, time: Date.now()});
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
  console.debug('[Sidebar] toggleSidebar -> collapsed=', sidebar.classList.contains('collapsed'));
};

// --- SIDEBAR SCROLL PERSISTENCE ---
document.addEventListener('DOMContentLoaded', () => {
  const sidebarNav = document.querySelector('.sidebar-nav');
  if (!sidebarNav) return;

  // Encontrar el elemento activo actual
  const activeItem = sidebarNav.querySelector('a.active');
  
  if (activeItem) {
    // Si la pantalla es pequeña y el scroll no es necesario, no forzamos
    // Utilizamos un pequeño retraso visual para que la animación suave sea perceptible y fluida
    setTimeout(() => {
      activeItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 100);
  }
});

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
      console.debug('[Sidebar] restored collapsed state from localStorage');
    });
  }
})();
</script>

<!-- Floating Notification Bell & Offcanvas -->
<div class="notification-widget" style="position: fixed; bottom: 30px; right: 30px; z-index: 1050;">
  <button type="button" class="btn rounded-circle shadow-lg position-relative" 
          style="width: 60px; height: 60px; font-size: 24px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #d4af37, #b58500); border: none;"
          data-bs-toggle="offcanvas" data-bs-target="#offcanvasNotifications" aria-controls="offcanvasNotifications" id="btnNotifications">
    <i class="bi bi-bell-fill" style="color: white; animation: pulse 2s infinite;"></i>
    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light" id="notificationCount" style="display: none; font-size: 12px; padding: 5px 8px;">
      0
      <span class="visually-hidden">notificaciones no leídas</span>
    </span>
  </button>
</div>

<style>
  @keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
  }
</style>

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNotifications" aria-labelledby="offcanvasNotificationsLabel" style="z-index: 1060; width: 350px;">
  <div class="offcanvas-header bg-light border-bottom">
    <h5 class="offcanvas-title fw-bold" id="offcanvasNotificationsLabel">
      <i class="bi bi-bell-fill text-warning me-2"></i>Centro de Notificaciones
    </h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-0" style="background-color: #f8f9fa;">
    <div id="notificationList" class="list-group list-group-flush">
      <div class="p-4 text-center text-muted">
        <div class="spinner-border spinner-border-sm mb-2 text-primary" role="status"></div><br>
        Cargando notificaciones...
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const notifCountBadge = document.getElementById('notificationCount');
  const notifList = document.getElementById('notificationList');
  
  // Use the PHP variable $_root defined in head.php for exact project root
  let basePath = '<?= $_root ?? "/" ?>';
  
  function fetchNotifications() {
    if (typeof axios === 'undefined') return; // En caso de que no haya cargado Axios
    axios.get(basePath + 'ajax/notificaciones.php')
      .then(response => {
        if (response.data && response.data.status === 'success') {
          const count = response.data.count;
          const data = response.data.data;
          
          const sidebarBadge = document.getElementById('sidebarNotifBadge');
          if (count > 0) {
            if(notifCountBadge) {
              notifCountBadge.innerText = count;
              notifCountBadge.style.display = 'block';
            }
            if (sidebarBadge) {
                sidebarBadge.innerText = count;
                sidebarBadge.style.display = 'inline-block';
            }
          } else {
            if(notifCountBadge) notifCountBadge.style.display = 'none';
            if (sidebarBadge) sidebarBadge.style.display = 'none';
          }
          
          if (count === 0) {
            notifList.innerHTML = '<div class="p-5 text-center text-muted"><i class="bi bi-bell-slash text-secondary fs-1 mb-3 d-block opacity-50"></i><p class="mb-0 fw-semibold">No hay notificaciones nuevas</p></div>';
            return;
          }
          
          let html = '';
          data.forEach(item => {
            let bgIcon = 'bg-primary';
            if (item.tipo === 'warning') bgIcon = 'bg-warning text-dark';
            if (item.tipo === 'danger') bgIcon = 'bg-danger';
            if (item.tipo === 'success') bgIcon = 'bg-success';
            if (item.tipo === 'info') bgIcon = 'bg-info text-dark';
            
            html += `
              <a href="${basePath + item.url}" class="list-group-item list-group-item-action p-3 border-bottom d-flex align-items-start" style="transition: all 0.2s;">
                <div class="rounded-circle ${bgIcon} p-2 d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 40px; height: 40px; flex-shrink: 0;">
                  <i class="bi ${item.icono} fs-5 ${item.tipo !== 'warning' && item.tipo !== 'info' ? 'text-white' : ''}"></i>
                </div>
                <div class="w-100">
                  <div class="d-flex w-100 justify-content-between mb-1">
                    <h6 class="mb-0 fw-bold" style="font-size: 14px;">${item.titulo}</h6>
                  </div>
                  <p class="mb-0 text-muted" style="font-size: 13px;">${item.mensaje}</p>
                </div>
              </a>
            `;
          });
          
          notifList.innerHTML = html;
        }
      })
      .catch(error => {
          console.error('Error fetching notifications:', error);
      });
  }
  
  fetchNotifications();
  setInterval(fetchNotifications, 300000);
  
  const offcanvasEl = document.getElementById('offcanvasNotifications');
  if (offcanvasEl) {
    offcanvasEl.addEventListener('show.bs.offcanvas', fetchNotifications);
  }
});
</script>
