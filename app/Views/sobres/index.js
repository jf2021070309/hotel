/**
 * app/Views/sobres/index.js
 * Lógica del dashboard mensual de sobres
 */
const { createApp, ref, onMounted, computed } = Vue;

createApp({
  setup() {
    const loading = ref(true);
    const modo = ref('mensual'); // Siempre mensual

    // Filtros mensuales
    const mesFiltro  = ref(new Date(SERVER_FECHA).getMonth() + 1);
    const anioFiltro = ref(new Date(SERVER_FECHA).getFullYear());
    const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    const anios = [2024, 2025, 2026];

    // En modo mensual guardamos la lista de días y los totales generales
    const dias = ref([]);
    const totalesMes = ref({
      ingresos: { PEN: 0, USD: 0, CLP: 0 },
      egresos:  { PEN: 0, USD: 0, CLP: 0 },
      neto:     { PEN: 0, USD: 0, CLP: 0 }
    });

    const toNumber = (v) => {
      if (v === null || v === undefined) return 0;
      if (typeof v === 'number') return v;
      const cleaned = String(v).replace(/,/g, '').replace(/\s+/g, '');
      const n = parseFloat(cleaned);
      return isNaN(n) ? 0 : n;
    };

    const formatDate = (iso) => {
      if (!iso) return '';
      const dt = new Date(iso + 'T00:00:00');
      try {
        return dt.toLocaleDateString('es-PE', { day: 'numeric', month: 'short', year: 'numeric' });
      } catch (e) {
        return dt.toLocaleDateString();
      }
    };

    const formatDateShort = (iso) => {
      if (!iso) return '';
      const dt = new Date(iso + 'T00:00:00');
      const diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
      const mesesCortos = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
      return `${diasSemana[dt.getDay()]} ${dt.getDate()} ${mesesCortos[dt.getMonth()]}`;
    };

    const getDetalleExtractions = (dia) => {
      const parts = [];
      if (dia?.MAÑANA?.egresos_detalle) parts.push(`M: ${dia.MAÑANA.egresos_detalle}`);
      if (dia?.TARDE?.egresos_detalle) parts.push(`T: ${dia.TARDE.egresos_detalle}`);
      return parts.join(' | ');
    };

    const debug = ref(false);
    const lastResponse = ref(null);
    const lastUrl = ref('');

    const lastResponseStr = computed(() => {
      try { return JSON.stringify(lastResponse.value, null, 2); } catch (e) { return String(lastResponse.value); }
    });

    const consultar = async () => {
      loading.value = true;
      try {
        const url = `${window.SOBRES_CONFIG.apiEndpoint}?action=resumen_alex_mensual&mes=${mesFiltro.value}&anio=${anioFiltro.value}`;
        const res = await axios.get(url);
        if (res.data.ok && res.data.data) {
          lastUrl.value = url;
          lastResponse.value = res.data;
          const payload = res.data.data || {};
          const diasData = payload.dias || {};
          totalesMes.value = payload.totales || {
              ingresos: { PEN: 0, USD: 0, CLP: 0 },
              egresos:  { PEN: 0, USD: 0, CLP: 0 },
              neto:     { PEN: 0, USD: 0, CLP: 0 }
          };

          dias.value = Object.keys(diasData).sort((a,b) => a.localeCompare(b)).map(f => {
            const d = diasData[f];
            return {
              fecha: f,
              MAÑANA: {
                PEN: toNumber(d.MAÑANA?.PEN),
                USD: toNumber(d.MAÑANA?.USD),
                CLP: toNumber(d.MAÑANA?.CLP),
                egresos_detalle: d.MAÑANA?.egresos_detalle || ''
              },
              TARDE: {
                PEN: toNumber(d.TARDE?.PEN),
                USD: toNumber(d.TARDE?.USD),
                CLP: toNumber(d.TARDE?.CLP),
                egresos_detalle: d.TARDE?.egresos_detalle || ''
              },
              TOTAL: d.TOTAL || { PEN: 0, USD: 0, CLP: 0 }
            };
          });
        }
      } catch (e) {
        console.error("Error al consultar reporte alex mensual", e);
      } finally {
        loading.value = false;
      }
    };

    const formatMoney = (val, dec = 2) => {
      const n = toNumber(val);
      return n.toLocaleString('en-US', {
        minimumFractionDigits: dec,
        maximumFractionDigits: dec
      });
    };

    const imprimirReporte = () => {
        window.open(`${window.SOBRES_CONFIG.imprimirUrl}?mes=${mesFiltro.value}&anio=${anioFiltro.value}`, '_blank');
    };

    const toggleDebug = () => {
      debug.value = !debug.value;
    };

    onMounted(() => {
      consultar();
    });

    return {
      loading,
      modo,
      mesFiltro,
      anioFiltro,
      meses,
      anios,
      dias,
      formatDate,
      formatDateShort,
      getDetalleExtractions,
      debug,
      lastResponse,
      lastResponseStr,
      lastUrl,
      totalesMes,
      consultar,
      formatMoney,
      imprimirReporte,
      toggleDebug
    };
  }
}).mount('#app-sobres');
