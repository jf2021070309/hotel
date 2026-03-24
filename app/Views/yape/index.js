/**
 * app/Views/yape/index.js
 */
const { createApp, ref, onMounted } = Vue;

createApp({
  setup() {
    const BASE = '../../../api/yape.php?action=';

    const loading = ref(true);
    const registros = ref([]);
    
    // Config filtros init
    const filtros = ref({
      mes: window.MES_ACTUAL || new Date().getMonth() + 1,
      anio: window.ANIO_ACTUAL || new Date().getFullYear()
    });

    const formatFecha = (f) => {
      if (!f) return '';
      const parts = f.split('-');
      return `${parts[2]}/${parts[1]}/${parts[0]}`;
    };

    const listar = async () => {
      loading.value = true;
      try {
        const res = await axios.get(`${BASE}listar`, { params: filtros.value });
        if (res.data.ok) {
          registros.value = res.data.data;
        }
      } catch (e) {
        console.error("Error al listar registros Yape", e);
        Swal.fire('Error', 'Fallo de red al listar', 'error');
      } finally {
        loading.value = false;
      }
    };

    const nuevoRegistro = async () => {
      const { value: turno } = await Swal.fire({
        title: 'Selecciona Turno',
        text: '¿Para qué turno crearás este registro de compras por Yape?',
        input: 'select',
        inputOptions: {
          'MAÑANA': 'Turno MAÑANA (6AM - 2PM)',
          'TARDE': 'Turno TARDE (2PM - 10PM)'
        },
        inputValue: window.TURNO_DEFAULT,
        showCancelButton: true,
        confirmButtonText: 'Continuar',
        cancelButtonText: 'Cancelar'
      });

      if (!turno) return;
      window.location.href = `form.php?nuevo=1&turno=${turno}`;
    };

    onMounted(() => {
      listar();
    });

    return {
      loading, registros, filtros,
      formatFecha, listar, nuevoRegistro
    };
  }
}).mount('#app-yape-index');
