/**
 * assets/js/limpieza_v2.js
 * Vue 3 — Panel Limpieza V2 (tabla Excel)
 */
'use strict';

const { createApp, ref, computed, onMounted } = Vue;

createApp({
  setup() {
    // ── Estado ─────────────────────────────────────────────────
    const API      = window.SERVER_DATA.apiBase;
    const fecha    = ref(window.SERVER_DATA.hoy);
    const lista    = ref([]);
    const loading  = ref(false);
    const busqueda = ref('');
    const filtroEstado = ref('todos');

    // ── Computed ────────────────────────────────────────────────
    const yaGenerado = computed(() => lista.value.length > 0);

    const listaFiltrada = computed(() => {
      let result = lista.value;

      // Filtro por estado activo
      if (filtroEstado.value !== 'todos') {
        result = result.filter(h => h.estado === filtroEstado.value);
      }

      // Búsqueda libre
      if (busqueda.value.trim()) {
        const q = busqueda.value.toLowerCase().trim();
        result = result.filter(h =>
          String(h.habitacion).includes(q) ||
          (h.estado  || '').toLowerCase().includes(q) ||
          (h.tipo_limpieza || '').toLowerCase().includes(q) ||
          (h.room_estado || '').toLowerCase().includes(q)
        );
      }

      return result;
    });

    const porcentajeGlobal = computed(() => {
      if (!lista.value.length) return 0;
      const listas = lista.value.filter(h => h.estado === 'lista').length;
      return Math.round((listas / lista.value.length) * 100);
    });

    // ── Helpers de conteo ──────────────────────────────────────
    function countEstado(estado) {
      return lista.value.filter(h => h.estado === estado).length;
    }

    // ── Colores / Labels ───────────────────────────────────────
    function getColorTipo(tipo) {
      const map = {
        salida:     '#dc2626',
        reposo:     '#d97706',
        programada: '#2563eb',
        estimacion: '#7c3aed',
      };
      return map[tipo] || '#64748b';
    }

    function labelTipo(tipo) {
      const map = {
        salida:     'SALIDA',
        reposo:     'REPASO',
        programada: 'PROG.',
        estimacion: 'ESTIM.',
      };
      return map[tipo] || (tipo || '—').toUpperCase();
    }

    function getRoomEstadoColor(estado) {
      const map = {
        ocupado:      '#2563eb',
        libre:        '#059669',
        limpieza:     '#d97706',
        sucio:        '#92400e',
        mantenimiento:'#dc2626',
      };
      return map[(estado||'').toLowerCase()] || '#64748b';
    }

    function getEstadoSelectClass(estado) {
      if (estado === 'lista')      return 'estado-lista';
      if (estado === 'en proceso') return 'estado-proceso';
      if (estado === 'pendiente')  return 'estado-pendiente';
      return '';
    }

    function getRowClass(h) {
      if (h.estado === 'lista')       return 'row-lista';
      if (h.estado === 'en proceso')  return 'row-proceso';
      if (h.estado === 'mantenimiento') return 'row-mant';
      return '';
    }

    // ── API calls ──────────────────────────────────────────────
    async function cargarDatos() {
      loading.value = true;
      try {
        const res = await axios.get(`${API}?action=detalle_fecha&fecha=${fecha.value}`);
        lista.value = (res.data.data || []).map(h => ({ ...h }));
      } catch (e) {
        console.error('[LimpiezaV2] cargarDatos error', e);
        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar la lista.', timer: 2500, showConfirmButton: false });
      }
      loading.value = false;
    }

    async function generarLista() {
      const { isConfirmed } = await Swal.fire({
        title: '¿Generar lista de limpieza?',
        text: `Se creará la lista para ${fecha.value}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, generar',
        confirmButtonColor: '#7c3aed',
        cancelButtonText: 'Cancelar',
      });
      if (!isConfirmed) return;

      loading.value = true;
      try {
        const res = await axios.post(`${API}?action=generar`);
        if (res.data.ok) {
          Swal.fire({ icon: 'success', title: '¡Lista generada!', text: res.data.msg || '', timer: 1800, showConfirmButton: false });
          await cargarDatos();
        } else {
          Swal.fire({ icon: 'warning', title: 'Aviso', text: res.data.msg || 'Sin cambios.' });
        }
      } catch (e) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo generar la lista.' });
      }
      loading.value = false;
    }

    async function resetNocturno() {
      const { isConfirmed } = await Swal.fire({
        title: '¿Ejecutar Limpieza Diaria?',
        text: 'Marcará todas las habitaciones ocupadas como SUCIAS para su repaso.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, ejecutar',
        confirmButtonColor: '#d97706',
        cancelButtonText: 'Cancelar',
      });
      if (!isConfirmed) return;

      loading.value = true;
      try {
        const res = await axios.post(`${API}?action=noche_reset`);
        if (res.data.ok) {
          Swal.fire({ icon: 'success', title: 'Listo', text: res.data.msg, timer: 2000, showConfirmButton: false });
          await cargarDatos();
        } else {
          Swal.fire({ icon: 'error', title: 'Error', text: res.data.msg });
        }
      } catch (e) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Fallo al ejecutar reset.' });
      }
      loading.value = false;
    }

    async function actualizarEstado(h) {
      // Guardar estado seleccionado inline
      try {
        const body = new FormData();
        body.append('id', h.id);
        body.append('estado', h.estado);
        await axios.post(`${API}?action=actualizar`, body);
        // Toast discreto
        const toast = Swal.mixin({ toast: true, position: 'bottom-end', showConfirmButton: false, timer: 1200, timerProgressBar: true });
        toast.fire({ icon: 'success', title: `Hab. ${h.habitacion} → ${h.estado.toUpperCase()}` });
      } catch (e) {
        console.error('[LimpiezaV2] actualizarEstado error', e);
        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo guardar el cambio.', timer: 2000 });
      }
    }

    async function toggleListo(h) {
      const nuevoEstado = h.estado === 'lista' ? 'pendiente' : 'lista';
      const anterior = h.estado;
      h.estado = nuevoEstado; // Optimistic update

      try {
        const body = new FormData();
        body.append('id', h.id);
        body.append('estado', nuevoEstado);
        const res = await axios.post(`${API}?action=actualizar`, body);
        if (!res.data.ok) {
          h.estado = anterior; // Revertir
          Swal.fire({ icon: 'error', title: 'Error', text: res.data.msg || 'No se pudo actualizar.' });
        } else {
          // Recargar para obtener hora_fin actualizada
          await cargarDatos();
        }
      } catch (e) {
        h.estado = anterior;
        Swal.fire({ icon: 'error', title: 'Error', text: 'Fallo de red.' });
      }
    }

    // ── Exportar Excel ─────────────────────────────────────────
    function exportarExcel() {
      if (!window.XLSX) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Librería XLSX no disponible.' });
        return;
      }
      const rows = lista.value.map(h => ({
        'Habitación':    h.habitacion,
        'Tipo Limpieza': labelTipo(h.tipo_limpieza),
        'Estado':        (h.estado || '').toUpperCase(),
        'Prioridad':     (h.prioridad || '').toUpperCase(),
        'Hora Inicio':   h.hora_inicio ? h.hora_inicio.substring(0, 5) : '—',
        'Hora Fin':      h.hora_fin && !h.hora_fin.startsWith('0000') ? h.hora_fin.substring(0, 5) : '—',
        'PAX':           h.pax ?? h.ocupantes ?? '—',
        'Estado Hab.':   (h.room_estado || '—').toUpperCase(),
        'Fecha':         fecha.value,
      }));

      const ws = XLSX.utils.json_to_sheet(rows);
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'Limpieza');
      XLSX.writeFile(wb, `limpieza_${fecha.value}.xlsx`);
    }

    // ── Init ───────────────────────────────────────────────────
    onMounted(cargarDatos);

    return {
      fecha, lista, loading, busqueda, filtroEstado,
      yaGenerado, listaFiltrada, porcentajeGlobal,
      countEstado, getColorTipo, labelTipo, getRoomEstadoColor,
      getEstadoSelectClass, getRowClass,
      cargarDatos, generarLista, resetNocturno,
      actualizarEstado, toggleListo, exportarExcel,
    };
  }
}).mount('#app-limpieza-v2');
