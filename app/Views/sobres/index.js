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

    const formatDateNumeric = (iso) => {
      if (!iso) return '';
      const parts = iso.split('-');
      if (parts.length === 3) {
          return `${parseInt(parts[2], 10)}/${parseInt(parts[1], 10)}/${parts[0]}`;
      }
      return iso;
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
                egresos_detalle: d.MAÑANA?.egresos_detalle || '',
                nota_entrega: d.MAÑANA?.nota_entrega || '',
                manual_pen: d.MAÑANA?.manual_pen || null,
                manual_usd: d.MAÑANA?.manual_usd || null,
                manual_clp: d.MAÑANA?.manual_clp || null,
                flujo_id: parseInt(d.MAÑANA?.flujo_id) || 0
              },
              TARDE: {
                PEN: toNumber(d.TARDE?.PEN),
                USD: toNumber(d.TARDE?.USD),
                CLP: toNumber(d.TARDE?.CLP),
                egresos_detalle: d.TARDE?.egresos_detalle || '',
                nota_entrega: d.TARDE?.nota_entrega || '',
                manual_pen: d.TARDE?.manual_pen || null,
                manual_usd: d.TARDE?.manual_usd || null,
                manual_clp: d.TARDE?.manual_clp || null,
                flujo_id: parseInt(d.TARDE?.flujo_id) || 0
              },
              TOTAL: d.TOTAL || { PEN: 0, USD: 0, CLP: 0 }
            };
          });

          // Sync original notes map to detect changes
          originalNotes.value.clear();
          dias.value.forEach(d => {
             originalNotes.value.set(`${d.fecha}_MAÑANA`, JSON.stringify({ n: d.MAÑANA.nota_entrega, p: d.MAÑANA.manual_pen, u: d.MAÑANA.manual_usd, c: d.MAÑANA.manual_clp }));
             originalNotes.value.set(`${d.fecha}_TARDE`, JSON.stringify({ n: d.TARDE.nota_entrega, p: d.TARDE.manual_pen, u: d.TARDE.manual_usd, c: d.TARDE.manual_clp }));
          });

          // Auto-scroll a la fila de hoy
          setTimeout(() => {
             const rowId = 'row-' + SERVER_FECHA;
             const el = document.getElementById(rowId);
             if (el) {
                 el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                 // Resaltado temporal
                 const originalBg = el.className;
                 el.className = originalBg.replace('bg-white', 'bg-yellow-100');
                 setTimeout(() => {
                     el.className = originalBg;
                 }, 3000);
             }
          }, 300);
        }
      } catch (e) {
        console.error("Error al consultar reporte alex mensual", e);
      } finally {
        loading.value = false;
      }
    };

    const originalNotes = ref(new Map());
    const guardandoNotas = ref(false);

    const pendingChanges = computed(() => {
        let count = 0;
        dias.value.forEach(d => {
            if (originalNotes.value.get(`${d.fecha}_MAÑANA`) !== JSON.stringify({ n: d.MAÑANA.nota_entrega, p: d.MAÑANA.manual_pen, u: d.MAÑANA.manual_usd, c: d.MAÑANA.manual_clp })) count++;
            if (originalNotes.value.get(`${d.fecha}_TARDE`) !== JSON.stringify({ n: d.TARDE.nota_entrega, p: d.TARDE.manual_pen, u: d.TARDE.manual_usd, c: d.TARDE.manual_clp })) count++;
        });
        return count;
    });

    const guardarNotas = async () => {
      if (pendingChanges.value === 0) return;
      guardandoNotas.value = true;
      try {
        const turnos = [];
        dias.value.forEach(d => {
          if (originalNotes.value.get(`${d.fecha}_MAÑANA`) !== JSON.stringify({ n: d.MAÑANA.nota_entrega, p: d.MAÑANA.manual_pen, u: d.MAÑANA.manual_usd, c: d.MAÑANA.manual_clp })) {
             turnos.push({ fecha: d.fecha, turno: 'MAÑANA', flujo_id: d.MAÑANA.flujo_id, nota_entrega: d.MAÑANA.nota_entrega, manual_pen: d.MAÑANA.manual_pen, manual_usd: d.MAÑANA.manual_usd, manual_clp: d.MAÑANA.manual_clp });
          }
          if (originalNotes.value.get(`${d.fecha}_TARDE`) !== JSON.stringify({ n: d.TARDE.nota_entrega, p: d.TARDE.manual_pen, u: d.TARDE.manual_usd, c: d.TARDE.manual_clp })) {
             turnos.push({ fecha: d.fecha, turno: 'TARDE', flujo_id: d.TARDE.flujo_id, nota_entrega: d.TARDE.nota_entrega, manual_pen: d.TARDE.manual_pen, manual_usd: d.TARDE.manual_usd, manual_clp: d.TARDE.manual_clp });
          }
        });
        
        const url = `${window.SOBRES_CONFIG.apiEndpoint}?action=guardar_notas_sobres`;
        const res = await axios.post(url, { turnos });
        if (res.data.ok) {
           // Actualizar mapeo original para resetear el botón
           turnos.forEach(t => originalNotes.value.set(`${t.fecha}_${t.turno}`, JSON.stringify({ n: t.nota_entrega, p: t.manual_pen, u: t.manual_usd, c: t.manual_clp })));
        } else {
           alert("Error al guardar: " + res.data.msg);
        }
      } catch (e) {
        console.error(e);
        alert("Ocurrió un error al guardar las notas");
      } finally {
        guardandoNotas.value = false;
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
      formatDateNumeric,
      getDetalleExtractions,
      debug,
      lastResponse,
      lastResponseStr,
      lastUrl,
      totalesMes,
      consultar,
      formatMoney,
      imprimirReporte,
      toggleDebug,
      pendingChanges,
      guardandoNotas,
      guardarNotas
    };
  }
}).mount('#app-sobres');
