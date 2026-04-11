/**
 * Módulo Frontend: Gestión de Rooming (FrontDesk).
 * 
 * Componente interactivo (Vue 3) que maneja el rack de habitaciones,
 * formularios de check-in dinámicos, gestión de pasajeros y estados de pago.
 * 
 * @module Rooming/FrontDeskJS
 */
const { createApp, ref, reactive, computed, onMounted, onUnmounted } = Vue;

createApp({
  setup() {
    const stays = ref([]);
    const habitacionesLibres = ref([]);
    const tcs = ref({ USD: 3.75, CLP: 0.0038 });
    const loading = ref(false);
    const busqueda = ref('');
    const isEditingAdelanto = ref(false);
    const adelantoExcede = ref(false);
    const filtroPiso = ref('');
    const filtroPago = ref('');
    const selectedStay = ref(null);
    const stayParaPago = ref(null);
    const mediosPago = ref([]);
    let pollingTimer = null;
    
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
        metodo_pago: '',
        tipo_comprobante: 'RECIBO',
        num_comprobante: '',
        carro: 'NO',
        total_cobrado: 0,
        estado_pago: 'pendiente',
        procedencia: '',
        observaciones: '',
        recargo_pos: false // Nuevo: +5% sobre moneda extranjera
      },
      pax: [{
        nombre_completo: '',
        documento_tipo: 'DNI',
        documento_num: '',
        nacionalidad: 'Peruana',
        ciudad: '',
        es_titular: true
      }],
      adelanto: 0,
      tipoPago: 'completo' // 'completo' o 'adelanto'
    });

    const pagoForm = reactive({
      stay_id: null,
      monto: 0,
      moneda: 'PEN',
      monto_pen: 0,
      tc: 1,
      tipo: '',
      recibo: '',
      fecha: new Date().toISOString().split('T')[0],
      recargo_pos: false
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
    const cargarDatos = async (silent = false) => {
      if (!silent) loading.value = true;
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
        if (!silent) loading.value = false;
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
        metodo_pago: '',
        tipo_comprobante: 'RECIBO',
        num_comprobante: '',
        carro: 'NO',
        total_cobrado: 0,
        estado_pago: 'pendiente',
        recargo_pos: false
      });
      form.pax = [{ nombre_completo: '', documento_tipo: 'DNI', documento_num: '', nacionalidad: 'Peruana', ciudad: '', es_titular: true }];
      form.adelanto = 0;
      form.tipoPago = 'completo';
    };

    const onHabChange = () => {
      const h = habitacionesLibres.value.find(x => x.id == form.stay.habitacion_id);
      if (h) {
        // En rooming el precio base siempre es PEN
        let base = h.precio_base * (form.stay.noches || 1);
        
        // Si el POS está activo, aplicamos el recargo al nuevo precio base
        if (form.stay.recargo_pos) {
          base *= 1.05;
        }
        
        form.stay.total_pago = base.toFixed(2);
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

    const recalcularMoneda = (fromSurchargeToggle = false) => {
      const tc = form.stay.moneda_pago === 'PEN' ? 1 : parseFloat(tcs.value[form.stay.moneda_pago]) || 1;
      form.stay.tc_aplicado = tc;
      
      let totalPen = parseFloat(form.stay.total_pago) || 0;

      // Solo ajustamos el total_pago si la función fue llamada por el evento del checkbox
      if (fromSurchargeToggle === true) {
        if (form.stay.recargo_pos) {
          totalPen = totalPen * 1.05;
        } else {
          totalPen = totalPen / 1.05;
        }
        form.stay.total_pago = totalPen.toFixed(2);
      }

      let foreign = totalPen;
      if (form.stay.moneda_pago === 'USD') {
        foreign = totalPen / tc;
      } else if (form.stay.moneda_pago === 'CLP') {
        foreign = totalPen * tc;
      }
      
      form.stay.monto_original = foreign.toFixed(2);
      
      // Si el pago es completo, sincronizamos el monto a cobrar con el total calculado
      if (form.tipoPago === 'completo') {
        form.adelanto = foreign.toFixed(2);
        // Forzamos el recalculo del abono en soles y estado de pago
        onAdelantoChange();
      }

      // ── Auto-selección de Método de Pago para POS ──────────────────
      if (form.stay.recargo_pos) {
        // Palabras clave por moneda para buscar el POS correcto
        const posKeywords = {
          'USD': ['dolar', 'usd', 'dollar'],
          'CLP': ['peso', 'clp', 'chile', 'pesos'],
          'PEN': ['sol', 'soles', 'pen']
        };
        const kwList = posKeywords[form.stay.moneda_pago] || [];

        // 1° intento: POS específico para la moneda
        let posMatch = mediosPago.value.find(m =>
          m.activo == 1 &&
          m.nombre.toLowerCase().includes('pos') &&
          kwList.some(kw => m.nombre.toLowerCase().includes(kw))
        );

        // 2° intento: cualquier POS activo
        if (!posMatch) {
          posMatch = mediosPago.value.find(m =>
            m.activo == 1 && m.nombre.toLowerCase().includes('pos')
          );
        }

        if (posMatch) form.stay.metodo_pago = posMatch.nombre;

      } else {
        // Si se desactiva POS y el método actual era un POS, limpiarlo
        if (form.stay.metodo_pago && form.stay.metodo_pago.toLowerCase().includes('pos')) {
          form.stay.metodo_pago = '';
        }
      }
      // ───────────────────────────────────────────────────────────────

      onAdelantoChange();
    };

    const onAdelantoChange = () => {
      const tc = parseFloat(form.stay.tc_aplicado) || 1;
      const amountEntered = parseFloat(form.adelanto) || 0;
      
      let cobradoPen = amountEntered;
      if (form.stay.moneda_pago === 'USD') {
        cobradoPen = amountEntered * tc;
      } else if (form.stay.moneda_pago === 'CLP') {
        cobradoPen = tc > 0 ? (amountEntered / tc) : 0;
      }
      
      if (isNaN(cobradoPen)) cobradoPen = 0;
      form.stay.total_cobrado = cobradoPen.toFixed(2);
      
      const totalPen = parseFloat(form.stay.total_pago) || 0;
      const diffBase = totalPen - cobradoPen;
      form.stay.estado_pago = diffBase <= 0.05 ? 'pagado' : (amountEntered > 0 ? 'parcial' : 'pendiente');

      // ── Validación: adelanto no debe superar el monto base en la divisa ──
      // monto_original ya está en la moneda del pago (USD, CLP o PEN)
      const montoMaxEnMoneda = parseFloat(form.stay.monto_original) || totalPen;
      // Si hay POS, el tope incluye el 5% de recargo
      const tope = form.stay.recargo_pos ? montoMaxEnMoneda * 1.05 : montoMaxEnMoneda;
      adelantoExcede.value = form.tipoPago === 'adelanto' && amountEntered > tope + 0.01;
    };

    const cambiarTipoPago = (tipo) => {
      form.tipoPago = tipo;
      recalcularMoneda();
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
      if (form.tipoPago === 'adelanto' && adelantoExcede.value) {
        return showToast('El adelanto no puede superar el costo total.', 'warning');
      }
      loading.value = true;
      try {
        const res = await axios.post('../../../api/rooming.php?action=checkin', form);
        if (res.data.ok) {
          showToast(res.data.msg, 'success');
          bootstrap.Modal.getInstance('#modalCheckin').hide();
          cargarDatos(true);
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

    const verDetalle = async (sOrId) => {
      loading.value = true;
      const id = typeof sOrId === 'object' ? sOrId.id : sOrId;
      try {
        const [resDet, resCons] = await Promise.all([
          axios.get(`../../../api/rooming.php?action=detalle&id=${id}`),
          axios.get(`../../../api/consumos.php?action=listar&stay_id=${id}`)
        ]);
        selectedStay.value = resDet.data.data;
        consumosStay.value = resCons.data.data || [];
        
        // El modal de detalle tiene id="modalDetalle"
        const modal = new bootstrap.Modal('#modalDetalle');
        modal.show();
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
        metodo_pago: null,
        recargo_pos: false
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
      const p = inventario.value.find(x => x.id === consumoForm.producto_id);
      if (p) {
        let base = p.precio_venta * consumoForm.cantidad;
        if (consumoForm.pago_inmediato && consumoForm.recargo_pos) {
          base *= 1.05;
        }
        consumoForm.total = base.toFixed(2);
      } else {
        consumoForm.total = 0;
      }

      // Auto-selección de método POS si el recargo está activo
      if (consumoForm.pago_inmediato && consumoForm.recargo_pos) {
        if (!consumoForm.metodo_pago || !consumoForm.metodo_pago.toLowerCase().includes('pos')) {
          const mPos = mediosPago.value.find(m => m.nombre.toLowerCase().includes('pos'));
          if (mPos) consumoForm.metodo_pago = mPos.nombre;
        }
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
          cargarDatos(true);
        } else {
          showToast(res.data.msg, 'error');
        }
      } catch (err) {
        showToast('Error al registrar consumo', 'error');
      } finally {
        loading.value = false;
      }
    };

    const abrirPago = (stay) => {
      pagoForm.stay_id = stay.id;
      pagoForm.monto = (parseFloat(stay.total_pago) - parseFloat(stay.total_cobrado)).toFixed(2);
      pagoForm.moneda = stay.moneda_pago;
      pagoForm.recargo_pos = false;
      pagoForm.tipo = '';
      pagoForm.recibo = 'ABONO-' + new Date().getTime().toString().substr(-6);
      recalcularPago();
      new bootstrap.Modal('#modalPago').show();
    };

    const recalcularPago = (fromToggle = false) => {
      const tc = pagoForm.moneda === 'PEN' ? 1 : parseFloat(tcs.value[pagoForm.moneda]) || 1;
      pagoForm.tc = tc;
      
      let amount = parseFloat(pagoForm.monto) || 0;
      
      if (fromToggle === true) {
        if (pagoForm.recargo_pos) {
          amount *= 1.05;
        } else {
          amount /= 1.05;
        }
        pagoForm.monto = amount.toFixed(2);
      }

      let pen = amount;
      if (pagoForm.moneda === 'USD') pen = amount * tc;
      else if (pagoForm.moneda === 'CLP') pen = tc > 0 ? (amount / tc) : 0;
      
      pagoForm.monto_pen = pen.toFixed(2);

      // Auto-selección de método POS
      if (pagoForm.recargo_pos) {
        if (!pagoForm.tipo || !pagoForm.tipo.toLowerCase().includes('pos')) {
          const mPos = mediosPago.value.find(m => m.nombre.toLowerCase().includes('pos'));
          if (mPos) pagoForm.tipo = mPos.nombre;
        }
      }
    };

    const guardarPago = async () => {
      if (pagoForm.monto <= 0) return showToast('Monto inválido', 'warning');
      
      const s = stayParaPago.value;
      if (s) {
        const saldo = parseFloat(s.total_pago) - parseFloat(s.total_cobrado);
        const montoPen = parseFloat(pagoForm.monto_pen) || 0;
        if (montoPen > saldo + 0.10) {
          return showToast('El monto ingresado (' + pagoForm.monto + ') supera el saldo pendiente de la habitación.', 'warning');
        }
      }

      loading.value = true;
      try {
        const res = await axios.post('../../../api/rooming.php?action=pago', pagoForm);
        if (res.data.ok) {
          showToast(res.data.msg, 'success');
          bootstrap.Modal.getInstance('#modalPago').hide();
          cargarDatos(true);
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
          cargarDatos(true);
        } catch (err) {
          showToast('Error en el proceso', 'error');
        }
      }
    };

    // HELPERS
    const fmtCur = (val) => {
      const n = parseFloat(val);
      if (isNaN(n)) return '0.00';
      return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

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
    const getMetodoPagoIcon = (m) => {
      if (!m) return 'bi-question-circle';
      const u = m.toUpperCase();
      if (u.includes('POS') || u.includes('TARJETA') || u.includes('VISA') || u.includes('MASTER')) return 'bi-credit-card me-1';
      if (u.includes('YAPE') || u.includes('PLIN') || u.includes('LUKITA')) return 'bi-phone me-1';
      if (u.includes('TRANSF') || u.includes('DEPOSITO') || u.includes('BANK') || u.includes('BANCO')) return 'bi-bank me-1';
      return 'bi-cash-coin me-1'; // Efectivo por defecto
    };
    const showToast = (msg, icon) => {
      Swal.fire({ toast: true, position: 'top-end', icon, title: msg, showConfirmButton: false, timer: 3000 });
    };

    onMounted(async () => {
      await cargarDatos();
      
      // Iniciar polling silencioso cada 10 segundos
      pollingTimer = setInterval(() => cargarDatos(true), 10000);
      
      const urlParams = new URLSearchParams(window.location.search);
      
      // AUTO-COBRO: Si viene de Reservas con una habitación específica
      const buscarHab = urlParams.get('buscar');
      if (buscarHab) {
        busqueda.value = buscarHab;
        // Esperamos un momento a que Vue procese la lista y buscamos el match
        const match = stays.value.find(s => s.hab_numero == buscarHab);
        if (match) {
          abrirPago(match);
        }
      }

      // DEEP LINKING: Abrir detalle si viene un stay_id por URL (existente)
      const stayId = urlParams.get('stay_id');
      if (stayId) {
        verDetalle(stayId);
      }
    });

    onUnmounted(() => {
      if (pollingTimer) clearInterval(pollingTimer);
    });

    return {
      stays, habitacionesLibres, tcs, loading, busqueda, filtroPiso, filtroPago, form, 
      staysFiltrados, selectedStay, stayParaPago, mediosPago, pagoForm,
      abrirCheckin, onHabChange, calcularNoches, onNochesChange, recalcularMoneda, 
      onAdelantoChange, agregarPax, setTitular, guardarCheckin, verDetalle, cargarDatos,
      fmtFecha, getPagoClass, getEstadBadge, procederCheckout, abrirPago, recalcularPago, guardarPago,
      activarReserva, cambiarTipoPago, fmtCur, isEditingAdelanto, adelantoExcede, getMetodoPagoIcon,
      // CONSUMOS
      inventario, inventarioAgrupado, stayParaConsumo, consumosStay, consumoForm,
      abrirConsumo, onProductoChange, calcularTotalConsumo, guardarConsumo,
      // AUTOCOMPLETE PAX
      sugerencias, buscarPax, aplicarSugerencia, ocultarSugerencias
    };
  }
}).mount('#app-rooming');
