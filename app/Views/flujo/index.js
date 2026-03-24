/**
 * app/Views/flujo/index.js
 * Vue 3 Composition API — Lista de Flujos de Caja
 */
const { createApp, ref, reactive, onMounted } = Vue;

createApp({
  setup() {
    const BASE = '../../../api/flujo.php?action=';

    const loading = ref(true);
    const loadingCheck = ref(false);
    const flujos = ref([]);
    
    const today = new Date();
    const filtros = reactive({
      mes: today.getMonth() + 1,
      anio: today.getFullYear(),
      estado: 'todos'
    });

    const meses = [
      'Enero','Febrero','Marzo','Abril','Mayo','Junio',
      'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'
    ];

    // CARGAR LISTA
    const listar = async () => {
      loading.value = true;
      try {
        const res = await axios.get(`${BASE}listar&mes=${filtros.mes}&anio=${filtros.anio}&estado=${filtros.estado}`);
        if (res.data.ok) {
          flujos.value = res.data.data;
        }
      } catch (e) {
        console.error("Error al listar flujos", e);
      } finally {
        loading.value = false;
      }
    };

    // BADGE COLORS
    const estadoClass = (estado) => ({
      'bg-secondary': estado === 'borrador',
      'bg-primary': estado === 'cerrado',
      'bg-success': estado === 'depositado'
    });

    // NUEVO TURNO
    const nuevoTurno = async () => {
      // Preguntar qué turno desea abrir
      const hora = new Date().getHours();
      const turnoSugerido = (hora >= 6 && hora < 14) ? 'MAÑANA' : 'TARDE';
      
      const { value: turno } = await Swal.fire({
        title: 'Abrir Nuevo Turno',
        input: 'radio',
        inputOptions: {
          'MAÑANA': 'Turno MAÑANA (6am - 2pm)',
          'TARDE': 'Turno TARDE (2pm - 10pm)'
        },
        inputValue: turnoSugerido,
        showCancelButton: true,
        confirmButtonText: 'Continuar',
        cancelButtonText: 'Cancelar'
      });

      if (!turno) return;

      loadingCheck.value = true;
      try {
        // En lugar de verificar y luego redireccionar, directamente llamamos a un stub de guardado
        // o abrimos form.php pasándole la fecha de hoy y el turno, que allí se encargue guardarlo.
        // Lo más seguro es mandar a form.php?nuevo=1&turno=TARDE
        window.location.href = `form.php?nuevo=1&turno=${turno}`;
      } catch (e) {
        console.error(e);
      } finally {
        loadingCheck.value = false;
      }
    };

    onMounted(() => {
      listar();
    });

    return {
      loading, loadingCheck,
      flujos, filtros, meses,
      listar, estadoClass, nuevoTurno
    };
  }
}).mount('#app-flujo-index');
