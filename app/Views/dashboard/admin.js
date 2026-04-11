/**
 * Módulo Frontend: Dashboard de Administrador.
 * 
 * Gestiona la visualización reactiva de KPIs financieros, gráficos de Chart.js
 * y el monitoreo en tiempo real del estado del hotel.
 * 
 * @module Dashboard/AdminJS
 */
const { createApp, ref, onMounted, onUnmounted } = Vue;

createApp({
  setup() {
    const loadingInicial = ref(true);
    const isRefreshing = ref(false);
    const segundosDesdeUpdate = ref(0);
    let timerUpdate = null;
    let pollingInterval = null;

    // Data states
    const kpi = ref({
      ocupacion: { ocupadas: 0, total: 0 },
      pax_hoy: 0,
      ingresos_hoy: 0,
      pendientes_hoy: 0,
      egresos_hoy: 0,
      neto_hoy: 0
    });
    const ingresos_desglose = ref([]);
    const egresos_desglose = ref([]);
    const habitaciones = ref({
      libres: 0, ocupadas: 0, limpieza: 0, mantenimiento: 0, late_checkout: 0
    });
    const cobros_pendientes = ref([]);
    const sobres = ref({
      manana: { monto: 0, estado: 'N/A' },
      tarde: { monto: 0, estado: 'N/A' }
    });

    let myChart = null;

    /**
     * Inicializa o actualiza el gráfico de barras comparativo de Ingresos vs Egresos.
     * 
     * @function initChart
     * @param {Array} datos Array de objetos con día, ingresos y egresos.
     * @returns {void}
     */
    const initChart = (datos) => {
      const ctx = document.getElementById('graficoMes');
      if (!ctx) return;

      const labels = datos.map(d => d.dia.split('-')[2]); // Solo el día
      const ingData = datos.map(d => parseFloat(d.ingresos));
      const egrData = datos.map(d => parseFloat(d.egresos));

      if (myChart) myChart.destroy();

      myChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Ingresos (S/)',
              data: ingData,
              backgroundColor: '#1cc88a',
              borderRadius: 4
            },
            {
              label: 'Egresos (S/)',
              data: egrData,
              backgroundColor: '#e74a3b',
              borderRadius: 4
            }
          ]
        },
        options: {
          maintainAspectRatio: false,
          responsive: true,
          scales: {
            y: { beginAtZero: true, grid: { drawBorder: false } },
            x: { grid: { display: false } }
          },
          plugins: {
            legend: { position: 'top' }
          }
        }
      });
    };

    /**
     * Solicita los datos actualizados del dashboard a la API.
     * Actualiza los estados reactivos de KPIs, desgloses y el gráfico.
     * 
     * @async
     * @function fetchData
     * @returns {Promise<void>}
     */
    const fetchData = async () => {
      isRefreshing.value = true;
      const icon = document.getElementById('icon-refreshing');
      if (icon) icon.classList.add('fa-spin');

      try {
        const res = await axios.get('api/dashboard.php');
        if (res.data.ok) {
          const d = res.data.data;
          kpi.value = d.kpi;
          ingresos_desglose.value = d.ingresos_desglose;
          egresos_desglose.value = d.egresos_desglose;
          habitaciones.value = d.habitaciones;
          cobros_pendientes.value = d.cobros_pendientes;
          sobres.value = d.sobres;
          
          segundosDesdeUpdate.value = 0;
          const el = document.getElementById('label-seconds');
          if (el) el.textContent = 'Hace 0s';

          initChart(d.grafico_mes);
        }
      } catch (e) {
        console.error("Error Dashboard Admin:", e);
      } finally {
        loadingInicial.value = false;
        isRefreshing.value = false;
        if (icon) icon.classList.remove('fa-spin');
      }
    };

    onMounted(() => {
      fetchData();
      
      // Counter for "updated X seconds ago"
      timerUpdate = setInterval(() => {
        segundosDesdeUpdate.value++;
        const el = document.getElementById('label-seconds');
        if (el) el.textContent = `Hace ${segundosDesdeUpdate.value}s`;
      }, 1000);

      // Refresh every 60 seconds
      pollingInterval = setInterval(fetchData, 60000);
    });

    onUnmounted(() => {
      clearInterval(timerUpdate);
      clearInterval(pollingInterval);
    });

    return {
      loadingInicial, isRefreshing, segundosDesdeUpdate,
      kpi, ingresos_desglose, egresos_desglose,
      habitaciones, cobros_pendientes, sobres
    };
  }
}).mount('#app-dash-admin');
