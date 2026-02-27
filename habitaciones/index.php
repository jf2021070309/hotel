<?php
// habitaciones/index.php — Shell PHP, Vue monta tabla de habitaciones
require_once '../config/conexion.php';
$base = '../'; $page_title = 'Habitaciones — Hotel Manager';
include '../includes/head.php';
include '../includes/sidebar.php';
?>
<div id="app-habitaciones" style="display:contents">
<div class="main-content">
  <div class="topbar">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
    <div>
      <h4><i class="bi bi-building me-2 text-primary"></i>Habitaciones</h4>
      <p>Gestión de habitaciones del hotel</p>
    </div>
    <button class="btn-primary-custom" @click="abrirModal(null)">
      <i class="bi bi-plus-circle-fill"></i> Nueva Habitación
    </button>

  </div>

  <div class="page-body">
    <!-- Spinner -->
    <div class="text-center py-5" v-if="loading">
      <div class="spinner-border text-primary"></div>
    </div>

    <!-- Alerta -->
    <div v-if="msg.text" class="alert-custom" :class="msg.ok ? 'alert-success' : 'alert-error'">
      <i :class="msg.ok ? 'bi bi-check-circle-fill' : 'bi bi-exclamation-triangle-fill'"></i>
      {{ msg.text }}
    </div>

    <!-- Tabla -->
    <div class="card-table" v-if="!loading">
      <table class="table">
        <thead>
          <tr>
            <th>#</th><th>Número</th><th>Tipo</th><th>Piso</th>
            <th>Estado</th><th>Precio Base</th><th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(h, i) in habitaciones" :key="h.id">
            <td>{{ i+1 }}</td>
            <td><strong>{{ h.numero }}</strong></td>
            <td>{{ h.tipo }}</td>
            <td>Piso {{ h.piso }}</td>
            <td>
              <span class="px-badge" :class="h.estado === 'libre' ? 'badge-libre' : 'badge-ocupado'">
                {{ h.estado === 'libre' ? 'Libre' : 'Ocupada' }}
              </span>
            </td>
            <td>S/ {{ parseFloat(h.precio_base).toFixed(2) }}</td>
            <td class="text-end">
              <a :href="'editar.php?id=' + h.id" class="btn-outline-custom btn-sm">
                <i class="bi bi-pencil-fill"></i> Editar
              </a>
            </td>
          </tr>
          <tr v-if="habitaciones.length === 0">
            <td colspan="7" class="text-center py-4 text-muted">No hay habitaciones.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Crear/Editar -->
    <div v-if="modal.visible" class="modal-overlay" @click.self="cerrarModal">
      <div class="form-card" style="position:relative;max-width:500px;margin:auto;margin-top:60px">
        <h5 class="fw-bold mb-4">{{ modal.id ? 'Editar Habitación' : 'Nueva Habitación' }}</h5>
        <div v-if="modal.error" class="alert-custom alert-error mb-3">
          <i class="bi bi-exclamation-triangle-fill"></i> {{ modal.error }}
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Número</label>
            <input v-model="modal.numero" class="form-control" placeholder="101">
          </div>
          <div class="col-md-6">
            <label class="form-label">Piso</label>
            <select v-model="modal.piso" class="form-select">
              <option v-for="p in 10" :value="p">Piso {{ p }}</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Tipo</label>
            <select v-model="modal.tipo" class="form-select">
              <option>Simple</option><option>Doble</option><option>Suite</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Precio Base (S/)</label>
            <input v-model="modal.precio_base" type="number" step="0.01" class="form-control" placeholder="0.00">
          </div>
        </div>
        <div class="mt-4 d-flex gap-2">
          <button class="btn-primary-custom flex-fill justify-content-center" @click="guardar" :disabled="modal.guardando">
            <i class="bi bi-save-fill"></i> {{ modal.guardando ? 'Guardando...' : 'Guardar' }}
          </button>
          <button class="btn-outline-custom" @click="cerrarModal">Cancelar</button>
        </div>
      </div>
    </div>
  </div>
</div>
</div><!-- /#app-habitaciones -->

<style>
.modal-overlay {
  position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;
  display:flex;align-items:flex-start;justify-content:center;overflow-y:auto;
}
</style>
<script src="index.js"></script>
</body></html>
