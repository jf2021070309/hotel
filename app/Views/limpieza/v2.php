<?php
/**
 * app/Views/limpieza/v2.php
 * Panel de Limpieza V2 — Vista tipo Excel con edición inline
 */
$base = '../../../';
$_projectRoot = defined('BASE_PATH') ? BASE_PATH : (rtrim(realpath(dirname(__DIR__, 3)), '\\/') . DIRECTORY_SEPARATOR);
require_once $_projectRoot . 'app/Middleware/session.php';
require_once $_projectRoot . 'app/Middleware/auth.php';
protegerPorRol('limpieza', 'limpieza');

$page_title = 'Limpieza V2 — Hotel Manager';
include $_projectRoot . 'app/Views/layouts/head.php';
?>

<div id="app-limpieza-v2" style="display:contents" v-cloak>
  <?php include $_projectRoot . 'app/Views/layouts/sidebar.php'; ?>

  <div class="main-content">

    <!-- ── CONTROLES SUPERIORES (PDF & GUARDAR) ──────────────────────────────────── -->
    <div class="px-3 pt-3 pb-2 d-flex justify-content-end gap-2 print-hide">
      <button class="btn btn-primary fw-bold shadow-sm" @click="guardarCambios">
        <i class="bi bi-save me-1"></i> Guardar Cambios
      </button>
      <button class="btn btn-danger fw-bold shadow-sm" onclick="window.print()">
        <i class="bi bi-file-earmark-pdf-fill me-1"></i> Guardar PDF / Imprimir
      </button>
    </div>

    <!-- ── HOJA DE CONTROL UNIFICADA ──────────────────────────────────── -->
    <div class="px-3 pb-4">
      <div id="printable-area" style="border: 2px solid #000; background-color: #fff; width: 100%; margin: 0 auto; min-width: 900px;">
        
        <!-- Logo & Título -->
        <div class="d-flex border-bottom border-2 border-dark">
          <div class="d-flex align-items-center justify-content-center border-end border-2 border-dark" style="width: 25%; padding: 10px;">
            <div class="text-center">
              <h4 class="fw-black mb-0 text-uppercase" style="letter-spacing:1px; color: #000;">PLATINIUM</h4>
              <div class="fw-bold" style="font-size: 10px; letter-spacing: 2px; color: #000;">HOTEL ★★★</div>
            </div>
          </div>
          <div class="d-flex align-items-center justify-content-center flex-grow-1" style="padding: 10px;">
            <h4 class="fw-bold mb-0 text-uppercase text-center" style="color: #000; letter-spacing: 1px;">HOJA DE CONTROL DE LIMPIEZA</h4>
          </div>
        </div>
        
        <!-- Responsable & Fecha -->
        <div class="d-flex border-bottom border-2 border-dark">
          <div class="d-flex align-items-center border-end border-2 border-dark" style="width: 50%; padding: 6px 10px;">
            <span class="fw-bold me-2" style="color: #000;">RESPONSABLE</span>
            <input type="text" class="form-control form-control-sm border-0 bg-transparent fw-bold text-uppercase p-0" placeholder="____________________" style="box-shadow: none; color: #000; flex: 1;">
          </div>
          <div class="d-flex align-items-center" style="width: 50%; padding: 6px 10px;">
            <span class="fw-bold me-2" style="color: #000;">FECHA</span>
            <input type="date" v-model="fecha" @change="cargarDatos" class="form-control form-control-sm border-0 bg-transparent fw-bold p-0" style="box-shadow: none; color: #000; width: 140px;">
          </div>
        </div>

        <!-- TABLA PAPEL -->
        <div class="lv2-grid-container" style="border: none; border-radius: 0; max-height: none; overflow: visible;">
          <table class="table table-bordered mb-0 lv2-table paper-table">
            <thead>
              <tr class="table-light text-center" style="font-size: 11px; color: #000; border-bottom: 2px solid #000;">
                <th class="lv2-sticky" style="padding: 10px; width: 12%; z-index: 15 !important;">HAB</th>
                <th style="padding: 10px; width: 19%;">RESERVAS</th>
                <th style="padding: 10px; width: 12%;">PAX</th>
                <th style="padding: 10px; width: 19%;">SALIDAS</th>
                <th style="padding: 10px; width: 19%;">REPASOS</th>
                <th style="padding: 10px; width: 19%;">PENDIENTES</th>
              </tr>
            </thead>

            <tbody>
              <!-- Cargando -->
              <tr v-if="loading">
                <td colspan="6" class="text-center py-5">
                  <div class="spinner-border text-dark me-2"></div>
                  <span class="fw-bold text-dark">Cargando datos...</span>
                </td>
              </tr>

              <!-- Sin datos -->
              <tr v-else-if="listaFiltrada.length === 0">
                <td colspan="6" class="text-center py-5 text-muted">
                  <i class="bi bi-inbox fs-1 d-block opacity-25 mb-2"></i>
                  <span>No hay registros para esta fecha.</span>
                </td>
              </tr>

              <!-- Filas de datos -->
              <tr v-else v-for="h in listaFiltrada" :key="h.id" :class="getRowClass(h)">

                <!-- HAB (sticky) -->
                <td class="lv2-sticky text-center fw-bold fs-6" style="color:#000;">
                  {{ h.habitacion }}
                </td>

                <!-- RESERVAS (Libre para escribir) -->
                <td class="text-center fw-bold align-middle p-0">
                  <input type="text" v-model="h.reservas_mark" class="form-control border-0 bg-transparent text-center fw-bold shadow-none w-100 h-100" style="font-size: 16px; color: #000; outline: none; cursor: text;">
                </td>

                <!-- PAX (Libre para escribir) -->
                <td class="text-center fw-bold align-middle p-0">
                  <input type="text" v-model="h.pax_mark" class="form-control border-0 bg-transparent text-center fw-bold shadow-none w-100 h-100" style="color:#000; outline: none; cursor: text;">
                </td>

                <!-- SALIDAS (Libre para escribir) -->
                <td class="text-center fw-bold align-middle p-0">
                  <input type="text" v-model="h.salidas_mark" class="form-control border-0 bg-transparent text-center fw-bold shadow-none w-100 h-100" style="font-size: 16px; color: #000; outline: none; cursor: text;">
                </td>

                <!-- REPASOS (Libre para escribir) -->
                <td class="text-center fw-bold align-middle p-0">
                  <input type="text" v-model="h.repasos_mark" class="form-control border-0 bg-transparent text-center fw-bold shadow-none w-100 h-100" style="font-size: 16px; color: #000; outline: none; cursor: text;">
                </td>

                <!-- PENDIENTES (Libre para escribir) -->
                <td class="text-center fw-bold align-middle p-0">
                  <input type="text" v-model="h.pendientes_mark" class="form-control border-0 bg-transparent text-center fw-bold shadow-none w-100 h-100" style="font-size: 16px; color: #000; outline: none; cursor: text;">
                </td>

              </tr>
            </tbody>


          </table>
        </div>
        <!-- Observaciones -->
        <div class="p-3 border-top-0 d-flex" style="background: #fff;">
            <div class="fw-bold" style="color: #000; font-size: 14px; width: 130px; padding-top: 4px;">OBSERVACIONES</div>
            <div class="flex-grow-1">
              <input type="text" class="form-control border-0 shadow-none p-0 mb-2 fw-bold" style="border-bottom: 1px solid #000 !important; border-radius: 0; background: transparent; color: #000;">
              <input type="text" class="form-control border-0 shadow-none p-0 mb-2 fw-bold" style="border-bottom: 1px solid #000 !important; border-radius: 0; background: transparent; color: #000;">
              <input type="text" class="form-control border-0 shadow-none p-0 fw-bold" style="border-bottom: 1px solid #000 !important; border-radius: 0; background: transparent; color: #000;">
            </div>
        </div>
      </div><!-- /printable-area -->

      <!-- 🟨🟨 CONTROLES INFERIORES (PDF & GUARDAR) 🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨🟨 -->
      <div class="pt-3 pb-2 d-flex justify-content-end gap-2 print-hide">
        <button class="btn btn-primary fw-bold shadow-sm" @click="guardarCambios">
          <i class="bi bi-save me-1"></i> Guardar Cambios
        </button>
        <button class="btn btn-danger fw-bold shadow-sm" onclick="window.print()">
          <i class="bi bi-file-earmark-pdf-fill me-1"></i> Guardar PDF / Imprimir
        </button>
      </div>
    </div><!-- /hoja unificada -->

  </div><!-- /main-content -->
</div><!-- /app -->

<!-- ── ESTILOS ──────────────────────────────────────────────── -->
<style>
  [v-cloak] { display: none !important; }

  .lv2-grid-container {
    max-height: calc(100vh - 200px);
    overflow: auto;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
  }
  .lv2-grid-container::-webkit-scrollbar { width:10px; height:10px; }
  .lv2-grid-container::-webkit-scrollbar-track { background:#f1f5f9; border-radius:4px; }
  .lv2-grid-container::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; border:2px solid #f1f5f9; }
  .lv2-grid-container::-webkit-scrollbar-thumb:hover { background:#94a3b8; }

  .lv2-table {
    min-width: 900px;
    font-size: 12px;
    border-collapse: separate;
    border-spacing: 0;
  }
  .lv2-table thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    color: #000 !important;
    font-weight: 800;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
    text-align: center;
    border: 1px solid #000;
    border-top: none;
    background: #f1f5f9;
    vertical-align: middle;
    padding: 10px 6px;
    white-space: nowrap;
  }
  /* Segunda fila de thead */
  .lv2-table thead tr:nth-child(2) th {
    top: 38px;
  }

  /* Sticky primera columna */
  .lv2-sticky {
    position: sticky !important;
    left: 0;
    z-index: 6;
    background-color: #fff;
    border-right: 1px solid #000 !important;
  }
  .lv2-table thead th.lv2-sticky {
    z-index: 16 !important;
    top: 0;
    background-color: #f1f5f9 !important;
    border-right: 1px solid #000 !important;
  }

  .lv2-table td {
    padding: 6px 4px;
    vertical-align: middle;
    border: 1px solid #000;
    background: #fff;
    white-space: nowrap;
  }
  
  /* Remove outer borders from table to prevent doubling with container */
  .paper-table th:first-child, .paper-table td:first-child {
    border-left: none !important;
  }
  .paper-table th:last-child, .paper-table td:last-child {
    border-right: none !important;
  }
  .paper-table tbody tr:last-child td {
    border-bottom: 1px solid #000 !important;
  }

  .lv2-table tbody tr:hover td { background:#f1f5f9; }
  .lv2-table tbody tr:hover td.lv2-sticky { background:#e2e8f0; }

  /* Impresión PDF */
  @media print {
    body * { visibility: hidden; }
    #printable-area, #printable-area * { visibility: visible; }
    #printable-area { position: absolute; left: 0; top: 0; width: 100%; border: 2px solid #000 !important; zoom: 0.92; }
    .print-hide { display: none !important; }
    .main-content { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
    .lv2-grid-container { max-height: none !important; overflow: visible !important; }
    .paper-table th { background: #f1f5f9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    
    /* Garantizar bordes negros exactos y ajustar alto de filas para forzar 1 página */
    .paper-table th, .paper-table td, #printable-area {
      border-color: #000 !important;
    }
    .paper-table td, .paper-table th {
      padding: 1px 2px !important;
      font-size: 10px !important;
      height: 22px !important;
    }
    .paper-table input[type="text"], .paper-table .lv2-select {
      font-size: 10px !important;
      padding: 0 !important;
      height: 100% !important;
    }
    
    @page { margin: 5mm; size: A4 portrait; }
  }

  /* Select editable estilo tabla */
  .lv2-select {
    border: 1px solid transparent;
    background: transparent;
    padding: 4px 6px;
    border-radius: 6px;
    font-size: 12px;
    width: 100%;
    cursor: pointer;
    transition: all .15s;
  }
  .lv2-select:hover { border-color: #cbd5e1; background: #f8fafc; }
  .lv2-select:focus { border-color: #6366f1; outline: none; box-shadow: 0 0 0 2px rgba(99,102,241,.2); }

  /* Colores de estado en el select */
  .lv2-select.estado-lista     { color: #065f46; background: #d1fae5; border-color: #a7f3d0; }
  .lv2-select.estado-pendiente { color: #991b1b; background: #fee2e2; border-color: #fca5a5; }
  .lv2-select.estado-proceso   { color: #92400e; background: #fef3c7; border-color: #fde68a; }

  /* Filas destacadas por estado */
  .row-lista    td { background: #f0fdf4 !important; }
  .row-lista    td.lv2-sticky { background: #dcfce7 !important; }
  .row-proceso  td { background: #fffbeb !important; }
  .row-mant     td { background: #fff1f2 !important; }

  /* fw-black utility */
  .fw-black { font-weight: 900 !important; }

  /* Spinner animación */
  @keyframes spin { to { transform: rotate(360deg); } }
  .spin-anim { animation: spin .6s linear infinite; display: inline-block; }

  /* Botones premium con bordes finos y colores sólidos idénticos a Clientes V2 */
  .btn-custom-blue {
    background-color: #1a56db !important; /* Azul vibrante corporativo */
    color: #ffffff !important;
    border: 1px solid #1e429f !important;
    transition: all 0.2s ease-in-out;
  }
  .btn-custom-blue:hover:not(:disabled) {
    background-color: #1e429f !important;
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
    background-color: #047857 !important;
    border-color: #047857 !important;
  }
  .btn-custom-green:disabled {
    opacity: 0.65;
  }

  .btn-custom-orange {
    background-color: #d97706 !important; /* Naranja/Ámbar sólido */
    color: #ffffff !important;
    border: 1px solid #b45309 !important;
    transition: all 0.2s ease-in-out;
  }
  .btn-custom-orange:hover:not(:disabled) {
    background-color: #b45309 !important;
    border-color: #b45309 !important;
  }
  .btn-custom-orange:disabled {
    opacity: 0.65;
  }
</style>

<!-- ── SERVER DATA ───────────────────────────────────────────── -->
<script>
  window.SERVER_DATA = {
    apiBase: <?= json_encode(project_base_url() . 'ajax/limpieza.php') ?>,
    hoy:     <?= json_encode(date('Y-m-d')) ?>
  };
</script>

<!-- ── LIBS ─────────────────────────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vue@3.3.4/dist/vue.global.prod.js"></script>
<script src="<?= $base ?>assets/js/limpieza_v2.js?v=<?= time() ?>"></script>

<?php include $_projectRoot . 'app/Views/layouts/footer.php'; ?>
