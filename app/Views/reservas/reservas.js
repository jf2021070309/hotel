/**
 * app/Views/cuadro_reservas/cuadro.js
 * Vue 3 Composition API — Cuadro de Reservas
 */
const { createApp, ref, reactive, computed, onMounted, onUnmounted } = Vue;

createApp({
  setup() {
    const BASE = PROJECT_BASE_URL + 'api/reservas.php?action=';
    const ROOMING_API = PROJECT_BASE_URL + 'api/rooming.php?action=';
    console.log("RESERVAS_CARGADO_V2_SIN_VISITAS");

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
    const ctxMenu = reactive({ visible: false, x: 0, y: 0, stay: null });
    const formQuick = reactive({
      id: null,
      editando: false,
      hab: null,
      fecha: '',
      titular: '',
      noches: 1,
      observaciones: '',
      canal: 'DIRECTO'
    });
    const activeQuickGuest = ref(null);

    // Detección inmediata de reserva rápida (Cliente Frecuente)
    const quickPaxData = localStorage.getItem('quick_reserva_pax');
    if (quickPaxData) {
      try {
        activeQuickGuest.value = JSON.parse(quickPaxData);
      } catch (e) { console.error(e); }
    }

    const pagoRapido = reactive({ monto: 0, moneda: 'PEN', metodo: 'efectivo' });

    const meses = [
      'Enero','Febrero','Marzo','Abril','Mayo','Junio',
      'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'
    ];

    let pollingTimer  = null;

    // ─── Computed ──────────────────────────────────────────────────────
    const colWidth = computed(() => {
      if (viewMode.value === 'compacto') return 45;
      if (viewMode.value === 'ampliado') return 140;
      return 85; // normal
    });

    const rowHeight = computed(() => {
      if (viewMode.value === 'compacto') return 28;
      if (viewMode.value === 'ampliado') return 56;
      return 42; // normal
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
    const cargarDatos = async (silent = false, forceToday = false) => {
      const wrapper = document.querySelector('.cuadro-wrapper');
      const prevScroll = wrapper ? wrapper.scrollLeft : 0;
      if (!silent) loading.value = true;
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
        }
      } catch (e) {
        console.error('Error cargando datos:', e);
      } finally {
        if (!silent) loading.value = false;
        setTimeout(() => {
          if (forceToday) {
            scrollToToday();
          } else {
            const w = document.querySelector('.cuadro-wrapper');
            if (w && prevScroll > 0) {
              w.scrollLeft = prevScroll;
            }
          }
        }, 50);
      }
    };

    // ─── Helpers de celda ─────────────────────────────────────────────
    const getCeldaStay = (hab, dia) => {
      const staysInDay = hab.stays.filter(s => s.dia_inicio <= dia && s.dia_fin >= dia);
      if (staysInDay.length === 0) return null;
      if (staysInDay.length === 1) return staysInDay[0];

      // Si hay choque (Ej: Checkout y Checkin el mismo día)
      const priorities = {
        'inhouse': 1,
        'activo': 1,
        'late_checkout': 2,
        'reservado': 3,
        'finalizado': 4,
        'cancelado': 5
      };
      
      staysInDay.sort((a, b) => {
        const pA = priorities[a.estado] || 99;
        const pB = priorities[b.estado] || 99;
        return pA - pB;
      });

      return staysInDay[0];
    };

    const getPaxTotalDia = (dia) => {
      let total = 0;
      for (const hab of habitacionesFiltradas.value) {
        const stay = getCeldaStay(hab, dia);
        if (stay && stay.pax) {
          total += Number(stay.pax) || 0;
        }
      }
      return total;
    };

    const esInicioStay = (hab, dia) => {
      const s = getCeldaStay(hab, dia);
      return s && s.dia_inicio === dia;
    };

    const esDiaEstadoEspecial = (hab, dia) => {
      const esHoy = dia === hoyDia.value && mesActual.value === mesHoy.value && anioActual.value === anioHoy.value;
      return esHoy && ['limpieza', 'sucio', 'bloqueado', 'mantenimiento', 'late_checkout'].includes(hab.estado);
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

    const formatNumber = (num, decimals = 2) => {
      return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
      }).format(num);
    };

    // ─── Cell click ───────────────────────────────────────────────────
    const cambiarEstadoHabitacion = async (habId, estado) => {
      loading.value = true;
      try {
        const res = await axios.post(`${BASE}estado_hab`, { hab_id: habId, estado: estado });
        if (res.data.ok) {
          Swal.fire({
            icon: 'success',
            title: `Estado cambiado a ${estado.toUpperCase()}`,
            timer: 1500,
            showConfirmButton: false
          });
          await cargarDatos(false, true);
          habitaciones.value = enrichHabs(habitaciones.value);
          scrollToToday();
        } else {
          Swal.fire('Error', res.data.msg || 'No se pudo cambiar el estado', 'error');
        }
      } catch (e) {
        Swal.fire('Error', 'Error de conexión al cambiar estado', 'error');
      } finally {
        loading.value = false;
      }
    };

    const onCeldaClick = (hab, dia) => {
      const stay = getCeldaStay(hab, dia);
      if (stay) {
        abrirDetalle(stay, hab.numero);
      } else {
        Swal.fire({
          title: `Habitación #${hab.numero} — Día ${dia}/${mesActual.value}`,
          html: `
            <div class="d-flex flex-column gap-2 mt-3">
              <button id="btn-opt-reserva" class="btn btn-primary py-3 fw-bold text-start shadow-sm d-flex align-items-center gap-3 border-0" style="background: linear-gradient(135deg, #0288D1, #01579B); border-radius: 12px;">
                <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                  <i class="bi bi-calendar-plus fs-5"></i>
                </div>
                <div class="text-white">
                  <div class="fs-6 mb-0">Crear Reserva</div>
                  <div class="small opacity-75 fw-normal" style="font-size: 11px;">Registrar nueva reserva para esta fecha</div>
                </div>
              </button>

              <button id="btn-opt-sucio" class="btn btn-secondary py-3 fw-bold text-start shadow-sm d-flex align-items-center gap-3 border-0" style="background: linear-gradient(135deg, #8D6E63, #5D4037); border-radius: 12px;">
                <div class="bg-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px; color: #5D4037;">
                  <i class="bi bi-droplet-half fs-5"></i>
                </div>
                <div class="text-white">
                  <div class="fs-6 mb-0">Cambiar estado sucio</div>
                  <div class="small opacity-75 fw-normal" style="font-size: 11px;">Marcar habitación para limpieza</div>
                </div>
              </button>

              <button id="btn-opt-mant" class="btn btn-danger py-3 fw-bold text-start shadow-sm d-flex align-items-center gap-3 border-0" style="background: linear-gradient(135deg, #E53935, #B71C1C); border-radius: 12px;">
                <div class="bg-white text-danger rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                  <i class="bi bi-tools fs-5"></i>
                </div>
                <div class="text-white">
                  <div class="fs-6 mb-0">Cambiar a mantenimiento</div>
                  <div class="small opacity-75 fw-normal" style="font-size: 11px;">Bloquear por reparaciones o mantenimiento</div>
                </div>
              </button>

              <button id="btn-opt-libre" class="btn btn-success py-3 fw-bold text-start shadow-sm d-flex align-items-center gap-3 border-0" style="background: linear-gradient(135deg, #2E7D32, #1B5E20); border-radius: 12px;">
                <div class="bg-white text-success rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                  <i class="bi bi-check-circle fs-5"></i>
                </div>
                <div class="text-white">
                  <div class="fs-6 mb-0">Cambiar a libre</div>
                  <div class="small opacity-75 fw-normal" style="font-size: 11px;">Habilitar habitación disponible</div>
                </div>
              </button>
            </div>
          `,
          showConfirmButton: false,
          showCloseButton: true,
          customClass: {
            popup: 'rounded-4 shadow-lg'
          },
          didOpen: () => {
            const popup = Swal.getPopup();
            popup.querySelector('#btn-opt-reserva').onclick = () => {
              Swal.close();
              abrirQuickReserva(hab, dia);
            };
            popup.querySelector('#btn-opt-sucio').onclick = () => {
              Swal.close();
              cambiarEstadoHabitacion(hab.id, 'sucio');
            };
            popup.querySelector('#btn-opt-mant').onclick = () => {
              Swal.close();
              cambiarEstadoHabitacion(hab.id, 'mantenimiento');
            };
            popup.querySelector('#btn-opt-libre').onclick = () => {
              Swal.close();
              cambiarEstadoHabitacion(hab.id, 'libre');
            };
          }
        });
      }
    };

    const abrirQuickReserva = (hab, dia) => {
      formQuick.id      = null;
      formQuick.editando = false;
      formQuick.hab     = hab;
      formQuick.fecha   = `${anioActual.value}-${String(mesActual.value).padStart(2,'0')}-${String(dia).padStart(2,'0')}`;
      
      if (activeQuickGuest.value) {
        formQuick.titular = activeQuickGuest.value.nombre;
        formQuick.observaciones = `DNI: ${activeQuickGuest.value.dni}`;
        localStorage.removeItem('quick_reserva_pax'); // Limpiar ahora que se usó
        activeQuickGuest.value = null; 
      } else {
        formQuick.titular = '';
        formQuick.observaciones = '';
      }

      formQuick.noches  = 1;
      formQuick.canal   = 'DIRECTO';
      bootstrap.Modal.getOrCreateInstance(document.getElementById('modalQuickReserva')).show();
    };

    const editarQuickReserva = (stay) => {
      cerrarDetalle();
      formQuick.id = stay.id;
      formQuick.editando = true;
      formQuick.hab = {
        id: stay.habitacion_id || stay.hab_id || null,
        numero: stay.hab_numero,
        tipo: stay.tipo_hab_declarado || stay.hab_tipo || 'RESERVA'
      };
      formQuick.fecha = stay.fecha_inicio;
      formQuick.titular = stay.titular || '';
      formQuick.noches = stay.noches || 1;
      formQuick.observaciones = stay.observaciones || '';
      formQuick.canal = stay.canal || 'DIRECTO';
      bootstrap.Modal.getOrCreateInstance(document.getElementById('modalQuickReserva')).show();
    };

    const guardarQuickReserva = async () => {
      if (!formQuick.titular) return;
      loading.value = true;
      try {
        const action = formQuick.editando ? 'editar_quick_reserva' : 'quick_reserva';
        const payload = {
          id:      formQuick.id,
          hab_id:  formQuick.hab.id,
          fecha:   formQuick.fecha,
          titular: formQuick.titular,
          noches:  formQuick.noches,
          observaciones: formQuick.observaciones,
          canal:   formQuick.canal
        };
        const res = await axios.post(`${BASE}${action}`, payload);
        if (res.data.ok) {
          bootstrap.Modal.getInstance(document.getElementById('modalQuickReserva'))?.hide();
          if (staySeleccionado.value?.id === formQuick.id) {
            cerrarDetalle();
          }
          Swal.fire({
            icon: 'success',
            title: formQuick.editando ? 'Reserva actualizada' : 'Reserva registrada',
            timer: 1500,
            showConfirmButton: false
          });
          await cargarDatos(false, true);
          habitaciones.value = enrichHabs(habitaciones.value);
          scrollToToday();
        } else {
          Swal.fire('Error', res.data.msg, 'error');
        }
      } catch (e) {
        Swal.fire('Error', 'No se pudo registrar la reserva', 'error');
      } finally {
        loading.value = false;
      }
    };

    const abrirDetalle = (stay, habNum = '') => {
      staySeleccionado.value = { ...stay, hab_numero: habNum || stay.hab_numero };
      pagoRapido.monto  = 0;
      pagoRapido.moneda = stay.moneda_pago || 'PEN';
      pagoRapido.metodo = 'efectivo';
      bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetalleReservas')).show();
    };

    const cerrarDetalle = () => {
      bootstrap.Modal.getInstance(document.getElementById('modalDetalleReservas'))?.hide();
    };

    const irARooming = (stay) => {
      if (!stay.hab_numero) {
        Swal.fire('Error', 'No se pudo identificar la habitación', 'error');
        return;
      }
      // Redirigir a rooming buscando la habitación
      window.location.href = `../rooming/index.php?buscar=${stay.hab_numero}`;
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
        await cargarDatos(false, true);
        scrollToToday();
        Swal.fire({ icon: 'success', title: 'Checkout realizado', timer: 1500, showConfirmButton: false });
      } catch (e) {
        Swal.fire('Error', 'No se pudo realizar el checkout', 'error');
      }
    };

    const realizarCheckin = async (stay) => {
      const confirm = await Swal.fire({
        title: '¿Confirmar Ingreso?',
        text: `Se marcará la entrada de ${stay.titular} a la habitación ${stay.hab_numero}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, registrar ingreso',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#0288D1'
      });
      if (!confirm.isConfirmed) return;
      
      loading.value = true;
      try {
        const res = await axios.post(`${BASE}checkin`, { id: stay.id });
        if (res.data.ok) {
          bootstrap.Modal.getInstance(document.getElementById('modalDetalleReservas'))?.hide();
          Swal.fire({ icon: 'success', title: '¡Check-in realizado!', timer: 2000, showConfirmButton: false });
          await cargarDatos(false, true);
          scrollToToday();
        } else {
          Swal.fire('Error', res.data.msg, 'error');
        }
      } catch (e) {
        Swal.fire('Error', 'No se pudo realizar el check-in', 'error');
      } finally {
        loading.value = false;
      }
    };

    // ─── Badge / bar helpers ──────────────────────────────────────────
    const confirmarReserva = async (stay) => {
      const confirm = await Swal.fire({
        title: 'Confirmar reserva',
        text: `Se registrara el ingreso de ${stay.titular} en la habitacion ${stay.hab_numero}.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Si, confirmar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#0288D1'
      });
      if (!confirm.isConfirmed) return;

      loading.value = true;
      try {
        const res = await axios.post(`${BASE}checkin`, { id: stay.id });
        if (res.data.ok) {
          cerrarDetalle();
          await cargarDatos(false, true);
          habitaciones.value = enrichHabs(habitaciones.value);
          scrollToToday();
          Swal.fire({ icon: 'success', title: 'Reserva confirmada', timer: 1800, showConfirmButton: false });
        } else {
          Swal.fire('Error', res.data.msg, 'error');
        }
      } catch (e) {
        Swal.fire('Error', 'No se pudo confirmar la reserva', 'error');
      } finally {
        loading.value = false;
      }
    };

    const rechazarReserva = async (stay) => {
      const confirm = await Swal.fire({
        title: 'Rechazar reserva',
        text: `La reserva de ${stay.titular} quedara como rechazada y saldra del cuadro activo.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Si, rechazar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545'
      });
      if (!confirm.isConfirmed) return;

      loading.value = true;
      try {
        const res = await axios.post(`${BASE}rechazar`, { id: stay.id });
        if (res.data.ok) {
          cerrarDetalle();
          await cargarDatos(false, true);
          habitaciones.value = enrichHabs(habitaciones.value);
          scrollToToday();
          Swal.fire({ icon: 'success', title: 'Reserva rechazada', timer: 1800, showConfirmButton: false });
        } else {
          Swal.fire('Error', res.data.msg, 'error');
        }
      } catch (e) {
        Swal.fire('Error', 'No se pudo rechazar la reserva', 'error');
      } finally {
        loading.value = false;
      }
    };

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

    const getColorPago = (stay) => {
      const perc = porcentajePago(stay);
      if (perc >= 100) return '#22c55e'; // Verde
      if (perc > 0)    return '#facc15'; // Amarillo
      return '#ef4444'; // Rojo
    };

    const getStayColorClass = (stay) => {
      if (!stay) return '';
      // Prioridad 0: Si ya hizo checkout (Finalizado)
      if (stay.estado === 'finalizado') return 'res-finalizado';
      
      // Prioridad 1: Si ya está en el hotel (In-house / Activo)
      if (stay.estado === 'activo' || stay.estado === 'inhouse' || stay.checkin_realizado) return 'res-inhouse';
      
      // Prioridad 2: Canal de reserva (para ingresos pendientes)
      const canal = (stay.canal || '').toLowerCase();
      if (canal.includes('booking')) return 'res-booking';
      
      // Por defecto (Llamada, WhatsApp, Directo)
      return 'res-directo';
    };

    // ─── Drag to scroll ───────────────────────────────────────────────
    const initDragToScroll = () => {
      const slider = document.querySelector('.cuadro-wrapper');
      if (!slider) return;

      let isDown = false;
      let startX;
      let scrollLeft;
      let isDragging = false;

      slider.addEventListener('mousedown', (e) => {
        if (e.target.closest('.modal') || e.target.closest('.context-menu')) return;
        isDown = true;
        isDragging = false;
        slider.classList.add('grabbing');
        startX = e.pageX - slider.offsetLeft;
        scrollLeft = slider.scrollLeft;
      });

      slider.addEventListener('mouseleave', () => {
        isDown = false;
        slider.classList.remove('grabbing');
      });

      slider.addEventListener('mouseup', () => {
        isDown = false;
        slider.classList.remove('grabbing');
      });

      slider.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - slider.offsetLeft;
        const walk = (x - startX) * 1.5;
        if (Math.abs(walk) > 5) {
          isDragging = true;
        }
        slider.scrollLeft = scrollLeft - walk;
      });

      slider.addEventListener('click', (e) => {
        if (isDragging) {
          e.stopPropagation();
          e.preventDefault();
          isDragging = false;
        }
      }, true);
    };

    // ─── Polling ──────────────────────────────────────────────────────
    const iniciarPolling = () => {
      if (pollingTimer) clearInterval(pollingTimer);
      pollingTimer = setInterval(() => cargarDatos(true), 10000);
    };

    // ─── Lifecycle ────────────────────────────────────────────────────
    onMounted(async () => {
      document.addEventListener('click', closeContextMenu);
      await cargarDatos();
      // Enrich with cols after loading
      habitaciones.value = enrichHabs(habitaciones.value);
      iniciarPolling();
      scrollToToday();
      setTimeout(initDragToScroll, 300);

      if (activeQuickGuest.value) {
        Swal.fire({ 
          title: `MODO RESERVA: ${activeQuickGuest.value.nombre}`,
          text: 'Selecciona una celda en el cuadro para completar la reserva',
          icon: 'info',
          timer: 5000,
          toast: true,
          position: 'top-end'
        });
      }
    });

    onUnmounted(() => {
      document.removeEventListener('click', closeContextMenu);
      if (pollingTimer) clearInterval(pollingTimer);
    });

    return {
      activeQuickGuest,
      loading, loadingPago,
      habitaciones, diasEnMes, resumen, ingresos,
      mesActual, anioActual, hoyDia, mesHoy, anioHoy,
      filtroPiso, filtroPago,
      staySeleccionado, pagoRapido,
      meses, pisos,
      habitacionesFiltradas, staysHoyMovil,
      // methods
      cargarDatos, cambiarMes, irHoy,
      getCeldaStay, getPaxTotalDia, esInicioStay, esDiaEstadoEspecial, calcCols,
      getDiaSemana, onCeldaClick, abrirDetalle,
      openContextMenu, handleCtxAction, ctxMenu,
      guardarPagoRapido, checkout,
      formQuick, abrirQuickReserva, editarQuickReserva, guardarQuickReserva,
      getTipoClass,
      badgeClass, barClass, porcentajePago,
      viewMode, colWidth, rowHeight, formatNumber,
      irARooming, getStayColorClass, getColorPago,
      confirmarReserva, rechazarReserva
    };
  }
}).mount('#app-reservas');
