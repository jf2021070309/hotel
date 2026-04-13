/**
 * app/Views/sobres/index.js
 * Lógica del dashboard de sobres
 */
const { createApp, ref, onMounted, computed } = Vue;

createApp({
  setup() {
    const loading = ref(true);
    const modo = ref('diario'); // diario | mensual
    const fechaFiltro = ref(SERVER_FECHA);

    // Filtros mensuales
    const mesFiltro  = ref(new Date(SERVER_FECHA).getMonth() + 1);
    const anioFiltro = ref(new Date(SERVER_FECHA).getFullYear());
    const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    const anios = [2024, 2025, 2026];

    const reporte = ref({
      MAÑANA: { PEN: 0, USD: 0, CLP: 0, egresos_detalle: '' },
      TARDE: { PEN: 0, USD: 0, CLP: 0, egresos_detalle: '' }
    });

    const totalSoles = computed(() => {
        return (parseFloat(reporte.value.MAÑANA.PEN) || 0) + (parseFloat(reporte.value.TARDE.PEN) || 0);
    });

    const setModo = (m) => {
        modo.value = m;
        consultar();
    };

    const consultar = async () => {
      loading.value = true;
      try {
        let url = `../../../api/flujo.php?action=resumen_alex&fecha=${fechaFiltro.value}`;
        if (modo.value === 'mensual') {
            url = `../../../api/flujo.php?action=resumen_alex_mensual&mes=${mesFiltro.value}&anio=${anioFiltro.value}`;
        }

        const res = await axios.get(url);
        if (res.data.ok && res.data.data) {
          if (modo.value === 'mensual') {
              // Consolidar el mensual en la vista de tarjetas
              const consolidado = {
                  MAÑANA: { PEN: 0, USD: 0, CLP: 0, egresos_detalle: '' },
                  TARDE: { PEN: 0, USD: 0, CLP: 0, egresos_detalle: '' }
              };
              
              const detallesM = [];
              const detallesT = [];

              // res.data.data es un objeto { "fecha": { MAÑANA: {}, TARDE: {} }, ... }
              Object.values(res.data.data).forEach(dia => {
                  if (dia.MAÑANA) {
                    consolidado.MAÑANA.PEN += parseFloat(dia.MAÑANA.PEN || 0);
                    consolidado.MAÑANA.USD += parseFloat(dia.MAÑANA.USD || 0);
                    consolidado.MAÑANA.CLP += parseFloat(dia.MAÑANA.CLP || 0);
                    if (dia.MAÑANA.egresos_detalle) detallesM.push(dia.MAÑANA.egresos_detalle);
                  }

                  if (dia.TARDE) {
                    consolidado.TARDE.PEN += parseFloat(dia.TARDE.PEN || 0);
                    consolidado.TARDE.USD += parseFloat(dia.TARDE.USD || 0);
                    consolidado.TARDE.CLP += parseFloat(dia.TARDE.CLP || 0);
                    if (dia.TARDE.egresos_detalle) detallesT.push(dia.TARDE.egresos_detalle);
                  }
              });

              consolidado.MAÑANA.egresos_detalle = detallesM.join(', ') || 'Ninguna';
              consolidado.TARDE.egresos_detalle  = detallesT.join(', ') || 'Ninguna';
              
              reporte.value = consolidado;
          } else {
              reporte.value = res.data.data;
          }
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
        if (modo.value === 'diario') {
            window.open(`imprimir.php?fecha=${fechaFiltro.value}`, '_blank');
        } else {
            window.open(`imprimir_mensual.php?mes=${mesFiltro.value}&anio=${anioFiltro.value}`, '_blank');
        }
    };

    onMounted(() => {
      consultar();
    });

    return {
      loading,
      modo,
      fechaFiltro,
      mesFiltro,
      anioFiltro,
      meses,
      anios,
      reporte,
      totalSoles,
      setModo,
      consultar,
      formatMoney,
      imprimirReporte
    };
  }
}).mount('#app-sobres');
