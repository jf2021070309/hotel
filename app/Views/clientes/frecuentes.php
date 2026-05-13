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

    <!-- BUSCADOR SOBRIO -->
    <div class="mb-4">
      <div class="position-relative shadow-sm rounded-3" style="background: #fff; border: 1px solid #e2e8f0; transition: all 0.3s ease; overflow: hidden;" id="search-wrapper">
        <i class="bi bi-search position-absolute" style="top:50%; left:15px; transform:translateY(-50%); color:#ef4444; font-size: 16px;"></i>
        <input v-model="buscar" 
               class="form-control border-0 ps-5 py-3 shadow-none" 
               placeholder="Buscar cliente por nombre o DNI/DOC..." 
               style="background: transparent; font-size: 14px; color: #444;"
               @focus="document.getElementById('search-wrapper').style.borderColor = '#ef4444'"
               @blur="document.getElementById('search-wrapper').style.borderColor = '#e2e8f0'">
        <button v-if="buscar" @click="buscar=''" 
                class="btn position-absolute border-0" 
                style="top:50%; right:15px; transform:translateY(-50%); background: transparent;">
          <i class="bi bi-x-circle-fill text-muted opacity-50"></i>
        </button>
      </div>
    </div>

    <!-- TABLA -->
    <div class="card border-0 shadow-sm" style="border-radius:12px; overflow:hidden;">
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-danger"></div>
        <p class="mt-2 text-muted">Cargando clientes frecuentes...</p>
      </div>
      <div class="table-responsive" v-else>
        <table class="table align-middle mb-0" style="font-size: 14px;">
          <thead>
            <tr class="text-uppercase" style="background-color: #ebebeb !important;">
              <th class="ps-4" style="width:40px; background-color: #ebebeb !important; color: #334155;">#</th>
              <th style="background-color: #ebebeb !important; color: #334155;">NOMBRE</th>
              <th style="background-color: #ebebeb !important; color: #334155;">DNI / DOC.</th>
              <th style="background-color: #ebebeb !important; color: #334155;">NACIONALIDAD</th>
              <th class="text-center" style="background-color: #ebebeb !important; color: #334155;">ESTADÍAS</th>
              <th class="text-center" style="background-color: #ebebeb !important; color: #334155;">ÚLTIMA VISITA</th>
              <th class="text-end pe-4" style="background-color: #ebebeb !important; color: #334155;">ACCIÓN</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="clientesFiltrados.length === 0">
              <td colspan="7" class="text-center py-5 text-muted">
                <i class="bi bi-person-heart fs-1 d-block mb-2 opacity-25"></i>
                No hay clientes frecuentes todavía.
              </td>
            </tr>
            <tr v-for="(c, i) in clientesFiltrados" :key="c.dni" class="row-hover">
              <td class="ps-4 text-muted">{{ i+1 }}</td>
              <td>
                <div class="text-dark">{{ c.nombre }}</div>
                <div class="text-muted" v-if="c.ciudad" style="font-size: 12px;"><i class="bi bi-geo-alt me-1"></i>{{ c.ciudad }}</div>
              </td>
              <td>
                <span class="text-dark">{{ c.tipo_doc }} {{ c.dni }}</span>
              </td>
              <td class="text-muted">{{ c.nacionalidad || '—' }}</td>
              <td class="text-center">
                <span class="text-dark">{{ c.total_estadias }} visitas</span>
              </td>
              <td class="text-center text-muted">{{ fmtFecha(c.ultima_visita) }}</td>
              <td class="text-end pe-4">
                <div class="d-flex justify-content-end gap-2">
                  <button @click="crearEstadiaRapida(c)" class="btn-premium" title="Check-in rápido">
                    <i class="bi bi-person-check"></i> Check-in
                  </button>
                  <button @click="crearReservaRapida(c)" class="btn-premium" title="Reserva rápida">
                    <i class="bi bi-calendar-plus"></i> Reserva
                  </button>
                  <button @click="verHistorial(c)" class="btn-premium" title="Ver historial">
                    <i class="bi bi-clock-history"></i> Historial
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- MODAL HISTORIAL MINIMALISTA EDITORIAL -->
  <div class="modal fade" id="modalHistorial" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
      <div class="modal-content border-0 shadow-lg" style="border-radius:12px; background: #fff;">
        
        <div class="modal-header border-bottom p-4">
          <div>
            <h5 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;">Historial de Visitas</h5>
            <p class="text-muted small mb-0" v-if="clienteSeleccionado">
              {{ clienteSeleccionado.nombre }} <span class="mx-1">/</span> {{ clienteSeleccionado.dni }}
            </p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        
        <div class="modal-body p-0">
          <!-- RESUMEN DISCRETO -->
          <div class="d-flex gap-4 p-4 bg-light border-bottom">
             <div>
                <span class="text-muted mini text-uppercase d-block mb-1">Estancias</span>
                <span class="fw-bold text-dark">{{ historial.length }}</span>
             </div>
             <div class="vr opacity-25"></div>
             <div>
                <span class="text-muted mini text-uppercase d-block mb-1">Inversión Total</span>
                <span class="fw-bold text-danger">S/ {{ totalPago }}</span>
             </div>
          </div>

          <div v-if="loadingHistorial" class="text-center py-5">
            <div class="spinner-border text-danger spinner-border-sm"></div>
            <p class="mt-2 text-muted" style="font-size: 12px;">Cargando registros...</p>
          </div>

          <div v-else class="p-0">
            <div v-for="s in historial" :key="s.id" class="p-4 border-bottom position-relative">
              <div class="row align-items-center g-3">
                 <!-- Columna 1: Habitación -->
                 <div class="col-md-3">
                    <div class="fw-bold text-dark mb-1" style="font-size: 13px;">HAB #{{ s.habitacion }}</div>
                    <div class="text-muted" style="font-size: 11px; letter-spacing: 0.5px; text-transform: uppercase;">{{ s.tipo_hab }}</div>
                 </div>

                 <!-- Columna 2: Fechas -->
                 <div class="col-md-4">
                    <div class="d-flex align-items-center gap-2">
                       <div>
                          <div class="text-muted mini text-uppercase mb-1">Entrada</div>
                          <div class="text-dark fw-medium" style="font-size: 12px;">{{ fmtFecha(s.check_in) }}</div>
                       </div>
                       <i class="bi bi-arrow-right text-muted mx-2 opacity-50"></i>
                       <div>
                          <div class="text-muted mini text-uppercase mb-1">Salida</div>
                          <div class="text-dark fw-medium" style="font-size: 12px;">{{ fmtFecha(s.check_out) }}</div>
                       </div>
                    </div>
                 </div>

                 <!-- Columna 3: Montos -->
                 <div class="col-md-3 text-md-end">
                    <div class="text-muted mini text-uppercase mb-1">Costo / Abonado</div>
                    <div class="text-dark" style="font-size: 13px;">
                       S/ {{ parseFloat(s.total_pago||0).toFixed(2) }} 
                       <span class="mx-1">/</span> 
                       <span :class="parseFloat(s.total_pago) > parseFloat(s.total_cobrado) ? 'text-danger' : 'text-success'">
                         S/ {{ parseFloat(s.total_cobrado||0).toFixed(2) }}
                       </span>
                    </div>
                 </div>

                 <!-- Columna 4: Estado -->
                 <div class="col-md-2 text-end">
                    <span :class="['px-2 py-1 rounded-1 fw-bold', s.estado === 'activo' ? 'bg-success-subtle text-success' : 'bg-light text-muted border']"
                          style="font-size: 9px; letter-spacing: 0.5px;">
                        {{ s.estado.toUpperCase() }}
                    </span>
                 </div>
              </div>
            </div>

            <div v-if="historial.length === 0" class="text-center py-5 text-muted">
               <span style="font-size: 13px;">No se encontraron registros de visitas.</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  [v-cloak] { display: none !important; }
  .table td, .table th { padding: 0.9rem 0.6rem !important; }
  
  /* Botón Premium Unificado */
  .btn-premium {
    background: #f8fafc;
    color: #334155;
    border: 1px solid #e2e8f0;
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s ease-in-out;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
  }

  .btn-premium:hover {
    background: #111;
    color: #fff;
    border-color: #111;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  }

  .btn-premium i {
    font-size: 14px;
  }

  .mini { font-size: 10px; letter-spacing: 0.3px; }
  .letter-spacing-1 { letter-spacing: 1px; }

  .pulse-green {
    animation: pulse-animation 2s infinite;
  }
  @keyframes pulse-animation {
    0% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.2); }
    100% { opacity: 1; transform: scale(1); }
  }

  .shadow-2xl {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
  }
  .shadow-success {
    box-shadow: 0 4px 14px 0 rgba(34, 197, 94, 0.39) !important;
  }
  
  .stay-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid rgba(0,0,0,0.03) !important;
  }
  .stay-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1) !important;
  }

  .modal-body.bg-light {
    background-color: #f8fafc !important;
  }
</style>

<?php include $base . 'includes/footer.php'; ?>
<script src="frecuentes.js?v=<?= time() ?>"></script>
