<?php
/**
 * app/Views/rooming/index.php
 */
$base = '../../../';
$_projectRoot = dirname(__DIR__, 3);
require_once $_projectRoot . '/auth/middleware.php';
protegerPorRol('cajera', 'rooming');

$page_title = 'Rooming & Check-in — Hotel Manager';
include $_projectRoot . '/includes/head.php';
?>

<div id="app-rooming" style="display:contents">
  <?php include $_projectRoot . '/includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar border-bottom-0 shadow-sm" style="background: linear-gradient(to right, #ffffff, #f8f9fa);">
      <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list fs-4"></i></button>
      <div>
        <h4 class="fw-bold" style="color: #111; letter-spacing: -0.5px;">
          <i class="bi bi-calendar-check-fill me-2" style="color: #d4af37;"></i>Rooming / Check-in
        </h4>
        <p class="mb-0 small text-muted fw-semibold">Gestión de estadías activas y registro de ingresos</p>
      </div>
      <div class="ms-auto d-flex gap-2 align-items-center">
        <button class="btn btn-sm btn-outline-secondary shadow-sm px-2 px-sm-3" @click="abrirReportePax"
          style="font-weight: 700; font-size: 11px; border-color: #555;">
          <i class="bi bi-file-earmark-person me-1" style="color:#6c757d;"></i>
          <span>REGISTRO PAX</span>
        </button>
        <button class="btn btn-sm btn-primary shadow-sm px-2 px-sm-3" @click="abrirCheckin"
          style="border: 1px solid #111; font-weight: 700; font-size: 11px;">
          <i class="bi bi-plus-lg text-warning me-1"></i>
          <span>NUEVO</span>
        </button>
      </div>
    </div>

    <div class="page-body">
      <!-- FILTROS Y BUSCADOR -->
      <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
        <div class="card-body p-3">
          <div class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
              <div class="input-group input-group-sm rounded shadow-sm">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-start-0 bg-white fw-bold text-secondary"
                  style="font-size: 12px;" v-model="busqueda" placeholder="Buscar huésped / hab...">
              </div>
            </div>
            <div class="col-6 col-md-2">
              <select class="form-select form-select-sm fw-bold text-secondary shadow-sm" style="font-size: 11px;"
                v-model="filtroPiso">
                <option value="">Pisos: Todos</option>
                <option v-for="p in [2,3,4,5,6]" :key="p" :value="p">Piso {{ p }}</option>
              </select>
            </div>
            <div class="col-6 col-md-2">
              <select class="form-select form-select-sm fw-bold text-secondary shadow-sm" style="font-size: 11px;"
                v-model="filtroPago">
                <option value="">Pagos: Todos</option>
                <option value="pendiente">Pendiente</option>
                <option value="adelanto">Adelanto</option>
                <option value="parcial">Parcial</option>
                <option value="pagado">Pagado</option>
              </select>
            </div>
            <div class="col col-md-auto ms-auto text-end">
              <button class="btn btn-light btn-sm shadow-sm px-3" @click="cargarDatos" :disabled="loading">
                <i class="bi bi-arrow-clockwise"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <style>
        .row-unpaid {
          background-color: #e8f5e9 !important;
        }

        /* Verde suave para pendientes */
        .row-unpaid:hover {
          background-color: #c8e6c9 !important;
        }
      </style>

      <!-- TABLA DE ESTADÍAS ACTIVAS -->
      <div class="card border-0 shadow-sm overflow-hidden" style="border-radius:12px;">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-dark text-white text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
              <tr>
                <th class="ps-4" style="width: 60px;">ID</th>
                <th style="width: 100px;">HAB.</th>
                <th style="width: 250px; max-width: 250px;">HUÉSPED TITULAR</th>
                <th style="width: 160px;">FECHAS</th>
                <th class="text-end">MONTO</th>
                <th class="text-center">PAGO</th>
                <th class="text-center">MEDIO</th>
                <th class="text-end pe-4" style="width: 120px;">ACCIONES</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="7" class="text-center py-5">
                  <div class="spinner-border text-primary"></div>
                </td>
              </tr>
              <tr v-else v-for="s in staysFiltrados" :key="s.id" :class="{'row-unpaid': s.estado_pago !== 'pagado'}">
                <td class="ps-4">
                  <span class="badge bg-light text-dark border fw-bold">#{{ s.id }}</span>
                </td>
                <td>
                  <div class="fw-bold fs-5" style="color: #111;">#{{ s.hab_numero }}</div>
                  <span class="badge" :class="getEstadBadge(s.estado)" style="font-size: 8px; padding: 4px 8px;">{{
                    s.estado.toUpperCase() }}</span>
                  <div class="text-muted small fw-semibold" style="letter-spacing: 0.5px;">{{ s.hab_tipo }}</div>
                </td>
                <td style="width: 250px; max-width: 250px;">
                  <div class="fw-bold" style="white-space: normal; line-height: 1.2; word-break: break-word;">{{
                    s.titular_nombre || '---' }}</div>
                  <div class="text-muted small">Pax: {{ s.pax_total }} personas</div>
                  <div class="mt-1">
                    <span
                      style="font-size:10px; background:#f0f9ff; color:#0369a1; padding:2px 8px; border-radius:20px; font-weight:600; letter-spacing:.3px; border:1px solid #bae6fd;">
                      <i class="bi bi-person-fill-check me-1"></i>{{ s.operador || s.cobrador || '—' }}
                    </span>
                  </div>
                </td>
                <td class="small text-nowrap">
                  <div class="mb-1">
                    <span>Ingreso: <span class="fw-bold">{{ fmtFecha(s.fecha_registro) }}</span></span>
                  </div>
                  <div>
                    <span><i class="bi bi-box-arrow-out-right text-danger me-1"></i> Salida: <span class="fw-bold">{{
                        fmtFecha(s.fecha_checkout) }}</span></span>
                  </div>
                  <div class="text-muted mt-1" style="font-size: 11px;">🛏️ {{ s.noches }} noches</div>
                </td>
                <td class="text-end fw-bold">
                  <div class="text-dark">{{ s.moneda_pago == 'USD' ? '$' : (s.moneda_pago == 'CLP' ? 'P$' : 'S/') }} {{
                    fmtCur(s.total_pago) }}</div>
                  <div class="text-success small" style="font-size: 10px;">Abono {{ s.moneda_pago == 'USD' ? '$' :
                    (s.moneda_pago == 'CLP' ? 'P$' : 'S/') }} {{ fmtCur(s.total_cobrado_orig || s.total_cobrado) }}
                  </div>
                </td>
                <td class="text-center">
                  <span class="badge" :class="getPagoClass(s.estado_pago)" style="font-size: 9px;">{{
                    s.estado_pago.toUpperCase() }}</span>
                </td>
                <td class="text-center">
                  <span v-if="s.metodo_pago" class="badge bg-light text-dark border fw-bold"
                    style="font-size:9px; padding:4px 7px;">
                    {{ s.metodo_pago }}
                  </span>
                  <span v-else class="text-muted small">—</span>
                </td>
                <td class="text-end pe-4">
                  <div class="btn-group shadow-sm" style="border-radius:8px; overflow:hidden;">
                    <button v-if="s.estado === 'reservado'" class="btn btn-success btn-sm border"
                      title="Activar Check-in" @click="activarReserva(s)">
                      <i class="bi bi-person-check-fill"></i>
                    </button>
                    <button class="btn btn-white btn-sm border" title="Detalle" @click="verDetalle(s)">
                      <i class="bi bi-eye text-primary"></i>
                    </button>
                    <button v-if="s.estado !== 'cancelado'" class="btn btn-white btn-sm border" title="Editar"
                      @click="abrirEdicion(s)">
                      <i class="bi bi-pencil-square text-secondary"></i>
                    </button>
                    <button v-if="s.estado !== 'reservado' && s.estado !== 'cancelado'"
                      class="btn btn-white btn-sm border" title="Registrar Consumo" @click="abrirConsumo(s)">
                      <i class="bi bi-cup-straw text-warning"></i>
                    </button>
                    <button v-if="s.estado !== 'reservado' && s.estado !== 'cancelado'"
                      class="btn btn-white btn-sm border" title="Registrar Pago" @click="abrirPago(s)">
                      <i class="bi bi-wallet2 text-success"></i>
                    </button>
                    <button v-if="s.estado !== 'reservado' && s.estado !== 'cancelado'"
                      class="btn btn-white btn-sm border" title="Checkout" @click="procederCheckout(s)">
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

  </div> <!-- end of main-content -->

  <!-- ╔══════════════════════════════════════════════════════╗ -->
  <!-- ║          MODAL REGISTRO PAX (Reporte Mensual)       ║ -->
  <!-- ╚══════════════════════════════════════════════════════╝ -->
  <div class="modal fade" id="modalReportePax" tabindex="-1" aria-labelledby="modalReportePaxLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
      <div class="modal-content border-0" style="background:#f4f6fb;">
        <!-- Header -->
        <div class="modal-header border-0 py-3 px-4"
          style="background:linear-gradient(135deg,#1a1a2e 0%,#16213e 60%,#0f3460 100%);">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center"
              style="width:40px;height:40px;background:rgba(255,255,255,0.12);">
              <i class="bi bi-file-earmark-person text-white fs-5"></i>
            </div>
            <div>
              <h5 class="modal-title fw-bold text-white mb-0" id="modalReportePaxLabel">Registro PAX — Reporte Mensual
              </h5>
              <small class="text-white opacity-60">Listado de check-ins por mes para control de huéspedes</small>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <!-- Filtros -->
        <div class="px-4 pt-3 pb-2" style="background:white; border-bottom:1px solid #e9ecef;">
          <div class="d-flex flex-wrap gap-3 align-items-end">
            <div>
              <label class="form-label small fw-bold text-muted text-uppercase mb-1"
                style="font-size:10px;letter-spacing:.5px;">Mes</label>
              <select v-model="reportePax.mes" class="form-select form-select-sm fw-bold" style="width:140px;"
                @change="cargarReportePax">
                <option value="1">Enero</option>
                <option value="2">Febrero</option>
                <option value="3">Marzo</option>
                <option value="4">Abril</option>
                <option value="5">Mayo</option>
                <option value="6">Junio</option>
                <option value="7">Julio</option>
                <option value="8">Agosto</option>
                <option value="9">Septiembre</option>
                <option value="10">Octubre</option>
                <option value="11">Noviembre</option>
                <option value="12">Diciembre</option>
              </select>
            </div>
            <div>
              <label class="form-label small fw-bold text-muted text-uppercase mb-1"
                style="font-size:10px;letter-spacing:.5px;">Año</label>
              <select v-model="reportePax.anio" class="form-select form-select-sm fw-bold" style="width:100px;"
                @change="cargarReportePax">
                <option v-for="y in reportePax.anios" :key="y" :value="y">{{ y }}</option>
              </select>
            </div>
            <div class="ms-auto d-flex gap-2">
              <span class="badge bg-primary align-self-center px-3 py-2" style="font-size:11px;"
                v-if="!reportePax.cargando">
                <i class="bi bi-people-fill me-1"></i>{{ reportePax.filas.length }} registros
              </span>
              <button class="btn btn-sm btn-success fw-bold px-3" @click="exportarReportePax"
                :disabled="reportePax.filas.length === 0">
                <i class="bi bi-file-earmark-excel me-1"></i>Exportar Excel
              </button>
            </div>
          </div>
        </div>

        <!-- Cuerpo -->
        <div class="modal-body p-0">
          <!-- Loader -->
          <div v-if="reportePax.cargando" class="d-flex justify-content-center align-items-center py-5">
            <div class="spinner-border text-primary me-3"></div>
            <span class="text-muted fw-semibold">Cargando reporte...</span>
          </div>

          <!-- Sin datos -->
          <div v-else-if="reportePax.filas.length === 0" class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 opacity-25 d-block mb-2"></i>
            <span class="fw-semibold">No se encontraron check-ins para este período.</span>
          </div>

          <!-- Tabla -->
          <div v-else class="table-responsive" style="overflow-x:auto;">
            <table id="tablaPaxReporte" class="table table-bordered table-hover mb-0 align-middle"
              style="font-size:11px; white-space:nowrap; min-width:2400px;">
              <thead style="background:#1a1a2e; color:white; position:sticky; top:0; z-index:10;">
                <tr>
                  <th class="px-3 py-2 text-center" style="min-width:80px;">OPERADOR</th>
                  <th class="px-3 py-2 text-center" style="min-width:90px;">FECHA<br>REGISTRO</th>
                  <th class="px-3 py-2 text-center" style="min-width:60px;">HAB</th>
                  <th class="px-3 py-2 text-center" style="min-width:130px;">TIPO DE HAB</th>
                  <th class="px-3 py-2 text-center" style="min-width:45px;">PAX</th>
                  <th class="px-3 py-2 text-center" style="min-width:110px;">MEDIO DE<br>RESERVA</th>
                  <th class="px-3 py-2 text-center" style="min-width:90px;">HORA DE<br>CHECK IN</th>
                  <th class="px-3 py-2 text-center" style="min-width:200px;">NOMBRE Y APELLIDO</th>
                  <th class="px-3 py-2 text-center" style="min-width:130px;">DOCUMENTO<br>DE IDENTIDAD</th>
                  <th class="px-3 py-2 text-center" style="min-width:110px;">NÚMERO</th>
                  <th class="px-3 py-2 text-center" style="min-width:100px;">NACIONALIDAD</th>
                  <th class="px-3 py-2 text-center" style="min-width:90px;">CIUDAD</th>
                  <th class="px-3 py-2 text-center" style="min-width:100px;">CHECK IN<br>FECHA</th>
                  <th class="px-3 py-2 text-center" style="min-width:100px;">CHECK OUT<br>FECHA</th>
                  <th class="px-3 py-2 text-center" style="min-width:100px;">PAGO TOTAL</th>
                  <th class="px-3 py-2 text-center" style="min-width:90px;">LATE<br>CHECK OUT</th>
                  <th class="px-3 py-2 text-center" style="min-width:120px;">MEDIO DE<br>PAGO</th>
                  <th class="px-3 py-2 text-center" style="min-width:130px;">COMPROBANTE<br>DE PAGO</th>
                  <th class="px-3 py-2 text-center" style="min-width:110px;">NÚMERO DE<br>COMPROBANTE</th>
                  <th class="px-3 py-2 text-center" style="min-width:90px;">QUIEN COBRO</th>
                  <th class="px-3 py-2 text-center" style="min-width:65px;">CARRO</th>
                  <th class="px-3 py-2 text-center" style="min-width:180px;">OBSERVACIONES</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="(fila, idx) in reportePax.filas" :key="idx">
                  <!-- Fila por cada PAX -->
                  <tr :class="fila.es_titular ? 'table-light fw-semibold' : ''"
                    :style="fila.es_titular ? 'border-top:2px solid #dee2e6;' : 'background:#fafafa;'">
                    <!-- OPERADOR -->
                    <td class="px-2 text-center">
                      <span v-if="fila.es_titular" class="badge"
                        style="background:#e8f4fd;color:#0369a1;font-size:10px;font-weight:700;">{{ fila.operador
                        }}</span>
                      <span v-else class="text-muted" style="font-size:10px;">—</span>
                    </td>
                    <!-- FECHA REGISTRO -->
                    <td class="px-2 text-center">
                      <span v-if="fila.es_titular">{{ fmtFecha(fila.fecha_registro) }}</span>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <!-- HAB -->
                    <td class="px-2 text-center fw-bold" style="color:#1a1a2e;">
                      <span v-if="fila.es_titular">#{{ fila.hab_numero }}</span>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <!-- TIPO HAB -->
                    <td class="px-2">
                      <span v-if="fila.es_titular" class="text-uppercase" style="font-size:10px;font-weight:600;">{{
                        fila.tipo_hab_declarado }}</span>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <!-- PAX -->
                    <td class="px-2 text-center">
                      <span v-if="fila.es_titular" class="badge bg-secondary" style="font-size:10px;">{{ fila.pax_total
                        }}</span>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <!-- MEDIO RESERVA -->
                    <td class="px-2 text-center">
                      <span v-if="fila.es_titular" class="badge"
                        style="background:#f0fdf4;color:#166534;font-size:9px;font-weight:700;">{{ fila.medio_reserva
                        }}</span>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <!-- HORA CHECK IN -->
                    <td class="px-2 text-center">
                      <span v-if="fila.es_titular" class="fw-bold text-dark">{{ fila.hora_checkin || '—' }}</span>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <!-- NOMBRE Y APELLIDO -->
                    <td class="px-2 fw-bold" style="color:#0f3460;">{{ fila.nombre_completo }}</td>
                    <!-- DOCUMENTO TIPO -->
                    <td class="px-2 text-center">
                      <span class="badge bg-dark" style="font-size:9px;">{{ fila.documento_tipo }}</span>
                    </td>
                    <!-- NÚMERO DOC -->
                    <td class="px-2 text-center fw-bold">{{ fila.documento_num }}</td>
                    <!-- NACIONALIDAD -->
                    <td class="px-2 text-center">{{ fila.nacionalidad || '—' }}</td>
                    <!-- CIUDAD -->
                    <td class="px-2 text-center">{{ fila.ciudad || '—' }}</td>
                    <!-- CHECK IN FECHA -->
                    <td class="px-2 text-center">
                      <span v-if="fila.es_titular" class="text-success fw-bold">{{ fmtFecha(fila.fecha_registro)
                        }}</span>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <!-- CHECK OUT FECHA -->
                    <td class="px-2 text-center">
                      <span v-if="fila.es_titular" class="text-danger fw-bold">{{ fmtFecha(fila.fecha_checkout)
                        }}</span>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <!-- PAGO TOTAL -->
                    <td class="px-2 text-end fw-bold" style="color:#0f3460;">
                      <span v-if="fila.es_titular">
                        {{ fila.moneda_pago == 'USD' ? '$' : (fila.moneda_pago == 'CLP' ? 'P$' : 'S/') }} {{
                        fmtCur(fila.total_pago) }}
                      </span>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <!-- LATE CHECK OUT -->
                    <td class="px-2 text-center">
                      <span v-if="fila.es_titular">
                        <span v-if="fila.estado === 'late_checkout'" class="badge bg-warning text-dark"
                          style="font-size:9px;">SÍ</span>
                        <span v-else class="text-muted" style="font-size:10px;">NO</span>
                      </span>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <!-- MEDIO PAGO -->
                    <td class="px-2 text-center">
                      <span v-if="fila.es_titular" class="badge bg-light text-dark border fw-bold"
                        style="font-size:9px;">{{ fila.metodo_pago || '—' }}</span>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <!-- COMPROBANTE DE PAGO -->
                    <td class="px-2 text-center">
                      <span v-if="fila.es_titular" class="fw-semibold text-uppercase" style="font-size:10px;">{{
                        fila.tipo_comprobante || '—' }}</span>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <!-- NÚMERO COMPROBANTE -->
                    <td class="px-2 text-center fw-bold">
                      <span v-if="fila.es_titular">{{ fila.num_comprobante || '—' }}</span>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <!-- QUIEN COBRÓ -->
                    <td class="px-2 text-center">
                      <span v-if="fila.es_titular" class="badge"
                        style="background:#fef3c7;color:#92400e;font-size:9px;font-weight:700;">{{ fila.cobrador || '—'
                        }}</span>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <!-- CARRO -->
                    <td class="px-2 text-center">
                      <span v-if="fila.es_titular">
                        <span v-if="fila.carro === 'SI'" class="badge bg-info text-dark" style="font-size:9px;"><i
                            class="bi bi-car-front-fill"></i> SÍ</span>
                        <span v-else class="text-muted" style="font-size:10px;">NO</span>
                      </span>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <!-- OBSERVACIONES -->
                    <td class="px-2" style="max-width:200px; white-space:normal;">
                      <span v-if="fila.es_titular" class="text-muted" style="font-size:10px;">{{ fila.observaciones ||
                        '' }}</span>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer border-0 bg-white py-2 px-4">
          <small class="text-muted me-auto">
            <i class="bi bi-info-circle me-1"></i>
            Reporte generado a partir de los check-ins confirmados del mes seleccionado. Una fila por cada huésped
            registrado.
          </small>
          <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>
  <!-- / MODAL REGISTRO PAX -->

  <!-- Registro de Check-in -->
  <div class="modal fade" id="modalCheckin" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content border-0 shadow" style="border-radius:16px;">
        <div class="modal-header border-0 pb-0 ps-4 pe-4 pt-4">
          <h5 class="fw-bold mb-0">{{ form.stay.id ? 'Editar Registro #' + form.stay.id : 'Registro de Check-in' }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form @submit.prevent="guardarCheckin">
          <div class="modal-body p-4">
            <div class="row g-4">
              <!-- SECCIÓN 1: HABITACIÓN Y ESTADÍA -->
              <div class="col-md-4 border-end">
                <div class="modal-section-title">1. HABITACIÓN Y ESTADÍA</div>
                <div class="mb-3">
                  <label class="form-label small fw-bold">Habitación disponible</label>
                  <select v-model="form.stay.habitacion_id" id="inputHabitacion" class="form-select" required
                    @change="onHabChange">
                    <option value="">Seleccione...</option>
                    <option v-for="h in habitacionesLibres" :key="h.id" :value="h.id">
                      #{{ h.numero }} - {{ h.tipo }} (S/ {{ h.precio_base }})
                    </option>
                  </select>
                </div>
                <div class="row g-2 mb-3">
                  <div class="col-6">
                    <label class="form-label small fw-bold">Check-in</label>
                    <input type="date" v-model="form.stay.fecha_registro" class="form-control" required
                      @change="calcularNoches">
                  </div>
                  <div class="col-6">
                    <label class="form-label small fw-bold">Hora</label>
                    <input type="time" v-model="form.stay.hora_checkin" class="form-control" required>
                  </div>
                </div>
                <div class="row g-2 mb-3">
                  <div class="col-6">
                    <label class="form-label small fw-bold">Noches</label>
                    <input type="number" v-model="form.stay.noches" class="form-control" min="1" required
                      @input="onNochesChange">
                  </div>
                  <div class="col-6">
                    <label class="form-label small fw-bold">Check-out Est.</label>
                    <input type="date" v-model="form.stay.fecha_checkout" class="form-control" readonly>
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label small fw-bold">Canal de Reserva</label>
                  <select v-model="form.stay.medio_reserva" class="form-select" required>
                    <option value="DIRECTO">DIRECTO</option>
                    <option value="LLAMADA">LLAMADA</option>
                    <option value="WHATSAPP">WHATSAPP</option>
                    <option value="BOOKING">BOOKING</option>
                    <option value="CORREO">CORREO</option>
                  </select>
                </div>
                <div class="mb-2">
                  <label class="form-label small fw-bold d-flex align-items-center gap-1">
                    <i class="bi bi-sticky text-warning"></i> Observaciones
                    <span class="text-muted fw-normal" style="font-size:10px;">(opcional)</span>
                  </label>
                  <textarea v-model="form.stay.observaciones" class="form-control form-control-sm" rows="2"
                    placeholder="Ej: Precio ajustado — hab. superior cedida a tarifa económica"
                    style="resize:none; font-size:12px;"></textarea>
                </div>
              </div>
              <!-- SECCIÓN 2: HUÉSPEDES (PAX) -->
              <div class="col-md-5 border-end">
                <div class="modal-section-title d-flex justify-content-between">
                  2. HUÉSPEDES (PAX)
                  <button type="button" class="btn btn-outline-primary btn-sm py-0" @click="agregarPax">
                    <i class="bi bi-person-plus"></i> Añadir
                  </button>
                </div>
                <div v-for="(pax, idx) in form.pax" :key="idx"
                  class="p-3 bg-light rounded-3 mb-3 position-relative shadow-sm" style="border: 1px solid #eee;">
                  <button v-if="idx > 0" type="button" class="btn-close position-absolute top-0 end-0 m-2"
                    style="font-size:10px" @click="form.pax.splice(idx, 1)"></button>
                  <div class="mb-2">
                    <input type="text" v-model="pax.nombre_completo" id="inputNombreHuesped"
                      class="form-control-sm form-control" placeholder="Nombre completo" required>
                  </div>
                  <div class="row g-2">
                    <div class="col-4">
                      <select v-model="pax.documento_tipo" class="form-select form-select-sm">
                        <option value="DNI">DNI</option>
                        <option value="CE">CE</option>
                        <option value="PASA">PASAPORTE</option>
                      </select>
                    </div>
                    <div class="col-8 position-relative">
                      <input type="text" v-model="pax.documento_num" id="inputDocumentoHuesped"
                        class="form-control form-control-sm" placeholder="Num. documento" required
                        @input="buscarPax(pax, idx)" @blur="ocultarSugerencias(idx)" autocomplete="off">
                      <!-- Dropdown sugerencias -->
                      <div v-if="sugerencias[idx] && sugerencias[idx].length"
                        class="position-absolute bg-white border rounded shadow-sm w-100 z-3"
                        style="top:100%; left:0; max-height:200px; overflow-y:auto;">
                        <div v-for="s in sugerencias[idx]" :key="s.documento_num"
                          class="px-3 py-2 cursor-pointer border-bottom d-flex align-items-center gap-2"
                          style="cursor:pointer; font-size:12px;" @mousedown.prevent="aplicarSugerencia(pax, idx, s)">
                          <span class="badge bg-secondary" style="font-size:9px;">{{ s.documento_tipo }}</span>
                          <span class="fw-bold text-primary">{{ s.documento_num }}</span>
                          <span class="text-muted">{{ s.nombre_completo }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="row g-2 mt-1">
                    <div class="col-6">
                      <input type="text" v-model="pax.nacionalidad" class="form-control form-control-sm"
                        placeholder="Nacionalidad">
                    </div>
                    <div class="col-6 d-flex align-items-center">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" :name="'titular'" :id="'tit'+idx"
                          :checked="pax.es_titular" @change="setTitular(idx)">
                        <label class="form-check-label small" :for="'tit'+idx">Titular</label>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="p-3 bg-white rounded-3 border">
                  <label class="form-label small fw-bold">Trae vehiculo</label>
                  <select v-model="form.stay.carro" class="form-select form-select-sm">
                    <option value="NO">NO</option>
                    <option value="SI">SI</option>
                  </select>
                </div>
              </div>
              <!-- SECCIÓN 3: PAGO Y REGISTRO -->
              <div class="col-md-3">
                <div class="modal-section-title">3. PAGO Y REGISTRO</div>

                <!-- TOTAL BASE (PEN) + DIVISA en una fila -->
                <div class="d-flex align-items-center gap-2 mb-3">
                  <div class="flex-grow-1">
                    <label class="form-label micro-text fw-bold mb-1">TOTAL BASE (PEN)</label>
                    <div class="input-group input-group-sm shadow-sm">
                      <span class="input-group-text bg-light fw-bold border-0">S/</span>
                      <input type="number" v-model="form.stay.total_pago" id="inputMontoPago"
                        class="form-control fw-bold text-dark" step="0.50" min="0" style="border-color:#ffc107;"
                        @input="recalcularMoneda">
                    </div>
                  </div>
                  <div>
                    <label class="form-label micro-text fw-bold mb-1">DIVISA</label>
                    <select v-model="form.stay.moneda_pago" class="form-select form-select-sm"
                      @change="recalcularMoneda" style="width:90px;">
                      <option value="PEN">S/ Soles</option>
                      <option value="USD">$ USD</option>
                      <option value="CLP">P$ CLP</option>
                    </select>
                  </div>
                </div>

                <!-- TC compacto (solo si divisa != PEN) -->
                <div v-if="form.stay.moneda_pago !== 'PEN'"
                  class="d-flex align-items-center gap-2 mb-3 px-2 py-1 rounded border bg-light"
                  style="font-size:12px;">
                  <span class="fw-bold text-muted text-nowrap">T.C.</span>
                  <input type="number" v-model="tcs[form.stay.moneda_pago]"
                    class="form-control form-control-sm border-0 bg-white fw-bold text-center shadow-sm"
                    style="width:58px; font-size:12px; height:28px;" step="0.0001" @input="recalcularMoneda">
                  <i class="bi bi-caret-right-fill text-secondary" style="font-size:10px;"></i>
                  <span class="fw-bold text-primary ms-auto text-nowrap" style="font-size:13px;">
                    {{ form.stay.moneda_pago == 'USD' ? '$' : 'CLP' }} {{ fmtCur(form.stay.monto_original) }}
                  </span>
                </div>

                <!-- MÉTODO DE PAGO -->
                <div class="mb-3">
                  <label class="form-label micro-text fw-bold mb-1">MÉTODO DE PAGO</label>
                  <select v-model="form.stay.metodo_pago" id="inputMetodoPago" class="form-select form-select-sm">
                    <option value="">Seleccione...</option>
                    <option v-for="m in mediosPago" :key="m.id" :value="m.nombre" :disabled="m.activo != 1">{{ m.nombre
                      }}</option>
                  </select>
                </div>

                <div class="mb-3">
                  <label class="form-label micro-text fw-bold mb-2 d-block">TIPO DE COBRO</label>
                  <div class="row g-2">
                    <div class="col-6">
                      <button type="button" class="btn btn-sm w-100 fw-bold"
                        :class="form.tipoPago === 'completo' ? 'btn-primary' : 'btn-outline-primary'"
                        @click="cambiarTipoPago('completo')">
                        Pagar Completo
                      </button>
                    </div>
                    <div class="col-6">
                      <button type="button" class="btn btn-sm w-100 fw-bold"
                        :class="form.tipoPago === 'adelanto' ? 'btn-warning text-dark' : 'btn-outline-warning'"
                        @click="cambiarTipoPago('adelanto')">
                        Dejar Adelanto
                      </button>
                    </div>
                  </div>
                </div>

                <div class="mb-3" v-if="form.tipoPago === 'adelanto'">
                  <label class="form-label micro-text fw-bold mb-1">ADELANTO ({{ form.stay.moneda_pago }})</label>
                  <input type="number" v-model="form.adelanto" class="form-control form-control-sm" min="0.01"
                    step="0.01" @input="onAdelantoChange" placeholder="0.00">
                  <div class="small mt-1" :class="adelantoExcede ? 'text-danger fw-bold' : 'text-muted'">
                    <span v-if="adelantoExcede">El adelanto no puede superar el total.</span>
                    <span v-else-if="(parseFloat(form.adelanto) || 0) <= 0">El adelanto debe ser mayor a 0.</span>
                    <span v-else>Saldo pendiente: {{ form.stay.moneda_pago }} {{ fmtCur(saldoPendienteOriginal)
                      }}</span>
                  </div>
                </div>

                <!-- COMPROBANTE + N° RECIBO -->
                <div class="row g-1">
                  <div class="col-7">
                    <label class="form-label micro-text fw-bold mb-1">COMPROBANTE</label>
                    <select v-model="form.stay.tipo_comprobante" class="form-select form-select-sm">
                      <option value="RECIBO">RECIBO</option>
                      <option value="BOLETA">BOLETA</option>
                      <option value="FACTURA">FACTURA</option>
                    </select>
                  </div>
                  <div class="col-5">
                    <label class="form-label micro-text fw-bold mb-1">RECIBO</label>
                    <input type="text" v-model="form.stay.num_comprobante" class="form-control form-control-sm"
                      placeholder="1372">
                  </div>
                </div>

              </div>
            </div>
          </div>
          <div class="modal-footer border-0 p-4 pt-0">
            <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" id="btnRegistrarCheckin" class="btn btn-primary px-5 shadow"
              :disabled="loading || adelantoInvalido">
              <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
              {{ form.stay.id ? 'Guardar Cambios' : 'Registrar Check-in' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Detalle del Stay -->
  <div class="modal fade" id="modalDetalle" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg" style="border-radius:16px;">
        <template v-if="selectedStay">
          <div class="modal-body p-0">
            <!-- Header del Modal -->
            <div class="bg-primary p-4 text-white" style="border-radius:16px 16px 0 0;">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h4 class="mb-0 fw-bold">Habitación #{{ selectedStay.hab_numero }}</h4>
                  <p class="mb-0 opacity-75 small text-uppercase fw-bold">{{ selectedStay.tipo_hab_declarado }}</p>
                </div>
                <span class="badge bg-white text-primary px-3 fs-6 shadow-sm">{{ selectedStay.estado.toUpperCase()
                  }}</span>
              </div>
            </div>

            <div class="p-4">
              <!-- Información de Fechas -->
              <div class="row g-3 mb-4 pb-4 border-bottom">
                <div class="col-md-3">
                  <div class="text-muted text-uppercase fw-bold mb-1" style="font-size: 11px;">Check-in</div>
                  <div class="text-dark fw-semibold">{{ selectedStay.fecha_registro }} <small class="text-muted">({{
                      selectedStay.hora_checkin }})</small></div>
                </div>
                <div class="col-md-3">
                  <div class="text-muted text-uppercase fw-bold mb-1" style="font-size: 11px;">Check-out</div>
                  <div class="text-dark fw-semibold">{{ selectedStay.fecha_checkout }}</div>
                </div>
                <div class="col-md-3">
                  <div class="text-muted text-uppercase fw-bold mb-1" style="font-size: 11px;">Estadía</div>
                  <div class="text-dark fw-semibold">{{ selectedStay.noches }} Noches</div>
                </div>
                <div class="col-md-3">
                  <div class="text-muted text-uppercase fw-bold mb-1" style="font-size: 11px;">Medio</div>
                  <div class="text-dark fw-semibold text-uppercase">{{ selectedStay.medio_reserva }}</div>
                </div>
              </div>

              <div class="row">
                <!-- Columna Izquierda -->
                <div class="col-md-6 border-end pe-4">
                  <div class="mb-4">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3" style="font-size: 13px;">HUÉSPEDES</h6>
                    <div v-for="p in selectedStay.pax" :key="p.id" class="d-flex align-items-center mb-3">
                      <div class="me-3">
                        <i class="bi"
                          :class="p.es_titular ? 'bi-person-fill-check text-primary' : 'bi-person text-muted'"
                          style="font-size: 18px;"></i>
                      </div>
                      <div>
                        <div class="fw-bold text-dark" style="font-size: 13px;">{{ p.nombre_completo }}</div>
                        <div class="text-muted small">{{ p.documento_tipo }}: {{ p.documento_num }} | {{ p.nacionalidad
                          }}</div>
                      </div>
                    </div>
                  </div>

                  <div>
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3" style="font-size: 13px;">CONSUMOS ADICIONALES
                    </h6>
                    <div v-if="consumosStay.length === 0"
                      class="text-muted small py-2 italic text-center bg-light rounded">No hay consumos registrados.
                    </div>
                    <div v-else class="table-responsive">
                      <table class="table table-sm align-middle" style="font-size: 13px;">
                        <thead>
                          <tr class="text-muted fw-bold" style="font-size: 11px;">
                            <th>CANT.</th>
                            <th>PRODUCTO</th>
                            <th class="text-end">TOTAL</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="c in consumosStay" :key="c.id">
                            <td>{{ c.cantidad }}</td>
                            <td>{{ c.nombre_producto }}</td>
                            <td class="text-end fw-bold">S/ {{ parseFloat(c.total).toFixed(2) }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <!-- Columna Derecha -->
                <div class="col-md-6 ps-4">
                  <h6 class="fw-bold text-dark border-bottom pb-2 mb-3" style="font-size: 13px;">RESUMEN DE CUENTA</h6>

                  <table class="table table-sm table-borderless mb-4" style="font-size: 14px;">
                    <tbody>
                      <tr>
                        <td class="text-muted py-1">Monto Hospedaje</td>
                        <td class="text-end fw-bold py-1 text-dark">{{ selectedStay.moneda_pago == 'USD' ? '$' :
                          (selectedStay.moneda_pago == 'CLP' ? 'P$' : 'S/') }} {{
                          parseFloat(selectedStay.monto_original).toFixed(2) }}</td>
                      </tr>
                      <tr>
                        <td class="text-muted py-1">Consumo Adicional</td>
                        <td class="text-end fw-bold py-1 text-primary">{{ selectedStay.moneda_pago == 'USD' ? '$' :
                          (selectedStay.moneda_pago == 'CLP' ? 'P$' : 'S/') }} {{ (parseFloat(selectedStay.total_pago) -
                          parseFloat(selectedStay.monto_original)).toFixed(2) }}</td>
                      </tr>
                      <tr class="border-bottom">
                        <td class="text-muted py-1">Monto Abonado</td>
                        <td class="text-end fw-bold py-1 text-success">{{ selectedStay.moneda_pago == 'USD' ? '$' :
                          (selectedStay.moneda_pago == 'CLP' ? 'P$' : 'S/') }} {{
                          parseFloat(selectedStay.total_cobrado_orig || selectedStay.total_cobrado).toFixed(2) }}</td>
                      </tr>
                      <tr>
                        <td class="py-2 fw-bold text-dark" style="font-size: 15px;">SALDO PENDIENTE</td>
                        <td class="text-end fw-bold py-2 h4 mb-0"
                          :class="(selectedStay.total_pago - selectedStay.total_cobrado) > 0 ? 'text-danger' : 'text-success'">
                          <span v-if="(selectedStay.total_pago - selectedStay.total_cobrado) <= 0.05">
                            <i class="bi bi-check-circle-fill me-1"></i> PAGADO
                          </span>
                          <span v-else>
                            {{ selectedStay.moneda_pago == 'USD' ? '$' : (selectedStay.moneda_pago == 'CLP' ? 'P$' :
                            'S/') }} {{ (parseFloat(selectedStay.total_pago) -
                            parseFloat(selectedStay.total_cobrado_orig || selectedStay.total_cobrado)).toFixed(2) }}
                          </span>
                        </td>
                      </tr>
                    </tbody>
                  </table>

                  <div v-if="selectedStay.pagos.length > 0">
                    <h6 class="fw-bold text-muted mb-2 ps-1" style="font-size: 11px; letter-spacing: 0.3px;">HISTORIAL
                      DE PAGOS</h6>
                    <div class="border rounded p-1 shadow-sm bg-white" style="max-height: 200px; overflow-y: auto;">
                      <table class="table table-sm table-hover mb-0" style="font-size: 12px;">
                        <tbody>
                          <tr v-for="pag in selectedStay.pagos" :key="pag.id">
                            <td class="ps-2">
                              <div class="fw-bold text-dark text-uppercase small">{{ pag.tipo_pago }}</div>
                              <div class="text-muted mini" style="font-size: 9px;">{{ pag.created_at.split(' ')[1] }} |
                                {{ pag.cajero_nom }}</div>
                            </td>
                            <td class="text-end pe-2 align-middle">
                              <span class="fw-bold text-success">+ {{ parseFloat(pag.monto).toFixed(2) }}</span>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-footer bg-light border-0 px-4 py-3" style="border-radius:0 0 16px 16px;">
              <div class="w-100 d-flex justify-content-between align-items-center">
                <div class="d-flex gap-2">
                  <button type="button" class="btn btn-sm btn-outline-warning fw-bold px-3"
                    @click="abrirConsumo(selectedStay)">
                    <i class="bi bi-plus-lg me-1"></i> CONSUMO
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-success fw-bold px-3"
                    @click="abrirPago(selectedStay)">
                    <i class="bi bi-wallet2 me-1"></i> REGISTRAR PAGO
                  </button>
                </div>
                <button type="button" class="btn btn-secondary fw-bold px-4" data-bs-dismiss="modal">CERRAR</button>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>

  <!-- MODAL REGISTRAR CONSUMO -->
  <div class="modal fade" id="modalConsumo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg" style="border-radius:16px;">
        <div class="modal-header bg-warning text-dark border-0 py-3" style="border-radius:16px 16px 0 0;">
          <h5 class="modal-title d-flex align-items-center gap-2">
            <i class="bi bi-cup-straw"></i> Registrar Consumo
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div v-if="stayParaConsumo" class="mb-3 p-2 bg-light rounded text-center fw-bold border">
            HAB #{{ stayParaConsumo.hab_numero }} — {{ stayParaConsumo.titular_nombre }}
          </div>

          <form @submit.prevent="guardarConsumo">
            <div class="mb-3">
              <label class="form-label small fw-bold">Producto</label>
              <select class="form-select" v-model="consumoForm.producto_id" @change="onProductoChange" required>
                <option value="">Seleccione...</option>
                <optgroup v-for="(prods, cat) in inventarioAgrupado" :label="cat">
                  <option v-for="p in prods" :key="p.id" :value="p.id" :disabled="p.stock_actual < 1">
                    {{ p.nombre }} (S/ {{ p.precio_venta }}) [Stock: {{ p.stock_actual }}]
                  </option>
                </optgroup>
              </select>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-6">
                <label class="form-label small fw-bold">Cantidad</label>
                <input type="number" class="form-control" v-model="consumoForm.cantidad" min="1" required
                  @input="calcularTotalConsumo">
              </div>
              <div class="col-6 text-center">
                <div class="small text-muted mini fw-bold text-uppercase">Total a Cobrar</div>
                <div class="h3 fw-bold text-primary mb-0">S/ {{ consumoForm.total }}</div>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold">Forma de Pago</label>
              <div class="row g-2">
                <div class="col-6">
                  <div class="p-2 border rounded text-center cursor-pointer"
                    :class="consumoForm.pago_inmediato ? 'bg-white text-muted' : 'bg-primary text-white'"
                    @click="consumoForm.pago_inmediato = false; consumoForm.metodo_pago = null; consumoForm.recargo_pos = false">
                    <i class="bi bi-clock-history mb-1 d-block"></i>
                    <span class="mini fw-bold">CARGAR A HAB.</span>
                  </div>
                </div>
                <div class="col-6">
                  <div class="p-2 border rounded text-center cursor-pointer"
                    :class="consumoForm.pago_inmediato ? 'bg-success text-white' : 'bg-white text-muted'"
                    @click="consumoForm.pago_inmediato = true">
                    <i class="bi bi-cash-stack mb-1 d-block"></i>
                    <span class="mini fw-bold">PAGO AL CONTADO</span>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="consumoForm.pago_inmediato" class="mb-3 animate__animated animate__fadeIn">
              <div
                class="d-flex justify-content-between align-items-center mb-2 px-2 py-1 bg-info bg-opacity-10 rounded border border-info border-opacity-25">
                <div class="form-check form-switch mb-0 ps-0 d-flex align-items-center gap-2">
                  <input class="form-check-input m-0" type="checkbox" id="checkPosConsumo"
                    v-model="consumoForm.recargo_pos" @change="calcularTotalConsumo" style="cursor:pointer;">
                  <label class="form-check-label small fw-bold text-info mb-0" for="checkPosConsumo">POS (+5%)</label>
                </div>
                <span v-if="consumoForm.recargo_pos" class="badge bg-danger" style="font-size:10px;">
                  + S/ {{ (parseFloat(consumoForm.total) * 0.05 / 1.05).toFixed(2) }}
                </span>
              </div>
              <label class="form-label small fw-bold">Medio de Pago</label>
              <select class="form-select" v-model="consumoForm.metodo_pago" required>
                <option v-for="m in mediosPago" :key="m.id" :value="m.nombre">{{ m.nombre }}</option>
              </select>
            </div>

            <div class="mt-4 d-grid">
              <button type="submit" class="btn btn-warning py-2 fw-bold shadow-sm" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                Confirmar Consumo
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL REGISTRAR PAGO -->
  <div class="modal fade" id="modalPago" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg" style="border-radius:16px;">
        <div class="modal-header bg-success text-white border-0 py-3" style="border-radius:16px 16px 0 0;">
          <h5 class="modal-title d-flex align-items-center gap-2">
            <i class="bi bi-wallet2"></i> Registrar Pago / Abono
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div v-if="stayParaPago" class="mb-4 p-3 bg-light rounded border shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="small text-muted fw-bold text-uppercase">Habitación</div>
                <div class="fw-bold">#{{ stayParaPago.hab_numero }} — {{ stayParaPago.titular_nombre }}</div>
              </div>
              <div class="text-end">
                <div class="small text-muted fw-bold text-uppercase">Saldo Pendiente</div>
                <div class="fw-bold text-danger fs-5">{{ stayParaPago.moneda_pago == 'USD' ? '$' :
                  (stayParaPago.moneda_pago == 'CLP' ? 'P$' : 'PEN') }} {{ (stayParaPago.monto_original -
                  stayParaPago.total_cobrado_orig).toFixed(2) }}</div>
              </div>
            </div>
          </div>

          <form @submit.prevent="guardarPago">
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Monto a Pagar</label>
                <div class="input-group input-group-sm">
                  <select v-model="pagoForm.moneda" class="form-select border-primary" @change="recalcularPago"
                    style="max-width: 80px;">
                    <option value="PEN">S/</option>
                    <option value="USD">$</option>
                  </select>
                  <input type="number" class="form-control border-primary" v-model="pagoForm.monto" step="0.01" required
                    @input="recalcularPago">
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Equivalente PEN</label>
                <input type="number" class="form-control form-control-sm bg-light fw-bold text-secondary"
                  :class="stayParaPago && parseFloat(pagoForm.monto_pen) > (parseFloat(stayParaPago.total_pago - stayParaPago.total_cobrado) + 0.05) ? 'border-danger text-danger' : ''"
                  v-model="pagoForm.monto_pen" readonly>
                <div
                  v-if="stayParaPago && parseFloat(pagoForm.monto_pen) > (parseFloat(stayParaPago.total_pago - stayParaPago.total_cobrado) + 0.05)"
                  class="text-danger fw-bold mt-1" style="font-size: 10px;">
                  <i class="bi bi-exclamation-triangle-fill"></i> El monto excede el saldo pendiente.
                </div>
              </div>
            </div>

            <div class="mb-3">
              <div
                class="d-flex justify-content-between align-items-center mb-2 px-2 py-1 bg-info bg-opacity-10 rounded border border-info border-opacity-25">
                <div class="form-check form-switch mb-0 ps-0 d-flex align-items-center gap-2">
                  <input class="form-check-input m-0" type="checkbox" id="checkPosPago" v-model="pagoForm.recargo_pos"
                    @change="recalcularPago(true)" style="cursor:pointer;">
                  <label class="form-check-label small fw-bold text-info mb-0" for="checkPosPago">POS (+5%)</label>
                </div>
                <span v-if="pagoForm.recargo_pos" class="badge bg-danger" style="font-size:10px;">
                  + {{ pagoForm.moneda }} {{ (parseFloat(pagoForm.monto) * 0.05 / 1.05).toFixed(2) }}
                </span>
              </div>
              <label class="form-label small fw-bold">Método de Pago</label>
              <select class="form-select form-select-sm" v-model="pagoForm.tipo" required>
                <option value="">Seleccione...</option>
                <option v-for="m in mediosPago" :key="m.id" :value="m.nombre" :disabled="m.activo != 1">
                  {{ m.nombre }}
                </option>
              </select>
            </div>

            <div class="row g-3 mb-4">
              <div class="col-6">
                <label class="form-label small fw-bold">Fecha</label>
                <input type="date" class="form-control form-control-sm" v-model="pagoForm.fecha" required>
              </div>
              <div class="col-6">
                <label class="form-label small fw-bold">N° Recibo / Ref.</label>
                <input type="text" class="form-control form-control-sm" v-model="pagoForm.recibo"
                  placeholder="Ref. opcional">
              </div>
            </div>

            <div class="mt-2 d-grid">
              <button type="submit" class="btn btn-success py-2 fw-bold shadow-sm" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                Confirmar Registro de Pago
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

</div> <!-- .main-content -->
</div> <!-- #app-rooming -->

<!-- Scripts -->
<script>
  window.authUser = <?= json_encode(['id' => $_SESSION['auth_id'], 'nombre' => $_SESSION['auth_nombre']]) ?>;
</script>
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/exceljs@4.4.0/dist/exceljs.min.js"></script>
<script src="index.js?v=<?= time() ?>"></script>


<style>
  .btn-white {
    background: white;
  }

  .btn-white:hover {
    background: #f8f9fa;
  }

  .badge {
    padding: 8px 12px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 10px;
  }

  .table thead th {
    padding: 12px 10px !important;
    font-size: 11px;
    letter-spacing: 0.5px;
    color: #6c757d;
    border-bottom: none;
    border-top: none;
    text-transform: uppercase;
  }

  .form-control,
  .form-select {
    border-radius: 8px;
    border: 1px solid #e0e0e0;
  }

  /* Secciones del Modal Check-in */
  .modal-section-title {
    font-size: 12px;
    font-weight: 800;
    color: #adb5bd;
    letter-spacing: 1px;
    margin-bottom: 20px;
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 8px;
  }

  @media (max-width: 768px) {
    .main-content {
      padding: 8px !important;
    }

    .topbar h4 {
      font-size: 1.1rem;
    }

    .topbar p {
      display: none;
    }

    .table td {
      font-size: 12.5px;
      padding: 10px 8px !important;
    }

    .btn-group-sm>.btn,
    .btn-sm {
      font-size: 11px;
    }

    .modal-body {
      padding: 15px !important;
    }

    .modal-section-title {
      font-size: 11px;
      margin-bottom: 12px;
    }
  }
</style>

</body>

</html>