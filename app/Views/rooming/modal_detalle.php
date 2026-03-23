<!-- modal_detalle.php -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow" style="border-radius:16px;">
      <div v-if="selectedStay" class="modal-body p-0">
        <div class="bg-primary p-4 text-white" style="border-radius:16px 16px 0 0;">
          <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0 fw-bold">Habitación #{{ selectedStay.hab_numero }}</h4>
            <span class="badge bg-white text-primary px-3 fs-6">{{ selectedStay.estado.toUpperCase() }}</span>
          </div>
          <p class="mb-0 opacity-75">Huésped: {{ selectedStay.operador }} | Canal: {{ selectedStay.medio_reserva }}</p>
        </div>
        
        <div class="p-4">
          <div class="row mb-4">
            <div class="col-md-6 border-end">
              <h6 class="fw-bold text-muted small mb-3">LISTA DE HUÉSPEDES</h6>
              <div v-for="p in selectedStay.pax" :key="p.id" class="d-flex align-items-center gap-3 mb-2">
                <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center" style="width:32px; height:32px">
                  <i class="bi" :class="p.es_titular ? 'bi-star-fill text-warning' : 'bi-person'"></i>
                </div>
                <div>
                   <div class="fw-bold small">{{ p.nombre_completo }}</div>
                   <div class="text-muted mini">{{ p.documento_tipo }}: {{ p.documento_num }} | {{ p.nacionalidad }}</div>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <h6 class="fw-bold text-muted small mb-3">MOVIMIENTOS / PAGOS</h6>
              <div v-for="pag in selectedStay.pagos" :key="pag.id" class="d-flex justify-content-between border-bottom pb-2 mb-2">
                <div class="small">
                  <div class="fw-bold">{{ pag.tipo_pago }} <span class="text-muted fw-normal">({{ pag.fecha }})</span></div>
                  <div class="text-muted mini">{{ pag.recibo || 'Sin recibo' }}</div>
                </div>
                <div class="text-success fw-bold">PEN {{ pag.monto_pen }}</div>
              </div>
              <div class="mt-3 p-3 bg-light rounded d-flex justify-content-between align-items-center">
                <div class="small fw-bold">TOTAL PAGADO</div>
                <div class="fs-5 fw-bold text-primary">PEN {{ selectedStay.total_cobrado }}</div>
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
