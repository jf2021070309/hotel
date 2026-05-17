/**
 * app/Views/caja_chica/index.js
 * Vue 3 Composition API — Lista de Ciclos de Caja Chica
 */
const { createApp, ref, computed, onMounted, onUnmounted } = Vue;

createApp({
  setup() {
    const BASE = '../../../api/caja_chica.php?action=';

    const loading = ref(true);
    const ciclos = ref([]);
    let pollingTimer = null;

    const hayCicloActivo = computed(() => {
      return ciclos.value.some(c => c.estado === 'abierta');
    });

    const listar = async (silent = false) => {
      if (!silent) loading.value = true;
      try {
        const res = await axios.get(`${BASE}listar`);
        if (res.data.ok) {
          ciclos.value = res.data.data;
        }
      } catch (e) {
        console.error("Error listar ciclos caja chica", e);
      } finally {
        if (!silent) loading.value = false;
      }
    };

    const abrirNuevoCiclo = async () => {
      // Si ya hay un ciclo activo, redirigir directamente al detalle
      if (hayCicloActivo.value) {
        window.location.href = 'detalle.php';
        return;
      }

      const { value: formValues } = await Swal.fire({
        title: 'Iniciar Nuevo Ciclo de Caja Chica',
        html: `
          <div class="text-start mb-3 mt-2">
            <label class="form-label fw-bold small text-muted mb-1">Nombre del Ciclo de Caja Chica <span class="text-danger">*</span></label>
            <input id="swal-name" class="form-control fw-bold text-uppercase" placeholder="Ej: FONDO SEMANA 20 MAYO" style="font-size: 15px; padding: 10px;">
          </div>
          <div class="text-start mb-3">
            <label class="form-label fw-bold small text-muted mb-1">Fondo Inicial de Reposición (S/)</label>
            <input id="swal-monto" class="form-control fw-bold" type="number" step="0.01" value="100.00" style="font-size: 15px; padding: 10px;">
          </div>
          
          <div class="text-start mt-3">
            <div class="card bg-light border-0 shadow-sm">
              <div class="card-body p-3">
                <label class="form-label fw-bold small mb-2 text-primary d-block">
                  <i class="bi bi-envelope-paper me-1"></i>Sobre de Origen (S/ 100):
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
          </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'Abrir y Descontar del Sobre',
        preConfirm: () => {
          const nombre = document.getElementById('swal-name').value.trim();
          const saldo = document.getElementById('swal-monto').value;
          
          if (!nombre) {
            Swal.showValidationMessage('¡El nombre del ciclo es obligatorio!');
            return false;
          }
          if (parseFloat(saldo) <= 0 || isNaN(saldo)) {
            Swal.showValidationMessage('¡El fondo inicial debe ser mayor a 0!');
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

          return { nombre, saldo, sobre_fecha: date, sobre_turno: turn };
        }
      });

      if (formValues) {
        const { nombre, saldo, sobre_fecha, sobre_turno } = formValues;
        try {
          const res = await axios.post(`${BASE}abrir`, {
            nombre,
            saldo_inicial: saldo,
            sobre_fecha,
            sobre_turno
          });

          if (res.data.ok) {
            Swal.fire('Éxito', res.data.msg, 'success').then(() => {
              window.location.href = 'detalle.php';
            });
          } else {
            Swal.fire('Error', res.data.msg, 'error');
          }
        } catch (e) {
          Swal.fire('Error de red', '', 'error');
        }
      }
    };

    onMounted(() => {
      listar();
      pollingTimer = setInterval(() => listar(true), 10000);
    });

    onUnmounted(() => {
      if (pollingTimer) clearInterval(pollingTimer);
    });

    return {
      loading, ciclos, hayCicloActivo,
      listar, abrirNuevoCiclo
    };
  }
}).mount('#app-cchica-index');
