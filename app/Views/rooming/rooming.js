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

    // MÉTODOS
    const cargarDatos = async () => {
      loading.value = true;
      try {
        const [resStays, resHabs, resTC] = await Promise.all([
          axios.get('../../../api/rooming.php?action=listar'),
          axios.get('../../../api/habitaciones.php?action=libres'),
          axios.get('../../../api/tipos_cambio.php')
        ]);
        stays.value = resStays.data.data || [];
        habitacionesLibres.value = resHabs.data.data || [];
        tcs.value = resTC.data.data;
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
        total_cobrado: 0,
        estado_pago: 'pendiente'
      });
      form.pax = [{ nombre_completo: '', documento_tipo: 'DNI', documento_num: '', nacionalidad: 'Peruana', ciudad: '', es_titular: true }];
      form.adelanto = 0;
    };

    const onHabChange = () => {
      const h = habitacionesLibres.value.find(x => x.id == form.stay.habitacion_id);
      if (h) {
        form.stay.monto_original = h.precio_base * form.stay.noches;
        form.stay.tipo_hab_declarado = h.tipo;
        recalcularMoneda();
      }
    };

    const calcularNoches = () => {
      const d = new Date(form.stay.fecha_registro);
      d.setDate(d.getDate() + parseInt(form.stay.noches));
      form.stay.fecha_checkout = d.toISOString().split('T')[0];
      onHabChange();
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
        showToast('Error al procesar check-in', 'error');
      } finally {
        loading.value = false;
      }
    };

    const verDetalle = async (s) => {
      loading.value = true;
      try {
        const res = await axios.get(`../../../api/rooming.php?action=detalle&id=${s.id}`);
        selectedStay.value = res.data.data;
        new bootstrap.Modal('#modalDetalle').show();
      } catch (err) {
        showToast('Error al cargar detalle', 'error');
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
    const showToast = (msg, icon) => {
      Swal.fire({ toast: true, position: 'top-end', icon, title: msg, showConfirmButton: false, timer: 3000 });
    };

    onMounted(cargarDatos);

    return {
      stays, habitacionesLibres, loading, busqueda, filtroPiso, filtroPago, form, 
      staysFiltrados, selectedStay,
      abrirCheckin, onHabChange, calcularNoches, onNochesChange, recalcularMoneda, 
      onAdelantoChange, agregarPax, setTitular, guardarCheckin, verDetalle, cargarDatos,
      fmtFecha, getPagoClass, procederCheckout
    };
  }
}).mount('#app-rooming');
