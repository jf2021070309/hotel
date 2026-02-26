<?php
// ============================================================
// habitaciones/editar.php — Formulario para editar habitación
// ============================================================
require_once '../config/conexion.php';
$base = '../'; $page_title = 'Editar Habitación — Hotel Manager';
$id_php = (int)($_GET['id'] ?? 0);
if ($id_php <= 0) redirigir('index.php');
include '../includes/head.php';
include '../includes/sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <div><h4><i class="bi bi-pencil-fill me-2 text-primary"></i>Editar Habitación</h4><p>Modificar datos de la habitación</p></div>
    <a href="index.php" class="btn-outline-custom"><i class="bi bi-arrow-left"></i> Volver</a>
  </div>

  <div class="page-body" id="app-hab-editar">
    <div class="text-center py-5" v-if="loading"><div class="spinner-border text-primary"></div></div>

    <div class="form-card" style="max-width:560px;margin:auto" v-if="!loading">
      <div class="mb-4 d-flex align-items-center gap-3">
        <div class="stat-icon amber" style="width:44px;height:44px;font-size:18px"><i class="bi bi-pencil-fill"></i></div>
        <div><h5 class="mb-0 fw-bold">Editar Habitación #{{ form.numero }}</h5>
          <small class="text-muted">Actualice los datos necesarios</small></div>
      </div>

      <div v-if="msg.text" class="alert-custom mb-3" :class="msg.ok ? 'alert-success' : 'alert-error'">
        <i :class="msg.ok ? 'bi bi-check-circle-fill' : 'bi bi-exclamation-triangle-fill'"></i> {{ msg.text }}
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Número</label>
          <input v-model="form.numero" class="form-control">
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
          <input v-model="form.precio_base" type="number" step="0.01" class="form-control">
        </div>
        <div class="col-12">
          <label class="form-label">Estado actual</label>
          <input :value="form.estado" class="form-control" readonly style="background:#f8fafc;color:#64748b">
          <small class="text-muted">El estado se actualiza automáticamente con los registros de ingreso/salida.</small>
        </div>
      </div>

      <div class="mt-4">
        <button class="btn-primary-custom w-100 justify-content-center" @click="guardar" :disabled="guardando">
          <i class="bi bi-save-fill"></i> {{ guardando ? 'Guardando...' : 'Guardar Cambios' }}
        </button>
      </div>
    </div>
  </div>
</div>

<script>const HAB_ID = <?= $id_php ?>;</script>
<script src="editar.js"></script>
</body></html>
