<?php
// habitaciones/index.php — Shell PHP, Vue monta tabla de habitaciones
require_once '../../../config/db.php';
require_once '../../../app/Middleware/auth.php';
$base = '../../../'; $page_title = 'Habitaciones — Hotel Manager'; $export_enabled = true;
include '../../../app/Views/layouts/head.php';
include '../../../app/Views/layouts/sidebar.php';
?>
<div id="app-habitaciones" style="display:contents">
<div class="main-content">
  <div class="topbar border-bottom-0 shadow-sm" style="background: linear-gradient(to right, #ffffff, #f8f9fa);">
    <button class="btn-burger" onclick="handleMenuClick()"><i class="bi bi-list fs-4"></i></button>
    <div>
      <h4 class="fw-bold" style="color: #111; letter-spacing: -0.5px;">
        <i class="bi bi-building me-2" style="color: #d4af37;"></i>Habitaciones
      </h4>
      <p class="mb-0 small text-muted fw-semibold">Gestión de habitaciones del hotel</p>
    </div>
    <div class="ms-auto d-flex flex-wrap gap-1 justify-content-end">
      <button class="btn btn-sm btn-primary shadow-sm px-2 px-sm-3" @click="abrirModal(null)" title="Nueva Habitación" style="border: 1px solid #111; font-weight: 700; font-size: 11px;">
        <i class="bi bi-plus-circle-fill text-warning me-1"></i>
        <span>NUEVA</span>
      </button>
      <button class="btn btn-sm btn-outline-danger shadow-sm px-2" @click="exportarPDF" title="Exportar PDF">
        <i class="bi bi-file-earmark-pdf-fill"></i>
      </button>
      <button class="btn btn-sm btn-outline-success shadow-sm px-2" @click="exportarExcel" title="Exportar Excel">
        <i class="bi bi-file-earmark-excel-fill"></i>
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

    <!-- Filtro y Acciones Rápidas -->
    <div class="row g-2 mb-3 align-items-center" v-if="!loading">
      
      <div class="col-6 col-sm-auto">
        <select v-model="filtros.estado" class="form-select form-select-sm bg-white border-1 shadow-sm text-secondary fw-bold" style="font-size: 11px;">
          <option value="">Estado: Todos</option>
          <option value="libre">Libre</option>
          <option value="ocupado">Ocupado</option>
          <option value="reservado">Reservado</option>
          <option value="limpieza">Limpieza</option>
          <option value="sucio">Sucio</option>
          <option value="mantenimiento">Mantenimiento</option>
        </select>
      </div>

      <div class="col-6 col-sm-auto">
        <select v-model="filtros.tipo" class="form-select form-select-sm bg-white border-1 shadow-sm text-secondary fw-bold" style="font-size: 11px;">
          <option value="">Tipo: Todos</option>
          <option v-for="t in tiposUnicos" :key="t" :value="t">{{ t }}</option>
        </select>
      </div>

      <div class="col-6 col-sm-auto">
        <select v-model="filtros.piso" class="form-select form-select-sm bg-white border-1 shadow-sm text-secondary fw-bold" style="font-size: 11px;">
          <option value="">Piso: Todos</option>
          <option v-for="p in pisosUnicos" :key="p" :value="p">Piso {{ p }}</option>
        </select>
      </div>

      <div class="col-6 col-sm-auto ms-sm-auto">
        <div class="input-group input-group-sm rounded shadow-sm border-1" style="border: 1px solid #e2e8f0;">
          <span class="input-group-text bg-white border-0 text-muted"><i class="bi bi-search"></i></span>
          <input type="text" class="form-control border-0 bg-white fw-bold text-secondary" style="font-size: 11px;" placeholder="Buscar..." v-model="searchQuery">
        </div>
      </div>
    </div>

    <!-- Tabla -->
    <div class="card border-0 shadow-sm" v-if="!loading" style="border-radius:10px; overflow:hidden;">
      <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle mb-0 text-sm" style="white-space: nowrap; background: #fff;">
        <thead class="table-dark text-white text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
          <tr>
            <th class="text-center" style="width: 50px;">#</th>
            <th>Número</th>
            <th>Tipo</th>
            <th>Piso</th>
            <th class="text-center">Estado</th>
            <th class="text-end">Precio Base</th>
            <th class="text-end pe-3">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(h, i) in habitacionesFiltradas" :key="h.id" class="bg-white">
            <td class="text-center text-muted">{{ i+1 }}</td>
            <td>
              <span class="fw-bold fs-6" style="color: #0f172a;"><i class="bi bi-door-closed me-1 text-secondary"></i> {{ h.numero }}</span>
            </td>
            <td class="fw-semibold text-secondary" style="font-size: 0.85rem;">{{ h.tipo }}</td>
            <td>
              <span class="px-badge shadow-sm" :style="colorPiso(h.piso)" style="font-size: 0.73rem; letter-spacing: 0.5px;">
                <i class="bi bi-layers-half me-1" style="opacity:0.8"></i> PISO {{ h.piso }}
              </span>
            </td>
            <td class="text-center">
              <span class="px-badge" :class="{
                'badge-libre': h.estado === 'libre',
                'badge-ocupado': h.estado === 'ocupado',
                'badge-reservado': h.estado === 'reservado',
                  'badge-limpieza': h.estado === 'limpieza',
                  'badge-sucio': h.estado === 'sucio',
                'badge-mantenimiento': h.estado === 'mantenimiento'
              }">
                {{ h.estado.charAt(0).toUpperCase() + h.estado.slice(1) }}
              </span>
            </td>
            <td class="text-end fw-bold text-success fs-6">S/ {{ parseFloat(h.precio_base).toFixed(2) }}</td>
            <td class="text-end pe-3">
              <button @click="abrirModal(h)" class="btn btn-sm btn-outline-primary shadow-sm" style="font-size: 11.5px; font-weight: 600;">
                <i class="bi bi-pencil-square"></i> Editar
              </button>
            </td>
          </tr>
          <tr v-if="habitacionesFiltradas.length === 0">
            <td colspan="7" class="text-center py-4 text-muted">
              <i class="bi bi-info-circle me-1"></i> No se encontraron habitaciones con esos criterios.
            </td>
          </tr>
        </tbody>
      </table>
      </div>
    </div>

    <!-- Modal Crear/Editar -->
    <div v-if="modal.visible" class="modal-overlay px-2 py-3" @click.self="cerrarModal">
      <div class="form-card w-100 shadow-lg" style="max-width:550px; border-top: 4px solid #111; border-radius: 12px; overflow: hidden; background: #fff;">
        <div style="background: linear-gradient(135deg, #0d0d0d, #1a1a1a); color: #fff; padding: 15px 25px; display: flex; align-items: center;">
          <h6 class="fw-bold mb-0" style="letter-spacing: 1px; color: #d4af37;"><i class="bi bi-door-open-fill me-2"></i>{{ modal.id ? 'EDITAR' : 'NUEVA' }}</h6>
          <button type="button" class="btn-close btn-close-white ms-auto" @click="cerrarModal" style="font-size: 12px;"></button>
        </div>
        <div style="padding: 20px;">
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
              <option value="limpieza">⚪ LIMPIEZA (PL)</option>
              <option value="sucio">🔵 SUCIO / PENDIENTE</option>
              <option value="mantenimiento">🔴 MANTENIMIENTO</option>
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
  position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:2000;
  display:flex;align-items:center;justify-content:center;overflow-y:auto;
}
.px-badge {
  display: inline-block; padding: 0.3em 0.6em; font-size: 0.7rem; font-weight: 700;
  line-height: 1; text-align: center; white-space: nowrap; border-radius: 50rem;
  letter-spacing: 0.3px;
}
.badge-libre { background-color: #d1fae5; color: #065f46; border: 1px solid #34d399; }
.badge-ocupado { background-color: #fee2e2; color: #991b1b; border: 1px solid #f87171; }
.badge-reservado { background-color: #fef3c7; color: #92400e; border: 1px solid #fbbf24; }
.badge-limpieza { background-color: #9ca3af; color: #ffffff; border: 1px solid #6b7280; }
.badge-sucio { background-color: #8d6e63; color: #ffffff; border: 1px solid #5d4037; }
.badge-mantenimiento { background-color: #e53935; color: #ffffff; border: 1px solid #b71c1c; }
.text-sm { font-size: 0.85rem; }
.table-hover tbody tr:hover { background-color: #f8f9fa !important; }

@media (max-width: 576px) {
  .topbar h4 { font-size: 1.1rem; }
  .topbar p { display: none; }
  .main-content { padding: 10px !important; }
}
</style>
<script src="index.js?v=<?= time() ?>"></script>
</body></html>
