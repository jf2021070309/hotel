/**
 * assets/js/limpieza.js
 */
const { createApp, ref, computed, onMounted } = Vue;

const appConfig = {
    setup() {
        const loading = ref(false);
        const yaGenerado = ref(false);
        const lista = ref([]);
        const filtro = ref({ estado: 'todos', tipo: 'todos' });
        const filtroFecha = ref(new Date().toLocaleDateString('en-CA'));
        const personalLimpieza = ref([]);

        // Historial
        const listaHistorial = ref([]);
        const filtroHist = ref({ mes: new Date().getMonth() + 1, anio: new Date().getFullYear() });
        const detalleDia = ref([]);
        const fechaDetalle = ref('');

        const stats = computed(() => ({
            salida:     lista.value.filter(h => h.tipo_limpieza === 'salida' || h.tipo_limpieza === 'estimacion').length,
            estadia:    lista.value.filter(h => h.tipo_limpieza === 'estadía').length,
            programada: lista.value.filter(h => h.tipo_limpieza === 'programada').length
        }));

        const listaFiltrada = computed(() => {
            return lista.value.filter(h => {
                const condEstado = filtro.value.estado === 'todos' || h.estado === filtro.value.estado;
                const condTipo   = filtro.value.tipo === 'todos' || h.tipo_limpieza === filtro.value.tipo;
                return condEstado && condTipo;
            });
        });

        /** Extrae sólo HH:MM de un string que puede ser "HH:MM:SS" o "0000-00-00 HH:MM:SS" */
        const fmtHora = (val) => {
            if (!val || val.startsWith('00:00') || val.startsWith('0000')) return '';
            // Si viene como "2026-03-27 14:30:00" extraemos la parte de tiempo
            const match = val.match(/(\d{2}:\d{2})/);
            return match ? match[1] : val;
        };

        const apiUrl = (rel) => {
            try {
                const base = document.baseURI || window.location.href;
                return new URL(rel, base).href;
            } catch (e) {
                return rel;
            }
        };

        const fetchPersonal = async () => {
            try {
                const res = await axios.get(apiUrl('../../../api/usuarios.php?action=personal_limpieza'));
                personalLimpieza.value = res.data.data || [];
                console.debug('[Limpieza] fetchPersonal ->', {
                    ok: !!res.data, status: res.status, dataLength: (res.data && res.data.data) ? res.data.data.length : 0,
                    payload: res.data
                });
            } catch (e) { /* silencio si no hay usuario limpieza aún */ }
        };

        const fetchHoy = async () => {
            loading.value = true;
            try {
                const hoy = new Date().toLocaleDateString('en-CA');
                const action = filtroFecha.value === hoy ? 'hoy' : `detalle_fecha&fecha=${filtroFecha.value}`;
                
                const res = await axios.get(apiUrl(`../../../api/limpieza.php?action=${action}`));
                console.groupCollapsed('%c[Limpieza] fetchHoy', 'color:teal;font-weight:bold', { action, fecha: filtroFecha.value, server: new Date().toISOString() });
                console.debug('response', res.data);
                if (res.data.ok) {
                    lista.value = res.data.data;
                    yaGenerado.value = res.data.ya_generado || (filtroFecha.value !== hoy);
                    console.debug('lista length', lista.value.length, 'stats', {
                        salida: lista.value.filter(h => h.tipo_limpieza === 'salida' || h.tipo_limpieza === 'estimacion').length,
                        estadia: lista.value.filter(h => h.tipo_limpieza === 'estadía').length,
                        programada: lista.value.filter(h => h.tipo_limpieza === 'programada').length
                    });
                } else {
                    console.warn('[Limpieza] fetchHoy -> API responded with ok=false', res.data);
                }
                console.groupEnd();
            } catch (e) { console.error(e); }
            loading.value = false;
        };

        const fetchPorFecha = () => {
            fetchHoy();
        };

        const generarLista = async () => {
            loading.value = true;
            try {
                const res = await axios.post(apiUrl('../../../api/limpieza.php?action=generar'));
                console.debug('[Limpieza] generarLista ->', res.data);
                if (res.data.ok) {
                    Swal.fire('¡Listo!', res.data.msg, 'success');
                    fetchHoy();
                } else {
                    Swal.fire('Atención', res.data.msg || 'Respuesta no esperada', 'info');
                }
            } catch (e) {
                Swal.fire('Error', 'No se pudo generar el cálculo.', 'error');
            }
            loading.value = false;
        };

        const tareaEdit = ref({});
        let tareaTarget = null;

        const toggleListo = async (h) => {
            const nuevoEstado = (h.estado === 'lista') ? 'pendiente' : 'lista';
            const formData = new FormData();
            formData.append('id', h.id);
            formData.append('estado', nuevoEstado);

            loading.value = true;
            try {
                const res = await axios.post(apiUrl('../../../api/limpieza.php?action=actualizar'), formData);
                console.debug('[Limpieza] toggleListo ->', {id: h.id, nuevoEstado, response: res.data});
                if (res.data.ok) {
                    const msg = nuevoEstado === 'lista' ? 'Habitación marcada como lista' : 'Habitación marcada como pendiente';
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: msg, showConfirmButton: false, timer: 2000 });
                    h.estado = nuevoEstado;
                    if (res.data.data && res.data.data.hora_fin) h.hora_fin = res.data.data.hora_fin;
                    else if (nuevoEstado === 'pendiente') h.hora_fin = null;
                } else {
                    Swal.fire('Error', res.data.msg, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Hubo un problema de conexión', 'error');
                console.error('[Limpieza] toggleListo error', e);
            }
            loading.value = false;
        };

        const getColorTop = (h) => {
            if (h.estado === 'mantenimiento' || h.tipo_limpieza === 'estimacion') return '#343a40'; 
            if (h.estado === 'sucio') return '#9ca3af';
            if (h.estado === 'lista') return '#198754';
            if (h.tipo_limpieza === 'salida') return '#dc3545';
            if (h.tipo_limpieza === 'estadía') return '#ffc107';
            return '#0dcaf0';
        };

        const getTipoClass = (t) => {
            if (t === 'salida')    return 'bg-danger';
            if (t === 'estimacion') return 'bg-dark text-white';
            if (t === 'estadía')   return 'bg-warning text-dark';
            return 'bg-info text-dark';
        };

        const getEstadoClass = (e) => {
            let est = String(e).toLowerCase();
            if (est === 'pendiente')  return 'bg-light text-dark border';
            if (est === 'en proceso' || est === 'en_proceso') return 'bg-warning text-dark';
            if (est === 'mantenimiento') return 'bg-danger text-white border border-danger';
            if (est === 'sucio') return 'bg-dark text-white';
            return 'bg-success text-white';
        };

        const getRoomStateClass = (state) => {
            const s = String(state).toLowerCase();
            if (s === 'mantenimiento') return 'bg-danger text-white';
            if (s === 'sucio') return 'bg-dark text-white';
            if (s === 'limpieza') return 'bg-secondary text-white';
            return 'bg-light text-dark';
        };

        // Tailwind class helpers for stronger visual cues
        const getRoomStateTwClass = (state) => {
            const s = String(state || '').toLowerCase();
            if (s === 'mantenimiento') return 'bg-red-600 text-white';
            if (s === 'sucio') return 'bg-amber-800 text-white';
            if (s === 'limpieza') return 'bg-gray-500 text-white';
            return 'bg-gray-100 text-gray-800';
        };

        const getRoomBgTwClass = (state) => {
            const s = String(state || '').toLowerCase();
            if (s === 'mantenimiento') return 'bg-red-50';
            if (s === 'sucio') return 'bg-amber-50';
            if (s === 'limpieza') return 'bg-gray-100';
            if (s === 'ocupado' || s === 'ocupada') return 'bg-pink-50';
            return 'bg-white';
        };

        const getEstadoTwClass = (estado) => {
            const e = String(estado || '').toLowerCase();
            if (e === 'pendiente') return 'bg-gray-100 text-gray-800 border';
            if (e === 'en proceso' || e === 'en_proceso') return 'bg-yellow-300 text-gray-900';
            if (e === 'mantenimiento') return 'bg-red-600 text-white';
            if (e === 'sucio') return 'bg-amber-800 text-white';
            if (e === 'lista') return 'bg-green-600 text-white';
            return 'bg-green-600 text-white';
        };

        const getTipoTwClass = (t) => {
            if (t === 'salida') return 'bg-red-500 text-white';
            if (t === 'estimacion') return 'bg-gray-800 text-white';
            if (t === 'estadía') return 'bg-yellow-400 text-gray-800';
            return 'bg-teal-400 text-gray-800';
        };

        const getColorTopTwClass = (h) => {
            if (!h) return '';
            if (h.estado === 'mantenimiento' || h.tipo_limpieza === 'estimacion') return 'border-t-4 border-gray-800';
            if (h.estado === 'sucio') return 'border-t-4 border-amber-800';
            if (h.estado === 'lista') return 'border-t-4 border-green-600';
            if (h.tipo_limpieza === 'salida') return 'border-t-4 border-red-500';
            if (h.tipo_limpieza === 'estadía') return 'border-t-4 border-yellow-400';
            return 'border-t-4 border-cyan-400';
        };

        // HISTORIAL
        const fetchHistorial = async () => {
            loading.value = true;
            try {
                const res = await axios.get(apiUrl(`../../../api/limpieza.php?action=listar&mes=${filtroHist.value.mes}&anio=${filtroHist.value.anio}`));
                console.debug('[Limpieza] fetchHistorial ->', { mes: filtroHist.value.mes, anio: filtroHist.value.anio, resp: res.data });
                if (res.data.ok) listaHistorial.value = res.data.data;
            } catch (e) { console.error(e); }
            loading.value = false;
        };

        const verDetalle = (fecha) => {
            fechaDetalle.value = fecha;
            axios.get(apiUrl('../../../api/limpieza.php?action=detalle&fecha=' + fecha)).then(res => {
                console.debug('[Limpieza] verDetalle ->', { fecha, resp: res.data });
                if (res.data.ok) {
                    detalleDia.value = res.data.data;
                    new bootstrap.Modal(document.getElementById('modalDetalle')).show();
                }
            });
        };

        const formatFecha = (f) => {
            if (!f) return '';
            const [y, m, d] = f.split('-');
            return `${d}/${m}/${y}`;
        };

        const formatFechaHora = (fh, rowFecha) => {
            if (!fh || fh.startsWith('0000')) return '';
            let dtString = fh.includes(' ') || fh.includes('T') ? fh.replace(' ', 'T') : `${rowFecha}T${fh}`;
            const dt = new Date(dtString);
            if (isNaN(dt)) return fh;
            return dt.toLocaleString('es-PE', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' }).replace(',', '');
        };

        onMounted(() => {
            fetchPersonal();
            if (document.getElementById('app-limpieza'))           fetchHoy();
            if (document.getElementById('app-limpieza-historial'))  fetchHistorial();
        });

        return {
            loading, yaGenerado, lista, filtro, filtroFecha, stats, listaFiltrada, personalLimpieza,
            generarLista, tareaEdit, toggleListo, fmtHora, fetchPorFecha,
            getTipoClass, getEstadoClass, getColorTop,
            getRoomStateClass,
            // Tailwind helpers
            getRoomStateTwClass, getEstadoTwClass, getTipoTwClass, getColorTopTwClass, getRoomBgTwClass,
            listaHistorial, filtroHist,
            detalleDia, fechaDetalle, fetchHistorial, verDetalle, formatFecha, formatFechaHora
        };
    }
};

createApp(appConfig).mount(
    document.getElementById('app-limpieza') ? '#app-limpieza' : '#app-limpieza-historial'
);
