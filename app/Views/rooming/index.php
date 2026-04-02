<?php
/**
 * app/Views/rooming/index.php
 */
$base = '../../../';
require_once $base . 'auth/middleware.php';
protegerPorRol('cajera', 'rooming');

$page_title = 'Rooming & Check-in — Hotel Manager';
include $base . 'includes/head.php';
?>

<div id="app-rooming">
<?php include $base . 'includes/sidebar.php'; ?>
<div class="main-content">
  <div class="topbar border-bottom-0 shadow-sm" style="background: linear-gradient(to right, #ffffff, #f8f9fa);">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list fs-4"></i></button>
    <div>
      <h4 class="fw-bold" style="color: #111; letter-spacing: -0.5px;">
        <i class="bi bi-calendar-check-fill me-2" style="color: #d4af37;"></i>Rooming / Check-in
      </h4>
      <p class="mb-0 small text-muted fw-semibold">Gestión de estadías activas y registro de ingresos</p>
    </div>
    <div class="ms-auto">
      <button class="btn-primary-custom shadow-sm" @click="abrirCheckin" style="border: 1px solid #111;">
        <i class="bi bi-plus-lg text-warning"></i> Nuevo Check-in
      </button>
    </div>
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
                <div class="fw-bold fs-5" style="color: #111;">#{{ s.hab_numero }}</div>
                <span class="badge" :class="getEstadBadge(s.estado)" style="font-size: 8px; padding: 4px 8px;">{{ s.estado.toUpperCase() }}</span>
                <div class="text-muted small fw-semibold" style="letter-spacing: 0.5px;">{{ s.hab_tipo }}</div>
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
                  <button v-if="s.estado === 'reservado'" class="btn btn-success btn-sm border" title="Activar Check-in" @click="activarReserva(s)">
                    <i class="bi bi-person-check-fill"></i>
                  </button>
                  <button class="btn btn-white btn-sm border" title="Detalle" @click="verDetalle(s)">
                    <i class="bi bi-eye text-primary"></i>
                  </button>
                  <button v-if="s.estado !== 'reservado'" class="btn btn-white btn-sm border" title="Registrar Consumo" @click="abrirConsumo(s)">
                    <i class="bi bi-cup-straw text-warning"></i>
                  </button>
                  <button v-if="s.estado !== 'reservado'" class="btn btn-white btn-sm border" title="Registrar Pago" @click="abrirPago(s)">
                    <i class="bi bi-wallet2 text-success"></i>
                  </button>
                  <button v-if="s.estado !== 'reservado'" class="btn btn-white btn-sm border" title="Checkout" @click="procederCheckout(s)">
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

  <!-- Registro de Check-in -->
  <div class="modal fade" id="modalCheckin" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content border-0 shadow" style="border-radius:16px;">
        <div class="modal-header border-0 pb-0 ps-4 pe-4 pt-4">
          <h5 class="fw-bold mb-0">Registro de Check-in</h5>
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
                  <select v-model="form.stay.habitacion_id" class="form-select" required @change="onHabChange">
                    <option value="">Seleccione...</option>
                    <option v-for="h in habitacionesLibres" :key="h.id" :value="h.id">
                      #{{ h.numero }} - {{ h.tipo }} (S/ {{ h.precio_base }})
                    </option>
                  </select>
                </div>
                <div class="row g-2 mb-3">
                  <div class="col-6">
                    <label class="form-label small fw-bold">Check-in</label>
                    <input type="date" v-model="form.stay.fecha_registro" class="form-control" required @change="calcularNoches">
                  </div>
                  <div class="col-6">
                    <label class="form-label small fw-bold">Hora</label>
                    <input type="time" v-model="form.stay.hora_checkin" class="form-control" required>
                  </div>
                </div>
                <div class="row g-2 mb-3">
                  <div class="col-6">
                    <label class="form-label small fw-bold">Noches</label>
                    <input type="number" v-model="form.stay.noches" class="form-control" min="1" required @input="onNochesChange">
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
                    <option value="EXPEDIA">EXPEDIA</option>
                    <option value="CORPORATIVO">CORPORATIVO</option>
                  </select>
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
                <div v-for="(pax, idx) in form.pax" :key="idx" class="p-3 bg-light rounded-3 mb-3 position-relative shadow-sm" style="border: 1px solid #eee;">
                  <button v-if="idx > 0" type="button" class="btn-close position-absolute top-0 end-0 m-2" style="font-size:10px" @click="form.pax.splice(idx, 1)"></button>
                  <div class="mb-2">
                    <input type="text" v-model="pax.nombre_completo" class="form-control-sm form-control" placeholder="Nombre completo" required>
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
                        <input type="text" v-model="pax.documento_num"
                               class="form-control form-control-sm"
                               placeholder="Num. documento" required
                               @input="buscarPax(pax, idx)"
                               @blur="ocultarSugerencias(idx)"
                               autocomplete="off">
                        <!-- Dropdown sugerencias -->
                        <div v-if="sugerencias[idx] && sugerencias[idx].length"
                             class="position-absolute bg-white border rounded shadow-sm w-100 z-3"
                             style="top:100%; left:0; max-height:200px; overflow-y:auto;">
                          <div v-for="s in sugerencias[idx]" :key="s.documento_num"
                               class="px-3 py-2 cursor-pointer border-bottom d-flex align-items-center gap-2"
                               style="cursor:pointer; font-size:12px;"
                               @mousedown.prevent="aplicarSugerencia(pax, idx, s)">
                            <span class="badge bg-secondary" style="font-size:9px;">{{ s.documento_tipo }}</span>
                            <span class="fw-bold text-primary">{{ s.documento_num }}</span>
                            <span class="text-muted">{{ s.nombre_completo }}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  <div class="row g-2 mt-1">
                    <div class="col-6">
                      <input type="text" v-model="pax.nacionalidad" class="form-control form-control-sm" placeholder="Nacionalidad">
                    </div>
                    <div class="col-6 d-flex align-items-center">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" :name="'titular'" :id="'tit'+idx" :checked="pax.es_titular" @change="setTitular(idx)">
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
                <div class="mb-3">
                  <label class="form-label small fw-bold">Total a pagar</label>
                  <div class="input-group">
                    <select v-model="form.stay.moneda_pago" class="form-select w-25" @change="recalcularMoneda">
                      <option value="PEN">S/</option>
                      <option value="USD">$</option>
                      <option value="CLP">P$</option>
                    </select>
                    <input type="number" v-model="form.stay.monto_original" class="form-control w-75" step="0.01" required @input="recalcularMoneda">
                  </div>
                </div>
                <div v-if="form.stay.moneda_pago !== 'PEN'" class="mb-3 p-2 bg-warning bg-opacity-10 rounded">
                  <label class="form-label small fw-bold">Tipo de Cambio / Equiv. PEN</label>
                  <div class="row g-2">
                    <div class="col-6"><input type="number" v-model="form.stay.tc_aplicado" class="form-control form-control-sm" step="0.0001" @input="recalcularMoneda"></div>
                    <div class="col-6"><input type="number" v-model="form.stay.total_pago" class="form-control form-control-sm" readonly></div>
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label small fw-bold">Adelanto (opcional)</label>
                  <input type="number" v-model="form.adelanto" class="form-control" step="0.1" @input="onAdelantoChange">
                  <small v-if="form.adelanto > 0" class="text-success fw-bold">PEN {{ form.stay.total_cobrado }}</small>
                </div>
                <div class="mb-3">
                  <label class="form-label small fw-bold">Método de pago</label>
                  <select v-model="form.stay.metodo_pago" class="form-select" :required="form.adelanto > 0">
                    <option value="">Seleccione...</option>
                    <option v-for="m in mediosPago" :key="m.id" :value="m.nombre" :disabled="m.activo != 1">
                      {{ m.nombre }}
                    </option>
                  </select>
                </div>
                <div class="row g-2 mb-3">
                  <div class="col-7">
                    <label class="form-label small fw-bold">Comprobante</label>
                    <select v-model="form.stay.tipo_comprobante" class="form-select">
                      <option value="RECIBO">RECIBO SIMPLE</option>
                      <option value="BOLETA">BOLETA</option>
                      <option value="FACTURA">FACTURA</option>
                    </select>
                  </div>
                  <div class="col-5">
                    <label class="form-label small fw-bold">N° comprob.</label>
                    <input type="text" v-model="form.stay.num_comprobante" class="form-control" placeholder="1372">
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-0 p-4 pt-0">
            <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary px-5 shadow" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
              Registrar Check-in
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <!-- Detalle del Stay -->
  <div class="modal fade" id="modalDetalle" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content border-0 shadow" style="border-radius:16px;">
        <div v-if="selectedStay" class="modal-body p-0">
          <div class="bg-primary p-4 text-white" style="border-radius:16px 16px 0 0;">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h4 class="mb-0 fw-bold">Habitación #{{ selectedStay.hab_numero }}</h4>
                <p class="mb-0 opacity-75 small text-uppercase fw-bold">{{ selectedStay.tipo_hab_declarado }}</p>
              </div>
              <span class="badge bg-white text-primary px-3 fs-6">{{ selectedStay.estado.toUpperCase() }}</span>
            </div>
          </div>
          <div class="p-4">
            <div class="row g-3 mb-4">
              <div class="col-6 col-md-3">
                <label class="text-muted mini text-uppercase fw-bold d-block">Check-in</label>
                <div class="fw-bold">{{ selectedStay.fecha_registro }} <span class="fw-normal small">({{ selectedStay.hora_checkin }})</span></div>
              </div>
              <div class="col-6 col-md-3">
                <label class="text-muted mini text-uppercase fw-bold d-block">Check-out Est.</label>
                <div class="fw-bold">{{ selectedStay.fecha_checkout }}</div>
              </div>
              <div class="col-6 col-md-3">
                <label class="text-muted mini text-uppercase fw-bold d-block">Noches</label>
                <div class="fw-bold">{{ selectedStay.noches }}</div>
              </div>
              <div class="col-6 col-md-3">
                <label class="text-muted mini text-uppercase fw-bold d-block">Medio / Canal</label>
                <div class="fw-bold">{{ selectedStay.medio_reserva }}</div>
              </div>
            </div>
            <div class="row mb-4">
              <div class="col-md-6 border-end">
                <h6 class="fw-bold text-muted small mb-3 border-bottom pb-2">LISTA DE HUÉSPEDES ({{ selectedStay.pax.length }})</h6>
                <div v-for="p in selectedStay.pax" :key="p.id" class="d-flex align-items-center gap-3 mb-2">
                  <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center" style="width:32px; height:32px">
                    <i class="bi" :class="p.es_titular ? 'bi-star-fill text-warning' : 'bi-person'"></i>
                  </div>
                  <div>
                     <div class="fw-bold small">{{ p.nombre_completo }}</div>
                     <div class="text-muted mini">{{ p.documento_tipo }}: {{ p.documento_num }} | {{ p.nacionalidad }}</div>
                  </div>
                </div>
                <div v-if="selectedStay.procedencia || selectedStay.observaciones" class="mt-4">
                  <h6 class="fw-bold text-muted small mb-2 border-bottom pb-2">OTROS DETALLES</h6>
                  <div v-if="selectedStay.procedencia" class="mb-2">
                    <label class="text-muted mini text-uppercase fw-bold d-block">Procedencia</label>
                    <div class="small">{{ selectedStay.procedencia }}</div>
                  </div>
                  <div v-if="selectedStay.observaciones">
                    <label class="text-muted mini text-uppercase fw-bold d-block">Observaciones</label>
                    <div class="small p-2 bg-light rounded italic" style="border-left: 3px solid #dee2e6;">{{ selectedStay.observaciones }}</div>
                  </div>
                </div>

                <div class="mt-4">
                  <h6 class="fw-bold text-muted small mb-2 border-bottom pb-2">CONSUMOS EXTRA</h6>
                  <div v-if="consumosStay.length === 0" class="text-muted small">No hay consumos registrados.</div>
                  <table v-else class="table table-sm table-borderless small">
                    <thead>
                      <tr class="text-muted mini fw-bold">
                        <th>Q</th>
                        <th>PRODUCTO</th>
                        <th class="text-end">TOTAL</th>
                        <th class="text-center">ESTADO</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="c in consumosStay" :key="c.id">
                        <td>{{ c.cantidad }}</td>
                        <td>{{ c.nombre_producto }}</td>
                        <td class="text-end">S/ {{ c.total }}</td>
                        <td class="text-center">
                          <span v-if="c.metodo_pago" class="badge bg-success p-1" style="font-size: 8px;">PAGADO</span>
                          <span v-else class="badge bg-danger p-1" style="font-size: 8px;">A CTA</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="col-md-6">
                <h6 class="fw-bold text-muted small mb-3 border-bottom pb-2">RESUMEN FINANCIERO</h6>
                <div class="d-flex justify-content-between mb-2">
                  <span class="small text-muted">Total Hospedaje:</span>
                  <span class="fw-bold">{{ selectedStay.moneda_pago }} {{ selectedStay.total_pago }}</span>
                </div>
                <div v-for="pag in selectedStay.pagos" :key="pag.id" class="d-flex justify-content-between border-bottom pb-1 mb-1">
                  <div class="small">
                    <div class="text-muted mini">{{ pag.tipo_pago }} - {{ pag.fecha }}</div>
                  </div>
                  <div class="text-success small fw-bold">+ {{ pag.moneda }} {{ pag.monto }}</div>
                </div>
                <div class="mt-3 p-3 rounded" :class="(selectedStay.total_pago - selectedStay.total_cobrado) > 0 ? 'bg-warning-subtle' : 'bg-success-subtle'">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="small fw-bold text-muted">A COBRAR ({{ selectedStay.moneda_pago }}):</span>
                    <span class="fw-bold fs-5">{{ selectedStay.total_pago }}</span>
                  </div>
                  <div class="d-flex justify-content-between align-items-center mb-1 border-bottom pb-1">
                    <span class="small fw-bold text-muted">TOTAL PAGADO (PEN):</span>
                    <span class="fw-bold text-success">{{ selectedStay.total_cobrado }}</span>
                  </div>
                  <div class="d-flex justify-content-between align-items-center pt-1">
                    <span class="small fw-bold text-dark">SALDO PENDIENTE:</span>
                    <span class="fs-5 fw-bold" :class="(selectedStay.total_pago - selectedStay.total_cobrado) > 0 ? 'text-danger' : 'text-success'">
                      {{ (selectedStay.total_pago - selectedStay.total_cobrado).toFixed(2) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
            <div class="text-end border-top pt-3">
              <button type="button" class="btn btn-light px-4 me-2" data-bs-dismiss="modal">Cerrar</button>
            </div>
          </div>
        </div>
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
                <input type="number" class="form-control" v-model="consumoForm.cantidad" min="1" required @input="calcularTotalConsumo">
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
                  <div class="p-2 border rounded text-center cursor-pointer" :class="consumoForm.pago_inmediato ? 'bg-white text-muted' : 'bg-primary text-white'" @click="consumoForm.pago_inmediato = false; consumoForm.metodo_pago = null">
                     <i class="bi bi-clock-history mb-1 d-block"></i>
                     <span class="mini fw-bold">CARGAR A HAB.</span>
                  </div>
                </div>
                <div class="col-6">
                  <div class="p-2 border rounded text-center cursor-pointer" :class="consumoForm.pago_inmediato ? 'bg-success text-white' : 'bg-white text-muted'" @click="consumoForm.pago_inmediato = true">
                     <i class="bi bi-cash-stack mb-1 d-block"></i>
                     <span class="mini fw-bold">PAGO AL CONTADO</span>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="consumoForm.pago_inmediato" class="mb-3 animate__animated animate__fadeIn">
              <label class="form-label small fw-bold">Método de Pago</label>
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
                <div class="fw-bold text-danger fs-5">PEN {{ (stayParaPago.total_pago - stayParaPago.total_cobrado).toFixed(2) }}</div>
              </div>
            </div>
          </div>

          <form @submit.prevent="guardarPago">
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Monto a Pagar</label>
                <div class="input-group input-group-sm">
                  <select v-model="pagoForm.moneda" class="form-select border-primary" @change="recalcularPago" style="max-width: 80px;">
                    <option value="PEN">S/</option>
                    <option value="USD">$</option>
                  </select>
                  <input type="number" class="form-control border-primary" v-model="pagoForm.monto" step="0.01" required @input="recalcularPago">
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Equivalente PEN</label>
                <input type="number" class="form-control form-control-sm bg-light fw-bold text-secondary" v-model="pagoForm.monto_pen" readonly>
              </div>
            </div>

            <div class="mb-3">
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
                <input type="text" class="form-control form-control-sm" v-model="pagoForm.recibo" placeholder="Ref. opcional">
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

</div>

<!-- Scripts -->
<script>
  window.authUser = <?= json_encode(['id' => $_SESSION['auth_id'], 'nombre' => $_SESSION['auth_nombre']]) ?>;
</script>
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="index.js?v=<?= time() ?>"></script>

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
