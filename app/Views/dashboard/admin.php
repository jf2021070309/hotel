<?php
/**
 * app/Views/dashboard/admin.php
 * Note: $base is strictly '' because this is required from the root index.php
 */
$base = '';
$page_title = 'Dashboard Admin — Hotel Manager';
$chartjs_enabled = true; // Activa Chart.js en el head
include 'includes/head.php';
include 'includes/sidebar.php';
?>

<div class="main-content" id="app-dash-admin" v-cloak>
  <div class="topbar border-bottom-0 shadow-sm" style="background: linear-gradient(to right, #ffffff, #f8f9fa);">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list fs-4"></i></button>
    <div>
      <h4 class="fw-bold" style="color: #111; letter-spacing: -0.5px;">
        <i class="bi bi-grid-1x2-fill me-2" style="color: #d4af37;"></i>Dashboard Ejecutivo
      </h4>
      <p class="mb-0 small text-muted fw-semibold">Centro de control y KPI Financiero — <?= date('d/m/Y') ?></p>
    </div>
    <div class="ms-auto d-flex align-items-center gap-3">
       <span class="badge bg-white text-dark shadow-sm border px-3 py-2 rounded-pill">
           <i class="bi bi-arrow-repeat me-1" :class="{'fa-spin': isRefreshing}"></i>
           Actualizado hace {{ segundosDesdeUpdate }}s
       </span>
       <span class="badge px-3 py-2 fs-6 rounded-pill" id="reloj" style="background: #111; color: #d4af37; border: 1px solid #d4af37;"></span>
    </div>
  </div>

  <div class="page-body">
    
    <div v-if="loadingInicial" class="text-center py-5 mt-5">
      <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
      <h5 class="mt-3 text-muted">Sincronizando Métrica Hotelera...</h5>
    </div>

    <!-- MAIN DASHBOARD CONTENT -->
    <div v-else>
      
      <!-- FILA 1: TARJETAS KPI -->
      <div class="row g-3 mb-4">
        <!-- Ocupación -->
        <div class="col-sm-6 col-lg-3">
          <div class="card shadow-sm border-0 border-top border-4 h-100" style="border-top-color: #111 !important; border-radius: 12px;">
            <div class="card-body">
              <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                  <div class="text-xs fw-bold text-uppercase mb-1" style="color: #64748b; letter-spacing: 1px;">🛏️ Ocupación Hoy</div>
                  <div class="h4 mb-0 fw-bold" style="color: #111;">{{ kpi.ocupacion.ocupadas }} <span class="fs-6 text-muted">/ {{ kpi.ocupacion.total }}</span></div>
                </div>
                <!-- Mini Progress Bar -->
                <div class="col-auto mt-2 w-100">
                  <div class="progress" style="height: 6px; background-color: #f1f5f9;">
                    <div class="progress-bar" style="background-color: #d4af37;" :style="{width: (kpi.ocupacion.ocupadas * 100 / (kpi.ocupacion.total || 1)) + '%'}"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- PAX en Hotel -->
        <div class="col-sm-6 col-lg-3">
          <div class="card shadow-sm border-0 border-top border-4 h-100" style="border-top-color: #475569 !important; border-radius: 12px;">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div class="text-xs fw-bold text-uppercase mb-1" style="color: #64748b; letter-spacing: 1px;">👥 PAX en Hotel</div>
                  <div class="h3 mb-0 fw-bold" style="color: #111;">{{ kpi.pax_hoy }} <span class="fs-6 text-muted">personas</span></div>
                </div>
                <i class="bi bi-people-fill opacity-25" style="font-size: 2.5rem; color: #111;"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Ingresos Hoy -->
        <div class="col-sm-6 col-lg-3">
          <div class="card shadow-sm border-0 border-top border-4 h-100" style="border-top-color: #16a34a !important; border-radius: 12px;">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div class="text-xs fw-bold text-uppercase mb-1" style="color: #16a34a; letter-spacing: 1px;">💰 Ingresos Hoy</div>
                  <div class="h4 mb-0 fw-bold" style="color: #111;">S/ {{ kpi.ingresos_hoy.toFixed(2) }}</div>
                </div>
                <i class="bi bi-cash-stack text-success opacity-25" style="font-size: 2.5rem;"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Egresos Hoy -->
        <div class="col-sm-6 col-lg-3">
          <div class="card shadow-sm border-0 border-top border-4 h-100" style="border-top-color: #dc2626 !important; border-radius: 12px;">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div class="text-xs fw-bold text-uppercase mb-1" style="color: #dc2626; letter-spacing: 1px;">📤 Egresos Hoy</div>
                  <div class="h4 mb-0 fw-bold" style="color: #111;">S/ {{ kpi.egresos_hoy.toFixed(2) }}</div>
                </div>
                <i class="bi bi-box-arrow-right text-danger opacity-25" style="font-size: 2.5rem;"></i>
              </div>
            </div>
          </div>
        </div>
      </div> <!-- /Fila 1 -->

      <!-- FILA 2: FINANZAS DEL DÍA -->
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white fw-bold text-success border-bottom-0 pt-3">
              <i class="bi bi-arrow-down-left-circle-fill me-1"></i> Desglose de Ingresos
            </div>
            <div class="card-body pt-0">
              <ul class="list-group list-group-flush mb-3">
                <li class="list-group-item d-flex justify-content-between px-0 py-2 border-dashed" v-for="ing in ingresos_desglose">
                  <span class="text-muted"><i class="bi bi-dot"></i> {{ ing.categoria }}</span>
                  <span class="fw-bold">S/ {{ parseFloat(ing.monto).toFixed(2) }}</span>
                </li>
                <li v-if="ingresos_desglose.length === 0" class="list-group-item px-0 text-muted fst-italic py-2 border-0">Aún no hay ingresos.</li>
              </ul>
              <div class="d-flex justify-content-between pt-2 border-top">
                <span class="fw-bold fs-6">TOTAL:</span>
                <span class="fw-bold fs-6 text-success">S/ {{ kpi.ingresos_hoy.toFixed(2) }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card shadow text-white h-100 border-0" style="background: linear-gradient(135deg, #0d0d0d 0%, #1a1a1a 100%);">
            <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-5">
              <h5 class="text-uppercase mb-4 fw-bold" style="letter-spacing: 3px; color: #d4af37;"><i class="bi bi-star-fill me-2 fs-6"></i>INGRESO NETO</h5>
              <h1 class="display-4 fw-bold mb-0" style="text-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);">S/ {{ kpi.neto_hoy.toFixed(2) }}</h1>
              <div class="mt-4 opacity-75 small px-3" style="color: #e2e8f0; font-weight: 300;">
                El flujo líquido calculado al instante cerrando saldos e inyecciones Yape.
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white fw-bold text-danger border-bottom-0 pt-3">
              <i class="bi bi-arrow-up-right-circle-fill me-1"></i> Desglose de Egresos
            </div>
            <div class="card-body pt-0">
              <ul class="list-group list-group-flush mb-3">
                <li class="list-group-item d-flex justify-content-between px-0 py-2 border-dashed" v-for="egr in egresos_desglose">
                  <span class="text-muted"><i class="bi bi-dot"></i> {{ egr.categoria }}</span>
                  <span class="fw-bold">S/ {{ parseFloat(egr.monto).toFixed(2) }}</span>
                </li>
                <li v-if="egresos_desglose.length === 0" class="list-group-item px-0 text-muted fst-italic py-2 border-0">Sin egresos registrados.</li>
              </ul>
              <div class="d-flex justify-content-between pt-2 border-top">
                <span class="fw-bold fs-6">TOTAL:</span>
                <span class="fw-bold fs-6 text-danger">S/ {{ kpi.egresos_hoy.toFixed(2) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div> <!-- /Fila 2 -->

      <!-- FILA 3 y 4: HABITACIONES Y SOBRES -->
      <div class="row g-3 mb-4">
        
        <!-- Estado Habitaciones -->
        <div class="col-lg-8">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-body d-flex flex-column justify-content-center">
              <h6 class="fw-bold mb-4 text-secondary">Estado Físico del Inmueble</h6>
              <div class="d-flex flex-wrap justify-content-around gap-2 text-center">
                <div class="p-2">
                  <div class="fs-2 text-success fw-bold">{{ habitaciones.libres }}</div>
                  <div class="small fw-bold text-uppercase text-muted"><i class="bi bi-circle-fill text-success small me-1"></i>Libres</div>
                </div>
                <div class="p-2">
                  <div class="fs-2 text-danger fw-bold">{{ habitaciones.ocupadas }}</div>
                  <div class="small fw-bold text-uppercase text-muted"><i class="bi bi-circle-fill text-danger small me-1"></i>Ocupadas</div>
                </div>
                <div class="p-2">
                  <div class="fs-2 text-info fw-bold">{{ habitaciones.limpieza }}</div>
                  <div class="small fw-bold text-uppercase text-muted"><i class="bi bi-circle-fill text-info small me-1"></i>Limpieza</div>
                </div>
                <div class="p-2">
                  <div class="fs-2 text-warning fw-bold">{{ habitaciones.mantenimiento }}</div>
                  <div class="small fw-bold text-uppercase text-muted"><i class="bi bi-circle-fill text-warning small me-1"></i>Manteni.</div>
                </div>
                <div class="p-2">
                  <div class="fs-2 text-purple fw-bold">{{ habitaciones.late_checkout }}</div>
                  <div class="small fw-bold text-uppercase text-muted"><i class="bi bi-circle-fill text-purple small me-1" style="color:#6f42c1"></i>Late Out</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sobres del día -->
        <div class="col-lg-4">
          <div class="card shadow-sm border-0 h-100 bg-light">
            <div class="card-body">
              <h6 class="fw-bold mb-3 text-secondary text-uppercase" style="letter-spacing: 1px;"><i class="bi bi-envelope-fill me-2"></i>Sobres Físicos Hoy</h6>
              
              <!-- Turno Mañana -->
              <div class="d-flex justify-content-between align-items-center mb-3 bg-white p-3 rounded shadow-sm border-start border-warning border-4">
                <div>
                  <div class="small fw-bold text-muted mb-1">TURNO MAÑANA</div>
                  <div class="fs-5 fw-bold text-dark">S/ {{ sobres.manana.monto.toFixed(2) }}</div>
                </div>
                <span class="badge" :class="sobres.manana.estado === 'cerrado' ? 'bg-success' : (sobres.manana.estado === 'borrador' ? 'bg-warning text-dark' : 'bg-secondary')">
                  {{ sobres.manana.estado.toUpperCase() }}
                </span>
              </div>
              
              <!-- Turno Tarde -->
              <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm border-start border-dark border-4">
                <div>
                  <div class="small fw-bold text-muted mb-1">TURNO TARDE</div>
                  <div class="fs-5 fw-bold text-dark">S/ {{ sobres.tarde.monto.toFixed(2) }}</div>
                </div>
                <span class="badge" :class="sobres.tarde.estado === 'cerrado' ? 'bg-success' : (sobres.tarde.estado === 'borrador' ? 'bg-warning text-dark' : 'bg-secondary')">
                  {{ sobres.tarde.estado.toUpperCase() }}
                </span>
              </div>

            </div>
          </div>
        </div>
      </div> <!-- /Fila 3 y 4 -->

      <!-- FILA 5 y 6: COBROS PENDIENTES & GRÁFICO MENSUAL -->
      <div class="row g-3">
        <!-- Cobros Pendientes -->
        <div class="col-lg-5">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center py-3">
              <span class="text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> Cobros Pendientes</span>
              <span class="badge bg-danger rounded-pill">{{ cobros_pendientes.length }}</span>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive" style="max-height: 350px;">
                <table class="table table-hover align-middle mb-0 text-sm">
                  <thead class="table-light sticky-top">
                    <tr>
                      <th class="ps-3">HAB.</th>
                      <th>HUÉSPED</th>
                      <th class="text-end">DEBE</th>
                      <th class="text-center">IR</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="cobros_pendientes.length === 0">
                      <td colspan="4" class="text-center text-success py-4 py-5"><i class="bi bi-shield-check fs-2 d-block mb-2"></i> No hay cobros pendientes.</td>
                    </tr>
                    <tr v-for="c in cobros_pendientes">
                      <td class="ps-3 fw-bold">{{ c.hab }}</td>
                      <td class="text-truncate" style="max-width: 150px;" :title="c.huesped">{{ c.huesped }}</td>
                      <td class="text-end text-danger fw-bold">S/ {{ parseFloat(c.debe).toFixed(2) }}</td>
                      <td class="text-center">
                        <a :href="'app/Views/rooming/index.php?buscar=' + c.hab" class="btn btn-sm btn-outline-danger px-2 py-0">→</a>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Gráfico Mensual -->
        <div class="col-lg-7">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white fw-bold py-3 text-primary">
              <i class="bi bi-bar-chart-line-fill me-1"></i> Resumen de Ingresos y Egresos del Mes (S/)
            </div>
            <div class="card-body">
               <canvas id="graficoMes" style="width:100%; height:300px;"></canvas>
            </div>
          </div>
        </div>
      </div>

    </div> <!-- /MAIN CONTENT v-else -->
  </div>
</div>

<style>
  [v-cloak] { display: none !important; }
  .text-sm { font-size: 0.85rem; }
  .border-dashed { border-bottom: 1px dashed #e3e6f0; }
  .text-purple { color: #6f42c1 !important; }
</style>

<script src="app/Views/dashboard/admin.js"></script>

<script>
  // Clock logic independent of Vue
  function tick() { document.getElementById('reloj').textContent = new Date().toLocaleTimeString('es-PE',{hour:'2-digit',minute:'2-digit'}); }
  tick(); setInterval(tick, 1000);
</script>
