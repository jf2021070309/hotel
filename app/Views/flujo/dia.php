<?php
/**
 * app/Views/flujo/dia.php
 */
$base = '../../../';
require_once $base . 'auth/middleware.php';
protegerPorRol('cajera', 'flujo');

$page_title = 'Resumen del Día — Flujo de Caja';
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';

$fecha = $_GET['fecha'] ?? date('Y-m-d');
?>

<div class="main-content" id="app-flujo-dia">
  <div class="topbar border-bottom-0 shadow-sm" style="background: linear-gradient(to right, #ffffff, #f8f9fa);">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list fs-4"></i></button>
    <div class="flex-grow-1">
      <h4 class="fw-bold mb-0" style="color: #111; letter-spacing: -0.5px;">
        <i class="bi bi-calendar2-range me-2" style="color:#d4af37"></i>Resumen
      </h4>
      <p class="mb-0 small text-muted fw-semibold d-none d-sm-block">Sumatoria de todos los turnos del día</p>
    </div>
    <div class="ms-auto d-flex align-items-center gap-1">
      <input type="date" class="form-control form-control-sm border-0 bg-light fw-bold" v-model="fechaFiltro" @change="consultar" style="width: 125px; font-size: 11px;">
      <a href="index.php" class="btn btn-sm btn-outline-secondary px-2"><i class="bi bi-arrow-left"></i></a>
    </div>
  </div>

  <div class="page-body">
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-dark"></div>
    </div>
    
    <div class="row justify-content-center" v-else>
      <div class="col-md-8 col-lg-6">
        
        <div class="card border-0 shadow-sm text-center py-5 mb-4" v-if="!resumen || resumen.turnos.length === 0">
          <i class="bi bi-file-earmark-x display-4 text-muted mb-3"></i>
          <h5 class="text-secondary">Sin turnos cerrados o guardados</h5>
          <p class="text-muted small">Aún no hay registros de flujo para esta fecha.</p>
        </div>

        <div v-else>
          <!-- TARJETAS POR TURNO -->
          <div class="d-flex justify-content-center gap-3 mb-4">
            <span v-for="t in resumen.turnos" :key="t.turno" class="badge rounded-pill bg-light border text-dark fs-6 px-4 py-2 shadow-sm">
              <i class="bi bi-check-circle-fill text-success me-1"></i> Turno {{ t.turno }}
            </span>
          </div>

          <!-- REPORTE DE TOTALES GENERALES -->
          <div class="card border-0 shadow-sm mb-4" style="border-radius:15px; overflow:hidden;">
            <div class="card-header border-0 bg-dark text-white text-center py-3">
              <h5 class="mb-0"><i class="bi bi-bar-chart-fill me-2"></i>TOTALES DEL DÍA</h5>
              <div class="small fw-light text-white-50">{{ resumen.fecha }}</div>
            </div>
            <div class="card-body p-4 bg-light">
              <div class="row text-center mb-4">
                <div class="col-6 border-end">
                  <div class="text-muted small fw-bold mb-1">TOTAL INGRESOS (PEN)</div>
                  <h3 class="fw-bold text-success mb-0">S/ {{ parseFloat(resumen.total_dia_ingresos).toFixed(2) }}</h3>
                </div>
                <div class="col-6">
                  <div class="text-muted small fw-bold mb-1">TOTAL EGRESOS (PEN)</div>
                  <h3 class="fw-bold text-danger mb-0">- S/ {{ parseFloat(resumen.total_dia_egresos).toFixed(2) }}</h3>
                </div>
              </div>

              <!-- RESUMEN EFECTIVO FÍSICO -->
              <div class="border-top pt-4">
                <h6 class="text-center fw-bold text-secondary mb-3"><i class="bi bi-envelope-paper-fill me-2"></i>SOBRES FÍSICOS RECAUDADOS</h6>
                
                <div class="p-3 bg-white rounded border border-success border-2 text-center mb-2">
                  <div class="small fw-bold text-success" style="letter-spacing:1px;">EFECTIVO SOLES (PEN)</div>
                  <h2 class="fw-bold mb-0">S/ {{ parseFloat(resumen.efectivo_pen).toFixed(2) }}</h2>
                </div>
                
                <div class="row">
                  <div class="col-6">
                    <div class="p-3 bg-white rounded border text-center">
                      <div class="small fw-bold text-muted">DÓLARES (USD)</div>
                      <h4 class="fw-bold mb-0 text-dark">$ {{ parseFloat(resumen.efectivo_usd).toFixed(2) }}</h4>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="p-3 bg-white rounded border text-center">
                      <div class="small fw-bold text-muted">PESOS (CLP)</div>
                      <h4 class="fw-bold mb-0 text-dark">$ {{ parseFloat(resumen.efectivo_clp).toFixed(0) }}</h4>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
          
          <div class="text-center mt-4 d-flex justify-content-center gap-3">
            <button class="btn btn-outline-dark" onclick="window.print()"><i class="bi bi-printer me-2"></i>Imprimir Resumen</button>
            <a :href="'../sobres/index.php?fecha=' + resumen.fecha" class="btn btn-success fw-bold shadow-sm px-4">
              <i class="bi bi-envelope-check me-2"></i>REPORTE ALEX (SOBRES)
            </a>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<style>
  [v-cloak] { display: none !important; }
  
  @media print {
    body * { visibility: hidden; }
    #app-flujo-dia, #app-flujo-dia * { visibility: visible; }
    #app-flujo-dia { position: absolute; left: 0; top: 0; width: 100%; }
    .topbar, .btn-outline-dark { display: none !important; }
    .card { border: 1px solid #ccc !important; box-shadow: none !important; }
  }

  @media (max-width: 768px) {
    .main-content { padding: 10px !important; }
    .page-body { padding: 0 !important; }
    .topbar h4 { font-size: 1.1rem; }
    .card { border-radius: 8px !important; }
    h2 { font-size: 1.75rem !important; }
    h3 { font-size: 1.25rem !important; }
    .px-4 { padding-left: 1rem !important; padding-right: 1rem !important; }
  }
</style>

<script>
  const SERVER_FECHA = '<?= $fecha ?>';
</script>
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="<?= $base ?>app/Views/flujo/dia.js"></script>
