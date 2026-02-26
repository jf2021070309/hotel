<?php
// gastos/index.php — Shell PHP para gastos
require_once '../config/conexion.php';
$base = '../'; $page_title = 'Gastos — Hotel Manager';
include '../includes/head.php';
include '../includes/sidebar.php';
?>
<div class="main-content" id="app-gastos">
  <div class="topbar">
    <div><h4><i class="bi bi-receipt-cutoff me-2 text-primary"></i>Gastos</h4><p>Control de gastos operativos</p></div>
    <button class="btn-primary-custom" @click="mostrarForm = true" v-if="!loading">
      <i class="bi bi-plus-circle-fill"></i> Nuevo Gasto
    </button>
  </div>

  <div class="page-body">
    <div class="text-center py-5" v-if="loading"><div class="spinner-border text-primary"></div></div>

    <div v-if="msg.text" class="alert-custom mb-3" :class="msg.ok ? 'alert-success' : 'alert-error'">
      <i :class="msg.ok ? 'bi bi-check-circle-fill' : 'bi bi-exclamation-triangle-fill'"></i> {{ msg.text }}
    </div>

    <!-- Modal flotante -->
    <transition name="modal-fade">
      <div class="modal-overlay" v-if="mostrarForm" @click.self="mostrarForm = false">
        <div class="modal-card">
          <div class="modal-card-header">
            <h6 class="fw-bold mb-0"><i class="bi bi-graph-down-arrow me-2 text-danger"></i>Nuevo Gasto</h6>
            <button class="modal-close-btn" @click="mostrarForm = false"><i class="bi bi-x-lg"></i></button>
          </div>
          <div class="modal-card-body">
            <div v-if="form.error" class="alert-custom alert-error mb-3">
              <i class="bi bi-exclamation-triangle-fill"></i> {{ form.error }}
            </div>
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Descripción</label>
                <input v-model="form.descripcion" class="form-control" placeholder="Limpieza, Suministros...">
              </div>
              <div class="col-md-6">
                <label class="form-label">Monto (S/)</label>
                <input v-model="form.monto" type="number" step="0.01" class="form-control" placeholder="0.00">
              </div>
              <div class="col-md-6">
                <label class="form-label">Fecha</label>
                <input v-model="form.fecha" type="date" class="form-control">
              </div>
            </div>
          </div>
          <div class="modal-card-footer">
            <button class="btn-primary-custom" @click="guardar" :disabled="form.guardando">
              <i class="bi bi-save-fill"></i> {{ form.guardando ? 'Guardando...' : 'Registrar' }}
            </button>
            <button class="btn-outline-custom" @click="mostrarForm = false">Cancelar</button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Filtro fecha -->
    <div class="report-card mb-4" style="padding:14px 20px" v-if="!loading">
      <div class="d-flex gap-2 align-items-center flex-wrap">
        <label style="font-size:13px;font-weight:600">Filtrar por fecha:</label>
        <input v-model="filtroFecha" type="date" class="form-control" style="max-width:200px">
        <button class="btn-outline-custom" style="padding:8px 16px" @click="filtroFecha=''">Limpiar</button>
      </div>
    </div>

    <!-- Tabla -->
    <div class="card-table" v-if="!loading">
      <table class="table">
        <thead><tr><th>#</th><th>Descripción</th><th>Monto</th><th>Fecha</th><th class="text-end">Acción</th></tr></thead>
        <tbody>
          <tr v-for="(g, i) in gastosFiltrados" :key="g.id">
            <td>{{ i+1 }}</td>
            <td>{{ g.descripcion }}</td>
            <td><strong style="color:var(--danger)">{{ fmt(g.monto) }}</strong></td>
            <td>{{ fmtFecha(g.fecha) }}</td>
            <td class="text-end">
              <button class="btn-danger-custom btn-sm" @click="eliminar(g.id)" title="Eliminar">
                <i class="bi bi-trash-fill"></i>
              </button>
            </td>
          </tr>
          <tr v-if="gastosFiltrados.length === 0"><td colspan="5" class="text-center py-4 text-muted">Sin gastos.</td></tr>
          <tr v-if="gastosFiltrados.length > 0" style="background:#fff3f3;font-weight:700">
            <td colspan="2" class="text-end" style="color:var(--danger)">Total:</td>
            <td style="color:var(--danger)">{{ fmt(totalGastos) }}</td><td colspan="2"></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<style>
/* ── Modal overlay ── */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  padding: 1rem;
}

.modal-card {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 24px 60px rgba(0,0,0,0.18), 0 8px 20px rgba(0,0,0,0.10);
  width: 100%;
  max-width: 520px;
  overflow: hidden;
}

.modal-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e9ecef;
  background: #f8fafc;
}

.modal-close-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: #6b7280;
  font-size: 1rem;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  transition: background .2s, color .2s;
  line-height: 1;
}
.modal-close-btn:hover { background: #fee2e2; color: #dc2626; }

.modal-card-body { padding: 1.5rem; }

.modal-card-footer {
  display: flex;
  gap: .75rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid #e9ecef;
  background: #f8fafc;
}

/* ── Animación entrada/salida ── */
.modal-fade-enter-active { animation: modalIn .22s cubic-bezier(.34,1.56,.64,1); }
.modal-fade-leave-active { animation: modalOut .18s ease-in; }

@keyframes modalIn {
  from { opacity: 0; transform: scale(.92) translateY(-14px); }
  to   { opacity: 1; transform: scale(1)   translateY(0); }
}
@keyframes modalOut {
  from { opacity: 1; transform: scale(1)   translateY(0); }
  to   { opacity: 0; transform: scale(.94) translateY(-8px); }
}
</style>

<script src="index.js"></script>
</body></html>
