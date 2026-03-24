<?php
/**
 * app/Views/flujo/dia.php
 */
$base = '../../../';
require_once $base . 'auth/middleware.php';
protegerPorRol('cajera'); 

$page_title = 'Resumen del Día — Flujo de Caja';
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';

$fecha = $_GET['fecha'] ?? date('Y-m-d');
?>

<div class="main-content" id="app-flujo-dia">
  <div class="topbar">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
    <div>
      <h4><i class="bi bi-calendar2-range me-2 text-dark"></i>Resumen Consoildado del Día</h4>
      <p class="mb-0 small text-muted">Muestra la sumatoria de todos los turnos del día consultado</p>
    </div>
    <div class="ms-auto d-flex gap-2 align-items-center">
      <input type="date" class="form-control" v-model="fechaFiltro" @change="consultar">
      <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver</a>
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
          
          <div class="text-center mt-4">
            <button class="btn btn-outline-dark" onclick="window.print()"><i class="bi bi-printer me-2"></i>Imprimir Resumen</button>
          </div>

        </div>

      </div>
    </div>
  </div>
</div>

<style>
  @media print {
    body * { visibility: hidden; }
    #app-flujo-dia, #app-flujo-dia * { visibility: visible; }
    #app-flujo-dia { position: absolute; left: 0; top: 0; width: 100%; }
    .topbar, .btn-outline-dark { display: none !important; }
    .card { border: 1px solid #ccc !important; box-shadow: none !important; }
  }
</style>

<script>
  const SERVER_FECHA = '<?= $fecha ?>';
</script>
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="<?= $base ?>app/Views/flujo/dia.js"></script>
