<?php
/**
 * app/Views/rooming/index.php
 */
$base = '../../../';
require_once $base . 'auth/middleware.php';
protegerPorRol('cajera');

$page_title = 'Rooming & Check-in — Hotel Manager';
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';
?>

<div class="main-content" id="app-rooming">
  <div class="topbar">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
    <div>
      <h4><i class="bi bi-calendar-check-fill me-2 text-primary"></i>Rooming / Check-in</h4>
      <p>Gestión de estadías activas y registro de ingresos</p>
    </div>
    <button class="btn btn-primary d-flex align-items-center gap-2" @click="abrirCheckin">
      <i class="bi bi-plus-lg"></i> Nuevo Check-in
    </button>
  </div>

  <div class="page-body">
    <!-- FILTROS Y BUSCADOR -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
      <div class="card-body p-3">
        <div class="row g-2 align-items-center">
          <div class="col-md-4">
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
              <input type="text" class="form-control border-start-0" v-model="busqueda" placeholder="Buscar por huésped o habitación...">
            </div>
          </div>
          <div class="col-md-2">
            <select class="form-select" v-model="filtroPiso">
              <option value="">Todos los pisos</option>
              <option v-for="p in [2,3,4,5,6]" :key="p" :value="p">Piso {{ p }}</option>
            </select>
          </div>
          <div class="col-md-2">
            <select class="form-select" v-model="filtroPago">
              <option value="">Todos los pagos</option>
              <option value="pendiente">Pendiente</option>
              <option value="parcial">Parcial</option>
              <option value="pagado">Pagado</option>
            </select>
          </div>
          <div class="col text-end">
             <button class="btn btn-light" @click="cargarDatos" :disabled="loading">
               <i class="bi bi-arrow-clockwise"></i>
             </button>
          </div>
        </div>
      </div>
    </div>

    <!-- TABLA DE ESTADÍAS ACTIVAS -->
    <div class="card border-0 shadow-sm overflow-hidden" style="border-radius:12px;">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light">
            <tr>
              <th class="ps-4" style="width: 125px;">HAB.</th>
              <th>HUÉSPED TITULAR</th>
              <th style="width: 280px; white-space: nowrap;">INGRESO / SALIDA</th>
              <th style="width: 180px;">MONTO / PAGADO</th>
              <th style="width: 120px;">ESTADO PAGO</th>
              <th style="width: 115px;">CANAL</th>
              <th class="text-end pe-4" style="width: 120px;">ACCIONES</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading" ><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>
            <tr v-else v-for="s in staysFiltrados" :key="s.id">
              <td class="ps-4">
                <div class="fw-bold fs-5 text-primary">#{{ s.hab_numero }}</div>
                <div class="text-muted small">{{ s.hab_tipo }}</div>
              </td>
              <td>
                <div class="fw-bold">{{ s.titular_nombre || '---' }}</div>
                <div class="text-muted small">ID Stay: {{ s.id }} | Pax: {{ s.pax_total }}</div>
              </td>
              <td class="small text-nowrap">
                <div class="d-flex align-items-center gap-3">
                  <span><i class="bi bi-box-arrow-in-right text-success me-1"></i> {{ fmtFecha(s.fecha_registro) }}</span>
                  <span class="text-muted opacity-50">|</span>
                  <span><i class="bi bi-box-arrow-out-right text-danger me-1"></i> {{ fmtFecha(s.fecha_checkout) }}</span>
                </div>
                <div class="text-muted mt-1">{{ s.noches }} noches</div>
              </td>
              <td>
                <div class="fw-bold">{{ s.moneda_pago }} {{ s.total_pago }}</div>
                <div class="text-success small">Cobrado: PEN {{ s.total_cobrado }}</div>
              </td>
              <td>
                <span class="badge" :class="getPagoClass(s.estado_pago)">{{ s.estado_pago.toUpperCase() }}</span>
              </td>
              <td>
                <span class="text-muted small">{{ s.medio_reserva }}</span>
              </td>
              <td class="text-end pe-4">
                <div class="btn-group shadow-sm" style="border-radius:8px; overflow:hidden;">
                  <button class="btn btn-white btn-sm border" title="Detalle" @click="verDetalle(s)">
                    <i class="bi bi-eye text-primary"></i>
                  </button>
                  <button class="btn btn-white btn-sm border" title="Registrar Pago" @click="abrirPago(s)">
                    <i class="bi bi-wallet2 text-success"></i>
                  </button>
                  <button class="btn btn-white btn-sm border" title="Checkout" @click="procederCheckout(s)">
                    <i class="bi bi-door-closed text-danger"></i>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!loading && staysFiltrados.length === 0">
              <td colspan="7" class="text-center py-5 text-muted">No se encontraron estadías activas.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- MODAL CHECKIN (EXTENSO) -->
  <?php include 'modal_checkin.php'; ?>
  
  <!-- MODAL DETALLE -->
  <?php include 'modal_detalle.php'; ?>

</div>

<!-- Scripts -->
<script>
  window.authUser = <?= json_encode(['id' => $_SESSION['auth_id'], 'nombre' => $_SESSION['auth_nombre']]) ?>;
</script>
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="rooming.js?v=<?= time() ?>"></script>

<style>
  .btn-white { background: white; }
  .btn-white:hover { background: #f8f9fa; }
  .badge { padding: 8px 12px; border-radius: 8px; font-weight: 600; font-size: 10px; }
  .table thead th { font-size: 11px; letter-spacing: 0.5px; color: #6c757d; border-bottom: none; border-top:none; text-transform: uppercase; }
  .form-control, .form-select { border-radius: 8px; border: 1px solid #e0e0e0; }
  
  /* Secciones del Modal Check-in */
  .modal-section-title { font-size: 12px; font-weight: 800; color: #adb5bd; letter-spacing: 1px; margin-bottom: 20px; border-bottom: 1px solid #f0f0f0; padding-bottom: 8px; }
</style>

</body></html>
