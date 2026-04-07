/**
 * app/Views/sobres/index.js
 * Lógica del dashboard de sobres
 */
const { createApp, ref, onMounted, computed } = Vue;

createApp({
  setup() {
    const loading = ref(true);
    const fechaFiltro = ref(SERVER_FECHA);
    const reporte = ref({
      MAÑANA: { PEN: 0, USD: 0, CLP: 0, egresos_detalle: '' },
      TARDE: { PEN: 0, USD: 0, CLP: 0, egresos_detalle: '' }
    });

    const totalSoles = computed(() => {
        return (parseFloat(reporte.value.MAÑANA.PEN) || 0) + (parseFloat(reporte.value.TARDE.PEN) || 0);
    });

    const consultar = async () => {
      loading.value = true;
      try {
        const res = await axios.get(`../../../api/flujo.php?action=resumen_alex&fecha=${fechaFiltro.value}`);
        if (res.data.ok) {
          reporte.value = res.data.data;
        }
      } catch (e) {
        console.error("Error al consultar reporte alex", e);
      } finally {
        loading.value = false;
      }
    };

    const formatMoney = (val, dec = 2) => {
      return parseFloat(val || 0).toLocaleString('en-US', {
        minimumFractionDigits: dec,
        maximumFractionDigits: dec
      });
    };

    const imprimirReporte = () => {
        window.open(`imprimir.php?fecha=${fechaFiltro.value}`, '_blank');
    };

    onMounted(() => {
      consultar();
    });

    return {
      loading,
      fechaFiltro,
      reporte,
      totalSoles,
      consultar,
      formatMoney,
      imprimirReporte
    };
  }
}).mount('#app-sobres');
