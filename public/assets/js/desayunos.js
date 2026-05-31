/**
 * assets/js/desayunos.js
 */
const { createApp, ref, computed, onMounted } = Vue;
const API = window.SERVER_DATA?.apiBase ?? '../../../ajax/desayunos.php';

createApp({
    setup() {
        const loading = ref(false);
        const guardando = ref(false);
        const soloLectura = ref(false);

        const actual = ref({
            id: null,
            fecha: window.SERVER_DATA?.hoy ?? new Date().toISOString().split('T')[0],
            pax_calculado: 0,
            pax_ajustado: 0,
            observacion: '',
            detalles: []
        });

        const busqueda = ref('');
        const detallesFiltrados = computed(() => {
            if (!actual.value.detalles) return [];
            const q = busqueda.value.toLowerCase().trim();
            if (!q) return actual.value.detalles;
            return actual.value.detalles.filter(d => {
                const habMatch = d.habitacion && d.habitacion.toString().toLowerCase().includes(q);
                const titMatch = d.titular && d.titular.toLowerCase().includes(q);
                return habMatch || titMatch;
            });
        });

        const totalHuespedes = computed(() => {
            return actual.value.detalles.reduce((s, d) => s + parseInt(d.pax || 0), 0);
        });

        const totalFinal = computed(() => {
            return actual.value.detalles.reduce((s, d) => s + (d.incluye_desayuno ? parseInt(d.pax || 0) : 0), 0);
        });

        const nuevoRegistro = async (fecha = null) => {
            loading.value = true;
            try {
                const url = fecha
                    ? `${API}?action=hoy&fecha=${fecha}&t=${Date.now()}`
                    : `${API}?action=hoy&t=${Date.now()}`;
                const res = await axios.get(url);
                if (res.data.ok) {
                    actual.value = res.data.data;
                    verificarSoloLectura(actual.value.fecha);
                }
            } catch (e) {
                console.error('Error al cargar desayunos:', e);
            }
            loading.value = false;
        };

        const verDetallePorFecha = () => {
            if (!actual.value.fecha) return;
            const url = new URL(window.location);
            url.searchParams.set('fecha', actual.value.fecha);
            window.history.pushState({}, '', url);
            nuevoRegistro(actual.value.fecha);
        };

        const verificarSoloLectura = (fecha) => {
            const hoy = new Date().toISOString().split('T')[0];
            const hora = new Date().getHours();
            soloLectura.value = (fecha < hoy) || (fecha === hoy && hora >= 12);
        };

        let autoGuardarTimer = null;
        const autoGuardar = async () => {
            if (soloLectura.value) return;
            guardando.value = true;
            try {
                const payload = { ...actual.value, pax_ajustado: totalFinal.value };
                const res = await axios.post(`${API}?action=guardar`, payload);
                if (res.data.ok && res.data.id) actual.value.id = res.data.id;
            } catch (e) {
                console.error('Error en auto-guardado', e);
            }
            setTimeout(() => { guardando.value = false; }, 500);
        };

        const triggerAutoGuardarDebounced = () => {
            if (autoGuardarTimer) clearTimeout(autoGuardarTimer);
            autoGuardarTimer = setTimeout(autoGuardar, 1000);
        };

        const exportarReporte = () => {
            if (typeof XLSX === 'undefined') return;
            const filas = actual.value.detalles.map(d => ({
                'N° HAB.':          d.habitacion,
                'HUESPED TITULAR':  d.titular,
                'CANT. PAX':        d.pax,
                'INCLUYE DESAYUNO': d.incluye_desayuno ? 'SÍ' : 'NO'
            }));
            const ws = XLSX.utils.json_to_sheet(filas);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Desayunos');
            XLSX.writeFile(wb, `Reporte_Desayunos_${actual.value.fecha}.xlsx`);
        };

        const formatFecha = (f) => {
            if (!f) return '';
            const d = f.split('-');
            return `${d[2]}/${d[1]}/${d[0]}`;
        };

        onMounted(async () => {
            const params = new URLSearchParams(window.location.search);
            const fechaUrl = params.get('fecha');
            await nuevoRegistro(fechaUrl || null);
        });

        return {
            actual, loading, guardando, soloLectura,
            busqueda, detallesFiltrados,
            totalHuespedes, totalFinal,
            verDetallePorFecha, autoGuardar, triggerAutoGuardarDebounced,
            exportarReporte, formatFecha
        };
    }
}).mount('#app-desayunos');
