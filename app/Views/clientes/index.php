<?php
/**
 * app/Views/clientes/index.php
 * Clientes = titulares registrados en rooming_pax
 */
$base = '../../../';
require_once $base . 'auth/middleware.php';
$page_title = 'Clientes — Hotel Manager';
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';
?>

<div class="main-content" id="app-clientes" v-cloak>
  <div class="topbar border-bottom-0 shadow-sm" style="background: linear-gradient(to right, #ffffff, #f8f9fa);">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list fs-4"></i></button>
    <div>
      <h4 class="fw-bold mb-0" style="color: #111; letter-spacing: -0.5px;">
        <i class="bi bi-people-fill me-2" style="color: #d4af37;"></i>Clientes
      </h4>
      <p class="mb-0 small text-muted fw-semibold">Huéspedes titulares registrados en el sistema</p>
    </div>
    <div class="ms-auto d-flex align-items-center gap-2">
      <span class="badge px-3 py-2 rounded-pill" style="background: #111; color: #d4af37; border: 1px solid #d4af37;">{{ clientes.length }} huéspedes</span>
    </div>
  </div>

  <div class="page-body">

    <!-- BUSCADOR -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
      <div class="card-body py-3">
        <div class="d-flex gap-2 align-items-center">
          <div class="position-relative flex-grow-1" style="max-width:420px;">
            <i class="bi bi-search position-absolute" style="top:50%;left:12px;transform:translateY(-50%);color:#adb5bd;"></i>
            <input v-model="buscar" class="form-control ps-4" placeholder="Buscar por nombre o DNI...">
          </div>
          <button @click="buscar=''" class="btn btn-light border"><i class="bi bi-x-circle"></i></button>
          <span class="ms-auto text-muted small">{{ clientesFiltrados.length }} resultado(s)</span>
        </div>
      </div>
    </div>

    <!-- TABLA -->
    <div class="card border-0 shadow-sm" style="border-radius:12px; overflow:hidden;">
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary"></div>
        <p class="mt-2 text-muted small">Cargando huéspedes...</p>
      </div>
      <div class="table-responsive" v-else>
        <table class="table table-hover align-middle mb-0 small">
          <thead class="bg-light text-muted text-uppercase" style="font-size:10px; letter-spacing:.5px;">
            <tr>
              <th class="ps-4" style="width:40px">#</th>
              <th>NOMBRE</th>
              <th>DNI / DOC.</th>
              <th>NACIONALIDAD</th>
              <th class="text-center">ESTADÍAS</th>
              <th class="text-center">ÚLTIMA VISITA</th>
              <th class="text-end pe-4">ACCIÓN</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="clientesFiltrados.length === 0">
              <td colspan="7" class="text-center py-5 text-muted">
                <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>
                No se encontraron huéspedes.
              </td>
            </tr>
            <tr v-for="(c, i) in clientesFiltrados" :key="c.dni">
              <td class="ps-4 text-muted">{{ i+1 }}</td>
              <td>
                <div class="fw-bold">{{ c.nombre }}</div>
                <div class="mini text-muted" v-if="c.ciudad">{{ c.ciudad }}</div>
              </td>
              <td>
                <span class="badge bg-light text-dark border">{{ c.tipo_doc }} {{ c.dni }}</span>
              </td>
              <td class="text-muted">{{ c.nacionalidad || '—' }}</td>
              <td class="text-center">
                <span class="badge bg-primary rounded-pill">{{ c.total_estadias }}</span>
              </td>
              <td class="text-center text-muted">{{ fmtFecha(c.ultima_visita) }}</td>
              <td class="text-end pe-4">
                <button @click="verHistorial(c)" class="btn btn-sm btn-outline-primary" title="Ver historial de estadías">
                  <i class="bi bi-clock-history me-1"></i> Historial
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- MODAL HISTORIAL -->
  <div class="modal fade" id="modalHistorial" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
      <div class="modal-content border-0 shadow" style="border-radius:16px;">
        <div class="modal-header border-0 p-4 pb-0">
          <div>
            <h5 class="fw-bold mb-0">
              <i class="bi bi-clock-history text-primary me-2"></i>Historial de Estadías
            </h5>
            <p class="text-muted small mb-0" v-if="clienteSeleccionado">
              <strong>{{ clienteSeleccionado.nombre }}</strong> —
              {{ clienteSeleccionado.tipo_doc }} {{ clienteSeleccionado.dni }}
            </p>
          </div>
          <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div v-if="loadingHistorial" class="text-center py-4">
            <div class="spinner-border text-primary"></div>
          </div>
          <div v-else-if="historial.length === 0" class="text-center py-4 text-muted">
            <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>
            Sin estadías registradas.
          </div>

          <!-- CARD POR ESTADÍA -->
          <div v-else class="d-flex flex-column gap-3">
            <div v-for="s in historial" :key="s.id" class="card border-0 shadow-sm" style="border-radius:12px; overflow:hidden;">
              <!-- Header de la estadía -->
              <div class="card-header d-flex align-items-center gap-3 py-2 px-3"
                   :class="s.estado === 'activo' ? 'bg-success bg-opacity-10' : 'bg-light'">
                <div class="fw-bold text-primary fs-5">HAB #{{ s.habitacion }}</div>
                <div class="small text-muted">{{ s.tipo_hab }}</div>
                <div class="ms-auto d-flex align-items-center gap-2">
                  <span class="badge" :class="s.estado === 'activo' ? 'bg-success' : 'bg-secondary'">
                    {{ s.estado === 'activo' ? 'ACTIVO' : 'CERRADO' }}
                  </span>
                  <span class="badge" :class="s.estado_pago === 'pagado' ? 'bg-success' : 'bg-warning text-dark'">
                    {{ s.estado_pago === 'pagado' ? '✅ PAGADO' : '⏳ PENDIENTE' }}
                  </span>
                </div>
              </div>
              <div class="card-body p-3">
                <!-- Fechas y montos -->
                <div class="row g-2 mb-3 small">
                  <div class="col-6 col-md-3">
                    <div class="text-muted mini text-uppercase fw-bold mb-1">Check-in</div>
                    <div>{{ fmtFecha(s.check_in) }}</div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="text-muted mini text-uppercase fw-bold mb-1">Check-out</div>
                    <div>{{ fmtFecha(s.check_out) }}</div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="text-muted mini text-uppercase fw-bold mb-1">Total</div>
                    <div class="fw-bold">S/ {{ parseFloat(s.total_pago||0).toFixed(2) }}</div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="text-muted mini text-uppercase fw-bold mb-1">Cobrado</div>
                    <div :class="s.estado_pago === 'pagado' ? 'text-success fw-bold' : 'text-warning fw-bold'">
                      S/ {{ parseFloat(s.total_cobrado||0).toFixed(2) }}
                    </div>
                  </div>
                </div>
                <!-- Lista de pax -->
                <div v-if="s.pax && s.pax.length" class="border-top pt-2">
                  <div class="mini text-muted text-uppercase fw-bold mb-2">
                    <i class="bi bi-people me-1"></i> Pasajeros ({{ s.pax.length }})
                  </div>
                  <div class="d-flex flex-wrap gap-2">
                    <div v-for="p in s.pax" :key="p.documento_num"
                         class="d-flex align-items-center gap-1 px-2 py-1 rounded-3 small"
                         :class="p.es_titular == 1 ? 'bg-primary bg-opacity-10 text-primary' : 'bg-light text-dark border'">
                      <i class="bi" :class="p.es_titular == 1 ? 'bi-person-fill' : 'bi-person'"></i>
                      <span class="fw-bold">{{ p.nombre_completo }}</span>
                      <span class="text-muted ms-1" style="font-size:10px;">
                        {{ p.documento_tipo }} {{ p.documento_num }}
                      </span>
                      <span v-if="p.es_titular == 1" class="badge bg-primary ms-1" style="font-size:9px;">TITULAR</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Total acumulado -->
            <div class="card border-0 bg-dark text-white p-3 d-flex flex-row justify-content-between align-items-center" style="border-radius:12px;">
              <span class="fw-bold small text-uppercase opacity-75">Total acumulado ({{ historial.length }} estadías)</span>
              <div class="text-end">
                <div class="small opacity-75">Facturado: <b>S/ {{ totalPago }}</b></div>
                <div class="text-success fw-bold">Cobrado: S/ {{ totalCobrado }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include $base . 'includes/footer.php'; ?>
<script src="index.js?v=<?= time() ?>"></script>
