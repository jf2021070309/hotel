/**
 * app/Views/flujo/form.js
 * Vue 3 Composition API — Formulario de Flujo de Caja
 */
const { createApp, ref, reactive, computed, onMounted } = Vue;

createApp({
  setup() {
    const BASE = '../../../api/flujo.php?action=';

    const loading = ref(true);
    const isSaving = ref(false);
    
    // IDs y Modo
    const id = ref(SERVER_DATA.id);
    const esNuevo = ref(SERVER_DATA.nuevo === 1);
    
    // Data structures
    const cabecera = reactive({
      fecha: new Date().toISOString().split('T')[0],
      turno: SERVER_DATA.turnoDefault,
      estado: 'borrador', // borrador | cerrado | depositado
      operador: '',
      nota_entrega: ''
    });

    const ingresos = ref([]);
    const egresos = ref([]);

    const categorias = reactive({
      ingreso: [],
      egreso: []
    });

    const tc = reactive({ USD: 3.7, CLP: 0.0039 }); // Default, overriden if load existing

    // ─── API FETCH ────────────────────────────────────────────────────
    const loadData = async () => {
      loading.value = true;
      try {
        // Cargar Categorias
        const catRes = await axios.get(`${BASE}categorias`);
        if (catRes.data.ok) {
          categorias.ingreso = catRes.data.data.filter(c => c.tipo === 'Ingreso');
          categorias.egreso  = catRes.data.data.filter(c => c.tipo === 'Egreso');
        }

        // Si es edición, cargar detalle
        if (!esNuevo.value && id.value !== null) {
          const detRes = await axios.get(`${BASE}detalle&id=${id.value}`);
          if (detRes.data.ok) {
            const d = detRes.data.data;
            cabecera.fecha = d.fecha;
            cabecera.turno = d.turno;
            cabecera.estado = d.estado;
            cabecera.operador = d.operador;
            cabecera.nota_entrega = d.nota_entrega;
            
            ingresos.value = d.ingresos || [];
            egresos.value  = d.egresos || [];
            
            if (d.tc) {
              tc.USD = d.tc.USD;
              tc.CLP = d.tc.CLP;
            }
          } else {
            Swal.fire('Error', 'Turno no encontrado', 'error').then(()=>window.location.href='index.php');
          }
        } else {
          // Add first row empty if new
          agregarMovimiento('ingresos');
          agregarMovimiento('egresos');
        }

      } catch (e) {
        console.error(e);
      } finally {
        loading.value = false;
      }
    };

    // ─── COMPUTADOS REALTIME ──────────────────────────────────────────
    const esEditable = computed(() => cabecera.estado === 'borrador');

    // PEN Totals using generic TC (approx for viewing, backend handles exact TC)
    const toSoles = (mov) => {
      let m = parseFloat(mov.monto) || 0;
      if (mov.moneda === 'USD') m *= tc.USD;
      if (mov.moneda === 'CLP') m *= tc.CLP;
      return m;
    };

    const totalesDia = computed(() => {
      let inPen = ingresos.value.reduce((acc, mov) => acc + toSoles(mov), 0);
      let egPen = egresos.value.reduce((acc, mov) => acc + toSoles(mov), 0);
      return {
        ingreso_pen: inPen.toFixed(2),
        egreso_pen: egPen.toFixed(2)
      };
    });

    const efectivoEnSobrePEN = computed(() => {
      let inEfectivoPEN = ingresos.value.filter(m => m.medio_pago === 'EFECTIVO' && m.moneda === 'PEN').reduce((acc, m) => acc + (parseFloat(m.monto)||0), 0);
      let egEfectivoPEN = egresos.value.filter(m => m.medio_pago === 'EFECTIVO' && m.moneda === 'PEN').reduce((acc, m) => acc + (parseFloat(m.monto)||0), 0);
      return (inEfectivoPEN - egEfectivoPEN).toFixed(2);
    });

    const efectivoEnSobreUSD = computed(() => {
      let inEfectivo = ingresos.value.filter(m => m.medio_pago === 'EFECTIVO' && m.moneda === 'USD').reduce((acc, m) => acc + (parseFloat(m.monto)||0), 0);
      let egEfectivo = egresos.value.filter(m => m.medio_pago === 'EFECTIVO' && m.moneda === 'USD').reduce((acc, m) => acc + (parseFloat(m.monto)||0), 0);
      return (inEfectivo - egEfectivo).toFixed(2);
    });

    const efectivoEnSobreCLP = computed(() => {
      let inEfectivo = ingresos.value.filter(m => m.medio_pago === 'EFECTIVO' && m.moneda === 'CLP').reduce((acc, m) => acc + (parseFloat(m.monto)||0), 0);
      let egEfectivo = egresos.value.filter(m => m.medio_pago === 'EFECTIVO' && m.moneda === 'CLP').reduce((acc, m) => acc + (parseFloat(m.monto)||0), 0);
      return (inEfectivo - egEfectivo).toFixed(0);
    });

    // ─── ACCIONES FILAS ───────────────────────────────────────────────
    const agregarMovimiento = (tipo) => {
      if (!esEditable.value) return;
      const t = tipo === 'ingresos' ? 'Ingreso' : 'Egreso';
      const arr = tipo === 'ingresos' ? ingresos : egresos;
      arr.value.push({
        categoria_id: null,
        categoria: '',
        tipo: t,
        moneda: 'PEN',
        monto: '',
        medio_pago: 'EFECTIVO',
        observacion: ''
      });
    };

    const eliminarMovimiento = (tipo, index) => {
      if (!esEditable.value) return;
      if (tipo === 'ingresos') ingresos.value.splice(index, 1);
      if (tipo === 'egresos')  egresos.value.splice(index, 1);
    };

    // ─── GUARDADOS ────────────────────────────────────────────────────
    const guardarTurno = async (cerrarFinal = false) => {
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
          egresos: egresos.value
        };

        const res = await axios.post(`${BASE}guardar`, data);
        if (res.data.ok) {
          id.value = res.data.data.id; // Refresh ID in case it was new
          esNuevo.value = false;
          
          if (cerrarFinal) {
            // Llama API cerrar
            const resCerrar = await axios.post(`${BASE}cerrar`, { id: id.value });
            if (resCerrar.data.ok) {
              Swal.fire('Cerrado', 'El turno ha sido cerrado', 'success').then(() => {
                window.location.href = `form.php?id=${id.value}`;
              });
            } else {
              Swal.fire('Error', resCerrar.data.msg, 'error');
            }
          } else {
            const Toast = Swal.mixin({
              toast: true, position: 'top-end', showConfirmButton: false, timer: 3000
            });
            Toast.fire({ icon: 'success', title: 'Borrador Guardado' });
            // Cargar datos denuevo pa refrescar DB keys
            loadData();
          }
        } else {
          Swal.fire('Error', res.data.msg, 'error');
        }
      } catch (e) {
        Swal.fire('Error', 'Ocurrió un error de red al guardar', 'error');
      } finally {
        isSaving.value = false;
      }
    };

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

    onMounted(() => {
      loadData();
    });

    return {
      loading, isSaving, esNuevo, esEditable,
      cabecera, ingresos, egresos, categorias,
      totalesDia, efectivoEnSobrePEN, efectivoEnSobreUSD, efectivoEnSobreCLP,
      agregarMovimiento, eliminarMovimiento, guardarTurno, marcarDepositado
    };
  }
}).mount('#app-flujo-form');
