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
        
        // Filtro activo: 'todos', 'frecuentes' o 'regulares'
        const filtroFrecuente     = ref('todos');

        // Filtro por tipo: 'todos', 'personas', 'empresas'
        const filtroTipo          = ref('todos');

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
            razon_social: '',
            vip: false
        });

        const clientesFiltrados = computed(() => {
            let filtrados = clientes.value;
            
            // 1. Filtro por tipo de cliente (Todos vs Frecuentes vs Regulares)
            if (filtroFrecuente.value === 'frecuentes') {
                filtrados = filtrados.filter(c => c.vip == 1);
            } else if (filtroFrecuente.value === 'regulares') {
                filtrados = filtrados.filter(c => c.vip != 1);
            }

            // 2. Filtro por tipo de persona/empresa (Todos vs Personas [sin RUC] vs Empresas [con RUC])
            if (filtroTipo.value === 'personas') {
                filtrados = filtrados.filter(c => !c.ruc || c.ruc.trim() === '');
            } else if (filtroTipo.value === 'empresas') {
                filtrados = filtrados.filter(c => c.ruc && c.ruc.trim() !== '');
            }

            // 3. Filtro por buscador (nombre, DNI, RUC, Razón Social)
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
                razon_social: '',
                vip: false
            };
            const modalEl = document.getElementById('modalNuevoCliente');
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        };

        const editarCliente = (c) => {
            isEditMode.value = true;
            nuevoCliente.value = {
                old_dni: c.dni, // Guardamos el DNI original para identificar y cascajear cambios de DNI
                nombre: c.nombre,
                dni: c.dni,
                tipo_doc: c.tipo_doc || 'DNI',
                nacionalidad: c.nacionalidad || 'Peruana',
                ciudad: c.ciudad || '',
                celular: c.celular || '',
                email: c.email || '',
                es_empresa: !!c.ruc || !!c.razon_social,
                ruc: c.ruc || '',
                razon_social: c.razon_social || '',
                vip: c.vip == 1
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

        const toggleVipStatus = async (c) => {
            const nuevoEstado = c.vip == 1 ? 0 : 1;
            
            // Optimistic update for instant visual response
            const index = clientes.value.findIndex(item => item.dni === c.dni);
            if (index !== -1) {
                clientes.value[index].vip = nuevoEstado;
            }

            try {
                const res = await axios.get(`../../../api/clientes.php?action=toggle_vip&dni=${encodeURIComponent(c.dni)}&vip=${nuevoEstado}`);
                if (!res.data.ok) {
                    // Revert if error
                    if (index !== -1) {
                        clientes.value[index].vip = nuevoEstado == 1 ? 0 : 1;
                    }
                    Swal.fire('Error', res.data.msg || 'No se pudo actualizar el estado VIP.', 'error');
                } else {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: nuevoEstado === 1 ? '¡Cliente Frecuente Añadido!' : '¡Cliente Frecuente Removido!',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            } catch (e) {
                console.error(e);
                if (index !== -1) {
                    clientes.value[index].vip = nuevoEstado == 1 ? 0 : 1;
                }
                const serverMsg = e.response?.data?.msg || 'Error de red al conectar con el servidor.';
                Swal.fire('Error', serverMsg, 'error');
            }
        };

        const fmtFecha = (f) => {
            if (!f) return '—';
            const d = f.split(' ')[0]; // Limpiar hora si viene en formato datetime
            const [y, m, day] = d.split('-');
            return `${day}/${m}/${y}`;
        };

        const exportarExcel = () => {
            const data = clientesFiltrados.value;
            if (data.length === 0) {
                Swal.fire('Atención', 'No hay datos disponibles para exportar', 'warning');
                return;
            }

            // Cabeceras de columnas
            const headers = ['Nombre Completo', 'Tipo Doc', 'Documento/DNI', 'Celular', 'Email', 'Empresa (Razón Social)', 'RUC', '¿Frecuente?', 'Total Estadías', 'Última Visita'];
            
            // Construir CSV con BOM para visualización correcta de acentos en Excel
            let csvContent = "\uFEFF"; 
            csvContent += headers.join(';') + "\r\n";

            data.forEach(c => {
                const row = [
                    c.nombre || '',
                    c.tipo_doc || 'DNI',
                    c.dni || '',
                    c.celular || '',
                    c.email || '',
                    c.razon_social || c.empresa || '',
                    c.ruc || '',
                    c.vip == 1 ? 'SÍ' : 'NO',
                    c.total_estadias || '0',
                    c.ultima_visita ? fmtFecha(c.ultima_visita) : ''
                ];
                
                // Sanitizar texto para evitar corromper el CSV
                const cleanedRow = row.map(val => {
                    let text = String(val).replace(/(\r\n|\n|\r)/gm, " ").trim();
                    text = text.replace(/;/g, ","); // Evitar saltos por punto y coma
                    return text;
                });
                csvContent += cleanedRow.join(';') + "\r\n";
            });

            // Descarga de archivo
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement("a");
            
            const dateStr = new Date().toISOString().slice(0, 10);
            link.setAttribute("href", url);
            link.setAttribute("download", `Clientes_Platinium_Hotel_${dateStr}.csv`);
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            Swal.fire({
                icon: 'success',
                title: '¡Exportación exitosa!',
                text: `Se han exportado ${data.length} registros a Excel (CSV).`,
                showConfirmButton: false,
                timer: 1500
            });
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
            filtroTipo,
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
            toggleVipStatus,
            exportarExcel,
            fmtFecha
        };
    }
}).mount('#app-clientes');
