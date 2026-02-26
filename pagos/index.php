<?php
// pagos/index.php — Shell PHP para pagos
require_once '../config/conexion.php';
$base = '../'; $page_title = 'Pagos — Hotel Manager';
include '../includes/head.php';
include '../includes/sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <div><h4><i class="bi bi-cash-coin me-2 text-primary"></i>Pagos</h4><p>Registro de cobros</p></div>
    <button class="btn-primary-custom" @click="mostrarForm = !mostrarForm" v-if="!loading">
      <i class="bi bi-plus-circle-fill"></i> Registrar Pago
    </button>
  </div>

  <div class="page-body" id="app-pagos">
    <div class="text-center py-5" v-if="loading"><div class="spinner-border text-primary"></div></div>

    <div v-if="msg.text" class="alert-custom mb-3" :class="msg.ok ? 'alert-success' : 'alert-error'">
      <i :class="msg.ok ? 'bi bi-check-circle-fill' : 'bi bi-exclamation-triangle-fill'"></i> {{ msg.text }}
    </div>

    <!-- Formulario colapsable -->
    <div class="form-card mb-4" v-if="mostrarForm">
      <h6 class="fw-bold mb-3"><i class="bi bi-cash-coin me-2 text-primary"></i>Registrar Pago</h6>
      <div v-if="form.error" class="alert-custom alert-error mb-3"><i class="bi bi-exclamation-triangle-fill"></i> {{ form.error }}</div>
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label">Huésped Activo</label>
          <select v-model="form.registro_id" class="form-select" @change="autoFill">
            <option value="">Seleccionar registro...</option>
            <option v-for="r in activos" :key="r.id" :value="r.id" :data-precio="r.precio">
              Hab. {{ r.hab_num }} — {{ r.cliente }}
            </option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Monto (S/)</label>
          <input v-model="form.monto" type="number" step="0.01" class="form-control" placeholder="0.00">
        </div>
        <div class="col-md-4">
          <label class="form-label">Método</label>
          <select v-model="form.metodo" class="form-select">
            <option value="efectivo">Efectivo</option>
            <option value="tarjeta">Tarjeta</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Fecha</label>
          <input v-model="form.fecha" type="date" class="form-control">
        </div>
      </div>
      <div class="mt-3 d-flex gap-2">
        <button class="btn-primary-custom" @click="guardar" :disabled="form.guardando">
          <i class="bi bi-save-fill"></i> {{ form.guardando ? 'Guardando...' : 'Registrar' }}
        </button>
        <button class="btn-outline-custom" @click="mostrarForm = false">Cancelar</button>
      </div>
    </div>

    <!-- Tabla pagos -->
    <div class="card-table" v-if="!loading">
      <table class="table">
        <thead><tr><th>#</th><th>Habitación</th><th>Cliente</th><th>Monto</th><th>Método</th><th>Fecha</th></tr></thead>
        <tbody>
          <tr v-for="(p, i) in pagos" :key="p.id">
            <td>{{ i+1 }}</td>
            <td><strong>Hab. {{ p.hab_num }}</strong></td>
            <td>{{ p.cliente }}</td>
            <td><strong>{{ fmt(p.monto) }}</strong></td>
            <td><span class="px-badge" :class="p.metodo === 'efectivo' ? 'badge-efectivo' : 'badge-tarjeta'">{{ p.metodo }}</span></td>
            <td>{{ fmtFecha(p.fecha) }}</td>
          </tr>
          <tr v-if="pagos.length === 0"><td colspan="6" class="text-center py-4 text-muted">No hay pagos.</td></tr>
          <tr v-if="pagos.length > 0" style="background:#f0fdf4;font-weight:700">
            <td colspan="3" class="text-end" style="color:var(--success)">Total:</td>
            <td style="color:var(--success)">{{ fmt(totalPagos) }}</td>
            <td colspan="2"></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script src="index.js"></script>
</body></html>
