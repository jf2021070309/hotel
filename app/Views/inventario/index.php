<?php
/**
 * app/Views/inventario/index.php
 */
require_once __DIR__ . '/../../../app/Middleware/auth.php';
protegerPorRol('cajera', 'inventario');

$page_title = 'Gestión de Inventario — Hotel Manager';
include __DIR__ . '/../layouts/head.php';
?>

<div class="main-content" id="app-inventario" v-cloak>

  <?php include __DIR__ . '/../layouts/sidebar.php'; ?>

  <!-- TOPBAR PREMIUM DARK -->
  <div class="topbar" style="background-color: #111827; padding: 0.75rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.05);">
    <div class="d-flex align-items-center justify-content-between w-100">
      <div class="d-flex align-items-center gap-3">
        <button class="btn btn-dark btn-sm rounded-circle d-md-none" onclick="handleMenuClick()" style="width:32px;height:32px;padding:0;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.1);border:none;">
          <i class="bi bi-list text-white"></i>
        </button>
        <div class="d-flex align-items-center gap-3">
          <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#f8fafc,#94a3b8);display:flex;align-items:center;justify-content:center;box-shadow:0 0 15px rgba(148,163,184,0.4);">
            <i class="bi bi-box-seam-fill text-dark fs-5"></i>
          </div>
          <div>
            <h4 class="fw-bold mb-0 text-white" style="font-size:18px;letter-spacing:-0.5px;">Gestión de Inventario</h4>
            <div class="text-white-50" style="font-size:11px;">Administración de bebidas, vinos y consumibles — edición directa</div>
          </div>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button @click="fetchInventario" :disabled="loading" class="btn btn-sm btn-outline-light d-flex align-items-center gap-2" style="font-size:12px;padding:4px 12px;border-color:rgba(255,255,255,0.2);">
          <i class="bi bi-arrow-clockwise" :class="{'spin-anim': loading}"></i>
          <span class="d-none d-md-inline">Actualizar</span>
        </button>
      </div>
    </div>
  </div>

  <!-- BODY -->
  <div class="page-body pt-3">

    <!-- CONTROL BAR -->
    <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
      <div class="card-body p-3">
        <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-3">
            <div class="input-group input-group-sm rounded shadow-sm" style="width: 300px;">
              <span class="input-group-text bg-white border-end-0 text-muted px-2"><i class="bi bi-search"></i></span>
              <input type="text" class="form-control border-start-0 bg-white text-dark" style="font-size: 13px;" v-model="busqueda" placeholder="Buscar producto...">
            </div>
            <div class="text-muted fw-semibold" style="font-size: 12px;" v-if="!loading">
              <i class="bi bi-list-ul me-1"></i>{{ productosFiltrados.length }} productos
            </div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <a href="historial.php" class="btn btn-sm btn-light fw-bold px-3 shadow-sm border" style="font-size: 12px; color: #475569;">
              <i class="bi bi-journal-text me-1"></i>Kardex
            </a>
            <button class="btn btn-sm btn-custom-blue fw-bold px-3 shadow-sm" @click="guardarCambiosMasivos" :disabled="loading || cambiosCount === 0" style="font-size: 12px;">
              <i class="bi bi-save me-1"></i>Guardar Cambios
              <span v-if="cambiosCount > 0" class="badge bg-warning text-dark ms-1" style="font-size: 10px;">{{ cambiosCount }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- GRID INTERACTIVO -->
    <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 12px;">
      <div class="inv-grid-container">
        <table class="table table-bordered table-hover mb-0 align-middle table-mensual">
          <thead>
            <!-- Fila 1: grupos -->
            <tr class="text-center text-white text-uppercase" style="font-size: 10px; letter-spacing: 0.5px; font-weight: 800;">
              <th colspan="4" style="background-color: #111827 !important; border-bottom: none !important;">IDENTIFICACIÓN DEL PRODUCTO</th>
              <th colspan="2" style="background-color: #293b95 !important; border-bottom: none !important; border-left: 1px solid rgba(255,255,255,0.1) !important;">PRECIO Y ALMACENAMIENTO</th>
              <th colspan="1" style="background-color: #6a1b9a !important; border-bottom: none !important; border-left: 1px solid rgba(255,255,255,0.1) !important;">STOCK ACTUAL</th>
            </tr>
            <!-- Fila 2: sub-cabeceras -->
            <tr class="text-center text-white text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
              <th style="width: 50px; top: 38px; background-color: #111827 !important; border-top: none !important;"><i class="bi bi-trash"></i></th>
              <th style="width: 60px; top: 38px; background-color: #111827 !important; border-top: none !important;">ID</th>
              <th style="min-width: 240px; top: 38px; background-color: #111827 !important; border-top: none !important;">PRODUCTO</th>
              <th style="width: 130px; top: 38px; background-color: #111827 !important; border-top: none !important;">CATEGORÍA</th>
              <th style="width: 140px; top: 38px; background-color: #293b95 !important; border-top: none !important; border-left: 1px solid rgba(255,255,255,0.1) !important;">PRECIO VENTA</th>
              <th style="width: 110px; top: 38px; background-color: #293b95 !important; border-top: none !important;">REFRIGERADORA #</th>
              <th style="width: 140px; top: 38px; background-color: #6a1b9a !important; border-top: none !important; border-left: 1px solid rgba(255,255,255,0.1) !important;">STOCK ACTUAL</th>
            </tr>
          </thead>
          <tbody>
            <!-- Spinner -->
            <tr v-if="loading">
              <td colspan="8" class="text-center py-5">
                <div class="spinner-border text-primary me-2"></div>
                <span class="text-muted fw-semibold">Cargando inventario...</span>
              </td>
            </tr>
            <!-- Sin datos -->
            <tr v-else-if="productosFiltrados.length === 0">
              <td colspan="8" class="text-center py-5 text-muted">
                <i class="bi bi-box-seam fs-1 d-block opacity-25 mb-2"></i>
                <span>No se encontraron productos.</span>
              </td>
            </tr>
            <!-- Filas de datos (inline editing) -->
            <tr v-else v-for="(p, idx) in productosFiltrados" :key="p.id || p.temp_id" :class="{'unsaved-row': p.modificado || !p.id}">
              <!-- ELIMINAR -->
              <td class="text-center px-1">
                <button class="btn btn-sm btn-link text-danger p-0" @click="eliminarFila(p)" title="Eliminar producto">
                  <i class="bi bi-trash-fill fs-6"></i>
                </button>
              </td>
              <!-- ID -->
              <td class="text-center px-1">
                <span v-if="p.id" class="badge bg-light text-dark border">#{{ p.id }}</span>
                <span v-else class="badge bg-warning text-dark border">Nuevo</span>
              </td>
              <!-- PRODUCTO (editable) -->
              <td class="px-1">
                <input type="text" v-model="p.nombre" class="table-editable-input fw-bold text-dark text-uppercase" @input="marcarModificado(p)" style="width: 100%;" placeholder="Nombre del producto...">
              </td>
              <!-- CATEGORÍA (editable) -->
              <td class="px-1">
                <select v-model="p.categoria" class="table-editable-input text-dark fw-bold" @change="marcarModificado(p)" style="width: 100%; cursor: pointer;">
                  <option value="BEBIDA">BEBIDA</option>
                  <option value="VINO">VINO</option>
                  <option value="SNACK">SNACK</option>
                  <option value="OTROS">OTROS</option>
                </select>
              </td>
              <!-- PRECIO VENTA (editable) -->
              <td class="px-1">
                <div class="d-flex align-items-center">
                  <span class="text-muted fw-bold px-1" style="font-size: 11px;">S/</span>
                  <input type="number" step="0.50" min="0" v-model="p.precio_venta" class="table-editable-input fw-bold text-success text-center" @input="marcarModificado(p)" style="width: 100%;">
                </div>
              </td>
              <!-- REFRIGERADORA (editable) -->
              <td class="px-1">
                <input type="number" min="1" max="9" v-model="p.refrigeradora" class="table-editable-input text-dark text-center fw-bold" @input="marcarModificado(p)" style="width: 100%;">
              </td>
              <!-- STOCK ACTUAL (editable) -->
              <td class="text-center px-1">
                <input type="number" min="0" v-model="p.stock_actual" class="table-editable-input text-dark text-center fw-bold" @input="marcarModificado(p)" style="width: 100%;" :class="p.stock_actual <= 3 ? 'text-danger' : ''">
              </td>
            </tr>

            <!-- Fila para añadir nuevo -->
            <tr v-if="!loading && busqueda === ''">
              <td class="text-center px-1" style="background-color: #f8fafc;">
                <button class="btn btn-sm text-primary p-0 w-100 d-flex align-items-center justify-content-center"
                        @click="agregarFila" title="Añadir nuevo producto"
                        style="min-height: 28px; border: 2px dashed #93c5fd; background-color: #eff6ff; border-radius: 4px;">
                  <i class="bi bi-plus-lg fw-bold" style="font-size: 15px;"></i>
                </button>
              </td>
              <td colspan="6" class="bg-light text-muted" style="vertical-align: middle; padding-left: 15px; font-size: 11px;">
                Haga clic en <i class="bi bi-plus-lg text-primary fw-bold"></i> para agregar un nuevo producto al inventario.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div><!-- /page-body -->

</div><!-- /app-inventario -->

<style>
  [v-cloak] { display: none !important; }
  body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }

  .inv-grid-container {
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
    vertical-align: middle;
    color: #ffffff !important;
    box-shadow: 0 1px 0 rgba(0,0,0,0.1);
  }
  .table-mensual tbody td {
    padding: 0;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
    background-color: #fff;
    transition: background-color 0.15s;
  }

  /* Bordes del encabezado */
  .table-mensual thead tr:first-child th { border-bottom: none !important; }
  .table-mensual thead tr:last-child th  { border-top: none !important; }
  .table-mensual thead th { border-left: none !important; border-right: none !important; }
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
  .table-editable-input:focus,
  .table-editable-input:hover:not(:disabled) {
    background-color: #f8fafc;
    border-color: #cbd5e1;
    border-radius: 4px;
  }
  select.table-editable-input {
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.5rem center;
    background-size: 16px 12px;
    padding-right: 2rem;
    cursor: pointer;
  }

  /* Filas modificadas */
  .unsaved-row td { background-color: #fffbeb !important; }

  /* Badges de stock */
  .badge-stock-ok  { background-color: #111827; color: #fff; border-radius: 8px; }
  .badge-stock-low { background-color: #dc2626; color: #fff; border-radius: 8px; animation: pulse-red 1.5s infinite; }
  @keyframes pulse-red { 0%,100%{opacity:1} 50%{opacity:0.7} }

  /* Botones */
  .btn-custom-blue {
    background-color: #1a56db !important;
    color: #fff !important;
    border: 1px solid #1e429f !important;
    transition: all 0.2s;
  }
  .btn-custom-blue:hover:not(:disabled) { background-color: #1e429f !important; }
  .btn-custom-blue:disabled { opacity: 0.65; }

  .btn-action-green {
    background: transparent;
    border: 1px solid #059669;
    color: #059669;
    border-radius: 6px;
    padding: 4px 10px;
    font-size: 11px;
    font-weight: 600;
    transition: all 0.2s;
  }
  .btn-action-green:hover { background: #059669; color: #fff; }

  .spin-anim { animation: spin 1s linear infinite; }
  @keyframes spin { 100% { transform: rotate(360deg); } }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="index.js?v=<?= time() ?>"></script>
</body></html>
