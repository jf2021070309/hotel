/**
 * app/Views/caja_chica/detalle.js
 * Vue 3 Composition API — Detalle Ciclo Activo
 */
const { createApp, ref, reactive, computed, onMounted } = Vue;

createApp({
  setup() {
    const BASE = '../../../api/caja_chica.php?action=';

    const loading = ref(true);
    const guardandoGasto = ref(false);
    
    const ciclo = ref(null);
    const categorias = ref([]);

    const formg = reactive({
      documento: '',
      monto: '',
      observacion: ''
    });

    const loadData = async () => {
      loading.value = true;
      try {
        const [catRes, actRes] = await Promise.all([
          axios.get(`${BASE}categorias`),
          axios.get(`${BASE}ciclo_activo`)
        ]);
        
        if (catRes.data.ok) categorias.value = catRes.data.data;
        if (actRes.data.ok) ciclo.value = actRes.data.data; // data can be null if no cycle

      } catch (e) {
        console.error(e);
      } finally {
        loading.value = false;
      }
    };

    const porcentaje_gastado = computed(() => {
      if (!ciclo.value) return 0;
      let init = parseFloat(ciclo.value.saldo_inicial);
      let gast = parseFloat(ciclo.value.total_gastado);
      if (init === 0) return 0;
      let p = (gast / init) * 100;
      return p > 100 ? 100 : p;
    });

    const registrarGasto = async () => {
      if (!formg.documento || formg.monto <= 0) return;
      
      if (parseFloat(formg.monto) > parseFloat(ciclo.value.saldo_actual)) {
        Swal.fire('Atención', 'El gasto es mayor al saldo actual disponible en la caja.', 'warning');
        return;
      }

      guardandoGasto.value = true;
      try {
        const res = await axios.post(`${BASE}gasto`, {
          caja_id: ciclo.value.id,
          rubro: formg.documento,
          documento: formg.documento,
          monto: formg.monto,
          observacion: formg.observacion
        });

        if (res.data.ok) {
          formg.documento = '';
          formg.monto = '';
          formg.observacion = '';
          loadData(); // refresh active
          const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
          Toast.fire({ icon: 'success', title: 'Gasto guardado' });
        } else {
          Swal.fire('Error', res.data.msg, 'error');
        }
      } catch (e) {
        const msg = e.response && e.response.data && e.response.data.msg ? e.response.data.msg : 'No se conectó al servidor';
        Swal.fire('Error', msg, 'error');
      } finally {
        guardandoGasto.value = false;
      }
    };

    const anularGasto = async (mov) => {
      const { value: motivo } = await Swal.fire({
        title: 'Anular Movimiento',
        input: 'text',
        inputLabel: 'Indique el motivo de la anulación',
        inputPlaceholder: 'Ingreso erróneo, recibo cancelado...',
        showCancelButton: true,
        inputValidator: (value) => {
          if (!value) return '¡El motivo es obligatorio!'
        }
      });

      if (motivo) {
        try {
          const res = await axios.post(`${BASE}anular`, {
            mov_id: mov.id,
            motivo: motivo
          });
          if (res.data.ok) {
            Swal.fire('Anulado', res.data.msg, 'success');
            loadData();
          } else {
            Swal.fire('Error', res.data.msg, 'error');
          }
        } catch(e) {
          const msg = e.response && e.response.data && e.response.data.msg ? e.response.data.msg : 'Fallo de red';
          Swal.fire('Error', msg, 'error');
        }
      }
    };

    const cerrarCiclo = async () => {
      const defaultName = `FONDO FIJO S/ 100 - ${new Date().toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' })}`;
      
      const confirm = await Swal.fire({
        title: 'Cerrar y Reponer Caja Chica',
        html: `
          <div class="text-start p-2">
            <p class="small text-muted mb-3">El saldo final de este ciclo será de <b>S/ ${parseFloat(ciclo.value.saldo_actual).toFixed(2)}</b>.</p>
            
            <div class="mb-3 border-top pt-3">
              <label class="form-label fw-bold small text-dark mb-1">Nombre para el Nuevo Ciclo de Reposición <span class="text-danger">*</span></label>
              <input id="swal-new-name" class="form-control fw-bold text-uppercase" placeholder="Ej: FONDO SEMANA 20 MAYO" value="${defaultName}" style="font-size: 14px;">
            </div>

            <div class="card bg-light border-0 mb-3 shadow-sm">
              <div class="card-body p-3">
                <label class="form-label fw-bold small mb-2 text-primary d-block">
                  <i class="bi bi-envelope-paper me-1"></i>Sobre de Origen para Reposición S/ 100:
                </label>
                
                <div class="form-check mb-2">
                  <input class="form-check-input" type="radio" name="swal-sobre" id="s-hoy-m" value="hoy|MAÑANA" checked>
                  <label class="form-check-label small" for="s-hoy-m">Sobre HOY (Mañana)</label>
                </div>
                <div class="form-check mb-2">
                  <input class="form-check-input" type="radio" name="swal-sobre" id="s-hoy-t" value="hoy|TARDE">
                  <label class="form-check-label small" for="s-hoy-t">Sobre HOY (Tarde)</label>
                </div>
                <div class="form-check mb-2">
                  <input class="form-check-input" type="radio" name="swal-sobre" id="s-ayer-p" value="ayer|TARDE">
                  <label class="form-check-label small" for="s-ayer-p">Sobre AYER (Tarde)</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="swal-sobre" id="s-ayer-m" value="ayer|MAÑANA">
                  <label class="form-check-label small" for="s-ayer-m">Sobre AYER (Mañana)</label>
                </div>
              </div>
            </div>

            <p class="small text-center"><i class="bi bi-info-circle me-1"></i>Si elige reponer, se creará un nuevo ciclo de S/ 100.</p>
          </div>
        `,
        icon: 'question',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonColor: '#198754',
        denyButtonColor: '#6c757d',
        confirmButtonText: 'Cerrar y Reponer S/ 100',
        denyButtonText: 'Cerrar SIN reponer',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
          const nombre = document.getElementById('swal-new-name').value.trim();
          if (!nombre) {
             Swal.showValidationMessage('¡El nombre para el nuevo ciclo es obligatorio!');
             return false;
          }

          const selected = document.querySelector('input[name="swal-sobre"]:checked').value;
          const [day, turn] = selected.split('|');
          
          let date = new Date().toISOString().split('T')[0];
          if (day === 'ayer') {
            const d = new Date();
            d.setDate(d.getDate() - 1);
            date = d.toISOString().split('T')[0];
          }

          return { nombre_reposicion: nombre, sobre_fecha: date, sobre_turno: turn };
        }
      });

      if (!confirm.isConfirmed && !confirm.isDenied) return;

      const reponer = confirm.isConfirmed; 
      const sobreData = confirm.isConfirmed ? confirm.value : {};

      try {
        const res = await axios.post(`${BASE}cerrar`, {
          caja_id: ciclo.value.id,
          reponer: reponer,
          ...sobreData
        });

        if (res.data.ok) {
          Swal.fire({
            title: '¡Caja Cerrada!',
            text: res.data.msg,
            icon: 'success'
          }).then(() => {
            window.location.href = 'index.php'; // return to history
          });
        } else {
          Swal.fire('Error', res.data.msg, 'error');
        }
      } catch (e) {
        const msg = e.response && e.response.data && e.response.data.msg ? e.response.data.msg : 'Error de red';
        Swal.fire('Error', msg, 'error');
      }
    };

    onMounted(() => {
      loadData();
    });

    return {
      loading, guardandoGasto, ciclo, categorias, formg, porcentaje_gastado,
      registrarGasto, anularGasto, cerrarCiclo
    };
  }
}).mount('#app-cchica-detalle');
