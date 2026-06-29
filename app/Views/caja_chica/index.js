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

    const filtros = ref({
      fecha: '',
      estado: 'todos' // 'todos', 'abierta', 'cerrada'
    });

    const ciclosFiltrados = computed(() => {
      return ciclos.value.filter(c => {
        let passFecha = true;
        if (filtros.value.fecha) {
          passFecha = c.fecha_apertura && c.fecha_apertura.startsWith(filtros.value.fecha);
        }
        
        let passEstado = true;
        if (filtros.value.estado !== 'todos') {
          passEstado = c.estado === filtros.value.estado;
        }

        return passFecha && passEstado;
      });
    });

    const limpiarFiltros = () => {
      filtros.value.fecha = '';
      filtros.value.estado = 'todos';
    };

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
      // Si ya hay un ciclo activo, notificar antes de redirigir
      if (hayCicloActivo.value) {
        Swal.fire({
          icon: 'info',
          title: 'Ya hay una caja chica abierta...',
          text: 'Serás redirigido al detalle de la caja chica actual.',
          confirmButtonText: 'Ir al detalle'
        }).then(() => {
          window.location.href = 'detalle.php';
        });
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
            <p class="small text-center"><i class="bi bi-info-circle me-1"></i>Al abrir el ciclo, se descontará el monto inicial del total general de sobres.</p>
          </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'Abrir y Descontar',
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

          return { nombre, saldo };
        }
      });

      if (formValues) {
        const { nombre, saldo } = formValues;
        try {
          const res = await axios.post(`${BASE}abrir`, {
            nombre,
            saldo_inicial: saldo
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

    const irDetalle = (id) => {
      window.location.href = 'detalle.php?id=' + id;
    };

    return {
      loading, ciclos, ciclosFiltrados, filtros, limpiarFiltros, hayCicloActivo,
      listar, abrirNuevoCiclo, irDetalle
    };
  }
}).mount('#app-cchica-index');
