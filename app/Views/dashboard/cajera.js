/**
 * app/Views/dashboard/cajera.js
 */
const { createApp, ref, onMounted, onUnmounted } = Vue;

createApp({
  setup() {
    const loadingInicial = ref(true);
    const segundosDesdeUpdate = ref(0);
    let timerUpdate = null;
    let pollingInterval = null;

    const usuario = ref({ nombre: '', turno: '' });
    const urgentes = ref([]);
    const checkouts_hoy = ref([]);
    const checkins_esperados = ref([]);
    const mi_turno = ref({
      ingresos: 0,
      egresos: 0,
      efectivo_sobre: 0,
      estado: 'inexistente'
    });

    const fetchData = async () => {
      try {
        const res = await axios.get('api/dashboard.php');
        if (res.data.ok) {
          const d = res.data.data;
          usuario.value = d.usuario;
          urgentes.value = d.urgentes;
          checkouts_hoy.value = d.checkouts_hoy;
          checkins_esperados.value = d.checkins_esperados;
          mi_turno.value = d.mi_turno;
          
          segundosDesdeUpdate.value = 0;
        }
      } catch (e) {
        console.error("Error Dashboard Cajera:", e);
      } finally {
        loadingInicial.value = false;
      }
    };

    onMounted(() => {
      fetchData();
      timerUpdate = setInterval(() => { segundosDesdeUpdate.value++; }, 1000);
      pollingInterval = setInterval(fetchData, 60000);
    });

    onUnmounted(() => {
      clearInterval(timerUpdate);
      clearInterval(pollingInterval);
    });

    return {
      loadingInicial, segundosDesdeUpdate,
      usuario, urgentes, checkouts_hoy, checkins_esperados, mi_turno
    };
  }
}).mount('#app-dash-cajera');
