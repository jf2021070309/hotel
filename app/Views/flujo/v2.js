/**
 * app/Views/flujo/v2.js
 * Lógica del frontend Vue 3 para la grilla de Flujo de Caja Mensual V2
 */
const { createApp, ref, reactive, onMounted } = Vue;

createApp({
  setup() {
    const loading = ref(false);
    const meses = [
      'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
      'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];

    const filtros = reactive({
      mes: new Date().getMonth() + 1,
      anio: new Date().getFullYear()
    });

    const diasGrid = ref([]);
    const totalesGenerales = ref({
      depo: 0,
      yape: 0,
      pos_usd: 0,
      pos_pen: 0,
      pesos: 0,
      usd_ef: 0,
      pen_ef: 0,
      mercado: 0,
      movilidad: 0,
      cafeteria: 0,
      lavanderia: 0,
      utiles: 0,
      recepcion: 0,
      repuestos: 0,
      personal: 0,
      otros_eg: 0,
      total_egreso: 0,
      total_entregar: 0,
      ingresos_soles: 0,
      egresos_soles: 0,
      soles_entregar: 0
    });

    /**
     * Da formato a un número para visualización
     */
    const formatearNumero = (num) => {
      if (num === null || num === undefined) return '';
      const val = parseFloat(num);
      if (isNaN(val)) return '';
      return val.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    /**
     * Mapea un movimiento a su columna correspondiente
     */
    const mapearColumna = (m) => {
      const name = (m.categoria_nombre || '').toUpperCase();
      const id = parseInt(m.categoria_id);
      const monto = parseFloat(m.monto || 0);

      if (m.tipo === 'Ingreso') {
        if (name.includes('DEPOS') || name.includes('TRAN') || id === 1) return { field: 'depo', val: monto };
        if (name.includes('YAPE') || name.includes('PLIN') || id === 2 || id === 8) return { field: 'yape', val: monto };
        if ((name.includes('POS') && name.includes('DOLAR')) || id === 3) return { field: 'pos_usd', val: monto };
        if ((name.includes('POS') && name.includes('SOL')) || id === 4) return { field: 'pos_pen', val: monto };
        if (name.includes('PESO') || id === 5) return { field: 'pesos', val: monto };
        if ((name.includes('DOLAR') && name.includes('EFEC')) || id === 6) return { field: 'usd_ef', val: monto };
        if ((name.includes('SOL') && name.includes('EFEC')) || id === 7) return { field: 'pen_ef', val: monto };
        return { field: 'pen_ef', val: monto };
      } else {
        if (name.includes('MERCADO') || id === 9) return { field: 'mercado', val: monto };
        if (name.includes('MOVIL') || id === 10) return { field: 'movilidad', val: monto };
        if (name.includes('CAFE') || name.includes('VEA') || name.includes('GENOV') || id === 11) return { field: 'cafeteria', val: monto };
        if (name.includes('LAVAN') || id === 12) return { field: 'lavanderia', val: monto };
        if (name.includes('ESCRIT') || name.includes('UTIL') || id === 13) return { field: 'utiles', val: monto };
        if (name.includes('RECEP') || name.includes('C.CH') || name.includes('CHICA') || id === 14) return { field: 'recepcion', val: monto };
        if (name.includes('REPUEST') || name.includes('SERV') || id === 15) return { field: 'repuestos', val: monto };
        if (name.includes('PERSO') || name.includes('PAGO') || id === 16) return { field: 'personal', val: monto };
        return { field: 'otros_eg', val: monto };
      }
    };

    /**
     * Inicializa un objeto de turno vacío
     */
    const crearTurnoVacio = () => ({
      flujo_id: null,
      operador: '',
      nota_entrega: '',
      depo: 0,
      yape: 0,
      pos_usd: 0,
      pos_pen: 0,
      pesos: 0,
      usd_ef: 0,
      pen_ef: 0,
      mercado: 0,
      movilidad: 0,
      cafeteria: 0,
      lavanderia: 0,
      utiles: 0,
      recepcion: 0,
      repuestos: 0,
      personal: 0,
      otros_eg: 0,
      total_egreso: 0,
      total_entregar: 0
    });

    /**
     * Carga todos los flujos del mes desde el servidor
     */
    const obtenerFechaHoy = () => {
      const hoyObj = new Date();
      return `${hoyObj.getFullYear()}-${String(hoyObj.getMonth() + 1).padStart(2, '0')}-${String(hoyObj.getDate()).padStart(2, '0')}`;
    };

    /**
     * Carga todos los flujos del mes desde el servidor
     */
    const cargarDatos = async () => {
      loading.value = true;
      try {
        const response = await axios.get(window.SERVER_ROUTES.apiMensual, {
          params: {
            mes: filtros.mes,
            anio: filtros.anio
          }
        });

        if (response.data && response.data.ok) {
          const apiData = response.data.data;
          procesarFlujosYMovimientos(apiData.flows, apiData.movements);
          
          // Auto-scroll a la fila de la fecha de hoy
          setTimeout(() => {
            const hoyStr = obtenerFechaHoy();
            const targetRow = document.getElementById(`row-manana-${hoyStr}`);
            if (targetRow) {
              targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
          }, 350);
        } else {
          Swal.fire('Error', response.data?.msg || 'No se pudo cargar la información.', 'error');
        }
      } catch (err) {
        console.error('Error al cargar datos de flujo:', err);
        Swal.fire('Error de red', 'Ocurrió un error al comunicarse con el servidor.', 'error');
      } finally {
        loading.value = false;
      }
    };

    /**
     * Procesa los flujos de caja y movimientos y arma la grilla de días
     */
    const procesarFlujosYMovimientos = (flows, movements) => {
      const anio = parseInt(filtros.anio);
      const mes = parseInt(filtros.mes);
      const totalDias = new Date(anio, mes, 0).getDate();
      const tempGrid = [];

      // Inicializar totales generales
      const gTotales = {
        depo: 0, yape: 0, pos_usd: 0, pos_pen: 0, pesos: 0, usd_ef: 0, pen_ef: 0,
        mercado: 0, movilidad: 0, cafeteria: 0, lavanderia: 0, utiles: 0, recepcion: 0, repuestos: 0, personal: 0, otros_eg: 0,
        total_egreso: 0, total_entregar: 0, ingresos_soles: 0, egresos_soles: 0, soles_entregar: 0
      };

      for (let d = 1; d <= totalDias; d++) {
        const dayStr = String(d).padStart(2, '0');
        const monthStr = String(mes).padStart(2, '0');
        const fecha = `${anio}-${monthStr}-${dayStr}`;
        const fechaFormateada = `${d}/${monthStr}/${anio}`;

        const manana = crearTurnoVacio();
        const tarde = crearTurnoVacio();

        // 1. Mapear Turno Mañana
        const flowKeyManana = `${fecha}_MAÑANA`;
        if (flows[flowKeyManana]) {
          const f = flows[flowKeyManana];
          manana.flujo_id = f.id;
          manana.operador = f.operador;
          manana.nota_entrega = f.nota_entrega;

          const movs = movements[f.id] || [];
          movs.forEach(m => {
            const mapRes = mapearColumna(m);
            manana[mapRes.field] += mapRes.val;
          });

          // Calcular egreso y entregar
          manana.total_egreso = manana.mercado + manana.movilidad + manana.cafeteria + manana.lavanderia + 
                               manana.utiles + manana.recepcion + manana.repuestos + manana.personal + manana.otros_eg;
          manana.total_entregar = manana.pen_ef - manana.total_egreso;
        }

        // 2. Mapear Turno Tarde
        const flowKeyTarde = `${fecha}_TARDE`;
        if (flows[flowKeyTarde]) {
          const f = flows[flowKeyTarde];
          tarde.flujo_id = f.id;
          tarde.operador = f.operador;
          tarde.nota_entrega = f.nota_entrega;

          const movs = movements[f.id] || [];
          movs.forEach(m => {
            const mapRes = mapearColumna(m);
            tarde[mapRes.field] += mapRes.val;
          });

          // Calcular egreso y entregar
          tarde.total_egreso = tarde.mercado + tarde.movilidad + tarde.cafeteria + tarde.lavanderia + 
                             tarde.utiles + tarde.recepcion + tarde.repuestos + tarde.personal + tarde.otros_eg;
          tarde.total_entregar = tarde.pen_ef - tarde.total_egreso;
        }

        // 3. Mapear Fila TOTAL del día
        const total = {
          depo: manana.depo + tarde.depo,
          yape: manana.yape + tarde.yape,
          pos_usd: manana.pos_usd + tarde.pos_usd,
          pos_pen: manana.pos_pen + tarde.pos_pen,
          pesos: manana.pesos + tarde.pesos,
          usd_ef: manana.usd_ef + tarde.usd_ef,
          pen_ef: manana.pen_ef + tarde.pen_ef,
          mercado: manana.mercado + tarde.mercado,
          movilidad: manana.movilidad + tarde.movilidad,
          cafeteria: manana.cafeteria + tarde.cafeteria,
          lavanderia: manana.lavanderia + tarde.lavanderia,
          utiles: manana.utiles + tarde.utiles,
          recepcion: manana.recepcion + tarde.recepcion,
          repuestos: manana.repuestos + tarde.repuestos,
          personal: manana.personal + tarde.personal,
          otros_eg: manana.otros_eg + tarde.otros_eg,
          total_egreso: manana.total_egreso + tarde.total_egreso,
          total_entregar: manana.total_entregar + tarde.total_entregar
        };

        // Agregar al acumulador mensual general
        Object.keys(gTotales).forEach(k => {
          if (k in total) {
            gTotales[k] += total[k];
          }
        });

        tempGrid.push({
          fecha,
          fecha_formateada: fechaFormateada,
          manana,
          tarde,
          total
        });
      }

      // Calcular tarjetas superiores
      gTotales.ingresos_soles = gTotales.depo + gTotales.yape + gTotales.pos_pen + gTotales.pen_ef;
      gTotales.egresos_soles = gTotales.total_egreso;
      gTotales.soles_entregar = gTotales.pen_ef - gTotales.total_egreso;

      diasGrid.value = tempGrid;
      totalesGenerales.value = gTotales;
    };

    /**
     * Generador automático al cambiar los filtros
     */
    const generarCalendario = () => {
      cargarDatos();
    };

    onMounted(() => {
      cargarDatos();
    });

    return {
      loading,
      meses,
      filtros,
      diasGrid,
      totalesGenerales,
      formatearNumero,
      cargarDatos,
      generarCalendario,
      obtenerFechaHoy,
      SERVER_ROUTES: window.SERVER_ROUTES
    };
  }
}).mount('#app-flujo-v2');
