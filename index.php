<?php
// ============================================================
// index.php — Dashboard shell (Vue monta #app-dashboard)
// ============================================================
require_once 'config/conexion.php';
require_once 'auth/middleware.php';
$base = ''; $page_title = 'Dashboard — Hotel Manager';
include 'includes/head.php';
include 'includes/sidebar.php';
require_once 'rutas.php';
?>
<div class="main-content">
  <div class="topbar">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
    <div>
      <h4><i class="bi bi-grid-1x2-fill me-2 text-primary"></i>Dashboard</h4>
      <p>Resumen general del hotel — <?= date('d/m/Y') ?></p>
    </div>
    <span class="badge bg-primary px-3 py-2" id="reloj"></span>
  </div>

  <div class="page-body">
    <div id="app-dashboard">
      <!-- Vue se monta aquí -->
      <div class="text-center py-5" v-if="loading">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Cargando datos...</p>
      </div>

      <!-- STATS -->
      <div class="row g-3 mb-4" v-if="!loading">
        <div class="col-6 col-md-4 col-xl-2">
          <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-building"></i></div>
            <div class="stat-info"><label>Total Habitaciones</label><span>{{ stats.total }}</span></div>
          </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
          <div class="stat-card">
            <div class="stat-icon red"><i class="bi bi-door-closed-fill"></i></div>
            <div class="stat-info"><label>Ocupadas</label><span>{{ stats.ocupadas }}</span></div>
          </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
          <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-door-open-fill"></i></div>
            <div class="stat-info"><label>Libres</label><span>{{ stats.libres }}</span></div>
          </div>
        </div>
        <!-- Finanzas deshabilitadas -->
      </div>

      <!-- MAPA DE HABITACIONES -->
      <div class="d-flex align-items-center justify-content-between mb-3" v-if="!loading">
        <h5 class="fw-bold mb-0" style="font-size:16px">Mapa de Habitaciones</h5>
        <div class="d-flex gap-3">
          <span class="legend"><span class="legend-dot" style="background:var(--success)"></span>Libre</span>
          <span class="legend"><span class="legend-dot" style="background:var(--danger)"></span>Ocupada</span>
        </div>
      </div>

      <div class="room-grid" v-if="!loading">
        <div class="room-card" :class="h.estado" v-for="h in habitaciones" :key="h.id">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="room-number">{{ h.numero }}</div>
              <div class="room-type">{{ h.tipo }} · Piso {{ h.piso }}</div>
            </div>
            <i :class="h.estado === 'libre' ? 'bi-door-open-fill' : 'bi-door-closed-fill'"
               class="bi"
               :style="{fontSize:'20px', color: h.estado === 'libre' ? 'var(--success)' : 'var(--danger)'}"></i>
          </div>
          <div class="room-badge" :class="h.estado">
            <span class="dot"></span>{{ h.estado === 'libre' ? 'Libre' : 'Ocupada' }}
          </div>
          <div class="room-action">
             <div class="text-center text-muted small py-2">Módulo en reestructuración</div>
          </div>
        </div>
      </div>
    </div><!-- /#app-dashboard -->
  </div>
</div>

<script src="index.js"></script>
<script>
  // Reloj en topbar
  function tick() { document.getElementById('reloj').textContent = new Date().toLocaleTimeString('es-PE',{hour:'2-digit',minute:'2-digit'}); }
  tick(); setInterval(tick, 1000);
</script>
</body></html>
