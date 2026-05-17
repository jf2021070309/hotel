/**
 * app/Views/clientes/index.js
 * Lógica unificada para el Control de Clientes, Clientes Frecuentes y Corporativos.
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
        
        // Filtro activo: 'todos' o 'frecuentes'
        const filtroFrecuente     = ref('todos');

        // Formulario de Nuevo / Editar Cliente
        const isEditMode          = ref(false);
        const guardando           = ref(false);
        const nuevoCliente        = ref({
            nombre: '',
            dni: '',
            tipo_doc: 'DNI',
            nacionalidad: 'Peruana',
            ciudad: '',
            celular: '',
            email: '',
            es_empresa: false,
            ruc: '',
            razon_social: ''
        });

        const clientesFiltrados = computed(() => {
            let filtrados = clientes.value;
            
            // 1. Filtro por tipo de cliente (Todos vs Frecuentes [>= 2 estadías])
            if (filtroFrecuente.value === 'frecuentes') {
                filtrados = filtrados.filter(c => parseInt(c.total_estadias || 0) >= 2);
            }

            // 2. Filtro por buscador (nombre, DNI, RUC, Razón Social)
            const q = buscar.value.toLowerCase().trim();
            if (!q) return filtrados;
            
            return filtrados.filter(c =>
                (c.nombre || '').toLowerCase().includes(q) ||
                (c.dni || '').toLowerCase().includes(q) ||
                (c.ruc || '').toLowerCase().includes(q) ||
                (c.razon_social || '').toLowerCase().includes(q)
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
                Swal.fire('Error', 'No se pudo cargar el listado de clientes.', 'error');
            } finally {
                loading.value = false;
            }
        };

        const abrirModalNuevo = () => {
            isEditMode.value = false;
            nuevoCliente.value = {
                nombre: '',
                dni: '',
                tipo_doc: 'DNI',
                nacionalidad: 'Peruana',
                ciudad: '',
                celular: '',
                email: '',
                es_empresa: false,
                ruc: '',
                razon_social: ''
            };
            const modalEl = document.getElementById('modalNuevoCliente');
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        };

        const editarCliente = (c) => {
            isEditMode.value = true;
            nuevoCliente.value = {
                nombre: c.nombre,
                dni: c.dni,
                tipo_doc: c.tipo_doc || 'DNI',
                nacionalidad: c.nacionalidad || 'Peruana',
                ciudad: c.ciudad || '',
                celular: c.celular || '',
                email: c.email || '',
                es_empresa: !!c.ruc || !!c.razon_social,
                ruc: c.ruc || '',
                razon_social: c.razon_social || ''
            };
            const modalEl = document.getElementById('modalNuevoCliente');
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        };

        const guardarNuevoCliente = async () => {
            if (!nuevoCliente.value.nombre || !nuevoCliente.value.dni) {
                Swal.fire('Atención', 'El Nombre y el Número de Documento son obligatorios', 'warning');
                return;
            }
            guardando.value = true;
            try {
                const res = await axios.post('../../../api/clientes.php?action=guardar', nuevoCliente.value);
                
                if (res.data.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: isEditMode.value ? '¡Datos actualizados!' : '¡Cliente registrado!',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    const modalEl = document.getElementById('modalNuevoCliente');
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                    cargar(); // Recargar la lista unificada
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.data.msg || 'No se pudo registrar los datos' });
                }
            } catch (e) {
                console.error(e);
                const errMsg = e.response && e.response.data && e.response.data.msg
                    ? e.response.data.msg
                    : 'Error de comunicación con el servidor.';
                Swal.fire({ icon: 'error', title: 'Error al guardar', text: errMsg });
            } finally {
                guardando.value = false;
            }
        };

        const verHistorial = async (c) => {
            clienteSeleccionado.value = c;
            historial.value = [];
            loadingHistorial.value = true;
            
            const modalEl = document.getElementById('modalHistorial');
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
            
            try {
                const res = await axios.get(`../../../api/clientes.php?action=historial&dni=${encodeURIComponent(c.dni)}`);
                if (res.data.ok) {
                    historial.value = res.data.data || [];
                } else {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: 'Error al cargar historial',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            } catch (e) {
                console.error('Error al cargar historial:', e);
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
            const d = f.split(' ')[0]; // Limpiar hora si viene en formato datetime
            const [y, m, day] = d.split('-');
            return `${day}/${m}/${y}`;
        };

        onMounted(() => {
            cargar();
            // Soporte para pre-filtrar Clientes Frecuentes desde URL
            const params = new URLSearchParams(window.location.search);
            if (params.get('filter') === 'frecuentes') {
                filtroFrecuente.value = 'frecuentes';
            }
        });

        return {
            clientes,
            loading,
            buscar,
            historial,
            loadingHistorial,
            clienteSeleccionado,
            filtroFrecuente,
            isEditMode,
            guardando,
            nuevoCliente,
            clientesFiltrados,
            totalPago,
            totalCobrado,
            cargar,
            abrirModalNuevo,
            editarCliente,
            guardarNuevoCliente,
            verHistorial,
            crearEstadiaRapida,
            crearReservaRapida,
            fmtFecha
        };
    }
}).mount('#app-clientes');
