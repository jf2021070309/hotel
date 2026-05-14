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

        const nuevoCliente = ref({
            nombre: '', dni: '', tipo_doc: 'DNI', nacionalidad: 'Peruana', ciudad: '', celular: '', email: '',
            es_empresa: false, ruc: '', razon_social: ''
        });
        const guardando = ref(false);

        const clientesFiltrados = computed(() => {
            // Mostrar todos los clientes de la base de datos sin restricción de visitas
            let filtrados = clientes.value;
            
            // Luego filtramos por búsqueda
            const q = buscar.value.toLowerCase().trim();
            if (!q) return filtrados;
            
            return filtrados.filter(c =>
                (c.nombre || '').toLowerCase().includes(q) ||
                (c.dni || '').toLowerCase().includes(q) ||
                (c.ruc || '').toLowerCase().includes(q) ||
                (c.razon_social || '').toLowerCase().includes(q)
            );
        });

        const abrirModalNuevo = () => {
            nuevoCliente.value = {
                nombre: '', dni: '', tipo_doc: 'DNI', nacionalidad: 'Peruana', ciudad: '', celular: '', email: '',
                es_empresa: false, ruc: '', razon_social: ''
            };
            const modalEl = document.getElementById('modalNuevoCliente');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        };

        const editarCliente = (c) => {
            nuevoCliente.value = {
                nombre: c.nombre,
                dni: c.dni,
                tipo_doc: c.tipo_doc,
                nacionalidad: c.nacionalidad,
                ciudad: c.ciudad || '',
                celular: c.celular,
                email: c.email || '',
                es_empresa: !!c.ruc,
                ruc: c.ruc || '',
                razon_social: c.razon_social || ''
            };
            const modalEl = document.getElementById('modalNuevoCliente');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        };

        const guardarNuevoCliente = async () => {
            if (!nuevoCliente.value.nombre || !nuevoCliente.value.dni) {
                Swal.fire('Error', 'Nombre y Documento son obligatorios', 'error');
                return;
            }
            guardando.value = true;
            try {
                // Enviamos el objeto directo como JSON (más limpio y compatible con nuestra API)
                const res = await axios.post('../../../api/clientes.php?action=guardar', nuevoCliente.value);
                
                if (res.data.ok) {
                    Swal.fire({ icon: 'success', title: '¡Cliente registrado!', showConfirmButton: false, timer: 1500 });
                    const modalEl = document.getElementById('modalNuevoCliente');
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                    cargar(); // Recargar la lista
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.data.msg || 'No se pudo guardar' });
                }
            } catch (e) {
                console.error(e);
                Swal.fire({ icon: 'error', title: 'Error de conexión' });
            } finally {
                guardando.value = false;
            }
        };

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
                celular: c.celular,
                email: c.email,
                tipo_doc: c.tipo_doc,
                frecuente: true,
                visitas: c.total_estadias,
                ruc: c.ruc,
                empresa: c.razon_social
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
                celular: c.celular,
                email: c.email,
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
            verHistorial, fmtFecha, crearEstadiaRapida, crearReservaRapida,
            abrirModalNuevo, editarCliente, nuevoCliente, guardando, guardarNuevoCliente
        };
    }
}).mount('#app-clientes-frecuentes');
