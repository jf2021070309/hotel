<?php
// ============================================================
// habitaciones/crear.php — Formulario para crear habitación
// ============================================================
require_once '../config/conexion.php';
$base = '../'; $page_title = 'Nueva Habitación — Hotel Manager';
include '../includes/head.php';
include '../includes/sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <div><h4><i class="bi bi-building me-2 text-primary"></i>Nueva Habitación</h4><p>Registrar habitación</p></div>
    <a href="index.php" class="btn-outline-custom"><i class="bi bi-arrow-left"></i> Volver</a>
  </div>

  <div class="page-body" id="app-hab-crear">
    <div class="form-card" style="max-width:560px;margin:auto">
      <div class="mb-4 d-flex align-items-center gap-3">
        <div class="stat-icon blue" style="width:44px;height:44px;font-size:18px"><i class="bi bi-building"></i></div>
        <div><h5 class="mb-0 fw-bold">Nueva Habitación</h5><small class="text-muted">Complete los datos de la habitación</small></div>
      </div>

      <div v-if="error" class="alert-custom alert-error mb-3"><i class="bi bi-exclamation-triangle-fill"></i> {{ error }}</div>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Número de habitación</label>
          <input v-model="form.numero" class="form-control" placeholder="101, 102...">
        </div>
        <div class="col-md-6">
          <label class="form-label">Piso</label>
          <select v-model="form.piso" class="form-select">
            <option v-for="p in 10" :value="p">Piso {{ p }}</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Tipo</label>
          <select v-model="form.tipo" class="form-select">
            <option>Simple</option><option>Doble</option><option>Suite</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Precio Base (S/)</label>
          <input v-model="form.precio_base" type="number" step="0.01" class="form-control" placeholder="0.00">
        </div>
      </div>

      <div class="mt-4">
        <button class="btn-primary-custom w-100 justify-content-center" @click="guardar" :disabled="guardando">
          <i class="bi bi-save-fill"></i> {{ guardando ? 'Guardando...' : 'Crear Habitación' }}
        </button>
      </div>
    </div>
  </div>
</div>
<script src="crear.js"></script>
</body></html>
