/**
 * Módulo Frontend: Dashboard de Operaciones (Cajera).
 * 
 * Presenta una vista simplificada enfocada en la operatividad diaria:
 * check-ins esperados, check-outs, deudas de huéspedes y estado de su turno actual.
 * 
 * @module Dashboard/CajeraJS
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
    const kpi = ref({
      ocupacion: { ocupadas: 0, total: 0 },
      pax_hoy: 0,
      ingresos_hoy: { PEN: 0, USD: 0, CLP: 0 },
      pendientes_hoy: { PEN: 0, USD: 0, CLP: 0 },
      egresos_hoy: { PEN: 0, USD: 0, CLP: 0 },
    });
    const alertasInventario = ref([]);

    /**
     * Obtiene la data operativa del día invocando al endpoint de dashboard.
     * También integra alertas de inventario críticas para la operación.
     * 
     * @async
     * @function fetchData
     * @returns {Promise<void>}
     */
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
          if (d.kpi) kpi.value = d.kpi;
          
          // Cargar alertas de inventario desde su propia API para mantener dashboard.php limpio
          const resInv = await axios.get('api/inventario.php?action=alertas');
          alertasInventario.value = resInv.data.data || [];
          
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
      usuario, urgentes, checkouts_hoy, checkins_esperados, mi_turno, kpi, alertasInventario
    };
  }
}).mount('#app-dash-cajera');
