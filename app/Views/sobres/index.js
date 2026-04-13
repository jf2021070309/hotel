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
    // En modo mensual guardamos la lista de días
    const dias = ref([]);

    const toNumber = (v) => {
      if (v === null || v === undefined) return 0;
      if (typeof v === 'number') return v;
      // Remove thousands separators and whitespace before parsing
      const cleaned = String(v).replace(/,/g, '').replace(/\s+/g, '');
      const n = parseFloat(cleaned);
      return isNaN(n) ? 0 : n;
    };

    // Helper para formatear fechas sin desplazamiento de zona horaria
    const formatDate = (iso) => {
      if (!iso) return '';
      const dt = new Date(iso + 'T00:00:00');
      try {
        return dt.toLocaleDateString('es-PE', { day: 'numeric', month: 'short', year: 'numeric' });
      } catch (e) {
        return dt.toLocaleDateString();
      }
    };

    const totalSoles = computed(() => {
        return toNumber(reporte.value.MAÑANA.PEN) + toNumber(reporte.value.TARDE.PEN);
    });
    const debug = ref(false);
    const lastResponse = ref(null);
    const lastConsolidado = ref(null);
    const lastUrl = ref('');

    const lastResponseStr = computed(() => {
      try { return JSON.stringify(lastResponse.value, null, 2); } catch (e) { return String(lastResponse.value); }
    });
    const lastConsolidadoStr = computed(() => {
      try { return JSON.stringify(lastConsolidado.value, null, 2); } catch (e) { return String(lastConsolidado.value); }
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
          lastUrl.value = url;
          lastResponse.value = res.data; // guardar para depuración
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
                    consolidado.MAÑANA.PEN += toNumber(dia.MAÑANA.PEN);
                    consolidado.MAÑANA.USD += toNumber(dia.MAÑANA.USD);
                    consolidado.MAÑANA.CLP += toNumber(dia.MAÑANA.CLP);
                    if (dia.MAÑANA.egresos_detalle) detallesM.push(dia.MAÑANA.egresos_detalle);
                  }

                  if (dia.TARDE) {
                    consolidado.TARDE.PEN += toNumber(dia.TARDE.PEN);
                    consolidado.TARDE.USD += toNumber(dia.TARDE.USD);
                    consolidado.TARDE.CLP += toNumber(dia.TARDE.CLP);
                    if (dia.TARDE.egresos_detalle) detallesT.push(dia.TARDE.egresos_detalle);
                  }
              });

              consolidado.MAÑANA.egresos_detalle = detallesM.join(', ') || 'Ninguna';
              consolidado.TARDE.egresos_detalle  = detallesT.join(', ') || 'Ninguna';
                lastConsolidado.value = consolidado;
                reporte.value = consolidado;
                  // Construir array de días (fecha + datos ya normalizados)
                  // Ordenar las fechas en orden descendente y construir array de días
                  dias.value = Object.keys(res.data.data).sort((a,b) => b.localeCompare(a)).map(f => {
                    const d = res.data.data[f];
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
                    // Si la consulta mensual no devolvió días, forzamos totales a 0
                    if (!dias.value.length) {
                      const emptyConsol = {
                        MAÑANA: { PEN: 0, USD: 0, CLP: 0, egresos_detalle: 'Ninguna' },
                        TARDE:  { PEN: 0, USD: 0, CLP: 0, egresos_detalle: 'Ninguna' }
                      };
                      lastConsolidado.value = emptyConsol;
                      reporte.value = emptyConsol;
                    }

              // (formatDate está definido en el scope superior)
          } else {
              // Normalizar valores numéricos para modo diario
              const d = res.data.data || {};
              const normalized = {
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
                }
              };
              lastConsolidado.value = normalized;
              reporte.value = normalized;
          }
        }
      } catch (e) {
        console.error("Error al consultar reporte alex", e);
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
        if (modo.value === 'diario') {
            window.open(`imprimir.php?fecha=${fechaFiltro.value}`, '_blank');
        } else {
            window.open(`imprimir_mensual.php?mes=${mesFiltro.value}&anio=${anioFiltro.value}`, '_blank');
        }
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
      fechaFiltro,
      mesFiltro,
      anioFiltro,
      meses,
      anios,
      reporte,
      dias,
      formatDate,
      totalSoles,
      debug,
      lastResponse,
      lastConsolidado,
      lastResponseStr,
      lastUrl,
      lastConsolidadoStr,
      setModo,
      consultar,
      formatMoney,
      imprimirReporte
      ,toggleDebug
    };
  }
}).mount('#app-sobres');
