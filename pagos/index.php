<?php
// pagos/index.php — Shell PHP para pagos
require_once '../config/conexion.php';
$base = '../'; $page_title = 'Pagos — Hotel Manager'; $export_enabled = true;
include '../includes/head.php';
include '../includes/sidebar.php';
?>
<div class="main-content" id="app-pagos">
  <div class="topbar">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
    <div><h4><i class="bi bi-cash-coin me-2 text-primary"></i>Pagos</h4><p>Registro de cobros</p></div>
    <div class="d-flex gap-2 flex-wrap">
      <button class="btn-primary-custom" onclick="__appPagos?.mostrarForm !== undefined && (__appPagos.mostrarForm = true)" title="Registrar Pago">
        <i class="bi bi-plus-circle-fill"></i> Registrar Pago
      </button>
      <button class="btn-outline-custom" onclick="__appPagos?.exportarPDF()" title="Exportar PDF">
        <i class="bi bi-file-earmark-pdf-fill text-danger"></i> PDF
      </button>
      <button class="btn-outline-custom" onclick="__appPagos?.exportarExcel()" title="Exportar Excel">
        <i class="bi bi-file-earmark-excel-fill text-success"></i> Excel
      </button>
    </div>
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
            <h6 class="fw-bold mb-0"><i class="bi bi-cash-coin me-2 text-primary"></i>Registrar Pago</h6>
            <button class="modal-close-btn" @click="mostrarForm = false"><i class="bi bi-x-lg"></i></button>
          </div>
          <div class="modal-card-body">
            <div v-if="form.error" class="alert-custom alert-error mb-3">
              <i class="bi bi-exclamation-triangle-fill"></i> {{ form.error }}
            </div>
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Huésped Activo</label>
                <select v-model="form.registro_id" class="form-select" @change="autoFill">
                  <option value="">Seleccionar registro...</option>
                  <option v-for="r in activos" :key="r.id" :value="r.id">
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
  max-width: 560px;
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
