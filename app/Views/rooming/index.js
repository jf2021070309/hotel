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
    const selColumnas = ref([
      { label: "OPERADOR", checked: true },
      { label: "FECHA REGISTRO", checked: true },
      { label: "HAB", checked: true },
      { label: "TIPO DE HAB", checked: true },
      { label: "PAX", checked: true },
      { label: "MEDIO DE RESERVA", checked: true },
      { label: "HORA DE CHECK IN", checked: true },
      { label: "NOMBRE Y APELLIDO", checked: true },
      { label: "TIPO DOC", checked: true },
      { label: "NÚMERO", checked: true },
      { label: "NACIONALIDAD", checked: true },
      { label: "CIUDAD", checked: true },
      { label: "ENTRADA", checked: true },
      { label: "SALIDA", checked: true },
      { label: "PAGO TOTAL", checked: true },
      { label: "LATE", checked: true },
      { label: "METODO", checked: true },
      { label: "COMPROBANTE", checked: true },
      { label: "Nº COMPROBANTE", checked: true },
      { label: "QUIEN COBRO", checked: true },
      { label: "CARRO", checked: true },
      { label: "OBS", checked: true },
    ]);

    const abrirConfigExportar = () => {
      bootstrap.Modal.getOrCreateInstance(document.getElementById('modalExportConfig')).show();
    };

    const confirmarExportacion = () => {
      const modalElem = document.getElementById("modalExportConfig");
      const modal = bootstrap.Modal.getInstance(modalElem);
      if (modal) modal.hide();
      exportarReportePax();
    };

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

    // Total del consumo en la moneda de la estadía (para display en el modal)
    const consumoFormTotalEnMonedaEstadia = computed(() => {
      if (!stayParaConsumo.value || parseFloat(consumoForm.total || 0) === 0) return 0;

      const totalPen = parseFloat(consumoForm.total) || 0;
      const monedaEstadia = stayParaConsumo.value.moneda_pago || 'PEN';
      const tcEstadia = parseFloat(tcs.value[monedaEstadia]) || 1;

      if (monedaEstadia === 'PEN') return totalPen;
      if (monedaEstadia === 'USD') return totalPen / tcEstadia;
      if (monedaEstadia === 'CLP') return totalPen * tcEstadia;
      return totalPen;
    });

    // Símbolo de la moneda de la estadía
    const monedaEstadiaSimbolo = computed(() => {
      if (!stayParaConsumo.value) return 'S/';
      const m = stayParaConsumo.value.moneda_pago;
      if (m === 'USD') return '$';
      if (m === 'CLP') return 'P$';
      return 'S/';
    });

    const form = reactive({
      stay: {
        habitacion_id: '',
        fecha_registro: new Date().toISOString().split('T')[0],
        hora_checkin: new Date().toTimeString().slice(0, 5),
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
        carro: '',
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
        celular: '',
        email: '',
        es_corporativo: false,
        empresa: '',
        ruc_empresa: '',
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

    const totalConsumoStay = computed(() => {
      if (!selectedStay.value || !consumosStay.value) return 0;
      const totalPen = consumosStay.value.reduce((acc, c) => acc + parseFloat(c.total || 0), 0);
      if (selectedStay.value.moneda_pago === 'PEN') return totalPen;
      const tc = parseFloat(selectedStay.value.tc_aplicado) || 1;
      if (selectedStay.value.moneda_pago === 'CLP') return totalPen * tc;
      if (selectedStay.value.moneda_pago === 'USD') return totalPen / tc;
      return totalPen;
    });

    const saldoPendienteStay = computed(() => {
      if (!selectedStay.value) return 0;
      const total = parseFloat(selectedStay.value.total_pago) || 0;
      const abonado = parseFloat(selectedStay.value.total_cobrado_orig || selectedStay.value.total_cobrado) || 0;
      return total - abonado;
    });

    // MÉTODOS
    const cargarHabitacionesDisponibles = async () => {
      const fi = form.stay.fecha_registro;
      const fo = form.stay.fecha_checkout;
      if (!fi || !fo) return;

      const exclude = form.stay.id || '';
      try {
        const res = await axios.get(`../../../api/habitaciones.php?action=disponibles&fecha_in=${fi}&fecha_out=${fo}&exclude=${exclude}`);
        habitacionesLibres.value = res.data.data || [];

        // Si hay una hab seleccionada pero ya no está en la lista (y no es la actual), resetearla
        if (form.stay.habitacion_id && !habitacionesLibres.value.some(h => h.id == form.stay.habitacion_id)) {
          // Solo si no estamos re-cargando la misma
          // form.stay.habitacion_id = ''; 
        }
      } catch (e) {
        console.error("Error cargando habs disponibles", e);
      }
    };

    const cargarDatos = async (silent = false) => {
      if (!silent) loading.value = true;
      try {
        const [resStays, resTC, resMedios] = await Promise.all([
          axios.get('../../../api/rooming.php?action=listar'),
          axios.get('../../../api/tipos_cambio.php'),
          axios.get('../../../api/medios_pago.php?action=listar')
        ]);
        stays.value = resStays.data.data || [];

        tcs.value = resTC.data.data;
        mediosPago.value = resMedios.data.data || [];

        // Inicializamos habitaciones libres para la fecha de hoy por defecto
        if (form.stay.fecha_registro && form.stay.fecha_checkout) {
          await cargarHabitacionesDisponibles();
        }
      } catch (err) {
        showToast('Error al cargar datos', 'error');
      } finally {
        if (!silent) loading.value = false;
      }
    };

    const validarCajaAbierta = async () => {
      try {
        const res = await axios.get('../../../api/flujo.php?action=verificar_apertura');
        // json_response() envuelve en {ok, data, msg} — el payload real está en data
        return res.data.data ?? res.data;
      } catch (e) {
        return { ok: false, msg: 'Error al verificar caja' };
      }
    };

    const mostrarModalCajaCerrada = (estadoCaja) => {
      // Caso A: hay caja del turno incorrecto abierta (ej: MAÑANA cuando ya es TARDE)
      if (estadoCaja?.turno_pendiente) {
        const turnoPendiente = estadoCaja.turno_pendiente;
        const turnoActual    = estadoCaja.turno_actual;
        Swal.fire({
          title: `⚠️ Caja de ${turnoPendiente} sin cerrar`,
          html: `
            <p>Estás en el turno <strong>${turnoActual}</strong>, pero tienes el flujo de caja de <strong>${turnoPendiente}</strong> todavía abierto.</p>
            <p class="mt-2 text-muted small">Cierra ese turno y luego abre el de <strong>${turnoActual}</strong> para registrar operaciones.</p>
          `,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#f59e0b',
          cancelButtonColor: '#6b7280',
          confirmButtonText: '<i class="bi bi-cash-stack"></i> IR A FLUJO DE CAJA',
          cancelButtonText: 'Cancelar'
        }).then((result) => {
          if (result.isConfirmed) window.location.href = '../flujo/index.php';
        });
        return;
      }

      // Caso B: no hay ninguna caja abierta hoy
      Swal.fire({
        title: '¡Sin Caja Abierta!',
        html: `
          <p>No tienes un flujo de caja abierto para el turno <strong>${estadoCaja?.turno_actual ?? ''}</strong>.</p>
          <p class="mt-2 text-muted small">Abre la caja del turno correspondiente antes de registrar check-ins o pagos.</p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: '<i class="bi bi-cash-stack"></i> IR A ABRIR CAJA',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) window.location.href = '../flujo/index.php';
      });
    };

    const abrirCheckin = async () => {
      loading.value = true;
      try {
        const estadoCaja = await validarCajaAbierta();
        if (!estadoCaja.ok) {
          loading.value = false;
          return mostrarModalCajaCerrada(estadoCaja);
        }
        loading.value = false;
        resetForm();
        calcularNoches();
        await cargarHabitacionesDisponibles();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCheckin')).show();
      } catch (err) {
        console.error("Error al abrir Check-in modal:", err);
        loading.value = false;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCheckin')).show();
      }
    };


    const resetForm = () => {
      // Limpiar IDs y campos dinámicos de control para evitar cruces
      delete form.stay.id;
      delete form.stay.estado;
      delete form.stay.tipo_hab_declarado;

      Object.assign(form.stay, {
        habitacion_id: '',
        fecha_registro: new Date().toISOString().split('T')[0],
        hora_checkin: new Date().toTimeString().slice(0, 5),
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
        carro: '',
        total_cobrado: 0,
        total_cobrado_orig: 0,
        estado_pago: 'pendiente',
        procedencia: '',
        observaciones: '',
        ruc_factura: '',
        razon_social: '',
        recargo_pos: false
      });
      form.pax = [{
        nombre_completo: '', documento_tipo: 'DNI', documento_num: '',
        nacionalidad: 'Peruana', ciudad: '',
        celular: '', email: '', es_corporativo: false,
        empresa: '', ruc_empresa: '', es_titular: true
      }];
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
            documento_tipo: p.documento_tipo || 'DNI',
            documento_num: (p.documento_num === '---') ? '' : (p.documento_num || ''),
            nacionalidad: p.nacionalidad || 'Peruana',
            ciudad: p.ciudad || '',
            celular: p.celular || '',
            email: p.email || '',
            es_corporativo: p.es_corporativo == 1,
            empresa: p.empresa || '',
            ruc_empresa: p.es_titular == 1 ? (data.ruc_factura || '') : '',
            es_titular: p.es_titular == 1
          }));
        }

        // Configurar UI de cobro según el estado actual
        const totalOriginalStay = parseFloat(data.monto_original) || 0;
        const cobradoOriginalStay = parseFloat(data.total_cobrado_orig) || 0;
        form.tipoPago = (cobradoOriginalStay > 0 && cobradoOriginalStay < totalOriginalStay) ? 'adelanto' : 'completo';
        form.adelanto = form.tipoPago === 'adelanto' ? cobradoOriginalStay : totalOriginalStay;
        onAdelantoChange();

        // Si el precio cargado es 0, sincronizamos automáticamente con el precio actual de la habitación
        if (parseFloat(data.total_pago) === 0) {
          onHabChange();
        }

        await cargarHabitacionesDisponibles();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCheckin')).show();
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
        cargarHabitacionesDisponibles();
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
          const results = res.data.data || [];
          sugerencias.value[idx] = results;

          // Si hay un match exacto con el DNI, auto-aplicar
          if (results.length === 1 && results[0].documento_num === q) {
            aplicarSugerencia(pax, idx, results[0]);
          }
        } catch (e) { /* silencio */ }
      }, 280);
    };

    const aplicarSugerencia = (pax, idx, s) => {
      pax.documento_num = s.documento_num;
      pax.documento_tipo = s.documento_tipo;
      pax.nombre_completo = s.nombre_completo;
      pax.nacionalidad = s.nacionalidad || pax.nacionalidad;
      pax.ciudad = s.ciudad || pax.ciudad;

      // Nuevos campos auto-completados (Prioridad al RUC del perfil de cliente frecuente)
      pax.celular = s.celular || '';
      pax.email = s.email || '';
      pax.empresa = s.empresa || '';
      // Si tiene RUC o Empresa, activamos el modo corporativo automáticamente
      pax.es_corporativo = !!(s.ruc || s.es_corporativo == 1 || s.ruc_factura);
      pax.ruc_empresa = s.ruc || s.ruc_factura || '';

      // Si es el titular, auto-llenar también la sección de Facturación (Prioridad al RUC del perfil)
      if (pax.es_titular) {
        form.stay.ruc_factura = s.ruc || s.ruc_factura || '';
        form.stay.razon_social = s.empresa || s.razon_social || '';
        if (form.stay.ruc_factura) {
          form.stay.tipo_comprobante = 'FACTURA';
        }
      }

      sugerencias.value[idx] = [];
    };

    const ocultarSugerencias = (idx) => {
      setTimeout(() => { sugerencias.value[idx] = []; }, 200);
    };

    // ─── LOOKUP DNI / RUC VÍA API (DocumentLookupService) ────────
    const lookupLoading = ref({});  // { [idx]: bool }
    const lookupOk      = ref({});
    const rucLoading    = ref({});
    const rucOk         = ref({});
    let dniTimer = null;
    let rucTimer = null;

    /**
     * Dispara el scraping de DNI cuando el número tiene exactamente 8 dígitos.
     * Solo autocompleta el Nombre Completo (sin tocar otros campos).
     */
    const lookupDocumento = (pax, idx) => {
      const num = (pax.documento_num || '').trim();
      lookupOk.value[idx] = false;

      // Solo aplica para DNI (8 dígitos numéricos)
      if (pax.documento_tipo !== 'DNI' || num.length !== 8 || !/^\d{8}$/.test(num)) return;

      // Si ya hay sugerencias de la BD local, no hace falta el API externo
      if (sugerencias.value[idx] && sugerencias.value[idx].length > 0) return;

      clearTimeout(dniTimer);
      lookupLoading.value[idx] = true;

      dniTimer = setTimeout(async () => {
        try {
          const res = await axios.get(`../../../api/usuarios.php?action=consultar_dni&dni=${num}`);
          if (res.data.success && res.data.data) {
            const nombre = res.data.data.nombre_completo || '';
            if (nombre && !pax.nombre_completo) {
              pax.nombre_completo = nombre;
            } else if (nombre && pax.nombre_completo !== nombre) {
              pax.nombre_completo = nombre;
            }
            lookupOk.value[idx] = true;
          }
        } catch (e) { /* silencio – API inactiva */ }
        finally { lookupLoading.value[idx] = false; }
      }, 400);
    };

    /**
     * Dispara el scraping de RUC cuando el número tiene exactamente 11 dígitos.
     * Solo autocompleta Razón Social (sin tocar DNI ni nombre completo del huésped).
     */
    const lookupRuc = (pax, idx) => {
      const ruc = (pax.ruc_empresa || '').trim();
      rucOk.value[idx] = false;

      if (ruc.length !== 11 || !/^\d{11}$/.test(ruc)) return;

      clearTimeout(rucTimer);
      rucLoading.value[idx] = true;

      rucTimer = setTimeout(async () => {
        try {
          const res = await axios.get(`../../../api/usuarios.php?action=consultar_ruc&ruc=${ruc}`);
          if (res.data.success && res.data.data) {
            const razon = res.data.data.razon_social || '';
            if (razon) {
              pax.empresa = razon;
              form.stay.razon_social = razon;
              form.stay.ruc_factura  = ruc;
              form.stay.tipo_comprobante = 'FACTURA';
            }
            rucOk.value[idx] = true;
          }
        } catch (e) { /* silencio */ }
        finally { rucLoading.value[idx] = false; }
      }, 400);
    };

    /**
     * Limpia nombre y estado de lookup cuando se cambia el tipo de documento.
     */
    const onDocTipoChange = (pax, idx) => {
      pax.documento_num   = '';
      pax.nombre_completo = '';
      lookupOk.value[idx]      = false;
      lookupLoading.value[idx] = false;
      sugerencias.value[idx]   = [];
    };
    // ────────────────────────────────────────────────────────────

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
          bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCheckin')).hide();
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
        selectedStay.value = { ...resDet.data.data, pagos: resDet.data.data.pagos || [], pax: resDet.data.data.pax || [] };
        consumosStay.value = resCons.data.data || [];

        // El modal de detalle tiene id="modalDetalle"
        const modalElem = document.getElementById('modalDetalle');
        if (modalElem) {
          const modal = new bootstrap.Modal(modalElem);
          modal.show();
        }
      } catch (err) {
        console.error('ERROR DETALLE:', err);
        showToast('Error al cargar detalle', 'error');
      } finally {
        loading.value = false;
      }
    };

    const abrirConsumo = async (s) => {
      loadingConsumo.value = true;
      const estadoCaja = await validarCajaAbierta();
      if (!estadoCaja.ok) {
        loadingConsumo.value = false;
        return mostrarModalCajaCerrada(estadoCaja);
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
      stayParaPago.value = stay;
      pagoForm.stay_id = stay.id;
      // Saldo pendiente en moneda original
      const saldoOrig = parseFloat(stay.total_pago) - parseFloat(stay.total_cobrado_orig || 0);
      pagoForm.monto = saldoOrig.toFixed(2);
      pagoForm.moneda = stay.moneda_pago;
      pagoForm.recargo_pos = false;
      pagoForm.tipo = '';
      pagoForm.recibo = 'ABONO-' + new Date().getTime().toString().substr(-6);
      pagoForm.fecha = new Date().toISOString().split('T')[0];
      recalcularPago();
      new bootstrap.Modal('#modalPago').show();
    };

    const recalcularPago = (fromToggle = false) => {
      if (!stayParaPago.value) return;

      const tcPago = pagoForm.moneda === 'PEN' ? 1 : parseFloat(tcs.value[pagoForm.moneda]) || 1;
      pagoForm.tc = tcPago;

      let amount = parseFloat(pagoForm.monto) || 0;

      // Manejo de recargo POS del 5%
      if (fromToggle === true) {
        if (pagoForm.recargo_pos) {
          amount *= 1.05;
        } else {
          amount /= 1.05;
        }
        pagoForm.monto = amount.toFixed(2);
      }

      // 1. Convertir pago a PEN (Base de caja)
      let pen = amount;
      if (pagoForm.moneda === 'USD') pen = amount * tcPago;
      else if (pagoForm.moneda === 'CLP') pen = tcPago > 0 ? (amount / tcPago) : 0;
      pagoForm.monto_pen = pen.toFixed(2);

      // 2. Determinar cuánto deduce de la MONEDA ORIGINAL de la estadía
      const monedaStay = stayParaPago.value.moneda_pago;
      const tcStay = monedaStay === 'PEN' ? 1 : parseFloat(tcs.value[monedaStay]) || 1;

      let deduction = pen;
      if (monedaStay === 'USD') deduction = pen / tcStay;
      else if (monedaStay === 'CLP') deduction = pen * tcStay;

      pagoForm.monto_orig_deducir = deduction.toFixed(2);

      if (pagoForm.recargo_pos) {
        if (!pagoForm.tipo || !pagoForm.tipo.toLowerCase().includes('pos')) {
          const mPos = mediosPago.value.find(m => m.nombre.toLowerCase().includes('pos'));
          if (mPos) pagoForm.tipo = mPos.nombre;
        }
      }
    };

    const cambiarMonedaPago = (nuevaMoneda) => {
      if (!stayParaPago.value) return;

      pagoForm.moneda = nuevaMoneda;
      const stay = stayParaPago.value;

      // Saldo pendiente en SU MONEDA ORIGINAL
      const saldoPendOrig = parseFloat(stay.total_pago) - parseFloat(stay.total_cobrado_orig || 0);

      // Convertir ese saldo pendiente original a PEN primeramente
      const tcStay = stay.moneda_pago === 'PEN' ? 1 : parseFloat(tcs.value[stay.moneda_pago]) || 1;
      let saldoEnPen = saldoPendOrig;
      if (stay.moneda_pago === 'USD') saldoEnPen = saldoPendOrig * tcStay;
      else if (stay.moneda_pago === 'CLP') saldoEnPen = tcStay > 0 ? (saldoPendOrig / tcStay) : 0;

      // Ahora convertir esos Soles a la NUEVA MONEDA de pago seleccionada
      const tcNueva = nuevaMoneda === 'PEN' ? 1 : parseFloat(tcs.value[nuevaMoneda]) || 1;
      let nuevoMonto = saldoEnPen;
      if (nuevaMoneda === 'USD') nuevoMonto = tcNueva > 0 ? (saldoEnPen / tcNueva) : 0;
      else if (nuevaMoneda === 'CLP') nuevoMonto = saldoEnPen * tcNueva;

      pagoForm.monto = nuevoMonto.toFixed(2);
      recalcularPago();
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
        ); reportePax.filas = (res.data.data || []).map(f => ({ ...f, excluir: false }));

        // Inicializar arrastre (Drag to Scroll)
        setTimeout(() => {
          const container = document.getElementById("containerReportePax");
          if (!container) return;

          let isDown = false;
          let startX;
          let scrollLeft;

          container.addEventListener("mousedown", (e) => {
            isDown = true;
            container.style.cursor = "grabbing";
            startX = e.pageX - container.offsetLeft;
            scrollLeft = container.scrollLeft;
          });

          container.addEventListener("mouseleave", () => {
            isDown = false;
            container.style.cursor = "grab";
          });

          container.addEventListener("mouseup", () => {
            isDown = false;
            container.style.cursor = "grab";
          });

          container.addEventListener("mousemove", (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - container.offsetLeft;
            const walk = (x - startX) * 2; // velocidad de scroll
            container.scrollLeft = scrollLeft - walk;
          });
        }, 300);
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

      // Columnas seleccionadas
      const colSpecs = selColumnas.value.filter(c => c.checked);
      if (colSpecs.length === 0) {
        return showToast("Debe seleccionar al menos una columna", "warning");
      }
      const labels = colSpecs.map(c => c.label);

      // 1. TÍTULO PRINCIPAL
      const titleRow = worksheet.addRow([tituloTexto]);
      worksheet.mergeCells(1, 1, 1, labels.length);
      const titleCell = worksheet.getCell(1, 1);
      titleCell.font = { name: "Arial", size: 16, bold: true };
      titleCell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFADD8E6" } };
      titleCell.alignment = { horizontal: "center", vertical: "middle" };
      titleRow.height = 40;

      worksheet.addRow([]);

      // 2. ENCABEZADOS
      const headerRow = worksheet.addRow(labels);
      headerRow.eachCell((cell) => {
        cell.font = { bold: true, color: { argb: "FF000000" } };
        cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFFFFF00" } };
        cell.border = { top: { style: "thin" }, left: { style: "thin" }, bottom: { style: "thin" }, right: { style: "thin" } };
        cell.alignment = { horizontal: "center", vertical: "middle", wrapText: true };
      });
      headerRow.height = 30;

      // 3. DATOS
      const simbolo = (m) => m === "USD" ? "$" : (m === "CLP" ? "P$" : "S/");

      // Identificar los IDs de estadías a excluir (donde el titular marcó el checkbox)
      const staysExcluded = reportePax.filas.filter(f => f.es_titular && f.excluir).map(f => f.stay_id || f.id);

      reportePax.filas.forEach(f => {
        // Si la fila actual pertenece a un stay excluido, no la procesamos
        const currentStayId = f.stay_id || f.id;
        if (staysExcluded.includes(currentStayId)) return;

        const fullData = [
          f.es_titular ? (f.operador || "") : "", f.es_titular ? (f.fecha_registro || "") : "", f.es_titular ? ("#" + (f.hab_numero || "")) : "",
          f.es_titular ? (f.tipo_hab_declarado || "") : "", f.es_titular ? (f.pax_total || "") : "", f.es_titular ? (f.medio_reserva || "") : "",
          f.es_titular ? (f.hora_checkin || "") : "", f.nombre_completo || "", f.documento_tipo || "", f.documento_num || "",
          f.nacionalidad || "", f.ciudad || "", f.es_titular ? (f.fecha_registro || "") : "", f.es_titular ? (f.fecha_checkout || "") : "",
          f.es_titular ? `${simbolo(f.moneda_pago)} ${parseFloat(f.total_pago || 0).toFixed(2)}` : "", f.es_titular ? (f.estado === "late_checkout" ? "SI" : "NO") : "",
          f.es_titular ? (f.metodo_pago || "") : "", f.es_titular ? (f.tipo_comprobante || "") : "", f.es_titular ? (f.num_comprobante || "") : "",
          f.es_titular ? (f.cobrador || "") : "", f.es_titular ? (f.carro || "") : "", f.es_titular ? (f.observaciones || "") : ""
        ];

        // Mapear solo los indices de las columnas seleccionadas
        const filteredData = selColumnas.value.reduce((acc, col, idx) => {
          if (col.checked) acc.push(fullData[idx]);
          return acc;
        }, []);

        const dataRow = worksheet.addRow(filteredData);
        dataRow.eachCell((cell, colIndex) => {
          cell.border = { top: { style: "thin" }, left: { style: "thin" }, bottom: { style: "thin" }, right: { style: "thin" } };

          // Hallar el label original para aplicar WrapText
          const currentLabel = labels[colIndex - 1];
          const needsWrap = ["TIPO DE HAB", "NOMBRE Y APELLIDO", "OBS"].includes(currentLabel);
          cell.alignment = { vertical: "middle", wrapText: needsWrap };
        });
      });

      // Anchos
      worksheet.columns.forEach((column, i) => {
        const label = labels[i];
        if (label === "TIPO DE HAB") column.width = 25;
        else if (label === "NOMBRE Y APELLIDO") column.width = 35;
        else if (label === "OBS") column.width = 45;
        else if (["OPERADOR", "MEDIO DE RESERVA", "TIPO DOC", "METODO", "COMPROBANTE", "Nº COMPROBANTE", "QUIEN COBRO"].includes(label)) column.width = 20;
        else column.width = 12;
      });

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

      // QUICK CHECK-IN desde Clientes Frecuentes
      const quickPax = localStorage.getItem('quick_checkin_pax');
      if (quickPax) {
        localStorage.removeItem('quick_checkin_pax'); // Limpiar inmediatamente para evitar bucles
        try {
          const data = JSON.parse(quickPax);

          // Lazo de reintentos ultra-robusto y tolerante a fallos para asegurar carga del DOM y de Bootstrap
          let attempts = 0;
          const interval = setInterval(async () => {
            attempts++;
            const modalEl = document.getElementById('modalCheckin');
            if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
              clearInterval(interval);
              try {
                // 1. Abrir modal de checkin (valida caja, carga habs y muestra modal)
                await abrirCheckin();

                // 2. Pre-llenar el primer pasajero con los datos recibidos
                if (form.pax.length > 0) {
                  form.pax[0].documento_num = data.dni;
                  form.pax[0].documento_tipo = data.tipo_doc || 'DNI';
                  form.pax[0].nombre_completo = data.nombre;
                  form.pax[0].nacionalidad = data.nacionalidad || 'Peruana';
                  form.pax[0].ciudad = data.ciudad || '';
                  form.pax[0].celular = data.celular || '';
                  form.pax[0].email = data.email || '';
                  form.pax[0].es_titular = true;

                  // Cargar datos corporativos si existen
                  if (data.ruc || data.empresa) {
                    form.pax[0].es_corporativo = true;
                    form.pax[0].ruc_empresa = data.ruc || '';
                    form.pax[0].empresa = data.empresa || '';

                    // También pre-llenar facturación
                    form.stay.ruc_factura = data.ruc || '';
                    form.stay.razon_social = data.empresa || '';
                    form.stay.tipo_comprobante = 'FACTURA';
                  }
                }

                showToast(`¡Cliente Frecuente detectado!`, 'success');
              } catch (err) {
                console.error('Error al instanciar checkin rápido:', err);
              }
            } else if (attempts >= 50) {
              clearInterval(interval);
              console.error('Error de carga: El modal de checkin o la librería Bootstrap no cargaron a tiempo.');
            }
          }, 100);
        } catch (e) {
          console.error('Error in quick checkin:', e);
        }
      }
    });

    watch(() => form.stay.habitacion_id, () => {
      if (form.stay.habitacion_id) onHabChange();
    });

    onUnmounted(() => {
      if (pollingTimer) clearInterval(pollingTimer);
    });

    const toggleStayExclusion = (titular) => {
      const sid = titular.stay_id || titular.id;
      reportePax.filas.forEach(f => {
        if ((f.stay_id || f.id) === sid) {
          f.excluir = titular.excluir;
        }
      });
    };

    return {
      toggleStayExclusion,
      selColumnas, abrirConfigExportar, confirmarExportacion,
      stays, habitacionesLibres, tcs, loading, busqueda, filtroPiso, filtroPago, form,
      staysFiltrados, selectedStay, stayParaPago, mediosPago, pagoForm,
      abrirCheckin, onHabChange, calcularNoches, onNochesChange, recalcularMoneda,
      onAdelantoChange, agregarPax, setTitular, guardarCheckin, verDetalle, cargarDatos,
      fmtFecha, getPagoClass, getEstadBadge, procederCheckout, abrirPago, recalcularPago, cambiarMonedaPago, guardarPago,
      abrirEdicion, activarReserva, cambiarTipoPago, fmtCur, isEditingAdelanto, adelantoExcede, getMetodoPagoIcon,
      saldoPendienteOriginal, adelantoInvalido,
      // CONSUMOS
      inventario, inventarioAgrupado, stayParaConsumo, consumosStay, consumoForm,
      abrirConsumo, onProductoChange, calcularTotalConsumo, guardarConsumo,
      // AUTOCOMPLETE PAX
      sugerencias, buscarPax, aplicarSugerencia, ocultarSugerencias,
      // LOOKUP DNI / RUC
      lookupDocumento, lookupRuc, onDocTipoChange,
      lookupLoading, lookupOk, rucLoading, rucOk,
      // REPORTE PAX
      reportePax, abrirReportePax, cargarReportePax, exportarReportePax,
      totalConsumoStay, saldoPendienteStay
    };
  }
}).mount('#app-rooming');
