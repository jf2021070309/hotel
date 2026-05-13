<?php
/**
 * app/Views/clientes/frecuentes.php
 * Clientes Frecuentes = titulares con total_estadias >= 2
 */
$base = '../../../';
require_once $base . 'auth/middleware.php';
$page_title = 'Clientes Frecuentes — Hotel Manager';
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';
?>

<div class="main-content" id="app-clientes-frecuentes" v-cloak>
  <div class="topbar border-bottom-0 shadow-sm" style="background: linear-gradient(to right, #ffffff, #f8f9fa);">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list fs-4"></i></button>
    <div>
      <h4 class="fw-bold mb-0" style="color: #111; letter-spacing: -0.5px;">
        <i class="bi bi-person-heart me-2" style="color: #ef4444;"></i>Clientes Frecuentes
      </h4>
      <p class="mb-0 small text-muted fw-semibold">Huéspedes con 3 o más visitas al hotel</p>
    </div>
    <div class="ms-auto d-flex align-items-center gap-1">
    </div>
  </div>

  <div class="page-body">

    <!-- BUSCADOR -->
    <div class="card border-0 shadow-sm mb-3" style="border-radius:12px;">
      <div class="card-body py-2 px-3">
        <div class="d-flex gap-2 align-items-center">
          <div class="position-relative flex-grow-1">
            <i class="bi bi-search position-absolute" style="top:50%;left:12px;transform:translateY(-50%);color:#adb5bd; font-size: 13px;"></i>
            <input v-model="buscar" class="form-control form-control-sm ps-4 fw-bold text-secondary" placeholder="Buscar por nombre o DNI/DOC..." style="font-size: 12px; height: 36px;">
          </div>
          <button @click="buscar=''" class="btn btn-sm btn-light border h-100 py-2"><i class="bi bi-x-circle text-muted"></i></button>
        </div>
      </div>
    </div>

    <!-- TABLA -->
    <div class="card border-0 shadow-sm" style="border-radius:12px; overflow:hidden;">
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-danger"></div>
        <p class="mt-2 text-muted small">Cargando clientes frecuentes...</p>
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
                <i class="bi bi-person-heart fs-1 d-block mb-2 opacity-25"></i>
                No hay clientes frecuentes todavía.
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
                <span class="badge bg-danger rounded-pill shadow-sm px-3">{{ c.total_estadias }} visitas</span>
              </td>
              <td class="text-center text-muted">{{ fmtFecha(c.ultima_visita) }}</td>
              <td class="text-end pe-4">
                <div class="d-flex justify-content-end gap-1">
                  <button @click="crearEstadiaRapida(c)" class="btn btn-sm btn-success" title="Iniciar Check-in rápido">
                    <i class="bi bi-person-check-fill me-1"></i> Check-in
                  </button>
                  <button @click="crearReservaRapida(c)" class="btn btn-sm btn-warning text-dark" title="Hacer reserva rápida">
                    <i class="bi bi-calendar-plus-fill me-1"></i> Reserva
                  </button>
                  <button @click="verHistorial(c)" class="btn btn-sm btn-outline-danger" title="Ver historial">
                    <i class="bi bi-clock-history me-1"></i> Historial
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- MODAL HISTORIAL (Idem index.php) -->
  <div class="modal fade" id="modalHistorial" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
      <div class="modal-content border-0 shadow" style="border-radius:16px;">
        <div class="modal-header border-0 p-4 pb-0">
          <div>
            <h5 class="fw-bold mb-0">
              <i class="bi bi-clock-history text-danger me-2"></i>Historial de Visitas
            </h5>
            <p class="text-muted small mb-0" v-if="clienteSeleccionado">
              <strong>{{ clienteSeleccionado.nombre }}</strong>
            </p>
          </div>
          <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div v-if="loadingHistorial" class="text-center py-4">
            <div class="spinner-border text-danger"></div>
          </div>
          <div v-else class="d-flex flex-column gap-3">
            <div v-for="s in historial" :key="s.id" class="card border-0 shadow-sm" style="border-radius:12px; overflow:hidden;">
              <div class="card-header d-flex align-items-center gap-3 py-2 px-3 bg-light">
                <div class="fw-bold text-danger fs-5">HAB #{{ s.habitacion }}</div>
                <div class="small text-muted">{{ s.tipo_hab }}</div>
                <div class="ms-auto">
                    <span class="badge bg-secondary">{{ s.estado }}</span>
                </div>
              </div>
              <div class="card-body p-3">
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
                    <div class="text-success fw-bold">S/ {{ parseFloat(s.total_cobrado||0).toFixed(2) }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  [v-cloak] { display: none !important; }
  .table td, .table th { padding: 0.75rem 0.6rem !important; }
  .btn-outline-danger:hover { color: white !important; }
</style>

<?php include $base . 'includes/footer.php'; ?>
<script src="frecuentes.js?v=<?= time() ?>"></script>
