<?php
/**
 * app/Views/reportes/comercial.php
 */
$base = '../../../';
$_projectRoot = dirname(__DIR__, 3);
require_once $_projectRoot . '/app/Middleware/auth.php';
protegerPorRol('cajera', 'reportes');

$page_title = 'Reportes Comerciales — Hotel Manager';
include $_projectRoot . '/app/Views/layouts/head.php';
?>

<!-- Librerías Necesarias -->
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/exceljs/dist/exceljs.min.js"></script>

<div id="app-comercial" style="display:contents">
  <?php include $_projectRoot . '/app/Views/layouts/sidebar.php'; ?>

  <div class="main-content">
    <div class="container-fluid py-4 animate__animated animate__fadeIn">
      
      <!-- ENCABEZADO -->
      <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-3 shadow-sm border-start border-primary border-4">
        <div>
          <h4 class="mb-0 fw-bold text-dark"><i class="bi bi-bar-chart-line me-2 text-primary"></i>Gestión Comercial y Reportes</h4>
          <p class="text-muted small mb-0">Listados avanzados para facturación, convenios corporativos y fidelización.</p>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary btn-sm" @click="cargarDatosActual">
            <i class="bi bi-arrow-clockwise" :class="{'bi-spin': loading}"></i> Actualizar
          </button>
        </div>
      </div>

      <!-- NAVEGACIÓN POR PESTAÑAS -->
      <ul class="nav nav-pills mb-3 bg-white p-2 rounded-3 shadow-sm" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active fw-bold" id="tab-facturas" data-bs-toggle="pill" data-bs-target="#content-facturas" type="button" @click="tab = 'facturas'">
            <i class="bi bi-receipt-cutoff me-2"></i>Facturas Solicitadas
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link fw-bold" id="tab-corporativas" data-bs-toggle="pill" data-bs-target="#content-corporativas" type="button" @click="tab = 'corporativas'">
            <i class="bi bi-building me-2"></i>Corporativas Extranjeras
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link fw-bold" id="tab-recurrentes" data-bs-toggle="pill" data-bs-target="#content-recurrentes" type="button" @click="tab = 'recurrentes'">
            <i class="bi bi-people me-2"></i>Pasajeros Recurrentes
          </button>
        </li>
      </ul>

      <div class="tab-content" id="pills-tabContent" v-cloak>
        <!-- 1. SOLICITUDES DE FACTURA -->
        <div class="tab-pane fade show active" id="content-facturas" role="tabpanel">
          <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-0 py-3 d-flex flex-wrap gap-3 align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-2">
                <input type="date" v-model="filtros.facturas.desde" class="form-control form-control-sm">
                <span class="text-muted small">al</span>
                <input type="date" v-model="filtros.facturas.hasta" class="form-control form-control-sm">
                <button class="btn btn-primary btn-sm px-3" @click="cargarFacturas">Filtrar</button>
              </div>
              <div class="d-flex gap-2">
                <input type="text" v-model="busqueda.facturas" class="form-control form-control-sm" placeholder="Buscar por RUC o Razón Social..." style="width:250px">
                <button class="btn btn-success btn-sm px-3" @click="exportarFacturas">
                  <i class="bi bi-file-earmark-excel me-1"></i> Excel
                </button>
              </div>
            </div>
            <div class="table-responsive" style="max-height: 650px;">
              <table class="table table-hover align-middle mb-0" style="font-size:13px;">
                <thead class="table-dark text-uppercase" style="font-size:10px; letter-spacing:0.5px;">
                  <tr>
                    <th class="px-3">Huésped Titular</th>
                    <th class="text-center">Doc</th>
                    <th class="text-center">Celular</th>
                    <th class="text-center">RUC</th>
                    <th>Razón Social</th>
                    <th class="text-center">Monto</th>
                    <th class="text-center">Fecha</th>
                    <th class="text-center">Comprobante</th>
                    <th class="text-center">Estado</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="f in facturasFiltradas" :key="f.num_comprobante">
                    <td class="px-3 fw-bold">{{ f.nombre_completo }}</td>
                    <td class="text-center text-muted">{{ f.documento_num }}</td>
                    <td class="text-center">{{ f.celular || '—' }}</td>
                    <td class="text-center fw-bold text-primary">{{ f.ruc_factura }}</td>
                    <td class="fw-semibold small">{{ f.razon_social || '—' }}</td>
                    <td class="text-center fw-bold">
                      {{ f.moneda_pago == 'USD' ? '$' : 'S/' }} {{ parseFloat(f.total_pago).toFixed(2) }}
                    </td>
                    <td class="text-center">{{ f.fecha_registro }}</td>
                    <td class="text-center"><span class="badge bg-light text-dark border">{{ f.num_comprobante || 'PENDIENTE' }}</span></td>
                    <td class="text-center">
                      <span class="badge" :class="f.estado == 'activo' ? 'bg-success' : 'bg-secondary'">{{ f.estado.toUpperCase() }}</span>
                    </td>
                  </tr>
                  <tr v-if="!facturasFiltradas.length">
                    <td colspan="9" class="text-center py-5 text-muted">No se encontraron facturas en el rango seleccionado.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- 2. CORPORATIVAS EXTRANJERAS -->
        <div class="tab-pane fade" id="content-corporativas" role="tabpanel">
           <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
              <input type="text" v-model="busqueda.corporativas" class="form-control form-control-sm" placeholder="Buscar por empresa..." style="width:300px">
              <button class="btn btn-success btn-sm px-3" @click="exportarCorporativas">
                <i class="bi bi-file-earmark-excel me-1"></i> Excel
              </button>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0" style="font-size:13px;">
                <thead class="table-dark text-uppercase" style="font-size:10px;">
                  <tr>
                    <th class="px-3">Empresa</th>
                    <th class="text-center">Nacionalidad</th>
                    <th>Contacto Ref.</th>
                    <th class="text-center">Celular / Email</th>
                    <th class="text-center">Visitas</th>
                    <th class="text-center">Primera</th>
                    <th class="text-center">Última</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="c in corpFiltradas" :key="c.empresa">
                    <td class="px-3 fw-bold text-primary">{{ c.empresa }}</td>
                    <td class="text-center">
                      <span class="badge bg-info text-dark">{{ c.nacionalidad || '—' }}</span>
                    </td>
                    <td>{{ c.contacto_referencia }}</td>
                    <td class="text-center small">
                       <div>{{ c.celular || '—' }}</div>
                       <div class="text-muted" style="font-size:10px;">{{ c.email || '—' }}</div>
                    </td>
                    <td class="text-center"><span class="badge bg-dark rounded-pill">{{ c.total_estadias }}</span></td>
                    <td class="text-center text-muted">{{ c.primera_visita }}</td>
                    <td class="text-center fw-bold">{{ c.ultima_visita }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- 3. PASAJEROS RECURRENTES -->
        <div class="tab-pane fade" id="content-recurrentes" role="tabpanel">
           <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-0 py-3 d-flex flex-wrap gap-3 justify-content-between align-items-center">
              <div class="d-flex align-items-center gap-2">
                <label class="small fw-bold">Mínimo Visitas:</label>
                <input type="number" v-model="filtros.recurrentes.min" class="form-control form-control-sm" style="width:70px" @change="cargarRecurrentes">
              </div>
              <div class="d-flex gap-2">
                <input type="text" v-model="busqueda.recurrentes" class="form-control form-control-sm" placeholder="Buscar por nombre o país..." style="width:300px">
                <button class="btn btn-success btn-sm px-3" @click="exportarRecurrentes">
                  <i class="bi bi-file-earmark-excel me-1"></i> Excel
                </button>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0" style="font-size:13px;">
                <thead class="table-dark text-uppercase" style="font-size:10px;">
                  <tr>
                    <th class="px-3">Nombre Completo</th>
                    <th class="text-center">Pasaporte</th>
                    <th class="text-center">Nacionalidad</th>
                    <th class="text-center">Celular / Email</th>
                    <th class="text-center">Total Visitas</th>
                    <th class="text-center">Primera Visita</th>
                    <th class="text-center">Última Visita</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="r in recFiltrados" :key="r.pasaporte">
                    <td class="px-3 fw-bold">{{ r.nombre_completo }}</td>
                    <td class="text-center text-muted fw-bold">{{ r.pasaporte }}</td>
                    <td class="text-center">
                      <div class="badge bg-light text-dark border">{{ r.nacionalidad || '—' }}</div>
                    </td>
                    <td class="text-center small">
                       <div>{{ r.celular || '—' }}</div>
                       <div class="text-muted" style="font-size:10px;">{{ r.email || '—' }}</div>
                    </td>
                    <td class="text-center"><span class="badge bg-warning text-dark rounded-pill" style="font-size:12px;">{{ r.total_visitas }}</span></td>
                    <td class="text-center text-muted">{{ r.primera_visita }}</td>
                    <td class="text-center fw-bold">{{ r.ultima_visita }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script src="comercial.js"></script>
<?php include $_projectRoot . '/app/Views/layouts/footer.php'; ?>
