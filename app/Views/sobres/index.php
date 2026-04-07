<?php
/**
 * app/Views/sobres/index.php
 * Dashboard del Módulo de Sobres (Consolidado de Efectivo)
 */
$base = '../../../';
require_once $base . 'auth/middleware.php';
protegerPorRol('cajera', 'sobres');

$page_title = 'Sobres de Alex — Consolidado';
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';

$fecha = $_GET['fecha'] ?? date('Y-m-d');
?>

<div class="main-content" id="app-sobres" v-cloak>
  <div class="topbar py-2" style="min-height: auto;">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
    <div class="d-flex align-items-center gap-2">
      <h5 class="mb-0 fw-bold text-success"><i class="bi bi-envelope-paper-fill me-1"></i>Sobre de Alex</h5>
      <span class="text-muted small d-none d-md-inline">| Control de efectivo físico</span>
    </div>
    <div class="ms-auto d-flex gap-2 align-items-center">
      <input type="date" class="form-control form-control-sm" v-model="fechaFiltro" @change="consultar" style="width: 140px;">
      <button class="btn btn-success btn-sm fw-bold px-3" @click="imprimirReporte">
        <i class="bi bi-printer me-1"></i>Imprimir
      </button>
    </div>
  </div>

  <div class="page-body">
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-success"></div>
    </div>

    <div class="row justify-content-center" v-else>
      <div class="col-lg-11">
        
        <!-- RESUMEN POR TURNO -->
        <div class="row g-3 mb-4">
          <!-- MAÑANA -->
          <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px; border-top: 4px solid #0d6efd !important;">
              <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 rounded-pill fw-bold" style="font-size: 11px;">TURNO MAÑANA</span>
                  <i class="bi bi-sun fs-5 text-warning"></i>
                </div>
                
                <div class="row text-center my-2">
                  <div class="col-4 border-end">
                    <div class="small fw-bold text-muted mb-0" style="font-size: 10px;">SOLES</div>
                    <div class="fs-5 fw-bold text-dark">S/ {{ formatMoney(reporte.MAÑANA.PEN) }}</div>
                  </div>
                  <div class="col-4 border-end">
                    <div class="small fw-bold text-muted mb-0" style="font-size: 10px;">DÓLARES</div>
                    <div class="fs-5 fw-bold text-primary">$ {{ formatMoney(reporte.MAÑANA.USD) }}</div>
                  </div>
                  <div class="col-4">
                    <div class="small fw-bold text-muted mb-0" style="font-size: 10px;">PESOS</div>
                    <div class="fs-5 fw-bold text-success">$ {{ formatMoney(reporte.MAÑANA.CLP, 0) }}</div>
                  </div>
                </div>

                <div class="mt-2 p-2 bg-light rounded border border-light">
                  <div class="small fw-bold text-secondary mb-0" style="font-size: 11px;"><i class="bi bi-scissors me-1"></i>Extracciones:</div>
                  <div class="small text-muted italic" style="font-size: 11px;" v-html="reporte.MAÑANA.egresos_detalle || 'Ninguna'"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- TARDE -->
          <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px; border-top: 4px solid #e65100 !important;">
              <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="badge bg-orange-subtle text-orange px-2 py-1 rounded-pill fw-bold" style="background-color: #fff3e0; color: #e65100; font-size: 11px;">TURNO TARDE</span>
                  <i class="bi bi-moon-stars fs-5 text-primary"></i>
                </div>
                
                <div class="row text-center my-2">
                  <div class="col-4 border-end">
                    <div class="small fw-bold text-muted mb-0" style="font-size: 10px;">SOLES</div>
                    <div class="fs-5 fw-bold text-dark">S/ {{ formatMoney(reporte.TARDE.PEN) }}</div>
                  </div>
                  <div class="col-4 border-end">
                    <div class="small fw-bold text-muted mb-0" style="font-size: 10px;">DÓLARES</div>
                    <div class="fs-5 fw-bold text-primary">$ {{ formatMoney(reporte.TARDE.USD) }}</div>
                  </div>
                  <div class="col-4">
                    <div class="small fw-bold text-muted mb-0" style="font-size: 10px;">PESOS</div>
                    <div class="fs-5 fw-bold text-success">$ {{ formatMoney(reporte.TARDE.CLP, 0) }}</div>
                  </div>
                </div>

                <div class="mt-2 p-2 bg-light rounded border border-light">
                  <div class="small fw-bold text-secondary mb-0" style="font-size: 11px;"><i class="bi bi-scissors me-1"></i>Extracciones:</div>
                  <div class="small text-muted italic" style="font-size: 11px;" v-html="reporte.TARDE.egresos_detalle || 'Ninguna'"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- TOTAL CONSOLIDADO -->
        <div class="card border-0 shadow text-white mb-2" style="border-radius:12px; background: linear-gradient(135deg, #1b5e20, #2e7d32);">
          <div class="card-body p-3 px-4">
            <div class="text-center mb-2 opacity-75 fw-bold" style="letter-spacing: 1px; font-size: 10px;">Entrega Consolidada de Efectivo</div>
            
            <div class="row text-center align-items-center g-0">
              <div class="col-4">
                <div class="small opacity-75 mb-0" style="font-size: 9px;">TOTAL SOLES</div>
                <h4 class="fw-bold mb-0">S/ {{ formatMoney(totalSoles) }}</h4>
              </div>
              <div class="col-4 border-start border-white border-opacity-25">
                <div class="small opacity-75 mb-0" style="font-size: 9px;">TOTAL DÓLARES</div>
                <h4 class="fw-bold mb-0">$ {{ formatMoney(reporte.MAÑANA.USD + reporte.TARDE.USD) }}</h4>
              </div>
              <div class="col-4 border-start border-white border-opacity-25">
                <div class="small opacity-75 mb-0" style="font-size: 9px;">TOTAL PESOS</div>
                <h4 class="fw-bold mb-0">$ {{ formatMoney(reporte.MAÑANA.CLP + reporte.TARDE.CLP, 0) }}</h4>
              </div>
            </div>
          </div>
        </div>

        <div class="alert alert-warning border-0 shadow-sm py-2 px-3 mb-0 d-flex align-items-center" style="font-size: 11px;">
            <i class="bi bi-info-circle-fill me-2"></i>
            <div>
              <strong>Nota:</strong> Los montos deben coincidir con el dinero físico entregado.
            </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
  const SERVER_FECHA = '<?= $fecha ?>';
</script>
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="<?= $base ?>app/Views/sobres/index.js"></script>

<?php include $base . 'includes/footer.php'; ?>
