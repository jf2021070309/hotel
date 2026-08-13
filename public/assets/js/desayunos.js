/**
 * assets/js/desayunos.js
 * Vue 3 — Panel Desayunos (tabla Excel manual)
 */
'use strict';

const { createApp, ref, computed, onMounted } = Vue;

createApp({
  setup() {
    // ── Estado ─────────────────────────────────────────────────
    const API      = window.SERVER_DATA?.apiBase ?? '../../../ajax/desayunos.php';
    const API_HABS = window.SERVER_DATA?.apiBase?.replace('desayunos.php', 'habitaciones.php') ?? '../../../ajax/habitaciones.php';
    const fecha    = ref(window.SERVER_DATA?.hoy ?? new Date().toISOString().split('T')[0]);
    const lista    = ref([]);
    const loading  = ref(false);

    // ── Computed ────────────────────────────────────────────────
    const totalPax = computed(() => {
      return lista.value.reduce((sum, h) => {
        const val = parseInt(h.pax);
        return sum + (isNaN(val) ? 0 : val);
      }, 0);
    });

    // ── Helpers ────────────────────────────────────────────────
    function getRowClass(h) {
      if (h.room_estado === 'mantenimiento') return 'table-danger';
      return '';
    }

    // ── API calls ──────────────────────────────────────────────
    async function cargarDatos() {
      loading.value = true;
      try {
        const [resDesayuno, resHabs] = await Promise.all([
          axios.get(`${API}?action=detalle_manual_fecha&fecha=${fecha.value}`),
          axios.get(`${API_HABS}?action=todos`)
        ]);

        const desayunoData = resDesayuno.data.data || [];
        const habsData = resHabs.data.data || [];

        const combined = habsData.map(h => {
          const d = desayunoData.find(x => String(x.habitacion) === String(h.numero)) || {};
          return {
            id: d.id || null,
            habitacion_id: h.id,
            habitacion: h.numero,
            tipo_hab: h.tipo,
            room_estado: h.estado,
            pax: d.pax || '',
            observaciones: d.observaciones || ''
          };
        });

        // Ordenar numéricamente
        combined.sort((a,b) => String(a.habitacion).localeCompare(String(b.habitacion), undefined, {numeric: true}));
        lista.value = combined;
      } catch (e) {
        console.error('[Desayunos] cargarDatos error', e);
        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar la lista.', timer: 2500, showConfirmButton: false });
      }
      loading.value = false;
    }

    async function guardarCambios() {
      loading.value = true;
      try {
        const body = { registros: lista.value, fecha: fecha.value };
        const res = await axios.post(`${API}?action=guardar_cambios_manuales`, body);
        if (res.data.ok) {
          Swal.fire({
            icon: 'success',
            title: '¡Cambios Guardados!',
            text: 'Las anotaciones manuales han sido registradas permanentemente.',
            timer: 2000,
            showConfirmButton: false
          });
          await cargarDatos();
        } else {
          Swal.fire({ icon: 'error', title: 'Error', text: res.data.msg || 'No se pudieron guardar.' });
        }
      } catch (e) {
        console.error('[Desayunos] guardarCambios error', e);
        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo guardar el cambio.' });
      }
      loading.value = false;
    }

    // ── Exportar PDF ───────────────────────────────────────────
    function imprimirHoja() {
      window.print();
    }

    // ── Watchers / Hooks ───────────────────────────────────────
    function changeFecha() {
      cargarDatos();
    }

    onMounted(() => {
      cargarDatos();
    });

    return {
      fecha,
      lista,
      loading,
      totalPax,
      getRowClass,
      changeFecha,
      guardarCambios,
      imprimirHoja
    };
  }
}).mount('#app-desayunos');
