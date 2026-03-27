<!-- modal_detalle.php -->
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
          <!-- INFO GENERAL -->
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
            <!-- HUÉSPEDES -->
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
            </div>

            <!-- PAGOS Y FACTURACIÓN -->
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

              <div v-if="selectedStay.num_comprobante || selectedStay.ruc_factura" class="mt-4">
                <h6 class="fw-bold text-muted small mb-2 border-bottom pb-2">DATOS DE COMPROBANTE</h6>
                <div class="small">
                  <div><span class="text-muted">Tipo:</span> {{ selectedStay.tipo_comprobante }}</div>
                  <div v-if="selectedStay.num_comprobante"><span class="text-muted">Número:</span> {{ selectedStay.num_comprobante }}</div>
                  <div v-if="selectedStay.ruc_factura"><span class="text-muted">RUC/DNI:</span> {{ selectedStay.ruc_factura }}</div>
                </div>
              </div>
              
              <div class="mt-4 small text-muted text-end">
                Registrado por: {{ selectedStay.operador }}
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
<style>
.mini { font-size: 10px; }
</style>
