  <!-- Bootstrap 5 Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- SweetAlert 2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Axios -->
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
  
  <script>
    // Configuración global de Sidebar para móviles
    function openSidebar() {
      document.querySelector('.sidebar').classList.add('show');
      document.querySelector('.sidebar-overlay').style.display = 'block';
    }
    
    function closeSidebar() {
      document.querySelector('.sidebar').classList.remove('show');
      document.querySelector('.sidebar-overlay').style.display = 'none';
    }

    function closeSidebarOnMobile() {
       if (window.innerWidth < 992) closeSidebar();
    }
  </script>

  <style>
    /* Estilos v-cloak para evitar destellos de Vue */
    [v-cloak] { display: none !important; }
    
    .sidebar-overlay {
      display: none;
      position: fixed;
      top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 1040;
    }
    
    @media (max-width: 991.98px) {
      .sidebar.show { left: 0 !important; }
    }
  </style>

  <div class="sidebar-overlay" onclick="closeSidebar()"></div>

</body>
</html>
