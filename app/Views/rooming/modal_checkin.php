<!-- modal_checkin.php -->
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
                  <div class="col-8">
                    <input type="text" v-model="pax.documento_num" class="form-control form-control-sm" placeholder="Num. documento" required>
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
                <select v-model="form.stay.metodo_pago" class="form-select">
                  <option value="EFECTIVO">EFECTIVO</option>
                  <option value="POS SOLES">POS SOLES</option>
                  <option value="TRANSF">TRANSFERENCIA</option>
                  <option value="YAPE/PLIN">YAPE/PLIN</option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-bold">Comprobante</label>
                <select v-model="form.stay.tipo_comprobante" class="form-select">
                  <option value="RECIBO">RECIBO SIMPLE</option>
                  <option value="BOLETA">BOLETA</option>
                  <option value="FACTURA">FACTURA</option>
                </select>
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
