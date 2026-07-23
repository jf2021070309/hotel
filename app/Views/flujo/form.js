/**
 * app/Views/flujo/form.js
 * Vue 3 Composition API — Formulario de Flujo de Caja.
 * 
 * Este módulo gestiona la interfaz interactiva para el registro de movimientos 
 */
const { createApp, ref, reactive, computed, onMounted, watch } = Vue;

createApp({
  setup() {
    /** @type {string} Base URL para los endpoints del controlador de flujo. */
    const BASE = '../../../api/flujo.php?action=';

    const loading = ref(true);
    const isSaving = ref(false);
    
    // Rastreo de campo enfocado para formateo visual
    const focusedField = ref(null);

    // Formateador de moneda
    const fmtMonto = (val, moneda) => {
      const n = parseFloat(val);
      if (isNaN(n)) return '';
      const decimals = (moneda === 'CLP') ? 0 : 2;
      return n.toLocaleString('en-US', { 
        minimumFractionDigits: decimals, 
        maximumFractionDigits: decimals 
      });
    };
    
    // Función para actualizar el badge de estado en la cabecera (fuera de Vue)
    const updateBadgeEstado = () => {
      const el = document.getElementById('badge-estado');
      if (el && cabecera.estado) {
        el.innerText = cabecera.estado.toUpperCase();
        el.classList.remove('d-none');
        // Cambiar color según estado
        el.className = 'badge ms-1 p-1 ' + (cabecera.estado === 'borrador' ? 'bg-secondary' : 'bg-success');
      }
    };
    
    // IDs y Modo
    const id = ref(SERVER_DATA.id);
    const esNuevo = ref(SERVER_DATA.nuevo === 1);
    
    /** 
     * @typedef {Object} Cabecera
     * @property {string} fecha - Fecha del turno (YYYY-MM-DD).
     * @property {string} turno - Nombre del turno.
     * @property {string} estado - Estado actual (borrador, cerrado, depositado).
     * @property {string} operador - Nombre del usuario que abrió el turno.
     * @property {string} nota_entrega - Observaciones finales del turno.
     */
    const cabecera = reactive({
      fecha: SERVER_DATA.fechaDefault || new Date().toISOString().split('T')[0],
      turno: SERVER_DATA.turnoDefault,
      estado: 'borrador', 
      operador: '',
      nota_entrega: ''
    });

    /** @type {Ref<Array>} Lista de movimientos de ingreso. */
    const ingresos = ref([]);
    /** @type {Ref<Array>} Lista de movimientos de egreso. */
    const egresos = ref([]);

    const categorias = reactive({
      ingreso: [],
      egreso: []
    });

    /** @type {Object} Tipos de cambio del día para visualización. */
    const tc = reactive({ USD: 3.7, CLP: 0.0039 });

    /** @type {Object} Totales acumulados netos del mes en curso. */
    const acumuladoMensual = reactive({
      PEN: '0.00',
      USD: '0.00',
      CLP: '0'
    });

    const loadAcumuladoMensual = async () => {
      if (!cabecera.fecha) return;
      const parts = cabecera.fecha.split('-');
      if (parts.length < 2) return;
      const anio = parts[0];
      const mes  = parseInt(parts[1], 10);
      try {
        const res = await axios.get(`${BASE}resumen_alex_mensual&mes=${mes}&anio=${anio}`);
        if (res.data.ok && res.data.data?.totales?.neto) {
          const neto = res.data.data.totales.neto;
          acumuladoMensual.PEN = parseFloat(neto.PEN || 0).toFixed(2);
          acumuladoMensual.USD = parseFloat(neto.USD || 0).toFixed(2);
          acumuladoMensual.CLP = parseFloat(neto.CLP || 0).toFixed(0);
        }
      } catch (e) {
        console.error('Error cargando acumulado mensual:', e);
      }
    };

    /**
     * Carga inicial de datos: categorías y detalle del turno si es edición.
     * @async
     */
    const loadData = async () => {
      loading.value = true;
      try {
        const catRes = await axios.get(`${BASE}categorias`);
        if (catRes.data.ok) {
          categorias.ingreso = catRes.data.data.filter(c => c.tipo === 'Ingreso');
          categorias.egreso  = catRes.data.data.filter(c => c.tipo === 'Egreso');
        }

        if (!esNuevo.value && id.value !== null) {
          const detRes = await axios.get(`${BASE}detalle&id=${id.value}`);
          if (detRes.data.ok) {
            const d = detRes.data.data;
            cabecera.fecha = d.fecha;
            cabecera.turno = d.turno;
            cabecera.estado = d.estado;
            cabecera.operador = d.operador;
            cabecera.nota_entrega = d.nota_entrega || '';
            
            ingresos.value = d.ingresos || [];
            egresos.value  = (d.egresos || []).map(e => ({
              ...e,
              _usaSobre: !!(e.sobre_fecha || e.sobre_turno),
              sobre_fecha: e.sobre_fecha || cabecera.fecha,
              sobre_turno: e.sobre_turno || cabecera.turno
            }));
            
            if (d.tc) {
              tc.USD = d.tc.USD;
              tc.CLP = d.tc.CLP;
            }
          } else {
            Swal.fire('Error', 'Turno no encontrado', 'error').then(()=>window.location.href = SERVER_DATA.flujoIndex);
          }
        } else {
          // Si llegamos aquí (vía URL antigua nuevo=1), creamos el registro de inmediato
          agregarMovimiento('ingresos');
          agregarMovimiento('egresos');
          cabecera.nota_entrega = '';
          setTimeout(() => triggerAutoSave(), 500); 
        }

        await loadAcumuladoMensual();

      } catch (e) {
        console.error(e);
      } finally {
        setTimeout(() => { loading.value = false; }, 200); 
      }
    };

    /**
     * Determina si el formulario permite edición basado en el estado y rol.
     * @type {ComputedRef<boolean>}
     */
    const esEditable = computed(() => {
      if (cabecera.estado === 'borrador') return true;
      if (cabecera.estado === 'cerrado' && SERVER_DATA.canEditClosed) return true;
      return false;
    });

    /**
     * Convierte un monto a moneda base (PEN) para fines de visualización.
     * @param {Object} mov Movimiento a convertir.
     * @returns {number}
     */
    const toSoles = (mov) => {
      let m = parseFloat(mov.monto) || 0;
      if (mov.moneda === 'USD') m *= tc.USD;
      if (mov.moneda === 'CLP') {
        m = tc.CLP > 1 ? (m / tc.CLP) : (m * tc.CLP);
      }
      return m;
    };

    /**
     * Totales acumulados del día en Soles (PEN).
     * @type {ComputedRef<Object>}
     */
    const totalesDia = computed(() => {
      let inPen = ingresos.value.reduce((acc, mov) => acc + toSoles(mov), 0);
      let egPen = egresos.value.reduce((acc, mov) => acc + toSoles(mov), 0);
      return {
        ingreso_pen: inPen.toFixed(2),
        egreso_pen: egPen.toFixed(2)
      };
    });

    /**
     * Saldo de Efectivo en Soles (PEN) ingresado en este turno.
     * @type {ComputedRef<string>}
     */
    const efectivoEnSobrePEN = computed(() => {
      let inEfectivoPEN = ingresos.value.filter(m => m.medio_pago === 'EFECTIVO' && m.moneda === 'PEN').reduce((acc, m) => acc + (parseFloat(m.monto)||0), 0);
      return inEfectivoPEN.toFixed(2);
    });

    const efectivoEnSobreUSD = computed(() => {
      let inEfectivo = ingresos.value.filter(m => m.medio_pago === 'EFECTIVO' && m.moneda === 'USD').reduce((acc, m) => acc + (parseFloat(m.monto)||0), 0);
      return inEfectivo.toFixed(2);
    });

    const efectivoEnSobreCLP = computed(() => {
      let inEfectivo = ingresos.value.filter(m => m.medio_pago === 'EFECTIVO' && m.moneda === 'CLP').reduce((acc, m) => acc + (parseFloat(m.monto)||0), 0);
      return inEfectivo.toFixed(0);
    });

    const totalesMonedas = computed(() => {
      let penIn = ingresos.value.filter(m => m.moneda === 'PEN').reduce((acc, m) => acc + (parseFloat(m.monto) || 0), 0);
      let penEg = egresos.value.filter(m => m.moneda === 'PEN').reduce((acc, m) => acc + (parseFloat(m.monto) || 0), 0);
      let usdIn = ingresos.value.filter(m => m.moneda === 'USD').reduce((acc, m) => acc + (parseFloat(m.monto) || 0), 0);
      let usdEg = egresos.value.filter(m => m.moneda === 'USD').reduce((acc, m) => acc + (parseFloat(m.monto) || 0), 0);
      let clpIn = ingresos.value.filter(m => m.moneda === 'CLP').reduce((acc, m) => acc + (parseFloat(m.monto) || 0), 0);
      let clpEg = egresos.value.filter(m => m.moneda === 'CLP').reduce((acc, m) => acc + (parseFloat(m.monto) || 0), 0);
      return {
        PEN: { ingresos: penIn.toFixed(2), egresos: penEg.toFixed(2) },
        USD: { ingresos: usdIn.toFixed(2), egresos: usdEg.toFixed(2) },
        CLP: { ingresos: clpIn.toFixed(0), egresos: clpEg.toFixed(0) }
      };
    });

    /**
     * Añade una nueva fila de movimiento (ingreso o egreso) al arreglo correspondiente.
     * @param {string} tipo - 'ingresos' o 'egresos'.
     */
    const agregarMovimiento = (tipo) => {
      if (!esEditable.value) return;
      const t = tipo === 'ingresos' ? 'Ingreso' : 'Egreso';
      const arr = tipo === 'ingresos' ? ingresos : egresos;
      const usaSobre = tipo === 'egresos';
      arr.value.push({
        categoria_id: null,
        categoria: '',
        tipo: t,
        moneda: 'PEN',
        monto: '',
        medio_pago: 'EFECTIVO',
        observacion: '',
        _usaSobre: usaSobre,
        sobre_fecha: cabecera.fecha,
        sobre_turno: cabecera.turno
      });
    };

    /** @type {Array} Productos estáticos del minibar */
    const minibarProductos = [
      { nombre: 'Agua Cielo', precio: 3.00 },
      { nombre: 'Inca Kola 500ml', precio: 4.00 },
      { nombre: 'Coca Cola 500ml', precio: 4.00 },
      { nombre: 'Cerveza Pilsen', precio: 6.00 },
      { nombre: 'Cerveza Cusqueña', precio: 7.00 },
      { nombre: 'Gatorade', precio: 5.00 },
      { nombre: 'Galleta Casino', precio: 2.00 },
      { nombre: 'Galleta Morocha', precio: 2.00 },
      { nombre: 'Papitas Lays', precio: 3.50 }
    ];

    /**
     * Añade rápidamente un producto de minibar a los ingresos.
     */
    const agregarMinibar = (prod) => {
      if (!esEditable.value) return;
      ingresos.value.push({
        categoria_id: null,
        categoria: 'MINIBAR', 
        tipo: 'Ingreso',
        moneda: 'PEN',
        monto: prod.precio.toFixed(2),
        medio_pago: 'EFECTIVO',
        observacion: prod.nombre,
        _usaSobre: false,
        sobre_fecha: cabecera.fecha,
        sobre_turno: cabecera.turno
      });
      const catList = categorias.ingreso;
      const found = catList.find(c => c.nombre.toUpperCase() === 'MINIBAR' || c.nombre.toUpperCase() === 'MINI BAR' || c.nombre.toUpperCase().includes('MINI'));
      if (found) {
        ingresos.value[ingresos.value.length - 1].categoria_id = found.id;
      }
    };

    /**
     * Automatiza el seteo del medio de pago basado en la categoría seleccionada.
     * Resuelve la duplicidad entre categoría y medio de pago para el usuario.
     * @param {Object} mov 
     */
    const onCategoriaChange = (mov) => {
      const cat = mov.categoria.toUpperCase();
      
      // Sincronizar el id de la categoría elegida
      const catList = mov.tipo === 'Ingreso' ? categorias.ingreso : categorias.egreso;
      const found = catList.find(c => c.nombre.toUpperCase() === cat);
      if (found) {
        mov.categoria_id = found.id;
      } else {
        mov.categoria_id = null;
      }

      // Categorías que implican dinero digital (NO EFECTIVO)
      const noEfectivoTerms = ['YAPE', 'PLIN', 'POS', 'DEPOS', 'TRANS'];
      
      const isNoEfectivo = noEfectivoTerms.some(term => cat.includes(term));
      
      if (isNoEfectivo) {
        mov.medio_pago = 'NO EFECTIVO';
      } else if (cat.includes('EFECTIVO')) {
        mov.medio_pago = 'EFECTIVO';
      } else {
        // Por defecto para otros, mantenemos lo que estaba o reseteamos a efectivo
        // Excepto si es una categoría de egreso común que suele ser efectivo
        mov.medio_pago = 'EFECTIVO';
      }
    };

    /**
     * Elimina una fila de movimiento.
     * @param {string} tipo - 'ingresos' o 'egresos'.
     * @param {number} index - Índice en el arreglo.
     */
    const eliminarMovimiento = (tipo, index) => {
      if (!esEditable.value) return;
      if (tipo === 'ingresos') ingresos.value.splice(index, 1);
      if (tipo === 'egresos')  egresos.value.splice(index, 1);
    };

    // --- AUTO-SAVE LOGIC ---
    let debounceTimer = null;
    const triggerAutoSave = () => {
      if (!esEditable.value || loading.value) return;
      if (debounceTimer) clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        guardarTurno(false, true); // (noCerrar, quietMode)
      }, 1500); 
    };

    // Observamos cambios en movimientos y notas para guardar solo
    watch([ingresos, egresos, () => cabecera.nota_entrega], () => {
       triggerAutoSave();
    }, { deep: true });

    watch(() => cabecera.estado, () => {
      updateBadgeEstado();
    });

    watch(() => cabecera.fecha, () => {
      loadAcumuladoMensual();
    });

    /**
     * Persiste los datos del turno en el servidor.
     * Puede opcionalmente cerrar el turno permanentemente.
     * @async
     * @param {boolean} cerrarFinal - Si es true, invoca el proceso de cierre tras guardar.
     * @param {boolean} quiet - Si es true, no muestra alertas de éxito (para el auto-save).
     */
    const guardarTurno = async (cerrarFinal = false, quiet = false) => {
      if (cerrarFinal) {
        const confirm = await Swal.fire({
          title: '¿Cerrar Turno?',
          text: "Una vez cerrado, no podrás editar ni agregar más movimientos.",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Sí, cerrar turno',
          cancelButtonColor: '#d33'
        });
        if (!confirm.isConfirmed) return;
      }

      isSaving.value = true;
      try {
        const data = {
          id: id.value,
          fecha: cabecera.fecha,
          turno: cabecera.turno,
          nota_entrega: cabecera.nota_entrega,
          ingresos: ingresos.value,
          egresos: egresos.value.map(e => {
            const copy = { ...e };
            if (!copy._usaSobre) {
              copy.sobre_fecha = null;
              copy.sobre_turno = null;
            }
            delete copy._usaSobre;
            return copy;
          })
        };

        const res = await axios.post(`${BASE}guardar`, data);
        if (res.data.ok) {
          id.value = res.data.data.id; 
          esNuevo.value = false;
          loadAcumuladoMensual();
          
          if (cerrarFinal) {
            const resCerrar = await axios.post(`${BASE}cerrar`, { id: id.value });
            if (resCerrar.data.ok) {
              Swal.fire('Cerrado', 'El turno ha sido cerrado y guardado.', 'success').then(() => {
                window.location.href = `${SERVER_DATA.flujoForm}?id=${id.value}`;
              });
            } else {
              Swal.fire('Error', resCerrar.data.msg, 'error');
            }
          } else if (!quiet) {
            const Toast = Swal.mixin({
              toast: true, position: 'top-end', showConfirmButton: false, timer: 2000
            });
            Toast.fire({ icon: 'success', title: 'Cambios sincronizados' });
          }
        } else if (!quiet) {
          Swal.fire('Error', res.data.msg, 'error');
        }
      } catch (e) {
        if (!quiet) Swal.fire('Error', 'Ocurrió un error de red al sincronizar', 'error');
      } finally {
        isSaving.value = false;
      }
    };

    /**
     * Envía acción de "Depositado" al servidor (Solo Admin).
     * @async
     */
    const marcarDepositado = async () => {
      const confirm = await Swal.fire({
        title: '¿Marcar como Depositado?',
        text: "Confirmas que el efectivo físico ha sido contabilizado y depositado.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, depositado',
      });
      if (!confirm.isConfirmed) return;
      
      try {
        const res = await axios.post(`${BASE}depositar`, { id: id.value });
        if (res.data.ok) {
          Swal.fire('Confirmado', 'Turno marcado como depositado', 'success')
            .then(() => window.location.reload());
        }
      } catch (e) {
        Swal.fire('Error', 'Ocurrió un error de red', 'error');
      }
    };

    /**
     * Reabre un turno cerrado (Solo Admin).
     * @async
     */
    const reabrirTurno = async () => {
      const confirm = await Swal.fire({
        title: '¿Reabrir Turno?',
        text: "Esto permitirá editar movimientos y añadir nuevas filas.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, reabrir',
      });
      if (!confirm.isConfirmed) return;

      isSaving.value = true;
      try {
        const res = await axios.post(`${BASE}reabrir`, { id: id.value });
        if (res.data.ok) {
          Swal.fire('Reabierto', res.data.msg, 'success').then(() => window.location.reload());
        } else {
          Swal.fire('Error', res.data.msg, 'error');
        }
      } catch (e) {
        Swal.fire('Error', 'Error de red', 'error');
      } finally {
        isSaving.value = false;
      }
    };

    onMounted(() => {
      loadData();
      updateBadgeEstado();
    });

    return {
      loading, isSaving, esNuevo, esEditable,
      cabecera, ingresos, egresos, categorias, minibarProductos,
      totalesDia, efectivoEnSobrePEN, efectivoEnSobreUSD, efectivoEnSobreCLP, acumuladoMensual, totalesMonedas,
      agregarMovimiento, agregarMinibar, eliminarMovimiento, onCategoriaChange, guardarTurno, marcarDepositado, reabrirTurno,
      SERVER_DATA,
      focusedField, fmtMonto
    };
  },
  directives: {
    focus: {
      mounted(el) { el.focus(); }
    }
  }
}).mount('#app-flujo-form');
