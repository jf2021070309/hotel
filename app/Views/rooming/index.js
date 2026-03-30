/**
 * admin/rooming/rooming.js
 * Vue 3 Composition API
 */
const { createApp, ref, reactive, computed, onMounted } = Vue;

createApp({
  setup() {
    const stays = ref([]);
    const habitacionesLibres = ref([]);
    const tcs = ref({ USD: 3.75, CLP: 0.0038 });
    const loading = ref(false);
    const busqueda = ref('');
    const filtroPiso = ref('');
    const filtroPago = ref('');
    const selectedStay = ref(null);
    const stayParaPago = ref(null);
    const mediosPago = ref([]);
    
    // CONSUMOS
    const inventario = ref([]);
    const stayParaConsumo = ref(null);
    const consumosStay = ref([]);
    const consumoForm = reactive({
      stay_id: '',
      producto_id: '',
      cantidad: 1,
      total: 0,
      pago_inmediato: false,
      metodo_pago: null
    });

    const form = reactive({
      stay: {
        habitacion_id: '',
        fecha_registro: new Date().toISOString().split('T')[0],
        hora_checkin: new Date().toTimeString().slice(0,5),
        fecha_checkout: '',
        noches: 1,
        medio_reserva: 'DIRECTO',
        total_pago: 0,
        moneda_pago: 'PEN',
        monto_original: 0,
        tc_aplicado: 1,
        metodo_pago: 'EFECTIVO',
        tipo_comprobante: 'RECIBO',
        num_comprobante: '',
        carro: 'NO',
        total_cobrado: 0,
        estado_pago: 'pendiente',
        procedencia: '',
        observaciones: ''
      },
      pax: [{
        nombre_completo: '',
        documento_tipo: 'DNI',
        documento_num: '',
        nacionalidad: 'Peruana',
        ciudad: '',
        es_titular: true
      }],
      adelanto: 0
    });

    const pagoForm = reactive({
      stay_id: '',
      monto: 0,
      moneda: 'PEN',
      monto_pen: 0,
      tc: 1,
      tipo: 'EFECTIVO',
      recibo: '',
      fecha: new Date().toISOString().split('T')[0]
    });

    // COMPUTED
    const staysFiltrados = computed(() => {
      let data = stays.value;
      if (busqueda.value) {
        const q = busqueda.value.toLowerCase();
        data = data.filter(s => 
          (s.titular_nombre && s.titular_nombre.toLowerCase().includes(q)) || 
          s.hab_numero.toString().includes(q)
        );
      }
      if (filtroPiso.value) {
        data = data.filter(s => s.hab_piso == filtroPiso.value);
      }
      if (filtroPago.value) {
        data = data.filter(s => s.estado_pago === filtroPago.value);
      }
      return data;
    });

    const inventarioAgrupado = computed(() => {
      const groups = {};
      inventario.value.forEach(p => {
        if (!groups[p.categoria]) groups[p.categoria] = [];
        groups[p.categoria].push(p);
      });
      return groups;
    });

    // MÉTODOS
    const cargarDatos = async () => {
      loading.value = true;
      try {
        const [resStays, resHabs, resTC, resMedios] = await Promise.all([
          axios.get('../../../api/rooming.php?action=listar'),
          axios.get('../../../api/habitaciones.php?action=libres'),
          axios.get('../../../api/tipos_cambio.php'),
          axios.get('../../../api/medios_pago.php?action=listar')
        ]);
        stays.value = resStays.data.data || [];
        habitacionesLibres.value = resHabs.data.data || [];
        tcs.value = resTC.data.data;
        mediosPago.value = resMedios.data.data || [];
      } catch (err) {
        showToast('Error al cargar datos', 'error');
      } finally {
        loading.value = false;
      }
    };

    const abrirCheckin = () => {
      resetForm();
      calcularNoches();
      new bootstrap.Modal('#modalCheckin').show();
    };

    const resetForm = () => {
      Object.assign(form.stay, {
        habitacion_id: '',
        fecha_registro: new Date().toISOString().split('T')[0],
        hora_checkin: new Date().toTimeString().slice(0,5),
        fecha_checkout: '',
        noches: 1,
        medio_reserva: 'DIRECTO',
        total_pago: 0,
        moneda_pago: 'PEN',
        monto_original: 0,
        tc_aplicado: 1,
        metodo_pago: 'EFECTIVO',
        tipo_comprobante: 'RECIBO',
        num_comprobante: '',
        carro: 'NO',
        total_cobrado: 0,
        estado_pago: 'pendiente'
      });
      form.pax = [{ nombre_completo: '', documento_tipo: 'DNI', documento_num: '', nacionalidad: 'Peruana', ciudad: '', es_titular: true }];
      form.adelanto = 0;
    };

    const onHabChange = () => {
      const h = habitacionesLibres.value.find(x => x.id == form.stay.habitacion_id);
      if (h) {
        form.stay.monto_original = h.precio_base * (form.stay.noches || 1);
        form.stay.tipo_hab_declarado = h.tipo;
        recalcularMoneda();
      }
    };

    const activarReserva = async (s) => {
      loading.value = true;
      try {
        const res = await axios.get(`../../../api/rooming.php?action=detalle&id=${s.id}`);
        const data = res.data.data;
        resetForm();
        
        // Cargar datos de la reserva al formulario
        form.stay.id = data.id;
        form.stay.habitacion_id = data.habitacion_id;
        form.stay.fecha_registro = data.fecha_registro;
        form.stay.noches = data.noches || 1;
        form.stay.medio_reserva = data.medio_reserva || 'DIRECTO';
        form.stay.observaciones = data.observaciones;
        
        // Mapeo de precios y estados
        form.stay.monto_original = data.monto_original || 0;
        form.stay.total_pago = data.total_pago || 0;
        form.stay.moneda_pago = data.moneda_pago || 'PEN';
        form.stay.estado_pago = data.estado_pago || 'pendiente';
        form.stay.tipo_hab_declarado = data.hab_tipo || 'ESTANDAR';

        // Cargar PAX (Huéspedes)
        if (data.pax && data.pax.length > 0) {
            form.pax = data.pax.map(p => ({
                nombre_completo: p.nombre_completo,
                documento_tipo:  p.documento_tipo || 'DNI',
                documento_num:   p.documento_num,
                nacionalidad:    p.nacionalidad || 'Peruana',
                ciudad:          p.ciudad || '',
                es_titular:      p.es_titular == 1
            }));
        }

        calcularNoches();
        recalcularMoneda();
        
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCheckin')).show();
      } catch (e) {
        showToast('Error al cargar reserva', 'error');
      } finally {
        loading.value = false;
      }
    };

    const calcularNoches = () => {
      if (!form.stay.fecha_registro) return;
      
      // Ensure nights is a valid number, default to 1 if empty/invalid
      const n = parseInt(form.stay.noches) || 0;
      
      // Create date at noon to avoid timezone issues with date-only strings
      const d = new Date(form.stay.fecha_registro + 'T12:00:00');
      
      if (!isNaN(d.getTime())) {
        d.setDate(d.getDate() + n);
        form.stay.fecha_checkout = d.toISOString().split('T')[0];
        onHabChange();
      }
    };

    const onNochesChange = () => {
      calcularNoches();
    };

    const recalcularMoneda = () => {
      const tc = form.stay.moneda_pago === 'PEN' ? 1 : tcs.value[form.stay.moneda_pago];
      form.stay.tc_aplicado = tc;
      form.stay.total_pago = (form.stay.monto_original * tc).toFixed(2);
      onAdelantoChange();
    };

    const onAdelantoChange = () => {
      form.stay.total_cobrado = (form.adelanto * form.stay.tc_aplicado).toFixed(2);
      form.stay.estado_pago = form.stay.total_cobrado >= form.stay.total_pago ? 'pagado' : (form.stay.total_cobrado > 0 ? 'parcial' : 'pendiente');
    };

    const agregarPax = () => {
      form.pax.push({ nombre_completo: '', documento_tipo: 'DNI', documento_num: '', nacionalidad: 'Peruana', ciudad: '', es_titular: false });
    };

    const setTitular = (idx) => {
      form.pax.forEach((p, i) => p.es_titular = (i === idx));
    };

    // ─── AUTOCOMPLETE DOCUMENTO ──────────────────────────────
    const sugerencias = ref({});   // { [idx]: [] }
    let acTimer = null;

    const buscarPax = (pax, idx) => {
      const q = pax.documento_num.trim();
      sugerencias.value[idx] = [];
      if (q.length < 3) return;
      clearTimeout(acTimer);
      acTimer = setTimeout(async () => {
        try {
          const res = await axios.get(`../../../api/clientes.php?action=buscar_pax&q=${encodeURIComponent(q)}`);
          sugerencias.value[idx] = res.data.data || [];
        } catch (e) { /* silencio */ }
      }, 280);
    };

    const aplicarSugerencia = (pax, idx, s) => {
      pax.documento_num   = s.documento_num;
      pax.documento_tipo  = s.documento_tipo;
      pax.nombre_completo = s.nombre_completo;
      pax.nacionalidad    = s.nacionalidad || pax.nacionalidad;
      pax.ciudad          = s.ciudad       || pax.ciudad;
      sugerencias.value[idx] = [];
    };

    const ocultarSugerencias = (idx) => {
      setTimeout(() => { sugerencias.value[idx] = []; }, 200);
    };
    // ────────────────────────────────────────────────────────

    const guardarCheckin = async () => {
      loading.value = true;
      try {
        const res = await axios.post('../../../api/rooming.php?action=checkin', form);
        if (res.data.ok) {
          showToast(res.data.msg, 'success');
          bootstrap.Modal.getInstance('#modalCheckin').hide();
          cargarDatos();
        } else {
          showToast(res.data.msg || 'Error al procesar check-in', 'error');
        }
      } catch (err) {
        const errorMsg = err.response && err.response.data && err.response.data.msg 
                       ? err.response.data.msg 
                       : 'Error al procesar check-in';
        showToast(errorMsg, 'error');
      } finally {
        loading.value = false;
      }
    };

    const verDetalle = async (s) => {
      loading.value = true;
      try {
        const [resDet, resCons] = await Promise.all([
          axios.get(`../../../api/rooming.php?action=detalle&id=${s.id}`),
          axios.get(`../../../api/consumos.php?action=listar&stay_id=${s.id}`)
        ]);
        selectedStay.value = resDet.data.data;
        consumosStay.value = resCons.data.data || [];
        new bootstrap.Modal('#modalDetalle').show();
      } catch (err) {
        showToast('Error al cargar detalle', 'error');
      } finally {
        loading.value = false;
      }
    };

    const abrirConsumo = async (s) => {
      stayParaConsumo.value = s;
      Object.assign(consumoForm, {
        stay_id: s.id,
        producto_id: '',
        cantidad: 1,
        total: 0,
        pago_inmediato: false,
        metodo_pago: null
      });
      // Recargar inventario para tener stock fresco
      const resInv = await axios.get('../../../api/inventario.php?action=listar');
      inventario.value = resInv.data.data || [];
      new bootstrap.Modal('#modalConsumo').show();
    };

    const onProductoChange = () => {
      const p = inventario.value.find(x => x.id == consumoForm.producto_id);
      if (p) {
        calcularTotalConsumo();
      }
    };

    const calcularTotalConsumo = () => {
      const p = inventario.value.find(x => x.id == consumoForm.producto_id);
      if (p) {
        consumoForm.total = (p.precio_venta * consumoForm.cantidad).toFixed(2);
      }
    };

    const guardarConsumo = async () => {
      if (!consumoForm.producto_id || consumoForm.cantidad <= 0) return;
      loading.value = true;
      try {
        const res = await axios.post('../../../api/consumos.php?action=registrar', consumoForm);
        if (res.data.ok) {
          showToast(res.data.msg, 'success');
          bootstrap.Modal.getInstance('#modalConsumo').hide();
          cargarDatos();
        } else {
          showToast(res.data.msg, 'error');
        }
      } catch (err) {
        showToast('Error al registrar consumo', 'error');
      } finally {
        loading.value = false;
      }
    };

    const abrirPago = (s) => {
      stayParaPago.value = s;
      const saldo = s.total_pago - s.total_cobrado;
      Object.assign(pagoForm, {
        stay_id: s.id,
        monto: saldo > 0 ? saldo.toFixed(2) : 0,
        moneda: 'PEN',
        monto_pen: saldo > 0 ? saldo.toFixed(2) : 0,
        tc: 1,
        tipo: 'EFECTIVO',
        recibo: '',
        fecha: new Date().toISOString().split('T')[0]
      });
      new bootstrap.Modal('#modalPago').show();
    };

    const recalcularPago = () => {
      const tc = pagoForm.moneda === 'PEN' ? 1 : tcs.value[pagoForm.moneda];
      pagoForm.tc = tc;
      pagoForm.monto_pen = (pagoForm.monto * tc).toFixed(2);
    };

    const guardarPago = async () => {
      if (pagoForm.monto <= 0) return showToast('Monto inválido', 'warning');
      loading.value = true;
      try {
        const res = await axios.post('../../../api/rooming.php?action=pago', pagoForm);
        if (res.data.ok) {
          showToast(res.data.msg, 'success');
          bootstrap.Modal.getInstance('#modalPago').hide();
          cargarDatos();
        } else {
          showToast(res.data.msg, 'error');
        }
      } catch (err) {
        showToast('Error al procesar pago', 'error');
      } finally {
        loading.value = false;
      }
    };

    const procederCheckout = async (s) => {
      const res = await Swal.fire({
        title: '¿Confirmar Checkout?',
        text: `Habitación #${s.hab_numero} pasará a limpieza.`,
        icon: 'warning',
        showCancelButton: true
      });
      if (res.isConfirmed) {
        try {
          await axios.post('../../../api/rooming.php?action=checkout', { id: s.id });
          showToast('Checkout realizado', 'success');
          cargarDatos();
        } catch (err) {
          showToast('Error en el proceso', 'error');
        }
      }
    };

    // HELPERS
    const fmtFecha = (f) => f;
    const getPagoClass = (p) => {
       if (p === 'pagado') return 'bg-success';
       if (p === 'parcial') return 'bg-warning text-dark';
       return 'bg-danger';
    };
    const getEstadBadge = (e) => {
       if (e === 'reservado') return 'bg-info text-dark fw-bold';
       if (e === 'late_checkout') return 'bg-dark text-white';
       return 'bg-success text-white';
    };
    const showToast = (msg, icon) => {
      Swal.fire({ toast: true, position: 'top-end', icon, title: msg, showConfirmButton: false, timer: 3000 });
    };

    onMounted(cargarDatos);

    return {
      stays, habitacionesLibres, loading, busqueda, filtroPiso, filtroPago, form, 
      staysFiltrados, selectedStay, stayParaPago, mediosPago, pagoForm,
      abrirCheckin, onHabChange, calcularNoches, onNochesChange, recalcularMoneda, 
      onAdelantoChange, agregarPax, setTitular, guardarCheckin, verDetalle, cargarDatos,
      fmtFecha, getPagoClass, getEstadBadge, procederCheckout, abrirPago, recalcularPago, guardarPago,
      activarReserva,
      // CONSUMOS
      inventario, inventarioAgrupado, stayParaConsumo, consumosStay, consumoForm,
      abrirConsumo, onProductoChange, calcularTotalConsumo, guardarConsumo,
      // AUTOCOMPLETE PAX
      sugerencias, buscarPax, aplicarSugerencia, ocultarSugerencias
    };
  }
}).mount('#app-rooming');
