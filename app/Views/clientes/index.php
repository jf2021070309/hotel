<?php
/**
 * app/Views/clientes/index.php
 * Clientes = listado unificado de titulares registrados en rooming_pax
 */
$base = '../../../';
$_projectRoot = dirname(__DIR__, 3);
require_once $_projectRoot . '/auth/middleware.php';
protegerPorRol('cajera', 'clientes');

$page_title = 'Base de Datos de Clientes — Hotel Manager';
include $_projectRoot . '/includes/head.php';
?>

<div id="app-clientes" style="display:contents" v-cloak>
  <?php include $_projectRoot . '/includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar border-bottom-0 shadow-sm" style="background: linear-gradient(to right, #ffffff, #f8f9fa);">
      <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list fs-4"></i></button>
      <div>
        <h4 class="fw-bold mb-0" style="color: #111; letter-spacing: -0.5px;">
          <i class="bi bi-people-fill me-2" style="color: #111;"></i>Huéspedes & Clientes
        </h4>
        <p class="mb-0 small text-muted fw-semibold">Gestión unificada de huéspedes, clientes frecuentes y registros corporativos</p>
      </div>
      <div class="ms-auto d-flex align-items-center gap-2">
        <span class="badge px-3 py-2 rounded-pill shadow-sm" style="background: #111; color: #d4af37; border: 1px solid #d4af37; font-size: 11px; font-weight: 700;">
          <i class="bi bi-people-fill me-1"></i> {{ clientes.length }} REGISTROS
        </span>
      </div>
    </div>

    <div class="page-body">
      <!-- FILTROS Y ACCIONES -->
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <!-- Tabs para filtrar Todos vs Frecuentes -->
        <div class="btn-group bg-white p-1 shadow-sm rounded-3 border" role="group" style="height: 42px;">
          <button type="button" class="btn btn-sm px-3 fw-bold rounded-2 transition-all d-flex align-items-center gap-2"
            :class="filtroFrecuente === 'todos' ? 'btn-dark text-white' : 'btn-white text-secondary border-0'"
            @click="filtroFrecuente = 'todos'">
            <i class="bi bi-people"></i> Todos
          </button>
          <button type="button" class="btn btn-sm px-3 fw-bold rounded-2 transition-all d-flex align-items-center gap-2"
            :class="filtroFrecuente === 'frecuentes' ? 'btn-warning text-dark' : 'btn-white text-secondary border-0'"
            @click="filtroFrecuente = 'frecuentes'">
            <i class="bi bi-star-fill text-dark"></i> Frecuentes
          </button>
          <button type="button" class="btn btn-sm px-3 fw-bold rounded-2 transition-all d-flex align-items-center gap-2"
            :class="filtroFrecuente === 'regulares' ? 'btn-secondary text-white' : 'btn-white text-secondary border-0'"
            @click="filtroFrecuente = 'regulares'">
            <i class="bi bi-star text-secondary"></i> Regulares (Sin Estrella)
          </button>
        </div>

        <div class="d-flex flex-grow-1 flex-md-grow-0 gap-2">
          <!-- Buscador Premium -->
          <div class="input-group border shadow-sm bg-white" style="border-radius: 8px; overflow: hidden; max-width: 350px;">
             <span class="input-group-text bg-white border-0 ps-3">
                <i class="bi bi-search text-muted"></i>
             </span>
             <input type="text" 
                    class="form-control border-0 shadow-none py-2" 
                    placeholder="Buscar por nombre, documento o RUC..." 
                    v-model="buscar"
                    style="font-size: 13px; font-weight: 600;">
             <button v-if="buscar" @click="buscar=''" class="btn btn-white border-0 text-muted px-2"><i class="bi bi-x-lg"></i></button>
          </div>
          <!-- Botón Exportar Excel -->
          <button @click="exportarExcel" 
                  class="btn btn-success px-3 shadow-sm fw-bold d-flex align-items-center gap-2" 
                  style="border-radius: 8px; font-size: 13px; background-color: #198754; border-color: #198754;">
             <i class="bi bi-file-earmark-excel"></i> Exportar
          </button>
          <!-- Botón Nuevo -->
          <button @click="abrirModalNuevo" 
                  class="btn btn-dark px-3 shadow-sm fw-bold d-flex align-items-center gap-2" 
                  style="border-radius: 8px; font-size: 13px;">
             <i class="bi bi-plus-lg text-warning"></i> Nuevo Registro
          </button>
        </div>
      </div>

      <!-- TABLA DE CLIENTES -->
      <div class="card border-0 shadow-sm overflow-hidden" style="border-radius:12px;">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
            <thead class="table-dark text-white text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">
              <tr>
                <th class="ps-4" style="width: 50px;">#</th>
                <th style="min-width: 250px;">HUÉSPED / TITULAR</th>
                <th style="width: 140px;">DOCUMENTO</th>
                <th style="width: 180px;">CELULAR / EMAIL</th>
                <th style="min-width: 200px;">EMPRESA / FACTURACIÓN</th>
                <th class="text-center" style="width: 120px;">ÚLTIMA VISITA</th>
                <th class="text-end pe-4" style="width: 280px;">ACCIONES</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="7" class="text-center py-5">
                  <div class="spinner-border text-primary me-2"></div>
                  <span class="text-muted fw-bold">Cargando base de datos...</span>
                </td>
              </tr>
              <tr v-else-if="clientesFiltrados.length === 0">
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>
                  No se encontraron clientes registrados con los filtros seleccionados.
                </td>
              </tr>
              <tr v-else v-for="(c, i) in clientesFiltrados" :key="c.dni" class="row-hover">
                <td class="ps-4 text-muted fw-bold">{{ i + 1 }}</td>
                <td>
                  <div class="d-flex align-items-center gap-1">
                    <span class="fw-bold text-dark" style="font-size: 14px;">{{ c.nombre }}</span>
                    <!-- Estrella interactiva premium -->
                    <span class="d-inline-flex align-items-center justify-content-center ms-2" style="width: 24px; height: 24px;">
                      <!-- Con estrella (frecuente manual) -->
                      <i v-if="c.vip == 1" 
                         @click.stop="toggleVipStatus(c)" 
                         class="bi bi-star-fill text-warning fs-5 cursor-pointer hover-scale pulse-star d-inline-block" 
                         style="transition: all 0.2s;"
                         title="Cliente Frecuente ★ — Clic para quitar estrella">
                      </i>
                      <!-- Sin estrella (regular) -->
                      <i v-else 
                         @click.stop="toggleVipStatus(c)" 
                         class="bi bi-star text-secondary opacity-25 fs-5 cursor-pointer hover-scale hover-color-warning d-inline-block" 
                         style="transition: all 0.2s;"
                         title="Marcar como VIP / Destacado ★">
                      </i>
                    </span>
                  </div>
                  <div class="text-muted small fw-semibold" v-if="c.ciudad">
                    <i class="bi bi-geo-alt-fill text-muted me-1"></i>{{ c.ciudad }}
                  </div>
                </td>
                <td>
                  <span class="badge bg-light text-dark border fw-bold" style="font-size: 10px; letter-spacing: 0.3px;">
                    {{ c.tipo_doc }} {{ c.dni }}
                  </span>
                </td>
                <td>
                  <div class="fw-semibold text-secondary" v-if="c.celular"><i class="bi bi-phone me-1 text-muted"></i>{{ c.celular }}</div>
                  <div class="text-muted small text-truncate" style="max-width: 170px;" v-if="c.email" :title="c.email">
                    <i class="bi bi-envelope me-1 text-muted"></i>{{ c.email }}
                  </div>
                  <div v-if="!c.celular && !c.email" class="text-muted opacity-50">—</div>
                </td>
                <td>
                  <div v-if="c.ruc || c.razon_social">
                    <div class="fw-bold text-primary" style="font-size:12px;">{{ c.razon_social || 'Empresa sin Razón Social' }}</div>
                    <div class="text-muted small fw-bold">RUC: {{ c.ruc || '—' }}</div>
                  </div>
                  <span v-else class="text-muted opacity-50">—</span>
                </td>
                <td class="text-center text-muted fw-semibold">
                  {{ fmtFecha(c.ultima_visita) }}
                </td>
                <td class="text-end pe-4">
                  <div class="btn-group shadow-sm" style="border-radius: 8px; overflow: hidden;">
                    <!-- Check-in rápido -->
                    <button @click="crearEstadiaRapida(c)" class="btn btn-sm btn-white border hover-bg-premium" title="Check-in rápido">
                      <i class="bi bi-person-check-fill text-success me-1"></i> Check-in
                    </button>
                    <!-- Reserva rápida -->
                    <button @click="crearReservaRapida(c)" class="btn btn-sm btn-white border hover-bg-premium" title="Reserva rápida">
                      <i class="bi bi-calendar-plus-fill text-warning me-1"></i> Reserva
                    </button>
                    <!-- Editar -->
                    <button @click="editarCliente(c)" class="btn btn-sm btn-white border hover-bg-premium" title="Editar datos">
                      <i class="bi bi-pencil-square text-secondary"></i>
                    </button>
                    <!-- Historial -->
                    <button @click="verHistorial(c)" class="btn btn-sm btn-white border hover-bg-premium" title="Ver historial de estadías">
                      <i class="bi bi-clock-history text-primary"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ╔══════════════════════════════════════════════════════╗ -->
  <!-- ║                     MODALES                         ║ -->
  <!-- ╚══════════════════════════════════════════════════════╝ -->

  <!-- MODAL HISTORIAL -->
  <div class="modal fade" id="modalHistorial" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
      <div class="modal-content border-0 shadow-lg" style="border-radius:16px;">
        <div class="modal-header border-0 p-4 pb-0">
          <div>
            <h5 class="fw-bold mb-0">
              <i class="bi bi-clock-history text-primary me-2"></i>Historial de Estadías
            </h5>
            <p class="text-muted small mb-0 mt-1" v-if="clienteSeleccionado">
              Huésped: <strong class="text-dark">{{ clienteSeleccionado.nombre }}</strong> — 
              <span class="badge bg-light text-dark border">{{ clienteSeleccionado.tipo_doc }} {{ clienteSeleccionado.dni }}</span>
            </p>
          </div>
          <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4 bg-light mt-3">
          <div v-if="loadingHistorial" class="text-center py-4">
            <div class="spinner-border text-primary me-2"></div>
            <span class="text-muted fw-bold">Cargando visitas...</span>
          </div>
          <div v-else-if="historial.length === 0" class="text-center py-4 text-muted">
            <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>
            Este huésped no registra estadías cerradas todavía.
          </div>

          <!-- LISTA DE ESTADÍAS -->
          <div v-else class="d-flex flex-column gap-3">
            <div v-for="s in historial" :key="s.id" class="card stay-card border-0 shadow-sm rounded-4 overflow-hidden">
              <div class="card-header d-flex align-items-center gap-3 py-2 px-3 bg-white border-bottom border-light">
                <div class="fw-bold text-primary fs-5"><i class="bi bi-door-open-fill me-1"></i>HAB #{{ s.habitacion }}</div>
                <div class="small text-muted fw-bold text-uppercase" style="font-size:10px;">{{ s.tipo_hab }}</div>
                <div class="ms-auto d-flex align-items-center gap-1">
                  <span class="badge font-bold" :class="s.estado === 'activo' ? 'bg-success-subtle text-success' : 'bg-light text-muted border'" style="font-size: 9px;">
                    {{ s.estado.toUpperCase() }}
                  </span>
                  <span class="badge font-bold" :class="s.estado_pago === 'pagado' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning-emphasis border border-warning'" style="font-size: 9px;">
                    {{ s.estado_pago === 'pagado' ? 'PAGADO' : 'PENDIENTE' }}
                  </span>
                </div>
              </div>
              <div class="card-body p-3 bg-white">
                <div class="row g-2 mb-3 small">
                  <div class="col-6 col-md-3">
                    <div class="text-muted mini text-uppercase fw-bold mb-1">Check-in</div>
                    <div class="fw-bold text-dark"><i class="bi bi-calendar-check text-success me-1"></i>{{ fmtFecha(s.check_in) }}</div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="text-muted mini text-uppercase fw-bold mb-1">Check-out</div>
                    <div class="fw-bold text-dark"><i class="bi bi-calendar-x text-danger me-1"></i>{{ fmtFecha(s.check_out) }}</div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="text-muted mini text-uppercase fw-bold mb-1">Costo Total</div>
                    <div class="fw-bold text-dark">S/ {{ parseFloat(s.total_pago||0).toFixed(2) }}</div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="text-muted mini text-uppercase fw-bold mb-1">Abonado</div>
                    <div class="fw-bold text-success">S/ {{ parseFloat(s.total_cobrado||0).toFixed(2) }}</div>
                  </div>
                </div>

                <!-- Pasajeros Acompañantes -->
                <div v-if="s.pax && s.pax.length" class="border-top pt-2 mt-2">
                  <div class="mini text-muted text-uppercase fw-bold mb-2">
                    <i class="bi bi-people-fill me-1"></i> Huéspedes en Habitación ({{ s.pax.length }})
                  </div>
                  <div class="d-flex flex-wrap gap-2">
                    <div v-for="p in s.pax" :key="p.documento_num"
                         class="d-flex align-items-center gap-1 px-2 py-1 rounded-3 small border"
                         :class="p.es_titular == 1 ? 'bg-primary bg-opacity-10 text-primary border-primary border-opacity-25' : 'bg-light text-dark border-light'">
                      <i class="bi" :class="p.es_titular == 1 ? 'bi-person-fill' : 'bi-person'"></i>
                      <span class="fw-bold">{{ p.nombre_completo }}</span>
                      <span class="text-muted ms-1" style="font-size:10px;">
                        ({{ p.documento_tipo }} {{ p.documento_num }})
                      </span>
                      <span v-if="p.es_titular == 1" class="badge bg-primary text-white ms-1" style="font-size:8px; padding: 2px 5px;">TITULAR</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Total acumulado -->
            <div class="card border-0 bg-dark text-white p-3 d-flex flex-row justify-content-between align-items-center mt-2 shadow" style="border-radius:12px;">
              <span class="fw-bold small text-uppercase opacity-75"><i class="bi bi-calculator me-1"></i>Total acumulado ({{ historial.length }} visitas)</span>
              <div class="text-end">
                <div class="small opacity-75">Facturado: <b>S/ {{ totalPago }}</b></div>
                <div class="text-warning fw-bold fs-5">Cobrado: S/ {{ totalCobrado }}</div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 p-4 pt-0 bg-light rounded-bottom-4">
          <button type="button" class="btn btn-secondary w-100 fw-bold py-2" data-bs-dismiss="modal" style="border-radius: 8px;">Cerrar Historial</button>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL NUEVO / EDITAR CLIENTE -->
  <div class="modal fade" id="modalNuevoCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg" style="border-radius:16px;">
        <div class="modal-header border-bottom p-4 d-flex flex-column align-items-center text-center position-relative">
          <h5 class="fw-bold mb-1 text-dark" style="font-size: 18px; letter-spacing: -0.5px;">
            <i class="bi bi-person-plus-fill text-warning me-2" v-if="!isEditMode"></i>
            <i class="bi bi-pencil-square text-secondary me-2" v-else></i>
            {{ isEditMode ? 'Editar Datos del Cliente' : 'Registro de Nuevo Cliente' }}
          </h5>
          <p class="text-muted mb-0" style="font-size: 13px;">{{ isEditMode ? 'Modifique los campos necesarios' : 'Ingrese los datos básicos del huésped' }}</p>
          <button type="button" class="btn-close position-absolute" style="top: 20px; right: 20px;" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4 bg-light">
          <form @submit.prevent="guardarNuevoCliente">
            <div class="row g-3 mb-4">
              <div class="col-12">
                <label class="text-muted text-uppercase fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Nombre Completo</label>
                <input type="text" v-model="nuevoCliente.nombre" class="form-control border-light shadow-sm py-2" placeholder="Ej: Juan Perez" required style="font-size: 14px; border-radius: 8px;">
              </div>
              <div class="col-md-6">
                <label class="text-muted text-uppercase fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Tipo Doc</label>
                <select v-model="nuevoCliente.tipo_doc" class="form-select border-light shadow-sm py-2" style="font-size: 14px; border-radius: 8px;">
                  <option value="DNI">DNI</option>
                  <option value="CE">CE</option>
                  <option value="PASAPORTE">PASAPORTE</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="text-muted text-uppercase fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Número</label>
                <input type="text" v-model="nuevoCliente.dni" class="form-control border-light shadow-sm py-2 fw-bold text-dark" placeholder="Documento..." required style="font-size: 14px; border-radius: 8px;">
              </div>
              <div class="col-md-6">
                <label class="text-muted text-uppercase fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Nacionalidad</label>
                <input type="text" v-model="nuevoCliente.nacionalidad" class="form-control border-light shadow-sm py-2" placeholder="Ej: Peruana" style="font-size: 14px; border-radius: 8px;">
              </div>
              <div class="col-md-6">
                <label class="text-muted text-uppercase fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Ciudad</label>
                <input type="text" v-model="nuevoCliente.ciudad" class="form-control border-light shadow-sm py-2" placeholder="Ej: Lima" style="font-size: 14px; border-radius: 8px;">
              </div>
              <div class="col-md-6">
                <label class="text-muted text-uppercase fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Celular</label>
                <input type="text" v-model="nuevoCliente.celular" class="form-control border-light shadow-sm py-2" placeholder="999 888 777" style="font-size: 14px; border-radius: 8px;">
              </div>
              <div class="col-md-6">
                <label class="text-muted text-uppercase fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Email</label>
                <input type="email" v-model="nuevoCliente.email" class="form-control border-light shadow-sm py-2" placeholder="ejemplo@correo.com" style="font-size: 14px; border-radius: 8px;">
              </div>
            </div>
            <div class="p-3 rounded-4 border bg-white shadow-sm mb-4">
              <div class="form-check form-switch d-flex align-items-center gap-2 mb-3">
                <input class="form-check-input cursor-pointer" type="checkbox" v-model="nuevoCliente.es_empresa" id="switchEmpresa">
                <label class="form-check-label text-muted fw-bold text-uppercase cursor-pointer mb-0" for="switchEmpresa" style="font-size: 11px; letter-spacing: 0.5px;">
                  <i class="bi bi-building me-1"></i> ¿Es una empresa? (Corporativo)
                </label>
              </div>

              <!-- Switch de Cliente VIP / Estrella -->
              <div class="form-check form-switch d-flex align-items-center gap-2 mb-2">
                <input class="form-check-input cursor-pointer" type="checkbox" v-model="nuevoCliente.vip" id="switchVip">
                <label class="form-check-label text-muted fw-bold text-uppercase cursor-pointer mb-0" for="switchVip" style="font-size: 11px; letter-spacing: 0.5px;">
                  <i class="bi bi-star-fill text-warning me-1"></i> ¿Destacar con Estrella? (Huésped VIP)
                </label>
              </div>

              <div v-if="nuevoCliente.es_empresa" class="row g-3 animate__animated animate__fadeIn mt-2 pt-2 border-top">
                <div class="col-12">
                  <label class="text-muted text-uppercase fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">RUC</label>
                  <input type="text" v-model="nuevoCliente.ruc" class="form-control border-light shadow-sm py-2 fw-bold text-primary" placeholder="RUC de la empresa..." style="font-size: 14px; border-radius: 8px;" maxlength="11">
                </div>
                <div class="col-12">
                  <label class="text-muted text-uppercase fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Razón Social</label>
                  <input type="text" v-model="nuevoCliente.razon_social" class="form-control border-light shadow-sm py-2 fw-bold" placeholder="Nombre de la empresa..." style="font-size: 14px; border-radius: 8px;">
                </div>
              </div>
            </div>
            <div class="d-grid mt-4">
              <button type="submit" class="btn btn-dark py-2 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" :disabled="guardando" style="font-size: 14px; border-radius: 8px;">
                <span v-if="guardando" class="spinner-border spinner-border-sm"></span>
                <i v-else class="bi bi-check-lg text-warning"></i>
                {{ guardando ? 'Guardando...' : (isEditMode ? 'Guardar Cambios' : 'Registrar Cliente') }}
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
  .table td, .table th { padding: 0.75rem 0.6rem !important; }
  .transition-all { transition: all 0.2s ease-in-out; }
  .hover-scale:hover { transform: scale(1.04); }
  .cursor-pointer { cursor: pointer !important; }
  .hover-color-warning {
    transition: all 0.2s ease-in-out;
  }
  .hover-color-warning:hover {
    color: #ffc107 !important;
    opacity: 1 !important;
    transform: scale(1.2);
  }
  .pulse-star {
    animation: starPulse 2.5s infinite ease-in-out;
  }
  @keyframes starPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.06); filter: brightness(1.1); }
    100% { transform: scale(1); }
  }

  .hover-bg-premium:hover {
    background-color: #f8fafc !important;
    border-color: #cbd5e1 !important;
  }

  .stay-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid rgba(0,0,0,0.03) !important;
  }
  .stay-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08) !important;
  }

  @media (max-width: 768px) {
    .main-content { padding: 10px !important; }
    .page-body { padding: 0 !important; }
    .topbar h4 { font-size: 1.1rem; }
    .table td { font-size: 12.5px; }
    .modal-body { padding: 12px !important; }
    .card { border-radius: 6px !important; }
    .badge { font-size: 9px; padding: 4px 6px; }
    .mini { font-size: 10px; }
  }
</style>

<?php include $_projectRoot . '/includes/footer.php'; ?>
<script src="index.js?v=<?= time() ?>"></script>
