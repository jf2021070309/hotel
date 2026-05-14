<?php
/**
 * app/Views/clientes/frecuentes.php
 * Clientes Frecuentes = titulares con total_estadias >= 2
 */
$base = '../../../';
require_once $base . 'auth/middleware.php';
$page_title = 'Base de Datos de Clientes — Hotel Manager';
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';
?>

<div class="main-content" id="app-clientes-frecuentes" v-cloak>
  <div class="topbar border-bottom-0 shadow-sm" style="background: linear-gradient(to right, #ffffff, #f8f9fa);">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list fs-4"></i></button>
    <div>
      <h4 class="fw-bold mb-0" style="color: #111; letter-spacing: -0.5px;">
        <i class="bi bi-people-fill me-2" style="color: #ef4444;"></i>Base de Datos de Clientes
      </h4>
      <p class="mb-0 small text-muted fw-semibold">Gestión integral de huéspedes y registros corporativos</p>
    </div>
    <div class="ms-auto d-flex align-items-center gap-1">
    </div>
  </div>

  <div class="page-body">

    <!-- CABECERA DE ACCIONES -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h5 class="fw-bold mb-0 text-dark">Base de Datos de Clientes</h5>
        <p class="text-muted small mb-0">Huéspedes frecuentes y registros corporativos</p>
      </div>
      <div class="d-flex gap-2">
        <!-- Buscador Rectangular -->
        <div class="input-group border shadow-sm bg-white" style="border-radius: 8px; overflow: hidden; width: 350px;">
           <span class="input-group-text bg-white border-0 ps-3">
              <i class="bi bi-search text-danger"></i>
           </span>
           <input type="text" 
                  class="form-control border-0 shadow-none py-2" 
                  placeholder="Buscar por nombre, DNI o RUC..." 
                  v-model="buscar"
                  style="font-size: 14px;">
        </div>
        <!-- Botón Nuevo -->
        <button @click="abrirModalNuevo" 
                data-bs-toggle="modal" 
                data-bs-target="#modalNuevoCliente"
                class="btn btn-danger px-4 shadow-sm fw-bold d-flex align-items-center gap-2" 
                style="border-radius: 8px; font-size: 14px;">
           <i class="bi bi-plus-lg"></i> Nuevo Registro
        </button>
      </div>
    </div>

    <!-- TABLA -->
    <div class="card border-0 shadow-sm" style="border-radius:12px; overflow:hidden;">
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-danger"></div>
        <p class="mt-2 text-muted">Cargando base de datos...</p>
      </div>
      <div class="table-responsive" v-else>
        <table class="table align-middle mb-0" style="font-size: 14px;">
          <thead>
            <tr class="text-uppercase" style="background-color: #ebebeb !important;">
              <th class="px-4" style="width:20%; background-color: #ebebeb !important; color: #334155;">NOMBRE</th>
              <th class="text-center" style="width:10%; background-color: #ebebeb !important; color: #334155;">DNI</th>
              <th class="text-center" style="width:12%; background-color: #ebebeb !important; color: #334155;">RUC</th>
              <th class="text-center" style="width:10%; background-color: #ebebeb !important; color: #334155;">CELULAR</th>
              <th style="width:23%; background-color: #ebebeb !important; color: #334155;">EMPRESA</th>
              <th class="text-center px-4" style="width:25%; background-color: #ebebeb !important; color: #334155;">ACCIÓN</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="clientesFiltrados.length === 0">
              <td colspan="7" class="text-center py-5 text-muted">
                <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>
                No hay clientes registrados todavía.
              </td>
            </tr>
            <tr v-for="(c, i) in clientesFiltrados" :key="c.dni" class="row-hover">
              <td class="px-4">
                <div class="text-dark">{{ c.nombre }}</div>
                <div class="text-muted" v-if="c.ciudad" style="font-size: 12px;"><i class="bi bi-geo-alt me-1"></i>{{ c.ciudad }}</div>
              </td>
              <td class="text-center text-dark">{{ c.dni }}</td>
              <td class="text-center text-dark">{{ c.ruc || '—' }}</td>
              <td class="text-center text-dark">{{ c.celular || '—' }}</td>
              <td class="text-dark">{{ c.razon_social || '—' }}</td>
              <td class="text-center px-4">
                <div class="d-flex justify-content-center gap-2">
                  <button @click="crearEstadiaRapida(c)" class="btn-premium" title="Check-in rápido">
                    <i class="bi bi-person-check"></i> Check-in
                  </button>
                  <button @click="crearReservaRapida(c)" class="btn-premium" title="Reserva rápida">
                    <i class="bi bi-calendar-plus"></i> Reserva
                  </button>
                  <button @click="editarCliente(c)" class="btn-premium" title="Editar datos">
                    <i class="bi bi-pencil-square"></i> Editar
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

  <!-- ================================================================================= -->
  <!-- MODALES AL FINAL PARA EVITAR CONFLICTOS DE VISIBILIDAD -->
  <!-- ================================================================================= -->

  <!-- MODAL HISTORIAL -->
  <div class="modal fade" id="modalHistorial" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
      <div class="modal-content border-0 shadow-lg" style="border-radius:15px; overflow:hidden;">
        <div class="modal-header bg-white border-0 pt-5 pb-4 d-flex flex-column align-items-center text-center position-relative">
          <h4 class="fw-bold mb-1 text-dark letter-spacing-1" v-if="clienteSeleccionado">{{ clienteSeleccionado.nombre.toUpperCase() }}</h4>
          <div class="text-muted mini text-uppercase fw-bold opacity-75" v-if="clienteSeleccionado">Historial de Estadías</div>
          <button type="button" class="btn-close position-absolute" style="top:25px; right:25px;" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body bg-light px-4 py-4">
          <div v-if="loadingHistorial" class="text-center py-5">
             <div class="spinner-grow text-danger" role="status"></div>
          </div>
          <div v-else class="container-fluid">
            <div v-for="(s, idx) in historial" :key="idx" class="card mb-3 stay-card shadow-sm border-0 rounded-3 overflow-hidden">
              <div class="card-body p-3 row align-items-center">
                <div class="col-md-3">
                   <div class="text-muted mini text-uppercase mb-1">Habitación</div>
                   <div class="text-dark fw-bold" style="font-size: 14px;"><i class="bi bi-door-open me-2 text-danger"></i>#{{ s.n_habitacion }}</div>
                </div>
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

  <!-- MODAL NUEVO CLIENTE -->
  <div class="modal fade" id="modalNuevoCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg" style="border-radius:12px;">
        <div class="modal-header border-bottom p-4 d-flex flex-column align-items-center text-center position-relative">
          <h5 class="fw-bold mb-1 text-dark" style="font-size: 18px; letter-spacing: -0.5px;">Registro de Nuevo Cliente</h5>
          <p class="text-muted mb-0" style="font-size: 13px;">Ingrese los datos para la base de datos de clientes</p>
          <button type="button" class="btn-close position-absolute" style="top: 20px; right: 20px;" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4 bg-light">
          <form @submit.prevent="guardarNuevoCliente">
            <div class="row g-3 mb-4">
              <div class="col-12">
                <label class="text-muted text-uppercase fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Nombre Completo</label>
                <input type="text" v-model="nuevoCliente.nombre" class="form-control border-0 shadow-sm py-2" placeholder="Ej: Juan Perez" required style="font-size: 14px;">
              </div>
              <div class="col-md-6">
                <label class="text-muted text-uppercase fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Tipo Doc</label>
                <select v-model="nuevoCliente.tipo_doc" class="form-select border-0 shadow-sm py-2" style="font-size: 14px;">
                  <option value="DNI">DNI</option>
                  <option value="RUC">RUC</option>
                  <option value="CE">CE</option>
                  <option value="PASAPORTE">PASAPORTE</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="text-muted text-uppercase fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Número</label>
                <input type="text" v-model="nuevoCliente.dni" class="form-control border-0 shadow-sm py-2" placeholder="Documento..." required style="font-size: 14px;">
              </div>
              <div class="col-md-6">
                <label class="text-muted text-uppercase fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Nacionalidad</label>
                <input type="text" v-model="nuevoCliente.nacionalidad" class="form-control border-0 shadow-sm py-2" placeholder="Ej: Peruana" style="font-size: 14px;">
              </div>
              <div class="col-md-6">
                <label class="text-muted text-uppercase fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Ciudad</label>
                <input type="text" v-model="nuevoCliente.ciudad" class="form-control border-0 shadow-sm py-2" placeholder="Ej: Lima" style="font-size: 14px;">
              </div>
              <div class="col-md-6">
                <label class="text-muted text-uppercase fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Celular</label>
                <input type="text" v-model="nuevoCliente.celular" class="form-control border-0 shadow-sm py-2" placeholder="999 888 777" style="font-size: 14px;">
              </div>
              <div class="col-md-6">
                <label class="text-muted text-uppercase fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Email</label>
                <input type="email" v-model="nuevoCliente.email" class="form-control border-0 shadow-sm py-2" placeholder="ejemplo@correo.com" style="font-size: 14px;">
              </div>
            </div>
            <div class="p-3 rounded-3 border bg-white shadow-sm">
              <div class="form-check form-switch d-flex align-items-center gap-2 mb-3">
                <input class="form-check-input" type="checkbox" v-model="nuevoCliente.es_empresa" id="switchEmpresa">
                <label class="form-check-label text-muted fw-bold text-uppercase" for="switchEmpresa" style="font-size: 11px; letter-spacing: 0.5px;">
                  <i class="bi bi-building me-1"></i> ¿Es una empresa? (Corporativo)
                </label>
              </div>
              <div v-if="nuevoCliente.es_empresa" class="row g-3 animate__animated animate__fadeIn">
                <div class="col-12">
                  <label class="text-muted text-uppercase fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">RUC</label>
                  <input type="text" v-model="nuevoCliente.ruc" class="form-control bg-light border-0 py-2" placeholder="RUC de la empresa..." style="font-size: 14px;">
                </div>
                <div class="col-12">
                  <label class="text-muted text-uppercase fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Razón Social</label>
                  <input type="text" v-model="nuevoCliente.razon_social" class="form-control bg-light border-0 py-2" placeholder="Nombre de la empresa..." style="font-size: 14px;">
                </div>
              </div>
            </div>
            <div class="mt-4 pt-2">
              <button type="submit" class="btn btn-dark w-100 py-2 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" :disabled="guardando" style="font-size: 14px;">
                <span v-if="guardando" class="spinner-border spinner-border-sm"></span>
                <i v-else class="bi bi-check-lg"></i>
                {{ guardando ? 'Guardando...' : 'Registrar Cliente' }}
              </button>
            </div>
          </form>
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
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
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
