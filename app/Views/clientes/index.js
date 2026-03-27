/**
 * app/Views/clientes/index.js
 */
const { createApp, ref, computed, onMounted } = Vue;

createApp({
    setup() {
        const clientes            = ref([]);
        const loading             = ref(true);
        const buscar              = ref('');
        const historial           = ref([]);
        const loadingHistorial    = ref(false);
        const clienteSeleccionado = ref(null);

        const clientesFiltrados = computed(() => {
            const q = buscar.value.toLowerCase().trim();
            if (!q) return clientes.value;
            return clientes.value.filter(c =>
                c.nombre.toLowerCase().includes(q) ||
                (c.dni || '').toLowerCase().includes(q)
            );
        });

        const totalPago = computed(() =>
            historial.value.reduce((s, r) => s + parseFloat(r.total_pago || 0), 0).toFixed(2)
        );
        const totalCobrado = computed(() =>
            historial.value.reduce((s, r) => s + parseFloat(r.total_cobrado || 0), 0).toFixed(2)
        );

        const cargar = async () => {
            loading.value = true;
            try {
                const res = await axios.get('../../../api/clientes.php?action=listar');
                clientes.value = res.data.data || [];
            } catch (e) {
                console.error('Error cargando clientes:', e);
            } finally {
                loading.value = false;
            }
        };

        const verHistorial = async (c) => {
            clienteSeleccionado.value = c;
            historial.value = [];
            loadingHistorial.value = true;
            new bootstrap.Modal('#modalHistorial').show();
            try {
                const res = await axios.get(`../../../api/clientes.php?action=historial&dni=${encodeURIComponent(c.dni)}`);
                if (res.data.ok) {
                    historial.value = res.data.data || [];
                } else {
                    console.error('API error:', res.data.message);
                    Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: res.data.message || 'Error al cargar historial', showConfirmButton: false, timer: 4000 });
                }
            } catch (e) {
                console.error('Network error:', e);
            } finally {
                loadingHistorial.value = false;
            }
        };

        const fmtFecha = (f) => {
            if (!f) return '—';
            const d = f.split(' ')[0]; // quitar hora si viene como datetime
            const [y, m, day] = d.split('-');
            return `${day}/${m}/${y}`;
        };

        onMounted(cargar);

        return {
            clientes, loading, buscar,
            historial, loadingHistorial, clienteSeleccionado,
            clientesFiltrados, totalPago, totalCobrado,
            verHistorial, fmtFecha
        };
    }
}).mount('#app-clientes');
