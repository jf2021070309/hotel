/**
 * app/Views/caja_chica/index.js
 * Vue 3 Composition API — Lista de Ciclos de Caja Chica
 */
const { createApp, ref, computed, onMounted } = Vue;

createApp({
  setup() {
    const BASE = '../../../api/caja_chica.php?action=';

    const loading = ref(true);
    const ciclos = ref([]);

    const hayCicloActivo = computed(() => {
      return ciclos.value.some(c => c.estado === 'abierta');
    });

    const listar = async () => {
      loading.value = true;
      try {
        const res = await axios.get(`${BASE}listar`);
        if (res.data.ok) {
          ciclos.value = res.data.data;
        }
      } catch (e) {
        console.error("Error listar ciclos caja chica", e);
      } finally {
        loading.value = false;
      }
    };

    const abrirNuevoCiclo = async () => {
      const { value: formValues } = await Swal.fire({
        title: 'Iniciar Nuevo Ciclo',
        html:
          '<input id="swal-input1" class="swal2-input" placeholder="Nombre (Ej: CICLO MARZO SEMANA 1)">' +
          '<input id="swal-input2" class="swal2-input" type="number" step="0.01" value="100.00" title="Saldo Inicial">',
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Abrir Caja Chica',
        preConfirm: () => {
          return [
            document.getElementById('swal-input1').value,
            document.getElementById('swal-input2').value
          ]
        }
      });

      if (formValues) {
        const [nombre, saldo] = formValues;
        if (!nombre || parseFloat(saldo) <= 0) {
          Swal.fire('Error', 'Debe indicar un nombre y un saldo mayor a 0', 'error');
          return;
        }

        try {
          const res = await axios.post(`${BASE}abrir`, {
            nombre: nombre,
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
    });

    return {
      loading, ciclos, hayCicloActivo,
      listar, abrirNuevoCiclo
    };
  }
}).mount('#app-cchica-index');
