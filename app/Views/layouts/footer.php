  <!-- Bootstrap 5 Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- SweetAlert 2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Axios -->
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
  

  <!-- Floating Notification Bell -->
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

  <!-- Offcanvas Notifications -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNotifications" aria-labelledby="offcanvasNotificationsLabel" style="z-index: 1060; width: 350px;">
    <div class="offcanvas-header bg-light border-bottom">
      <h5 class="offcanvas-title fw-bold" id="offcanvasNotificationsLabel">
        <i class="bi bi-bell-fill text-warning me-2"></i>Centro de Notificaciones
      </h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0" style="background-color: #f8f9fa;">
      <div id="notificationList" class="list-group list-group-flush">
        <!-- AJAX content goes here -->
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
    
    // Attempt to determine correct base path, fallback to absolute path if needed
    let basePath = '/hotel/'; 
    if (typeof window._root !== 'undefined') {
        basePath = window._root;
    } else if (document.querySelector('base')) {
        basePath = document.querySelector('base').href;
    } else if (window.location.pathname.includes('/hotel/')) {
        basePath = window.location.origin + '/hotel/';
    }
    
    function fetchNotifications() {
      axios.get(basePath + 'ajax/notificaciones.php')
        .then(response => {
          if (response.data && response.data.status === 'success') {
            const count = response.data.count;
            const data = response.data.data;
            
            if (count > 0) {
              notifCountBadge.innerText = count;
              notifCountBadge.style.display = 'block';
            } else {
              notifCountBadge.style.display = 'none';
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
            // Si hay error (e.g. no auth), se ignora silenciosamente para no molestar.
        });
    }
    
    // Fetch initial notifications
    fetchNotifications();
    
    // Refresh every 5 minutes (300000 ms)
    setInterval(fetchNotifications, 300000);
    
    // Refresh when offcanvas is opened
    const offcanvasEl = document.getElementById('offcanvasNotifications');
    if (offcanvasEl) {
      offcanvasEl.addEventListener('show.bs.offcanvas', fetchNotifications);
    }
  });
  </script>

</body>
</html>
