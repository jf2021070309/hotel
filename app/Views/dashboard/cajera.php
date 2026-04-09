<?php
/**
 * app/Views/dashboard/cajera.php
 * Note: $base is strictly '' because this is required from the root index.php
 */
$base = '';
$page_title = 'Panel Operativo — Hotel Manager';
include 'includes/head.php';
include 'includes/sidebar.php';
?>

<div class="main-content" id="app-dash-cajera" v-cloak>
  <div class="topbar">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
    <div>
      <h4>
        ¡Hola, <?= explode(' ', $_SESSION['auth_nombre'] ?? 'Operador')[0] ?>!
        <span class="fs-6 text-muted fw-normal ms-2">Turno
          <?= (date('H') >= 6 && date('H') < 14) ? 'MAÑANA' : 'TARDE' ?></span>
      </h4>
      <p class="mb-0 small text-muted">Panel de control operativo — <?= date('d/m/Y') ?></p>
    </div>
    <div class="ms-auto d-flex align-items-center gap-2">
      <span class="badge bg-light text-dark border px-3 py-2">
        <i class="bi bi-clock-history me-1"></i> Actualizado hace {{ segundosDesdeUpdate }}s
      </span>
      <span class="badge bg-dark px-3 py-2 fs-6" id="reloj"></span>
    </div>
  </div>

  <div class="page-body">

    <div v-if="loadingInicial" class="text-center py-5 mt-5">
      <div class="spinner-border text-primary" role="status"></div>
      <h5 class="mt-3 text-muted">Cargando tus tareas de hoy...</h5>
    </div>

    <div v-else>

      <!-- FILA 1: TARJETAS KPI (Global) -->
      <div class="row g-3 mb-4">
        <!-- Ocupación -->
        <div class="col-sm-6 col-lg-4">
          <div class="card shadow-sm border-0 border-top border-4 h-100"
            style="border-top-color: #111 !important; border-radius: 12px;">
            <div class="card-body">
              <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                  <div class="text-xs fw-bold text-uppercase mb-1" style="color: #64748b; letter-spacing: 1px;">🛏️
                    Ocupación Hoy</div>
                  <div class="h4 mb-0 fw-bold" style="color: #111;">{{ kpi.ocupacion.ocupadas }} <span
                      class="fs-6 text-muted">/ {{ kpi.ocupacion.total }}</span></div>
                </div>
                <!-- Mini Progress Bar -->
                <div class="col-auto mt-2 w-100">
                  <div class="progress" style="height: 6px; background-color: #f1f5f9;">
                    <div class="progress-bar" style="background-color: #d4af37;"
                      :style="{width: (kpi.ocupacion.ocupadas * 100 / (kpi.ocupacion.total || 1)) + '%'}"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- PAX en Hotel -->
        <div class="col-sm-6 col-lg-4">
          <div class="card shadow-sm border-0 border-top border-4 h-100"
            style="border-top-color: #475569 !important; border-radius: 12px;">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div class="text-xs fw-bold text-uppercase mb-1" style="color: #64748b; letter-spacing: 1px;">👥 PAX
                    en Hotel</div>
                  <div class="h3 mb-0 fw-bold" style="color: #111;">{{ kpi.pax_hoy }} <span
                      class="fs-6 text-muted">personas</span></div>
                </div>
                <i class="bi bi-people-fill opacity-25" style="font-size: 2.5rem; color: #111;"></i>
              </div>
            </div>
          </div>
        </div>


        <!-- Egresos Hoy -->
        <div class="col-sm-6 col-lg-4">
          <div class="card shadow-sm border-0 border-top border-4 h-100"
            style="border-top-color: #dc2626 !important; border-radius: 12px;">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div class="text-xs fw-bold text-uppercase mb-1" style="color: #dc2626; letter-spacing: 1px;">📤
                    Egresos Hoy</div>
                  <div class="h4 mb-0 fw-bold" style="color: #111;">S/ {{ formatNumber(kpi.egresos_hoy.PEN) }}</div>
                </div>
                <i class="bi bi-box-arrow-right text-danger opacity-25" style="font-size: 2.5rem;"></i>
              </div>
            </div>
          </div>
        </div>
      </div> <!-- /Fila 1 -->

      <div class="row g-4">

        <!-- COLUMNA IZQUIERDA: ALERTAS Y ACCIONES -->
        <div class="col-lg-8">

          <!-- BLOQUE 1: URGENTE AHORA (Cobros) -->
          <div class="card shadow-sm border-0 mb-4 border-start border-danger border-5">
            <div class="card-header bg-white py-3">
              <h6 class="m-0 fw-bold text-danger"><i class="bi bi-exclamation-octagon-fill me-2"></i> URGENTE AHORA:
                Cobros pendientes</h6>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light text-secondary small">
                    <tr>
                      <th class="ps-3">HAB.</th>
                      <th>HUÉSPED</th>
                      <th class="text-center">DEUDA</th>
                      <th class="text-end pe-3">ACCIÓN</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="urgentes.length === 0">
                      <td colspan="4" class="text-center py-4 text-success fw-bold">
                        <i class="bi bi-check-circle-fill me-1"></i> No hay cobros urgentes pendientes en este momento.
                      </td>
                    </tr>
                    <tr v-for="u in urgentes">
                      <td class="ps-3 fw-bold fs-5">{{ u.hab }}</td>
                      <td>
                        <div class="fw-bold">{{ u.huesped }}</div>
                        <small class="text-muted">Gasto acumulado</small>
                      </td>
                      <td class="text-center">
                        <span class="badge bg-danger fs-6 px-3">S/ {{ formatNumber(u.debe) }}</span>
                      </td>
                      <td class="text-end pe-3">
                        <a :href="'app/Views/rooming/index.php?buscar=' + u.hab"
                          class="btn btn-primary fw-bold shadow-sm">
                          COBRAR <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- BLOQUE 2: CHECKOUTS DE HOY -->
          <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
              <h6 class="m-0 fw-bold text-dark"><i class="bi bi-calendar-x me-2"></i> CHECKOUTS DE HOY (Salidas)</h6>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table align-middle mb-0">
                  <thead class="table-light text-secondary small">
                    <tr>
                      <th class="ps-3">HAB.</th>
                      <th>HUÉSPED</th>
                      <th class="text-center">SALDO</th>
                      <th class="text-center">ESTADO PAGO</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="checkouts_hoy.length === 0">
                      <td colspan="4" class="text-center py-4 text-muted">No se registran salidas programadas para hoy.
                      </td>
                    </tr>
                    <tr v-for="c in checkouts_hoy" :class="parseFloat(c.saldo) > 0 ? 'table-danger' : 'table-success'">
                      <td class="ps-3 fw-bold">{{ c.hab }}</td>
                      <td>{{ c.huesped }}</td>
                      <td class="text-center fw-bold">S/ {{ formatNumber(c.saldo) }}</td>
                      <td class="text-center">
                        <span v-if="parseFloat(c.saldo) > 0" class="badge bg-danger">DEBE SALDO</span>
                        <span v-else class="badge bg-success">✅ PAGADO</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- BLOQUE 3: CHECK-INS ESPERADOS -->
          <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
              <h6 class="m-0 fw-bold text-primary"><i class="bi bi-calendar-check me-2"></i> CHECK-INS ESPERADOS
                (Entradas)</h6>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table align-middle mb-0">
                  <thead class="table-light text-secondary small">
                    <tr>
                      <th class="ps-3">HAB.</th>
                      <th>CANAL</th>
                      <th class="text-center">PAX</th>
                      <th class="text-center">HORA EST.</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="checkins_esperados.length === 0">
                      <td colspan="4" class="text-center py-4 text-muted">No hay reservas pendientes de check-in para
                        hoy.</td>
                    </tr>
                    <tr v-for="i in checkins_esperados">
                      <td class="ps-3 fw-bold">{{ i.hab }}</td>
                      <td><span class="badge bg-light text-dark border">{{ i.canal }}</span></td>
                      <td class="text-center">{{ i.pax }}</td>
                      <td class="text-center text-primary fw-bold">{{ i.hora_estimada }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

        </div>

        <div class="col-lg-4">

          <!-- ALERTAS DE INVENTARIO -->
          <div v-if="alertasInventario.length > 0"
            class="card shadow-sm border-0 mb-4 animate__animated animate__shakeX">
            <div class="card-header bg-warning text-dark py-2 fw-bold small">
              <i class="bi bi-exclamation-triangle-fill me-1"></i> ALERTAS DE INVENTARIO
            </div>
            <div class="card-body p-2">
              <div v-for="a in alertasInventario" :key="a.id"
                class="d-flex justify-content-between align-items-center mb-1 p-2 bg-light rounded">
                <span class="small fw-bold">{{ a.nombre }}</span>
                <span class="badge bg-danger">Quedan {{ a.stock_actual }}</span>
              </div>
            </div>
          </div>

          <!-- MI TURNO -->
          <div class="card shadow-sm border-0 mb-4 bg-white">
            <div class="card-header bg-primary text-white py-3">
              <h6 class="m-0 fw-bold"><i class="bi bi-wallet2 me-2"></i>💰 MI TURNO (En curso)</h6>
            </div>
            <div class="card-body">
              <div class="mb-2 d-flex justify-content-between small">
                <span class="text-muted">POS Soles:</span>
                <span class="fw-bold text-dark">S/ {{ formatNumber(desgloseFormateado.pos_pen) }}</span>
              </div>
              <div class="mb-2 d-flex justify-content-between small">
                <span class="text-muted">POS Dólares:</span>
                <span class="fw-bold text-dark">USD {{ formatNumber(desgloseFormateado.pos_usd) }}</span>
              </div>
              <div class="mb-2 d-flex justify-content-between small border-bottom pb-1">
                <span class="text-muted">POS Pesos:</span>
                <span class="fw-bold text-dark">CLP {{ formatNumber(desgloseFormateado.pos_clp, 0) }}</span>
              </div>
              
              <div class="mb-2 d-flex justify-content-between small mt-2">
                <span class="text-muted">Yape / Plin:</span>
                <span class="fw-bold text-primary">S/ {{ formatNumber(desgloseFormateado.yape_plin) }}</span>
              </div>
              
              <div class="mb-2 d-flex justify-content-between small mt-2">
                <span class="text-muted">Efectivo Soles:</span>
                <span class="fw-bold text-success">S/ {{ formatNumber(desgloseFormateado.efectivo_pen) }}</span>
              </div>
              <div class="mb-2 d-flex justify-content-between small">
                <span class="text-muted">Efectivo Dólares:</span>
                <span class="fw-bold text-success">USD {{ formatNumber(desgloseFormateado.efectivo_usd) }}</span>
              </div>
              <div class="mb-2 d-flex justify-content-between small border-bottom pb-1">
                <span class="text-muted">Efectivo Pesos:</span>
                <span class="fw-bold text-success">CLP {{ formatNumber(desgloseFormateado.efectivo_clp, 0) }}</span>
              </div>

              <div class="mb-2 d-flex justify-content-between small mt-2">
                <span class="text-muted">Transferencia / Depósito:</span>
                <span class="fw-bold text-info">S/ {{ formatNumber(desgloseFormateado.transferencia) }}</span>
              </div>
              
              <div class="mb-3 d-flex justify-content-between border-top pt-2 mt-2">
                <span class="text-muted fw-bold">Egresos:</span>
                <span class="fw-bold text-danger">S/ {{ formatNumber(mi_turno.egresos) }}</span>
              </div>
              <hr>
              <div class="mb-4 d-flex justify-content-between align-items-center">
                <span class="fw-bold h6 mb-0 text-dark">EFECTIVO EN SOBRE:</span>
                <span v-if="mi_turno.efectivo_sobre >= 0" class="h4 mb-0 fw-bold text-primary">S/ {{
                  formatNumber(mi_turno.efectivo_sobre) }}</span>
                <span v-else class="h4 mb-0 fw-bold text-danger"
                  title="El cajón está en negativo por falta de saldo inicial.">S/ {{ formatNumber(mi_turno.efectivo_sobre)
                  }} <small class="fs-6">(Faltante)</small></span>
              </div>

              <div v-if="mi_turno.estado === 'inexistente'" class="alert alert-warning border-0 small mb-0">
                <i class="bi bi-exclamation-triangle-fill"></i> No has iniciado tu flujo de caja de hoy.
              </div>
              <div v-else class="d-flex align-items-center gap-2">
                <span class="text-muted small">Estado:</span>
                <span class="badge" :class="mi_turno.estado === 'borrador' ? 'bg-warning text-dark' : 'bg-success'">
                  {{ mi_turno.estado.toUpperCase() }}
                </span>
                <button @click="abrirModalReporte(mi_turno.id)" class="btn btn-sm btn-outline-primary ms-auto"
                  title="Imprimir Reporte">
                  <i class="bi bi-file-earmark-pdf-fill me-1"></i> Ver Reporte
                </button>
              </div>
            </div>
          </div>



        </div>

      </div>
    </div>

  </div>

  <!-- MODAL REPORTE FLOTANTE -->
  <div class="modal fade" id="modalReporte" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content shadow-lg border-0" style="border-radius: 12px; overflow: hidden; background: #f8f9fa;">
        <div class="modal-header bg-dark text-white border-0 py-2">
          <h5 class="modal-title fs-6"><i class="bi bi-file-earmark-text me-2"></i>Vista de Reporte</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body p-0 text-center" style="height: 85vh;">
          <iframe id="iframeReporte" src=""
            style="width: 100%; height: 100%; border: none; background: #e9ecef;"></iframe>
        </div>
      </div>
    </div>
  </div>

</div>

<script src="app/Views/dashboard/cajera.js"></script>

<script>
  function tick() { document.getElementById('reloj').textContent = new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' }); }
  tick(); setInterval(tick, 1000);
</script>

<?php include 'includes/footer.php'; ?>