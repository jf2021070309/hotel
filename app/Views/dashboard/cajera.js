/**
 * Módulo Frontend: Dashboard de Operaciones (Cajera).
 * 
 * Presenta una vista simplificada enfocada en la operatividad diaria:
 * check-ins esperados, check-outs, deudas de huéspedes y estado de su turno actual.
 * 
 * @module Dashboard/CajeraJS
 */
const { createApp, ref, computed, onMounted, onUnmounted } = Vue;

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
      estado: 'inexistente',
      desglose: []
    });
    const kpi = ref({
      ocupacion: { ocupadas: 0, total: 0 },
      pax_hoy: 0,
      ingresos_hoy: { PEN: 0, USD: 0, CLP: 0 },
      pendientes_hoy: { PEN: 0, USD: 0, CLP: 0 },
      egresos_hoy: { PEN: 0, USD: 0, CLP: 0 },
    });
    const alertasInventario = ref([]);

    // COMPUTED: Desglose granular solicitado
    const desgloseFormateado = computed(() => {
      const d = mi_turno.value.desglose || [];
      const res = {
        pos_pen: 0, pos_usd: 0, pos_clp: 0,
        yape_plin: 0,
        efectivo_pen: 0, efectivo_usd: 0, efectivo_clp: 0,
        transferencia: 0
      };

      d.forEach(item => {
        const medio = (item.medio_pago || '').toUpperCase();
        const cat = (item.categoria || '').toUpperCase();
        const mon = (item.moneda || 'PEN').toUpperCase();
        const total = parseFloat(item.total || 0);

        // Prioridad 1: Yape / Plin (por categoría o medio)
        if (cat.includes('YAPE') || cat.includes('PLIN') || medio.includes('YAPE') || medio.includes('PLIN')) {
          res.yape_plin += total;
        } 
        // Prioridad 2: POS (por categoría o medio)
        else if (cat.includes('POS') || medio.includes('POS')) {
          if (mon === 'PEN') res.pos_pen += total;
          else if (mon === 'USD') res.pos_usd += total;
          else if (mon === 'CLP') res.pos_clp += total;
        }
        // Prioridad 3: Transferencias / Depósitos
        else if (cat.includes('TRANS') || cat.includes('DEPOS') || medio.includes('TRANS') || medio.includes('DEPOS')) {
          res.transferencia += total;
        }
        // Prioridad 4: Efectivo (Evitar que "NO EFECTIVO" caiga aquí)
        else if (medio === 'EFECTIVO' || cat.includes('EFECTIVO')) {
          if (mon === 'PEN') res.efectivo_pen += total;
          else if (mon === 'USD') res.efectivo_usd += total;
          else if (mon === 'CLP') res.efectivo_clp += total;
        }
      });

      return res;
    });

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

    const abrirModalReporte = (id) => {
      document.getElementById('iframeReporte').src = 'app/Views/flujo/reporte_sobre.php?id=' + id;
      const modal = new bootstrap.Modal(document.getElementById('modalReporte'));
      modal.show();
    };

    const formatNumber = (val, decimals = 2) => {
      return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
      }).format(parseFloat(val || 0));
    };

    return {
      loadingInicial, segundosDesdeUpdate, usuario, urgentes, checkouts_hoy, checkins_esperados, mi_turno, kpi, alertasInventario,
      desgloseFormateado,
      abrirModalReporte, formatNumber
    };
  }
}).mount('#app-dash-cajera');
