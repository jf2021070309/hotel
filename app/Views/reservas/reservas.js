/**
 * app/Views/cuadro_reservas/cuadro.js
 * Vue 3 Composition API — Cuadro de Reservas
 */
const { createApp, ref, reactive, computed, onMounted, onUnmounted } = Vue;

createApp({
  setup() {
    const BASE = '../../../api/reservas.php?action=';
    const ROOMING_API = '../../../api/rooming.php?action=';

    // ─── State ─────────────────────────────────────────────────────────
    const loading       = ref(true);
    const loadingPago   = ref(false);
    const habitaciones  = ref([]);
    const diasEnMes     = ref(30);
    const resumen       = ref({
      ocupadas: 0, total: 0, pax_total: 0,
      ingresos_hoy: 0, pendientes: 0,
      cnt_pendiente: 0, cnt_adelanto: 0, cnt_parcial: 0, cnt_pagado: 0,
    });

    const today         = new Date();
    const mesActual     = ref(today.getMonth() + 1);
    const anioActual    = ref(today.getFullYear());
    const hoyDia        = ref(today.getDate());
    const mesHoy        = ref(today.getMonth() + 1);
    const anioHoy       = ref(today.getFullYear());

    const filtroPiso    = ref('');
    const filtroPago    = ref('');
    const viewMode      = ref('normal');   // 'compacto' | 'normal' | 'ampliado'
    const staySeleccionado = ref(null);
    const segsActualizado  = ref(0);
    const ctxMenu = reactive({ visible: false, x: 0, y: 0, stay: null });
    const formQuick = reactive({ hab: null, fecha: '', titular: '', noches: 1, observaciones: '' });

    const pagoRapido = reactive({ monto: 0, moneda: 'PEN', metodo: 'efectivo' });

    const meses = [
      'Enero','Febrero','Marzo','Abril','Mayo','Junio',
      'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'
    ];

    let pollingTimer  = null;
    let segsTimer     = null;

    // ─── Computed ──────────────────────────────────────────────────────
    const colWidth = computed(() => {
      if (viewMode.value === 'compacto') return 38;
      if (viewMode.value === 'ampliado') return 110;
      return 65; // normal
    });

    const rowHeight = computed(() => {
      if (viewMode.value === 'compacto') return 26;
      if (viewMode.value === 'ampliado') return 50;
      return 36; // normal
    });

    const pisos = computed(() => {
      const set = new Set(habitaciones.value.map(h => h.piso).filter(Boolean));
      return [...set].sort((a, b) => a - b);
    });

    const habitacionesFiltradas = computed(() => {
      return habitaciones.value.filter(h => {
        if (filtroPiso.value && h.piso != filtroPiso.value) return false;
        if (filtroPago.value) {
          const tiene = h.stays.some(s => s.estado_pago === filtroPago.value);
          if (!tiene) return false;
        }
        return true;
      });
    });

    const ingresos = computed(() =>
      resumen.value.ingresos_hoy.toFixed(2)
    );

    const staysHoyMovil = computed(() => {
      const hoy = `${anioActual.value}-${String(mesActual.value).padStart(2,'0')}-${String(hoyDia.value).padStart(2,'0')}`;
      return habitaciones.value.flatMap(h =>
        h.stays
          .filter(s => s.fecha_inicio <= hoy && s.fecha_fin > hoy)
          .map(s => ({ ...s, hab_numero: h.numero }))
      );
    });

    // ─── API ───────────────────────────────────────────────────────────
    const cargarDatos = async () => {
      loading.value = true;
      try {
        const res = await axios.get(
          `${BASE}datos&mes=${mesActual.value}&anio=${anioActual.value}`
        );
        if (res.data.ok) {
          const d = res.data.data;
          habitaciones.value = d.habitaciones;
          diasEnMes.value    = d.dias_en_mes;
          resumen.value      = d.resumen;
          hoyDia.value       = d.hoy;
          segsActualizado.value = 0;
        }
      } catch (e) {
        console.error('Error cargando datos:', e);
      } finally {
        loading.value = false;
      }
    };

    // ─── Helpers de celda ─────────────────────────────────────────────
    const getCeldaStay = (hab, dia) => {
      return hab.stays.find(s => s.dia_inicio <= dia && s.dia_fin > dia) || null;
    };

    const esInicioStay = (hab, dia) => {
      const s = getCeldaStay(hab, dia);
      return s && s.dia_inicio === dia;
    };

    const esDiaEstadoEspecial = (hab, dia) => {
      const esHoy = dia === hoyDia.value && mesActual.value === mesHoy.value && anioActual.value === anioHoy.value;
      return esHoy && ['limpieza', 'bloqueado', 'mantenimiento', 'late_checkout'].includes(hab.estado);
    };

    const getTipoClass = (tipo) => {
      if (!tipo) return 'cat-generic';
      const t = tipo.toUpperCase();
      if (t.includes('TRIPLE')) return 'cat-triple';
      if (t.includes('EJECUTIVA')) return 'cat-ejecutiva';
      if (t.includes('DOBLE')) return 'cat-doble';
      if (t.includes('MATRIMONIAL')) return 'cat-matrimonial';
      if (t.includes('PLATINIUM') || t.includes('SUITE')) return 'cat-platinium';
      return 'cat-generic';
    };

    // Cuántas columnas abarca el stay (clamp al fin del mes)
    const calcCols = (stay) => {
      const fin = Math.min(stay.dia_fin, diasEnMes.value + 1);
      return Math.max(1, fin - stay.dia_inicio);
    };

    // Enrich stays with cols before returning
    const enrichHabs = (habs) => habs.map(h => ({
      ...h,
      stays: h.stays.map(s => ({ ...s, cols: calcCols(s) }))
    }));

    // ─── Scroll to today ──────────────────────────────────────────────
    const scrollToToday = () => {
      setTimeout(() => {
        const todayCell = document.querySelector('.today-hdr');
        const wrapper = document.querySelector('.cuadro-wrapper');
        if (todayCell && wrapper) {
          const stickyWidth = 160; // Ancho de la primera columna fija
          wrapper.scrollTo({
            left: Math.max(0, todayCell.offsetLeft - stickyWidth - 10),
            behavior: 'smooth'
          });
        }
      }, 100);
    };

    // ─── Navigation ───────────────────────────────────────────────────
    const cambiarMes = (delta) => {
      let m = mesActual.value + delta;
      let a = anioActual.value;
      if (m > 12) { m = 1; a++; }
      if (m < 1)  { m = 12; a--; }
      mesActual.value  = m;
      anioActual.value = a;
      cargarDatos();
    };

    const irHoy = async () => {
      mesActual.value  = today.getMonth() + 1;
      anioActual.value = today.getFullYear();
      await cargarDatos();
      scrollToToday();
    };

    // ─── Date helpers ─────────────────────────────────────────────────
    const getDiaSemana = (dia) => {
      const d = new Date(anioActual.value, mesActual.value - 1, dia);
      return ['D','L','M','X','J','V','S'][d.getDay()];
    };

    // ─── Cell click ───────────────────────────────────────────────────
    const onCeldaClick = (hab, dia) => {
      const stay = getCeldaStay(hab, dia);
      if (stay) {
        abrirDetalle(stay);
      } else {
        abrirQuickReserva(hab, dia);
      }
    };

    const abrirQuickReserva = (hab, dia) => {
      formQuick.hab     = hab;
      formQuick.fecha   = `${anioActual.value}-${String(mesActual.value).padStart(2,'0')}-${String(dia).padStart(2,'0')}`;
      formQuick.titular = '';
      formQuick.noches  = 1;
      formQuick.observaciones = '';
      bootstrap.Modal.getOrCreateInstance(document.getElementById('modalQuickReserva')).show();
    };

    const guardarQuickReserva = async () => {
      if (!formQuick.titular) return;
      loading.value = true;
      try {
        const res = await axios.post(`${BASE}quick_reserva`, {
          hab_id:  formQuick.hab.id,
          fecha:   formQuick.fecha,
          titular: formQuick.titular,
          noches:  formQuick.noches,
          observaciones: formQuick.observaciones
        });
        if (res.data.ok) {
          bootstrap.Modal.getInstance(document.getElementById('modalQuickReserva'))?.hide();
          Swal.fire({ icon: 'success', title: 'Reserva registrada', timer: 1500, showConfirmButton: false });
          await cargarDatos();
          habitaciones.value = enrichHabs(habitaciones.value);
        } else {
          Swal.fire('Error', res.data.msg, 'error');
        }
      } catch (e) {
        Swal.fire('Error', 'No se pudo registrar la reserva', 'error');
      } finally {
        loading.value = false;
      }
    };

    const abrirDetalle = (stay) => {
      staySeleccionado.value = stay;
      pagoRapido.monto  = 0;
      pagoRapido.moneda = stay.moneda_pago || 'PEN';
      pagoRapido.metodo = 'efectivo';
      bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetalleReservas')).show();
    };

    // ─── Context Menu ─────────────────────────────────────────────────
    const closeContextMenu = () => { ctxMenu.visible = false; };
    const openContextMenu = (e, stay) => {
      e.preventDefault();
      ctxMenu.visible = true;
      ctxMenu.x = e.clientX;
      ctxMenu.y = e.clientY;
      ctxMenu.stay = stay;
    };
    const handleCtxAction = (action) => {
      const stay = ctxMenu.stay;
      closeContextMenu();
      if (!stay) return;

      if (action === 'detalle') {
        abrirDetalle(stay);
      } else if (action === 'cobrar') {
        abrirDetalle(stay);
        setTimeout(() => document.querySelector('input[placeholder="Monto"]')?.focus(), 500);
      } else if (action === 'checkout') {
        checkout(stay);
      }
    };

    // ─── Pago rápido ──────────────────────────────────────────────────
    const guardarPagoRapido = async () => {
      if (!pagoRapido.monto || pagoRapido.monto <= 0) {
        Swal.fire('Error', 'Ingresa un monto válido', 'warning');
        return;
      }
      loadingPago.value = true;
      try {
        const res = await axios.post(`${BASE}pago_rapido`, {
          stay_id: staySeleccionado.value.id,
          monto:   pagoRapido.monto,
          moneda:  pagoRapido.moneda,
          metodo:  pagoRapido.metodo,
          tc:      1,
        });
        if (res.data.ok) {
          // Update in place without full reload
          const d = res.data.data;
          const hab = habitaciones.value.find(h =>
            h.stays.some(s => s.id === d.stay_id)
          );
          if (hab) {
            const stay = hab.stays.find(s => s.id === d.stay_id);
            if (stay) {
              stay.total_cobrado = d.total_cobrado;
              stay.estado_pago   = d.estado_pago;
              if (staySeleccionado.value?.id === d.stay_id) {
                staySeleccionado.value.total_cobrado = d.total_cobrado;
                staySeleccionado.value.estado_pago   = d.estado_pago;
              }
            }
          }
          Swal.fire({ icon: 'success', title: 'Pago registrado', timer: 1500, showConfirmButton: false });
          pagoRapido.monto = 0;
        } else {
          Swal.fire('Error', res.data.msg, 'error');
        }
      } catch (e) {
        Swal.fire('Error', 'No se pudo registrar el pago', 'error');
      } finally {
        loadingPago.value = false;
      }
    };

    // ─── Checkout ─────────────────────────────────────────────────────
    const checkout = async (stay) => {
      const confirm = await Swal.fire({
        title: '¿Confirmar Checkout?',
        text: `${stay.titular} — ${stay.fecha_fin}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, checkout',
        cancelButtonText: 'Cancelar',
      });
      if (!confirm.isConfirmed) return;
      try {
        await axios.post(`${ROOMING_API}checkout`, { id: stay.id });
        bootstrap.Modal.getInstance(document.getElementById('modalDetalleReservas'))?.hide();
        await cargarDatos();
        Swal.fire({ icon: 'success', title: 'Checkout realizado', timer: 1500, showConfirmButton: false });
      } catch (e) {
        Swal.fire('Error', 'No se pudo realizar el checkout', 'error');
      }
    };

    const lateCheckout = async (stay) => {
      try {
        await axios.post(`${BASE}late_checkout`, { id: stay.id });
        bootstrap.Modal.getInstance(document.getElementById('modalDetalleReservas'))?.hide();
        Swal.fire({ icon: 'info', title: 'Late Checkout aplicado', timer: 1500, showConfirmButton: false });
        await cargarDatos();
      } catch (e) {
        Swal.fire('Error', 'No se pudo aplicar late checkout', 'error');
      }
    };

    // ─── Badge / bar helpers ──────────────────────────────────────────
    const badgeClass = (estado) => ({
      'bg-danger':  estado === 'pendiente',
      'bg-warning text-dark': estado === 'adelanto',
      'bg-warning': estado === 'parcial',
      'bg-success': estado === 'pagado',
    });

    const barClass = (estado) => ({
      'bg-danger':  estado === 'pendiente',
      'bg-warning': estado === 'adelanto' || estado === 'parcial',
      'bg-success': estado === 'pagado',
    });

    const porcentajePago = (stay) => {
      if (!stay.total_pago) return 0;
      return Math.min(100, Math.round((stay.total_cobrado / stay.total_pago) * 100));
    };

    // ─── Polling ──────────────────────────────────────────────────────
    const iniciarPolling = () => {
      pollingTimer = setInterval(cargarDatos, 30_000);
      segsTimer    = setInterval(() => { segsActualizado.value++; }, 1000);
    };

    // ─── Lifecycle ────────────────────────────────────────────────────
    onMounted(async () => {
      document.addEventListener('click', closeContextMenu);
      await cargarDatos();
      // Enrich with cols after loading
      habitaciones.value = enrichHabs(habitaciones.value);
      iniciarPolling();
      scrollToToday();
    });

    onUnmounted(() => {
      document.removeEventListener('click', closeContextMenu);
      clearInterval(pollingTimer);
      clearInterval(segsTimer);
    });

    return {
      loading, loadingPago,
      habitaciones, diasEnMes, resumen, ingresos,
      mesActual, anioActual, hoyDia, mesHoy, anioHoy,
      filtroPiso, filtroPago,
      staySeleccionado, pagoRapido,
      segsActualizado, meses, pisos,
      habitacionesFiltradas, staysHoyMovil,
      // methods
      cargarDatos, cambiarMes, irHoy,
      getCeldaStay, esInicioStay, esDiaEstadoEspecial, calcCols,
      getDiaSemana, onCeldaClick, abrirDetalle,
      openContextMenu, handleCtxAction, ctxMenu,
      guardarPagoRapido, checkout, lateCheckout,
      formQuick, abrirQuickReserva, guardarQuickReserva,
      getTipoClass,
      badgeClass, barClass, porcentajePago,
      viewMode, colWidth, rowHeight,
    };
  }
}).mount('#app-reservas');
