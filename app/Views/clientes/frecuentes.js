/**
 * app/Views/clientes/frecuentes.js
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
            // Primero filtramos por frecuencia (mínimo 3 estadías)
            let filtrados = clientes.value.filter(c => parseInt(c.total_estadias) >= 3);
            
            // Luego filtramos por búsqueda
            const q = buscar.value.toLowerCase().trim();
            if (!q) return filtrados;
            
            return filtrados.filter(c =>
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
                // Usamos el mismo endpoint de clientes, la lógica de frecuencia la hace el JS
                const res = await axios.get('../../../api/clientes.php?action=listar');
                clientes.value = res.data.data || [];
            } catch (e) {
                console.error('Error cargando clientes frecuentes:', e);
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
                    Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Error al cargar historial', showConfirmButton: false, timer: 3000 });
                }
            } catch (e) {
                console.error('Error:', e);
            } finally {
                loadingHistorial.value = false;
            }
        };

        const crearEstadiaRapida = (c) => {
            const data = {
                dni: c.dni,
                nombre: c.nombre,
                nacionalidad: c.nacionalidad,
                ciudad: c.ciudad,
                tipo_doc: c.tipo_doc,
                frecuente: true,
                visitas: c.total_estadias
            };
            localStorage.setItem('quick_checkin_pax', JSON.stringify(data));
            window.location.href = '../rooming/index.php';
        };

        const crearReservaRapida = (c) => {
            const data = {
                dni: c.dni,
                nombre: c.nombre,
                nacionalidad: c.nacionalidad,
                ciudad: c.ciudad,
                tipo_doc: c.tipo_doc,
                frecuente: true,
                visitas: c.total_estadias
            };
            localStorage.setItem('quick_reserva_pax', JSON.stringify(data));
            window.location.href = '../reservas/index.php';
        };

        const fmtFecha = (f) => {
            if (!f) return '—';
            const d = f.split(' ')[0];
            const [y, m, day] = d.split('-');
            return `${day}/${m}/${y}`;
        };

        onMounted(cargar);

        return {
            clientes, loading, buscar,
            historial, loadingHistorial, clienteSeleccionado,
            clientesFiltrados, totalPago, totalCobrado,
            verHistorial, fmtFecha, crearEstadiaRapida, crearReservaRapida
        };
    }
}).mount('#app-clientes-frecuentes');
