<?php
/**
 * app/Views/admin/medios_pago.php
 */
require_once __DIR__ . '/../../../app/Middleware/auth.php';
protegerPorRol('admin', 'medios_pago');

require_once __DIR__ . '/../../../config/db.php';

$page_title = 'Medios de Pago — Hotel Manager';
include __DIR__ . '/../layouts/head.php';
?>

<div id="app-medios-pago" style="display:contents" v-cloak>
  <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
  
  <div class="main-content">
    <!-- TOPBAR PREMIUM DARK -->
    <div class="topbar" style="background-color:#111827;padding:0.75rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.05);">
      <div class="d-flex align-items-center justify-content-between w-100">
        <div class="d-flex align-items-center gap-3">
          <button class="btn btn-dark btn-sm rounded-circle d-md-none" onclick="handleMenuClick()" style="width:32px;height:32px;padding:0;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.1);border:none;">
            <i class="bi bi-list text-white"></i>
          </button>
          <div class="d-flex align-items-center gap-3">
            <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#f8fafc,#94a3b8);display:flex;align-items:center;justify-content:center;box-shadow:0 0 15px rgba(148,163,184,0.4);">
              <i class="bi bi-credit-card-2-back-fill text-dark fs-5"></i>
            </div>
            <div>
              <h4 class="fw-bold mb-0 text-white" style="font-size:18px;letter-spacing:-0.5px;">Medios de Pago</h4>
              <div class="text-white-50" style="font-size:11px;">Configuración de métodos de cobro estilo Excel</div>
            </div>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-sm btn-outline-light d-flex align-items-center gap-2" @click="fetchMedios" :disabled="loading" style="font-size:12px;padding:4px 12px;border-color:rgba(255,255,255,0.2);">
            <i class="bi bi-arrow-clockwise" :class="{'spin-anim': loading}"></i>
            <span class="d-none d-md-inline">Actualizar</span>
          </button>
        </div>
      </div>
    </div>

    <!-- BODY -->
    <div class="page-body pt-3">
      <!-- CONTROL BAR & ACCIONES -->
      <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
        <div class="card-body p-3">
          <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
              <div class="input-group input-group-sm rounded shadow-sm" style="width: 300px;">
                <span class="input-group-text bg-white border-end-0 text-muted px-2"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-start-0 bg-white text-dark" 
                       style="font-size: 13px;" v-model="busqueda" placeholder="Buscar medio de pago...">
              </div>
              <div class="text-muted fw-semibold" style="font-size: 12px;" v-if="!loading">
                <i class="bi bi-list-ul me-1"></i>{{ mediosFiltrados.length }} registros
              </div>
            </div>
            
            <div class="d-flex align-items-center gap-2">
              <button class="btn btn-sm btn-custom-blue fw-bold px-3 shadow-sm" @click="guardarCambiosMasivos" :disabled="loading || cambiosCount === 0" style="font-size: 12px;">
                <i class="bi bi-save me-1"></i>Guardar Cambios
                <span v-if="cambiosCount > 0" class="badge bg-warning text-dark ms-1" style="font-size: 10px;">{{ cambiosCount }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- GRID INTERACTIVE CONTAINER -->
      <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 12px;">
        <div class="mensual-grid-container">
          <table class="table table-bordered table-hover mb-0 align-middle table-mensual">
            <thead class="table-dark text-white text-uppercase text-center" style="font-size: 10px; letter-spacing: 0.5px;">
              <tr>
                <th class="sticky-col" style="padding: 12px 16px; width: 50px; z-index: 12 !important;"><i class="bi bi-trash"></i></th>
                <th class="sticky-col" style="padding: 12px 16px; width: 70px; z-index: 12 !important;">ID</th>
                <th style="padding: 12px 16px; width: 100px;">ORDEN</th>
                <th style="padding: 12px 16px; min-width: 300px;">NOMBRE / DETALLE</th>
                <th style="padding: 12px 16px; width: 150px;">ESTADO</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="5" class="text-center py-5">
                  <div class="spinner-border text-primary me-2"></div>
                  <span class="text-muted fw-semibold">Cargando medios de pago...</span>
                </td>
              </tr>
              <tr v-else-if="mediosFiltrados.length === 0">
                <td colspan="5" class="text-center py-5 text-muted">
                  <i class="bi bi-credit-card-2-back fs-1 d-block opacity-25 mb-2"></i>
                  <span>No se encontraron medios de pago.</span>
                </td>
              </tr>
              <tr v-else v-for="(m, idx) in mediosFiltrados" :key="m.id || m.temp_id" :class="{'unsaved-row': m.modificado || !m.id}">
                <!-- ELIMINAR -->
                <td class="sticky-col text-center px-1">
                  <button class="btn btn-sm btn-link text-danger p-0" @click="eliminarFila(m, idx)" title="Eliminar registro">
                    <i class="bi bi-trash-fill fs-6"></i>
                  </button>
                </td>
                
                <!-- ID -->
                <td class="sticky-col text-center px-1">
                  <span v-if="m.id" class="badge bg-light text-dark border">#{{ m.id }}</span>
                  <span v-else class="badge bg-warning text-dark border">Nuevo</span>
                </td>
                
                <!-- ORDEN -->
                <td class="px-1">
                  <input type="number" v-model="m.orden" class="table-editable-input fw-bold text-dark text-center" @input="marcarModificado(m)" style="width: 100%;" min="0">
                </td>

                <!-- NOMBRE -->
                <td class="px-1">
                  <input type="text" v-model="m.nombre" class="table-editable-input text-dark fw-bold text-uppercase" @input="marcarModificado(m)" style="width: 100%;">
                </td>
                
                <!-- ESTADO -->
                <td class="px-1">
                  <select v-model="m.activo" class="table-editable-input fw-bold text-dark" @change="marcarModificado(m)" style="width: 100%; cursor: pointer;">
                    <option :value="1">Activo</option>
                    <option :value="0">Inactivo</option>
                  </select>
                </td>
              </tr>

              <!-- Fila adicional para "Añadir Fila" -->
              <tr v-if="!loading && busqueda === ''">
                <td class="sticky-col text-center px-1" style="background-color: #f8fafc;">
                  <button class="btn btn-sm text-primary p-0 w-100 d-flex align-items-center justify-content-center hover-bg-premium" 
                          @click="agregarFila" title="Añadir nuevo medio" 
                          style="min-height: 28px; border: 2px dashed #93c5fd; background-color: #eff6ff; border-radius: 4px;">
                    <i class="bi bi-plus-lg fw-bold" style="font-size: 15px;"></i>
                  </button>
                </td>
                <td colspan="4" class="bg-light text-muted" style="vertical-align: middle; padding-left: 15px; font-size: 11px;">
                  Haga clic en el botón <i class="bi bi-plus-lg text-primary fw-bold"></i> para agregar un nuevo medio de pago al final de la tabla. Al guardar, se le asignará el siguiente orden disponible.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div> <!-- /page-body -->
  </div> <!-- /main-content -->
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  window.authUser = <?= json_encode(['id' => $_SESSION['usuario_id'] ?? null, 'rol' => $_SESSION['rol'] ?? null]) ?>;
</script>
<script src="medios_pago.js?v=<?= time() ?>"></script>

<style>
  [v-cloak] { display: none !important; }
  body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
  
  /* GRID STYLES */
  .mensual-grid-container {
    overflow-x: auto;
    background: #fff;
    position: relative;
  }
  .table-mensual {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
  }
  .table-mensual thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    font-size: 12px;
    font-weight: 700;
    padding: 10px 8px;
    box-shadow: 0 1px 0 rgba(0,0,0,0.1);
    vertical-align: middle;
    color: #ffffff !important;
  }
  .table-mensual tbody td {
    padding: 0;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
    background-color: #fff;
    transition: background-color 0.2s;
  }
  
  .sticky-col {
    position: sticky;
    left: 0;
    background-color: #f8fafc !important;
    z-index: 5;
    border-right: 1px solid #e2e8f0 !important;
  }
  .table-mensual thead th.sticky-col {
    z-index: 15 !important;
    background-color: #212529 !important;
    border-right: none !important;
  }

  /* Eliminar bordes internos entre las dos filas del encabezado */
  .table-mensual thead tr:first-child th {
    border-bottom: none !important;
  }
  .table-mensual thead tr:last-child th {
    border-top: none !important;
  }
  /* Forzar que los bordes laterales internos del thead sean invisibles */
  .table-mensual thead th {
    border-left: none !important;
    border-right: none !important;
  }
  /* Excepto la línea divisoria entre grupos de color */
  .table-mensual thead th[style*="border-left"] {
    border-left: 1px solid rgba(255,255,255,0.15) !important;
  }
  
  /* INPUTS ESTILO EXCEL */
  .table-editable-input {
    width: 100%;
    min-width: 60px;
    border: 1px solid transparent;
    background-color: transparent;
    padding: 10px 8px;
    font-size: 12.5px;
    color: #1e293b;
    border-radius: 0;
    outline: none;
    transition: all 0.2s;
  }
  .table-editable-input:focus, .table-editable-input:hover:not(:disabled) {
    background-color: #f8fafc;
    border-color: #cbd5e1;
    border-radius: 4px;
  }
  .table-editable-input:disabled {
    color: #94a3b8 !important;
    background-color: #f1f5f9;
    cursor: not-allowed;
  }
  select.table-editable-input {
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.5rem center;
    background-size: 16px 12px;
    padding-right: 2rem;
  }
  
  /* ESTADOS */
  .unsaved-row td {
    background-color: #fffbeb !important;
  }
  .unsaved-row .sticky-col {
    background-color: #fef3c7 !important;
  }
  
  .btn-custom-blue {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;
    border: none;
  }
  .btn-custom-blue:hover:not(:disabled) {
    background: linear-gradient(135deg, #1e40af, #1e3a8a);
    color: #fff;
  }
  .spin-anim {
    animation: spin 1s linear infinite;
  }
  @keyframes spin { 100% { transform: rotate(360deg); } }
  
  .hover-bg-premium:hover { background-color: #e0e7ff !important; color: #3730a3 !important; }
  .hover-bg-danger:hover { background-color: #fee2e2 !important; color: #b91c1c !important; }
</style>

</body></html>
