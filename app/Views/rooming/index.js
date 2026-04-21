/**
 * Módulo Frontend: Gestión de Rooming (FrontDesk).
 * 
 * Componente interactivo (Vue 3) que maneja el rack de habitaciones,
 * formularios de check-in dinámicos, gestión de pasajeros y estados de pago.
 * 
 * @module Rooming/FrontDeskJS
 */
const { createApp, ref, reactive, computed, watch, onMounted, onUnmounted } = Vue;

createApp({
  setup() {
    const stays = ref([]);
    const habitacionesLibres = ref([]);
    const tcs = ref({ USD: 3.75, CLP: 0.0038 });
    const loading = ref(false);
    const loadingConsumo = ref(false);
    const busqueda = ref('');
    const isEditingAdelanto = ref(false);
    const adelantoExcede = ref(false);
    const filtroPiso = ref('');
    const filtroPago = ref('');
    const selectedStay = ref(null);
    const stayParaPago = ref(null);
    const mediosPago = ref([]);
    let pollingTimer = null;

    // ── REPORTE PAX ────────────────────────────────────────────
    const hoy = new Date();
    const reportePax = reactive({
      cargando: false,
      mes: hoy.getMonth() + 1,
      anio: hoy.getFullYear(),
      anios: Array.from({ length: 5 }, (_, i) => hoy.getFullYear() - i),
      filas: []
    });
    
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
        recargo_pos: false
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
      tipoPago: 'completo'
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

    const habitacionSeleccionada = computed(() =>
      habitacionesLibres.value.find(x => String(x.id) === String(form.stay.habitacion_id)) || null
    );

    const adelantoInvalido = computed(() => {
      if (form.tipoPago !== 'adelanto') return false;
      const adelanto = parseFloat(form.adelanto) || 0;
      return adelanto <= 0 || adelantoExcede.value;
    });

    const totalOriginal = computed(() => {
      const totalPen = parseFloat(form.stay.total_pago) || 0;
      const tc = parseFloat(form.stay.tc_aplicado) || 1;
      if (form.stay.moneda_pago === 'USD') return tc > 0 ? totalPen / tc : 0;
      if (form.stay.moneda_pago === 'CLP') return totalPen * tc;
      return totalPen;
    });

    const saldoPendienteOriginal = computed(() => {
      const total = totalOriginal.value;
      const adelanto = parseFloat(form.adelanto) || 0;
      return Math.max(0, total - adelanto);
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
        
        // BUGFIX: Preservar la habitación seleccionada si el usuario está en medio de un check-in
        const currentId = form.stay.habitacion_id;
        let newHabs = resHabs.data.data || [];
        if (currentId && !newHabs.some(h => h.id == currentId)) {
          const currentObj = habitacionesLibres.value.find(h => h.id == currentId);
          if (currentObj) {
            newHabs.push(currentObj);
          }
        }
        habitacionesLibres.value = newHabs;

        tcs.value = resTC.data.data;
        mediosPago.value = resMedios.data.data || [];
      } catch (err) {
        showToast('Error al cargar datos', 'error');
      } finally {
        if (!silent) loading.value = false;
      }
    };

    const validarCajaAbierta = async () => {
      try {
        const res = await axios.get('../../../api/flujo.php?action=verificar_apertura');
        return res.data.ok;
      } catch (e) {
        return false;
      }
    };

    const mostrarModalCajaCerrada = () => {
      Swal.fire({
        title: '¡Caja Cerrada!',
        text: 'No puedes realizar check-ins ni registrar pagos sin un turno de caja abierto para hoy.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: '<i class="bi bi-cash-stack"></i> IR A ABRIR CAJA',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = '../flujo/index.php';
        }
      });
    };

    const abrirCheckin = async () => {
      loading.value = true;
      if (!(await validarCajaAbierta())) {
        loading.value = false;
        return mostrarModalCajaCerrada();
      }
      loading.value = false;
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
        total_cobrado_orig: 0,
        estado_pago: 'pendiente',
        recargo_pos: false
      });
      form.pax = [{ nombre_completo: '', documento_tipo: 'DNI', documento_num: '', nacionalidad: 'Peruana', ciudad: '', es_titular: true }];
      form.adelanto = 0;
      form.tipoPago = 'completo';
      adelantoExcede.value = false;
    };

    const onHabChange = () => {
      const h = habitacionSeleccionada.value;
      if (h) {
        // En rooming el precio base siempre es PEN
        const precioBase = parseFloat(h.precio_base) || 0;
        const noches = Math.max(1, parseInt(form.stay.noches) || 1);
        let base = precioBase * noches;
        
        // Si el POS está activo, aplicamos el recargo al nuevo precio base
        if (form.stay.recargo_pos) {
          base *= 1.05;
        }
        
        form.stay.total_pago = base.toFixed(2);
        form.stay.tipo_hab_declarado = h.tipo;
        recalcularMoneda(); 
      }
    };

    const activarReserva = (s) => {
        abrirEdicion(s, true);
    };

    const abrirEdicion = async (s, isActivating = false) => {
      loading.value = true;




      try {
        const res = await axios.get(`../../../api/rooming.php?action=detalle&id=${s.id}`);
        const data = res.data.data;
        if (!data) return showToast('Registro no encontrado', 'warning');
        
        resetForm();
        
        // Mapeo exhaustivo de campos
        Object.assign(form.stay, {
            id: data.id,
            habitacion_id: data.habitacion_id,
            fecha_registro: data.fecha_registro,
            hora_checkin: data.hora_checkin || '12:00',
            fecha_checkout: data.fecha_checkout,
            noches: data.noches || 1,
            medio_reserva: data.medio_reserva || 'DIRECTO',
            total_pago: data.total_pago,
            moneda_pago: data.moneda_pago || 'PEN',
            monto_original: data.monto_original,
            tc_aplicado: data.tc_aplicado || 1,
            metodo_pago: data.metodo_pago || '',
            tipo_comprobante: data.tipo_comprobante || 'RECIBO',
            num_comprobante: data.num_comprobante || '',
            carro: data.carro || 'NO',
            total_cobrado: data.total_cobrado || 0,
            total_cobrado_orig: data.total_cobrado_orig || data.total_cobrado || 0,
            estado_pago: data.estado_pago || 'pendiente',
            observaciones: data.observaciones || '',
            procedencia: data.procedencia || '',
            recargo_pos: parseFloat(data.recargo_tarjeta || 0) > 0,
            estado: isActivating ? 'activo' : data.estado // Si es activar, pasamos a activo, sino conservamos
        });

        // Asegurar que la habitación actual aparezca en el selector (aunque sea ocupada)
        if (!habitacionesLibres.value.some(h => h.id == data.habitacion_id)) {
            habitacionesLibres.value.push({
                id: data.habitacion_id,
                numero: data.hab_numero,
                tipo: data.hab_tipo || 'ESTANDAR',
                precio_base: data.hab_precio || 0
            });
        }

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

        // Configurar UI de cobro según el estado actual
        const totalOriginalStay = parseFloat(data.monto_original) || 0;
        const cobradoOriginalStay = parseFloat(data.total_cobrado_orig) || 0;
        form.tipoPago = (cobradoOriginalStay > 0 && cobradoOriginalStay < totalOriginalStay) ? 'adelanto' : 'completo';
        form.adelanto = form.tipoPago === 'adelanto' ? cobradoOriginalStay : totalOriginalStay;
        onAdelantoChange();
        
        new bootstrap.Modal('#modalCheckin').show();
      } catch (e) {
        showToast('Error al cargar datos de edición', 'error');
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
      const tc = form.stay.moneda_pago === 'PEN' ? 1 : parseFloat(tcs.value[form.stay.moneda_pago]) || 1;
      form.stay.tc_aplicado = tc;

      const totalPen = parseFloat(form.stay.total_pago) || 0;

      // Calcular equivalente en moneda extranjera
      let foreign = totalPen;
      if (form.stay.moneda_pago === 'USD') {
        foreign = totalPen / tc;
      } else if (form.stay.moneda_pago === 'CLP') {
        foreign = totalPen * tc;
      }
      form.stay.monto_original = foreign.toFixed(2);
      if (form.tipoPago === 'completo') {
        form.adelanto = foreign.toFixed(2);
      }
      onAdelantoChange();
    };

    const onAdelantoChange = () => {
      const totalPen = parseFloat(form.stay.total_pago) || 0;
      const totalOrig = totalOriginal.value;
      let adelantoOrig = parseFloat(form.adelanto) || 0;

      if (form.tipoPago === 'completo') {
        adelantoOrig = totalOrig;
      }

      adelantoExcede.value = adelantoOrig > totalOrig + 0.0001;

      const tc = parseFloat(form.stay.tc_aplicado) || 1;
      let cobradoPen = adelantoOrig;
      if (form.stay.moneda_pago === 'USD') cobradoPen = adelantoOrig * tc;
      else if (form.stay.moneda_pago === 'CLP') cobradoPen = tc > 0 ? adelantoOrig / tc : 0;

      if (form.tipoPago === 'adelanto' && adelantoOrig <= 0) {
        cobradoPen = 0;
      }

      cobradoPen = Math.min(cobradoPen, totalPen);
      form.stay.total_cobrado = cobradoPen.toFixed(2);
      form.stay.total_cobrado_orig = adelantoOrig.toFixed(2);

      if (cobradoPen <= 0) form.stay.estado_pago = 'pendiente';
      else if (cobradoPen >= totalPen - 0.01) form.stay.estado_pago = 'pagado';
      else form.stay.estado_pago = 'adelanto';
    };

    const cambiarTipoPago = (tipo) => {
      form.tipoPago = tipo;
      if (tipo === 'completo') {
        form.adelanto = totalOriginal.value.toFixed(2);
      } else if ((parseFloat(form.adelanto) || 0) <= 0) {
        form.adelanto = '0.00';
      }
      onAdelantoChange();
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
      if (form.tipoPago === 'adelanto') {
        const adelanto = parseFloat(form.adelanto) || 0;
        if (adelanto <= 0) {
          showToast('El adelanto debe ser mayor a 0.', 'warning');
          return;
        }
        if (adelantoExcede.value) {
          showToast('El adelanto no puede superar el monto total.', 'warning');
          return;
        }
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
        console.error('=== ERROR CHECKIN ===');
        console.error('Status:', err.response?.status);
        console.error('Data:', err.response?.data);
        console.error('Full error:', err);
        const errorMsg = err.response?.data?.msg || 'Error al procesar check-in';
        showToast(errorMsg, 'error');
      } finally {
        loading.value = false;
      }
    };

    const verDetalle = async (sOrId) => {
      selectedStay.value = null;
      loading.value = true;
      try {
        const id = typeof sOrId === 'object' ? sOrId.id : sOrId;
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
      loadingConsumo.value = true;
      if (!(await validarCajaAbierta())) {
        loadingConsumo.value = false;
        return mostrarModalCajaCerrada();
      }
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
      loadingConsumo.value = false;
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
      // Validaciones preventivas con avisos al usuario
      if (!consumoForm.producto_id) {
        return showToast('Por favor, seleccione un producto de la lista.', 'warning');
      }
      if (consumoForm.cantidad <= 0) {
        return showToast('La cantidad debe ser al menos 1.', 'warning');
      }
      
      // Validación previa para pagos al contado
      if (consumoForm.pago_inmediato && !consumoForm.metodo_pago) {
        return showToast('Debe seleccionar un medio de pago para el cobro al contado.', 'warning');
      }

      loadingConsumo.value = true;
      try {
        const res = await axios.post('../../../api/consumos.php?action=registrar', consumoForm);
        if (res.data.ok) {
          showToast(res.data.msg, 'success');
          const modalElem = document.getElementById('modalConsumo');
          const modal = bootstrap.Modal.getInstance(modalElem);
          if (modal) modal.hide();
          cargarDatos(true);
        } else {
          showToast(res.data.msg, 'error');
        }
      } catch (err) {
        const msg = err.response?.data?.msg || 'Error al registrar consumo';
        showToast(msg, 'error');
      } finally {
        loadingConsumo.value = false;
      }
    };

    const abrirPago = async (stay) => {
      loading.value = true;
      if (!(await validarCajaAbierta())) {
        loading.value = false;
        return mostrarModalCajaCerrada();
      }
      loading.value = false;
      stayParaPago.value = stay; // IMPORTANTE: Para validaciones posteriores
      pagoForm.stay_id = stay.id;
      pagoForm.monto = (parseFloat(stay.monto_original || stay.total_pago) - parseFloat(stay.total_cobrado_orig || stay.total_cobrado)).toFixed(2);
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
        
        // Validar si el monto excede el saldo (con margen de error de 0.05 por redondeos)
        if (montoPen > (saldo + 0.05)) {
          return Swal.fire({
            title: 'Monto Excedido',
            text: `El monto a pagar (S/ ${montoPen.toFixed(2)}) supera el saldo pendiente de la habitación (S/ ${saldo.toFixed(2)}). Por favor, ajuste el monto.`,
            icon: 'warning',
            confirmButtonColor: '#3085d6'
          });
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
      // VALIDACIÓN: No permitir checkout si el pago no está completo
      if (s.estado_pago !== 'pagado') {
        return Swal.fire({
          title: 'Pago Pendiente',
          text: `La habitación #${s.hab_numero} tiene un saldo pendiente. Debe completar el pago antes de realizar el checkout.`,
          icon: 'error',
          confirmButtonColor: '#3085d6',
          confirmButtonText: 'Entendido'
        });
      }

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

    // ── REPORTE PAX ────────────────────────────────────────────
    const abrirReportePax = () => {
      new bootstrap.Modal('#modalReportePax').show();
      cargarReportePax();
    };

    const cargarReportePax = async () => {
      reportePax.cargando = true;
      reportePax.filas = [];
      try {
        const res = await axios.get(
          `../../../api/rooming.php?action=reporte_pax&mes=${reportePax.mes}&anio=${reportePax.anio}`
        );
        reportePax.filas = res.data.data || [];
      } catch (e) {
        showToast('Error al cargar reporte PAX', 'error');
      } finally {
        reportePax.cargando = false;
      }
    };
    const exportarReportePax = async () => {
      if (!reportePax.filas || reportePax.filas.length === 0) return;
      
      const meses = ["", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
      const nombreMes = meses[reportePax.mes] || "Reporte";
      const tituloTexto = `REPORTE ROOMING ${nombreMes.toUpperCase()} ${reportePax.anio}`;
      
      const workbook = new ExcelJS.Workbook();
      const worksheet = workbook.addWorksheet("Registro PAX");

      const colsHeaders = ["OPERADOR", "FECHA REGISTRO", "HAB", "TIPO DE HAB", "PAX", "MEDIO DE RESERVA", "HORA DE CHECK IN", "NOMBRE Y APELLIDO", "TIPO DOC", "NÚMERO", "NACIONALIDAD", "CIUDAD", "ENTRADA", "SALIDA", "PAGO TOTAL", "LATE", "METODO", "COMPROBANTE", "Nº COMPROBANTE", "QUIEN COBRO", "CARRO", "OBS"];

      // 1. TÍTULO PRINCIPAL
      const titleRow = worksheet.addRow([tituloTexto]);
      worksheet.mergeCells(1, 1, 1, colsHeaders.length);
      const titleCell = worksheet.getCell(1, 1);
      titleCell.font = { name: "Arial", size: 16, bold: true };
      titleCell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFADD8E6" } }; // Celeste
      titleCell.alignment = { horizontal: "center", vertical: "middle" };
      titleRow.height = 40;

      worksheet.addRow([]); // Fila vacía de separación

      // 2. ENCABEZADOS
      const headerRow = worksheet.addRow(colsHeaders);
      headerRow.eachCell((cell) => {
        cell.font = { bold: true, color: { argb: "FF000000" } };
        cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFFFFF00" } }; // Amarillo
        cell.border = {
          top: { style: "thin" }, left: { style: "thin" }, bottom: { style: "thin" }, right: { style: "thin" }
        };
        // APLICAR AJUSTAR TEXTO A TODOS LOS ENCABEZADOS
        cell.alignment = { 
          horizontal: "center", 
          vertical: "middle",
          wrapText: true 
        };
      });
      headerRow.height = 30; // Altura un poco mayor para permitir el ajuste

      // 3. DATOS
      const simbolo = (m) => m === "USD" ? "$" : (m === "CLP" ? "P$" : "S/");

      reportePax.filas.forEach(f => {
        const rowData = [
          f.es_titular ? (f.operador || "") : "", f.es_titular ? (f.fecha_registro || "") : "", f.es_titular ? ("#" + (f.hab_numero || "")) : "",
          f.es_titular ? (f.tipo_hab_declarado || "") : "", f.es_titular ? (f.pax_total || "") : "", f.es_titular ? (f.medio_reserva || "") : "",
          f.es_titular ? (f.hora_checkin || "") : "", f.nombre_completo || "", f.documento_tipo || "", f.documento_num || "",
          f.nacionalidad || "", f.ciudad || "", f.es_titular ? (f.fecha_registro || "") : "", f.es_titular ? (f.fecha_checkout || "") : "",
          f.es_titular ? `${simbolo(f.moneda_pago)} ${parseFloat(f.total_pago || 0).toFixed(2)}` : "", f.es_titular ? (f.estado === "late_checkout" ? "SI" : "NO") : "",
          f.es_titular ? (f.metodo_pago || "") : "", f.es_titular ? (f.tipo_comprobante || "") : "", f.es_titular ? (f.num_comprobante || "") : "",
          f.es_titular ? (f.cobrador || "") : "", f.es_titular ? (f.carro || "") : "", f.es_titular ? (f.observaciones || "") : ""
        ];
        const dataRow = worksheet.addRow(rowData);
        dataRow.eachCell((cell, colNumber) => {
          cell.border = {
             top: { style: "thin" }, left: { style: "thin" }, bottom: { style: "thin" }, right: { style: "thin" }
          };
          cell.alignment = { 
            vertical: "middle",
            wrapText: colNumber === 8 // MANTENER AJUSTE EN DATOS SOLO PARA NOMBRES
          };
        });
      });

      // Ajustar anchos
      worksheet.columns.forEach((column, i) => {
        const colNum = i + 1;
        if (colNum === 8) {
          column.width = 30; // Nombre
        } else if ([1,6,9,17,18,19].includes(colNum)) {
          column.width = 20; // Columnas con nombres de encabezado largos
        } else {
          column.width = 12;
        }
      });

      // Exportar
      const buffer = await workbook.xlsx.writeBuffer();
      const blob = new Blob([buffer], { type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `Registro_PAX_${nombreMes}_${reportePax.anio}.xlsx`;
      a.click();
      URL.revokeObjectURL(url);
    };
    // ────────────────────────────────────────────────────────────

    // HELPERS
    const fmtCur = (val) => {
      const n = parseFloat(val);
      if (isNaN(n)) return '0.00';
      return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    const fmtFecha = (f) => f;
    const getPagoClass = (p) => {
       if (p === 'pagado') return 'bg-success';
       if (p === 'adelanto') return 'bg-info text-dark';
       if (p === 'parcial') return 'bg-warning text-dark';
       return 'bg-danger';
    };
    const getEstadBadge = (e) => {
       if (e === 'reservado') return 'bg-info text-dark fw-bold';
       if (e === 'cancelado') return 'bg-danger text-white fw-bold';
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

    watch(() => form.stay.habitacion_id, () => {
      if (form.stay.habitacion_id) onHabChange();
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
      abrirEdicion, activarReserva, cambiarTipoPago, fmtCur, isEditingAdelanto, adelantoExcede, getMetodoPagoIcon,
      saldoPendienteOriginal, adelantoInvalido,
      // CONSUMOS
      inventario, inventarioAgrupado, stayParaConsumo, consumosStay, consumoForm,
      abrirConsumo, onProductoChange, calcularTotalConsumo, guardarConsumo,
      // AUTOCOMPLETE PAX
      sugerencias, buscarPax, aplicarSugerencia, ocultarSugerencias,
      // REPORTE PAX
      reportePax, abrirReportePax, cargarReportePax, exportarReportePax
    };
  }
}).mount('#app-rooming');
