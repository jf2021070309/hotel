<?php
// habitaciones/index.php — Shell PHP, Vue monta tabla de habitaciones
require_once '../../../config/conexion.php';
require_once '../../../auth/middleware.php';
$base = '../../../'; $page_title = 'Habitaciones — Hotel Manager'; $export_enabled = true;
include '../../../includes/head.php';
include '../../../includes/sidebar.php';
?>
<div id="app-habitaciones" style="display:contents">
<div class="main-content">
  <div class="topbar border-bottom-0 shadow-sm" style="background: linear-gradient(to right, #ffffff, #f8f9fa);">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list fs-4"></i></button>
    <div>
      <h4 class="fw-bold" style="color: #111; letter-spacing: -0.5px;">
        <i class="bi bi-building me-2" style="color: #d4af37;"></i>Habitaciones
      </h4>
      <p class="mb-0 small text-muted fw-semibold">Gestión de habitaciones del hotel</p>
    </div>
    <div class="d-flex gap-2 align-items-center ms-auto">
      <button class="btn-primary-custom shadow-sm" @click="abrirModal(null)" title="Nueva Habitación" style="border: 1px solid #111;">
        <i class="bi bi-plus-circle-fill text-warning"></i>
        <span class="d-none d-sm-inline"> Nueva Habitación</span>
      </button>
      <button class="btn-outline-custom shadow-sm" @click="exportarPDF" title="Exportar PDF">
        <i class="bi bi-file-earmark-pdf-fill text-danger"></i>
        <span class="d-none d-md-inline"> PDF</span>
      </button>
      <button class="btn-outline-custom shadow-sm" @click="exportarExcel" title="Exportar Excel">
        <i class="bi bi-file-earmark-excel-fill text-success"></i>
        <span class="d-none d-md-inline"> Excel</span>
      </button>
    </div>
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
              <span class="px-badge" :class="{
                'badge-libre': h.estado === 'libre',
                'badge-ocupado': h.estado === 'ocupado',
                'badge-reservado': h.estado === 'reservado',
                'badge-limpieza': h.estado === 'limpieza',
                'badge-mantenimiento': h.estado === 'mantenimiento'
              }">
                {{ h.estado.charAt(0).toUpperCase() + h.estado.slice(1) }}
              </span>
            </td>
            <td>S/ {{ parseFloat(h.precio_base).toFixed(2) }}</td>
            <td class="text-end">
              <button @click="abrirModal(h)" class="btn-outline-custom btn-sm me-1">
                <i class="bi bi-pencil-fill"></i> Editar
              </button>
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
      <div class="form-card" style="position:relative;max-width:550px;margin:auto;margin-top:60px; border-top: 4px solid #111; border-radius: 12px; padding: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
        <div style="background: linear-gradient(135deg, #0d0d0d, #1a1a1a); color: #fff; padding: 20px 32px; border-radius: 12px 12px 0 0; display: flex; align-items: center;">
          <h5 class="fw-bold mb-0" style="letter-spacing: 1px; color: #d4af37;"><i class="bi bi-door-open-fill me-2"></i>{{ modal.id ? 'Editar Habitación' : 'Nueva Habitación' }}</h5>
        </div>
        <div style="padding: 32px;">
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
              <optgroup label="Tipos Estándar">
                <option>SIMPLE</option>
                <option>DOBLE</option>
                <option>TRIPLE</option>
                <option>TRIPLE FAMILIAR</option>
              </optgroup>
              <optgroup label="Tipos Premier">
                <option>MATRIMONIAL SUPERIOR</option>
                <option>EJECUTIVA SUPERIOR</option>
                <option>PLATINIUM SUITE</option>
              </optgroup>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Precio Base (S/)</label>
            <input v-model="modal.precio_base" type="number" step="0.01" class="form-control" placeholder="0.00">
          </div>
          <!-- NUEVO: Cambio de Estado Manual -->
          <div class="col-md-12" v-if="modal.id">
            <label class="form-label text-primary fw-bold">Estado Actual (Semáforo)</label>
            <select v-model="modal.estado" class="form-select border-primary">
              <option value="libre">🟢 LIBRE Y LIMPIA</option>
              <option value="ocupado">🔴 OCUPADA</option>
              <option value="reservado">🟡 RESERVADA</option>
              <option value="limpieza">🔵 SUCIA / PENDIENTE</option>
              <option value="mantenimiento">⚫ BLOQUEADA / MANTENIMIENTO</option>
            </select>
          </div>
        </div>
        <div class="mt-4 d-flex gap-2">
          <button class="btn-primary-custom flex-fill justify-content-center" @click="guardar" :disabled="modal.guardando">
            <i class="bi bi-save-fill"></i> {{ modal.guardando ? 'Guardando...' : 'Guardar' }}
          </button>
          <button class="btn-outline-custom" @click="cerrarModal">Cancelar</button>
        </div>
        </div> <!-- end padding div -->
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
.badge-libre { background: #198754; color: white; }
.badge-ocupado { background: #dc3545; color: white; }
.badge-reservado { background: #ffc107; color: #000; }
.badge-limpieza { background: #0dcaf0; color: white; }
.badge-mantenimiento { background: #212529; color: white; }
</style>
<script src="index.js?v=<?= time() ?>"></script>
</body></html>
