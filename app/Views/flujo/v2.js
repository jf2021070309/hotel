/**
 * app/Views/flujo/v2.js
 * Lógica del frontend Vue 3 para la grilla de Flujo de Caja Mensual V2
 */
const { createApp, ref, reactive, onMounted } = Vue;

const app = createApp({
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
      total_entregar: 0,
      detalles: {
        depo: [], yape: [], pos_usd: [], pos_pen: [], pesos: [], usd_ef: [], pen_ef: []
      }
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
            if (mapRes && mapRes.field) {
               manana[mapRes.field] += mapRes.val;
               if (manana.detalles[mapRes.field]) {
                 manana.detalles[mapRes.field].push(m);
               }
            }
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
            if (mapRes && mapRes.field) {
               tarde[mapRes.field] += mapRes.val;
               if (tarde.detalles[mapRes.field]) {
                 tarde.detalles[mapRes.field].push(m);
               }
            }
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

    // LOGICA DE EDICIÓN DE EGRESOS INLINE
    const edicionActiva = ref(null);
    const turnosModificados = ref(new Set());

    const iniciarEdicion = (turnoObj, campo) => {
      if (!turnoObj.flujo_id) {
        Swal.fire('Atención', 'Este turno no tiene un registro de caja abierto. Ábralo o créelo primero para agregar gastos.', 'warning');
        return;
      }
      edicionActiva.value = turnoObj.flujo_id + '_' + campo;
    };

    const finalizarEdicion = (diaObj, turnoNombre) => {
      edicionActiva.value = null;
      recalcularFila(diaObj, turnoNombre);
    };

    const recalcularFila = (diaObj, turnoNombre) => {
      const t = diaObj[turnoNombre];
      t.total_egreso = (t.mercado||0) + (t.movilidad||0) + (t.cafeteria||0) + (t.lavanderia||0) + 
                       (t.utiles||0) + (t.recepcion||0) + (t.repuestos||0) + (t.personal||0) + (t.otros_eg||0);
      
      t.total_entregar = (t.pen_ef||0) - t.total_egreso;

      diaObj.total.mercado = (diaObj.manana.mercado||0) + (diaObj.tarde.mercado||0);
      diaObj.total.movilidad = (diaObj.manana.movilidad||0) + (diaObj.tarde.movilidad||0);
      diaObj.total.cafeteria = (diaObj.manana.cafeteria||0) + (diaObj.tarde.cafeteria||0);
      diaObj.total.lavanderia = (diaObj.manana.lavanderia||0) + (diaObj.tarde.lavanderia||0);
      diaObj.total.utiles = (diaObj.manana.utiles||0) + (diaObj.tarde.utiles||0);
      diaObj.total.recepcion = (diaObj.manana.recepcion||0) + (diaObj.tarde.recepcion||0);
      diaObj.total.repuestos = (diaObj.manana.repuestos||0) + (diaObj.tarde.repuestos||0);
      diaObj.total.personal = (diaObj.manana.personal||0) + (diaObj.tarde.personal||0);
      diaObj.total.otros_eg = (diaObj.manana.otros_eg||0) + (diaObj.tarde.otros_eg||0);
      
      diaObj.total.total_egreso = (diaObj.manana.total_egreso||0) + (diaObj.tarde.total_egreso||0);
      diaObj.total.total_entregar = (diaObj.manana.total_entregar||0) + (diaObj.tarde.total_entregar||0);

      recalcularTotalesGenerales();
      turnosModificados.value.add(t.flujo_id);
    };

    const recalcularTotalesGenerales = () => {
      const g = {
        mercado: 0, movilidad: 0, cafeteria: 0, lavanderia: 0, utiles: 0, 
        recepcion: 0, repuestos: 0, personal: 0, otros_eg: 0, total_egreso: 0, total_entregar: 0
      };
      diasGrid.value.forEach(d => {
        g.mercado += d.total.mercado; g.movilidad += d.total.movilidad;
        g.cafeteria += d.total.cafeteria; g.lavanderia += d.total.lavanderia;
        g.utiles += d.total.utiles; g.recepcion += d.total.recepcion;
        g.repuestos += d.total.repuestos; g.personal += d.total.personal;
        g.otros_eg += d.total.otros_eg; g.total_egreso += d.total.total_egreso;
        g.total_entregar += d.total.total_entregar;
      });
      totalesGenerales.value.mercado = g.mercado;
      totalesGenerales.value.movilidad = g.movilidad;
      totalesGenerales.value.cafeteria = g.cafeteria;
      totalesGenerales.value.lavanderia = g.lavanderia;
      totalesGenerales.value.utiles = g.utiles;
      totalesGenerales.value.recepcion = g.recepcion;
      totalesGenerales.value.repuestos = g.repuestos;
      totalesGenerales.value.personal = g.personal;
      totalesGenerales.value.otros_eg = g.otros_eg;
      totalesGenerales.value.total_egreso = g.total_egreso;
      totalesGenerales.value.egresos_soles = g.total_egreso;
      totalesGenerales.value.soles_entregar = totalesGenerales.value.pen_ef - g.total_egreso;
    };

    const guardarCambiosEgresos = async () => {
      if (turnosModificados.value.size === 0) return;
      
      const turnosData = [];
      diasGrid.value.forEach(d => {
        if (d.manana.flujo_id && turnosModificados.value.has(d.manana.flujo_id)) {
           turnosData.push({ flujo_id: d.manana.flujo_id, mercado: d.manana.mercado, movilidad: d.manana.movilidad, cafeteria: d.manana.cafeteria, lavanderia: d.manana.lavanderia, utiles: d.manana.utiles, recepcion: d.manana.recepcion, repuestos: d.manana.repuestos, personal: d.manana.personal, otros_eg: d.manana.otros_eg });
        }
        if (d.tarde.flujo_id && turnosModificados.value.has(d.tarde.flujo_id)) {
           turnosData.push({ flujo_id: d.tarde.flujo_id, mercado: d.tarde.mercado, movilidad: d.tarde.movilidad, cafeteria: d.tarde.cafeteria, lavanderia: d.tarde.lavanderia, utiles: d.tarde.utiles, recepcion: d.tarde.recepcion, repuestos: d.tarde.repuestos, personal: d.tarde.personal, otros_eg: d.tarde.otros_eg });
        }
      });

      try {
        const payload = { turnos: turnosData };
        const res = await axios.post(window.SERVER_ROUTES.apiMensual.replace('action=mensual_grid', 'action=guardar_egresos_lote'), payload);
        if (res.data.ok) {
          Swal.fire({ icon: 'success', title: 'Egresos guardados', timer: 1500, showConfirmButton: false });
          turnosModificados.value.clear();
          cargarDatos();
        } else {
          Swal.fire('Error', res.data.msg || 'No se pudo guardar', 'error');
        }
      } catch (err) {
        console.error(err);
        Swal.fire('Error', 'No se pudieron guardar los egresos', 'error');
      }
    };

    // LOGICA DE CONSUMO RAPIDO Y TOOLTIP FLOTANTE
    const getTooltipHtml = (movs) => {
      if (!movs || movs.length === 0) return '';
      let res = `<strong style="color: #0288D1; display: block; margin-bottom: 3px; font-size: 12px;">Desglose de Ingresos</strong>`;
      movs.forEach(m => {
        let label = m.observacion || m.categoria_nombre || 'Venta';
        let pre = m.moneda === 'USD' ? '$' : (m.moneda === 'CLP' ? '₱' : 'S/');
        res += `<div>${label}: <span class="fw-bold">${pre} ${formatearNumero(m.monto)}</span></div>`;
      });
      return res;
    };

    const staysActivos = ref([]);
    const productosRefri = ref([]);
    const modalConsumoObj = ref(null);

    const formConsumo = reactive({
      flujo_id: null,
      columna: '',
      turnoName: '',
      stay_id: '',
      tipo: 'BEBIDA',
      producto_id: '',
      precio: 0
    });

    const cargarOpcionesConsumo = async (fecha) => {
      try {
        const response = await axios.get(window.SERVER_ROUTES.apiMensual.replace('action=mensual_grid', 'action=datos_consumo_rapido') + '&fecha=' + fecha);
        if (response.data && response.data.ok) {
          staysActivos.value = response.data.data.stays || [];
          productosRefri.value = response.data.data.productos || [];
        }
      } catch(e) { console.error(e); }
    };

    const abrirMenuHabitaciones = async (diaObj, turnoObj, turnoLabel, columna) => {
      if (!turnoObj.flujo_id) {
        Swal.fire('Atención', 'Este turno no está abierto o no tiene Flujo ID.', 'warning');
        return;
      }
      formConsumo.flujo_id = turnoObj.flujo_id;
      formConsumo.columna = columna;
      formConsumo.turnoName = turnoLabel;
      formConsumo.stay_id = '';
      formConsumo.tipo = 'BEBIDA';
      formConsumo.producto_id = '';
      formConsumo.precio = 0;

      await cargarOpcionesConsumo(diaObj.fecha);

      if (!modalConsumoObj.value) {
        modalConsumoObj.value = new bootstrap.Modal(document.getElementById('modalAddConsumoFlujo'));
      }
      modalConsumoObj.value.show();
    };

    const onProductoChange = () => {
      if (formConsumo.tipo === 'BEBIDA' && formConsumo.producto_id) {
        const p = productosRefri.value.find(x => x.id == formConsumo.producto_id);
        if (p) formConsumo.precio = parseFloat(p.precio_venta);
      }
    };

    const guardarConsumoFlujo = async () => {
      if (!formConsumo.stay_id) { Swal.fire('Error', 'Seleccione una habitación', 'error'); return; }
      if (formConsumo.tipo === 'BEBIDA' && !formConsumo.producto_id) { Swal.fire('Error', 'Seleccione una bebida', 'error'); return; }
      if (formConsumo.precio <= 0) { Swal.fire('Error', 'El precio debe ser mayor a 0', 'error'); return; }

      const payload = { ...formConsumo };
      try {
        const res = await axios.post(window.SERVER_ROUTES.apiMensual.replace('action=mensual_grid', 'action=guardar_consumo_rapido'), payload);
        if (res.data.ok) {
          modalConsumoObj.value.hide();
          Swal.fire({ icon: 'success', title: 'Agregado correctamente', timer: 1500, showConfirmButton: false });
          cargarDatos();
        } else {
          Swal.fire('Error', res.data.msg, 'error');
        }
      } catch (err) {
        console.error(err);
        Swal.fire('Error', 'No se pudo guardar el consumo', 'error');
      }
    };

    onMounted(() => {
      cargarOpcionesConsumo();
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
      SERVER_ROUTES: window.SERVER_ROUTES,
      getTooltipHtml,
      abrirMenuHabitaciones,
      formConsumo,
      staysActivos,
      productosRefri,
      onProductoChange,
      guardarConsumoFlujo,
      edicionActiva,
      turnosModificados,
      iniciarEdicion,
      finalizarEdicion,
      guardarCambiosEgresos
    };
  }
});

app.directive('focus', {
  mounted(el) {
    el.focus();
    el.select();
  }
});

app.mount('#app-flujo-v2');
