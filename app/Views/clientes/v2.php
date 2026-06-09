<?php
/**
 * app/Views/clientes/v2.php
 * Vista de la grilla plana Clientes V2.
 */
$base = '../../../';
$_projectRoot = dirname(__DIR__, 3);
require_once $_projectRoot . '/app/Middleware/auth.php';
protegerPorRol('cajera', 'clientes');

require_once $_projectRoot . '/config/db.php'; // Asegurar PDO

$page_title = 'Clientes V2 — Hotel Manager';
include $_projectRoot . '/app/Views/layouts/head.php';
?>

<div id="app-clientes-v2" style="display:contents" v-cloak>
  <?php include $_projectRoot . '/app/Views/layouts/sidebar.php'; ?>
  
  <div class="main-content">
    <!-- TOPBAR PREMIUM DARK -->
    <div class="topbar" style="background-color: #111827; padding: 0.75rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.05);">
      <div class="d-flex align-items-center justify-content-between w-100">
        <div class="d-flex align-items-center gap-3">
          <button class="btn btn-dark btn-sm rounded-circle d-md-none" onclick="handleMenuClick()" style="width:32px;height:32px;padding:0;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.1);border:none;">
            <i class="bi bi-list text-white"></i>
          </button>
          <div class="d-flex align-items-center gap-3">
            <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#f8fafc,#94a3b8);display:flex;align-items:center;justify-content:center;box-shadow:0 0 15px rgba(148,163,184,0.4);">
              <i class="bi bi-people-fill text-dark fs-5"></i>
            </div>
            <div>
              <h4 class="fw-bold mb-0 text-white" style="font-size:18px;letter-spacing:-0.5px;">Pax Frecuentes</h4>
              <div class="text-white-50" style="font-size:11px;">Edición directa de todos los clientes estilo Excel</div>
            </div>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-sm btn-outline-light d-flex align-items-center gap-2" @click="cargarDatos" :disabled="loading" style="font-size:12px;padding:4px 12px;border-color:rgba(255,255,255,0.2);">
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
            <!-- Izquierda: Buscador y conteo -->
            <div class="d-flex align-items-center gap-3">
              <div class="input-group input-group-sm rounded shadow-sm" style="width: 380px;">
                <span class="input-group-text bg-white border-end-0 text-muted px-2"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-start-0 bg-white text-dark" 
                       style="font-size: 13px;" v-model="busqueda" placeholder="Buscar por Nombre, DNI, RUC, Empresa o Celular...">
              </div>
              
              <div class="text-muted fw-semibold" style="font-size: 12px;" v-if="!loading">
                <i class="bi bi-list-ul me-1"></i>{{ filasFiltradas.length }} registros
              </div>
            </div>
            
            <!-- Derecha: Acciones masivas -->
            <div class="d-flex align-items-center gap-2">
              <button class="btn btn-sm btn-custom-blue fw-bold px-3 shadow-sm" @click="guardarCambios" :disabled="loading || filas.length === 0" style="font-size: 12px;">
                <i class="bi bi-save me-1"></i>Guardar Cambios
                <span v-if="cambiosCount > 0" class="badge bg-warning text-dark ms-1" style="font-size: 10px;">{{ cambiosCount }}</span>
              </button>
              
              <button class="btn btn-sm btn-custom-green fw-bold px-3 shadow-sm" @click="exportarExcel" :disabled="loading || filas.length === 0" style="font-size: 12px;">
                <i class="bi bi-file-earmark-excel me-1"></i>Exportar Excel
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
                <th class="sticky-col" style="padding: 12px 16px; min-width: 60px; width: 60px; z-index: 12 !important;"><i class="bi bi-trash"></i></th>
                <th style="padding: 12px 16px; min-width: 600px; width: 600px;">NOMBRE</th>
                <th style="padding: 12px 16px; min-width: 200px; width: 200px;">DNI</th>
                <th style="padding: 12px 16px; min-width: 200px; width: 200px;">NACIONALIDAD</th>
                <th style="padding: 12px 16px; min-width: 200px; width: 200px;">CIUDAD</th>
                <th style="padding: 12px 16px; min-width: 200px; width: 200px;">CELULAR</th>
                <th style="padding: 12px 16px; min-width: 400px; width: 400px;">EMAIL</th>
                <th style="padding: 12px 16px; min-width: 200px; width: 200px;">RUC</th>
                <th style="padding: 12px 16px; min-width: 600px; width: 600px;">EMPRESA</th>
                <th style="padding: 12px 16px; min-width: 250px; width: 250px;">ACCIONES</th>
              </tr>
            </thead>
            
            <tbody>
              <!-- Spinner de carga -->
              <tr v-if="loading">
                <td colspan="10" class="text-center py-5">
                  <div class="spinner-border text-primary me-2"></div>
                  <span class="text-muted fw-semibold">Cargando registros...</span>
                </td>
              </tr>
              
              <!-- Sin datos -->
              <tr v-else-if="filasFiltradas.length === 0">
                <td colspan="10" class="text-center py-5 text-muted">
                  <i class="bi bi-inbox fs-1 d-block opacity-25 mb-2"></i>
                  <span>No se encontraron clientes.</span>
                </td>
              </tr>
              
              <!-- Filas de datos -->
              <tr v-else v-for="(f, idx) in filasFiltradas" :key="f.id || f.temp_id" :class="{'unsaved-row': f.modificado || !f.id}">
                <!-- Botón Eliminar Fila (Sticky 1) -->
                <td class="sticky-col text-center px-1">
                  <button class="btn btn-sm btn-link text-danger p-0" @click="eliminarFila(f, idx)" title="Eliminar registro">
                    <i class="bi bi-trash-fill fs-6"></i>
                  </button>
                </td>
                
                <!-- NOMBRE -->
                <td class="px-1">
                  <input type="text" v-model="f.nombre" class="table-editable-input fw-bold text-dark text-uppercase" @input="marcarModificado(f)" style="width: 100%;">
                </td>

                <!-- DNI -->
                <td class="px-1 position-relative">
                  <div class="d-flex align-items-center">
                    <input type="text" v-model="f.dni" class="table-editable-input text-center fw-bold text-primary" 
                           @input="marcarModificado(f); lookupDni(f, idx)" 
                           placeholder="DNI..." style="width: 100%;" maxlength="15">
                    <span v-if="lookupLoading[idx]" class="spinner-border spinner-border-sm text-primary ms-1" style="width: 12px; height: 12px;"></span>
                  </div>
                </td>
                
                <!-- NACIONALIDAD -->
                <td class="px-1">
                  <input type="text" v-model="f.nacionalidad" class="table-editable-input" @input="marcarModificado(f)" placeholder="Ej: Peruana" style="width: 100%;">
                </td>

                <!-- CIUDAD -->
                <td class="px-1">
                  <input type="text" v-model="f.ciudad" class="table-editable-input" @input="marcarModificado(f)" placeholder="Ej: Lima" style="width: 100%;">
                </td>
                
                <!-- CELULAR -->
                <td class="px-1">
                  <input type="text" v-model="f.celular" class="table-editable-input text-center" @input="marcarModificado(f)" placeholder="Celular..." style="width: 100%;">
                </td>

                <!-- EMAIL -->
                <td class="px-1">
                  <input type="text" v-model="f.email" class="table-editable-input" @input="marcarModificado(f)" placeholder="ejemplo@correo.com" style="width: 100%;">
                </td>

                <!-- RUC -->
                <td class="px-1 position-relative">
                  <div class="d-flex align-items-center">
                    <input type="text" v-model="f.ruc" class="table-editable-input text-center fw-bold text-success" 
                           @input="marcarModificado(f); lookupRuc(f, idx)" 
                           placeholder="RUC..." style="width: 100%;" maxlength="20">
                    <span v-if="lookupRucLoading[idx]" class="spinner-border spinner-border-sm text-success ms-1" style="width: 12px; height: 12px;"></span>
                  </div>
                </td>
                
                <!-- EMPRESA -->
                <td class="px-1">
                  <input type="text" v-model="f.empresa" class="table-editable-input fw-bold text-dark text-uppercase" @input="marcarModificado(f)" style="width: 100%;">
                </td>

                <!-- ACCIONES -->
                <td class="text-center px-1">
                  <div class="d-flex justify-content-center gap-1">
                    <button @click="crearEstadiaRapida(f)" class="btn btn-sm btn-white border hover-bg-premium text-dark fw-semibold d-flex align-items-center gap-1" style="font-size: 11px; padding: 4px 8px;" title="Check-in rápido">
                      <i class="bi bi-person-check-fill text-success" style="font-size: 13px;"></i> Check-in
                    </button>
                    <button @click="crearReservaRapida(f)" class="btn btn-sm btn-white border hover-bg-premium text-dark fw-semibold d-flex align-items-center gap-1" style="font-size: 11px; padding: 4px 8px;" title="Reserva rápida">
                      <i class="bi bi-calendar-plus-fill text-warning" style="font-size: 13px;"></i> Reserva
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Fila adicional para "Añadir Fila" -->
              <tr v-if="!loading && busqueda === ''">
                <td class="sticky-col text-center px-1" style="background-color: #f8fafc;">
                  <button class="btn btn-sm text-primary p-0 w-100 d-flex align-items-center justify-content-center hover-bg-premium" 
                          @click="agregarFila" title="Añadir nueva fila" 
                          style="min-height: 28px; border: 2px dashed #93c5fd; background-color: #eff6ff; border-radius: 4px;">
                    <i class="bi bi-plus-lg fw-bold" style="font-size: 15px;"></i>
                  </button>
                </td>
                <td colspan="9" class="bg-light text-muted" style="vertical-align: middle; padding-left: 15px; font-size: 11px;">
                  Haga clic en el botón <i class="bi bi-plus-lg text-primary fw-bold"></i> de la izquierda para agregar un nuevo registro al final de la tabla.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  [v-cloak] { display: none !important; }
  
  .mensual-grid-container {
    max-height: calc(100vh - 145px);
    overflow: auto;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    background-color: #f8fafc;
  }
  
  /* Scrollbar elegante */
  .mensual-grid-container::-webkit-scrollbar {
    width: 10px;
    height: 10px;
  }
  .mensual-grid-container::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
  }
  .mensual-grid-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
    border: 2px solid #f1f5f9;
  }
  .mensual-grid-container::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
  }

  .table-mensual {
    width: 3000px !important;
    min-width: 3000px !important;
    max-width: none !important;
    font-size: 11px;
    border-collapse: separate;
    border-spacing: 0;
    table-layout: fixed !important;
  }
  
  /* Sticky de headers */
  .table-mensual thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    color: #ffffff !important;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.5px;
    text-align: center;
    border: 1px solid #cbd5e1;
    vertical-align: middle;
    padding: 10px 6px;
  }

  /* Sticky columnas de izquierda */
  .table-mensual th.sticky-col,
  .table-mensual td.sticky-col {
    position: sticky;
    left: 0;
    z-index: 6;
    background-color: #f8fafc;
    border-right: 1px solid #cbd5e1;
  }
  
  .table-mensual thead th.sticky-col {
    z-index: 12 !important;
    background-color: #212529 !important;
    border-right: none !important;
  }

  .table-mensual td {
    padding: 3px 4px;
    vertical-align: middle;
    border: 1px solid #e2e8f0;
    background-color: #ffffff;
  }

  .table-mensual tbody tr:hover td {
    background-color: #f1f5f9;
  }
  .table-mensual tbody tr:hover td.sticky-col {
    background-color: #e2e8f0;
  }
  
  /* Highlight de filas no guardadas o modificadas */
  .unsaved-row td {
    background-color: #fefbeb !important;
  }
  .unsaved-row td.sticky-col {
    background-color: #fef3c7 !important;
  }
  .unsaved-row {
    border-left: 4px solid #f59e0b !important;
  }

  /* Inputs editables */
  .table-editable-input {
    border: 1px solid transparent;
    background: transparent;
    padding: 3px 5px;
    border-radius: 4px;
    font-size: 10.5px;
    font-weight: inherit;
    color: inherit;
    text-align: inherit;
    transition: all 0.15s ease;
  }
  .table-editable-input:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
  }
  .table-editable-input:focus {
    border-color: #3b82f6;
    background: #ffffff;
    outline: none;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
  }

  .hover-bg-premium:hover {
    background-color: #f8fafc !important;
    border-color: #cbd5e1 !important;
  }
  
  .hover-bg-light:hover {
    background-color: rgba(255,255,255,0.15) !important;
  }

  /* Botones personalizados */
  .btn-custom-blue {
    background-color: #1a56db !important; /* Azul vibrante */
    color: #ffffff !important;
    border: 1px solid #1e429f !important;
    transition: all 0.2s ease-in-out;
  }
  .btn-custom-blue:hover:not(:disabled) {
    background-color: #1e429f !important; /* Azul más oscuro al pasar el mouse */
    border-color: #1e429f !important;
  }
  .btn-custom-blue:disabled {
    opacity: 0.65;
  }

  .btn-custom-green {
    background-color: #059669 !important; /* Verde sólido oscuro */
    color: #ffffff !important;
    border: 1px solid #047857 !important;
    transition: all 0.2s ease-in-out;
  }
  .btn-custom-green:hover:not(:disabled) {
    background-color: #047857 !important; /* Verde aún más oscuro al pasar el mouse */
    border-color: #047857 !important;
  }
  .btn-custom-green:disabled {
    opacity: 0.65;
  }
</style>

<!-- SERVER VARIABLES -->
<script>
  window.SERVER_DATA = {
    apiEndpoint: <?= json_encode(project_base_url() . 'ajax/clientes_v2.php') ?>
  };
</script>

<!-- LIBRARIES -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- SheetJS para exportar a Excel -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<!-- Vue 3 -->
<script src="https://cdn.jsdelivr.net/npm/vue@3.3.4/dist/vue.global.prod.js"></script>

<!-- Vue App Controller -->
<script src="<?= $_root ?>app/Views/clientes/v2.js?v=<?= time() ?>"></script>
<?php include $_projectRoot . '/app/Views/layouts/footer.php'; ?>
