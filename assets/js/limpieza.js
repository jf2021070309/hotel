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

        // Historial
        const listaHistorial = ref([]);
        const filtroHist = ref({ mes: new Date().getMonth() + 1, anio: 2026 });
        const detalleDia = ref([]);
        const fechaDetalle = ref('');

        const stats = computed(() => {
            return {
                salida: lista.value.filter(h => h.tipo_limpieza === 'salida').length,
                estadia: lista.value.filter(h => h.tipo_limpieza === 'estadía').length,
                programada: lista.value.filter(h => h.tipo_limpieza === 'programada').length
            };
        });

        const listaFiltrada = computed(() => {
            return lista.value.filter(h => {
                const condEstado = filtro.value.estado === 'todos' || h.estado === filtro.value.estado;
                const condTipo = filtro.value.tipo === 'todos' || h.tipo_limpieza === filtro.value.tipo;
                return condEstado && condTipo;
            });
        });

        const fetchHoy = async () => {
            loading.value = true;
            try {
                const res = await axios.get('api/limpieza.php?action=hoy');
                if (res.data.ok) {
                    lista.value = res.data.data;
                    yaGenerado.value = res.data.ya_generado;
                }
            } catch (e) { console.error(e); }
            loading.value = false;
        };

        const generarLista = async () => {
            loading.value = true;
            try {
                const res = await axios.post('api/limpieza.php?action=generar');
                if (res.data.ok) {
                    Swal.fire('¡Listo!', res.data.msg, 'success');
                    fetchHoy();
                }
            } catch (e) {
                Swal.fire('Error', 'No se pudo generar el cálculo.', 'error');
            }
            loading.value = false;
        };

        const cambiarEstado = async (h, nuevoEstado) => {
            const formData = new FormData();
            formData.append('id', h.id);
            formData.append('estado', nuevoEstado);
            try {
                const res = await axios.post('api/limpieza.php?action=actualizar', formData);
                if (res.data.ok) {
                   h.estado = nuevoEstado;
                   if (res.data.data.hora_inicio) h.hora_inicio = res.data.data.hora_inicio;
                   if (res.data.data.hora_fin) h.hora_fin = res.data.data.hora_fin;
                }
            } catch (e) { console.error(e); }
        };

        const asignarResponsable = async (h) => {
            const { value: nombre } = await Swal.fire({
                title: 'Asignar Personal',
                input: 'text',
                inputLabel: 'Nombre del responsable para la HAB ' + h.habitacion,
                inputPlaceholder: 'Ej: Maria Lopez',
                showCancelButton: true
            });
            if (nombre) {
                const formData = new FormData();
                formData.append('id', h.id);
                formData.append('responsable', nombre);
                axios.post('api/limpieza.php?action=actualizar', formData).then(() => {
                    h.responsable = nombre;
                });
            }
        };

        const mostrarMenu = async (h) => {
            const { value: action } = await Swal.fire({
                title: 'Opciones HAB ' + h.habitacion,
                input: 'select',
                inputOptions: {
                    'obs': 'Agregar Observación',
                    'reset': 'Resetear Tiempos (Admin)'
                },
                inputPlaceholder: 'Seleccioná una acción',
                showCancelButton: true
            });

            if (action === 'obs') {
                const { value: texto } = await Swal.fire({
                    title: 'Observación',
                    input: 'textarea',
                    inputValue: h.observacion,
                    showCancelButton: true
                });
                if (texto !== undefined) {
                    const formData = new FormData();
                    formData.append('id', h.id);
                    formData.append('observacion', texto);
                    axios.post('api/limpieza.php?action=observacion', formData).then(() => {
                        h.observacion = texto;
                    });
                }
            }
        };

        const getTipoClass = (t) => {
            if (t === 'salida') return 'bg-danger';
            if (t === 'estadía') return 'bg-warning text-dark';
            return 'bg-info';
        };

        const getEstadoClass = (e) => {
            if (e === 'pendiente') return 'bg-light text-dark border';
            if (e === 'en_proceso') return 'bg-warning text-dark';
            return 'bg-success';
        };

        // HISTORIAL FUNCTIONS
        const fetchHistorial = async () => {
            loading.value = true;
            try {
                const res = await axios.get(`api/limpieza.php?action=listar&mes=${filtroHist.value.mes}&anio=${filtroHist.value.anio}`);
                if (res.data.ok) listaHistorial.value = res.data.data;
            } catch (e) { console.error(e); }
            loading.value = false;
        };

        const verDetalle = (fecha) => {
            fechaDetalle.value = fecha;
            axios.get('api/limpieza.php?action=detalle&fecha=' + fecha).then(res => {
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

        onMounted(() => {
            if (document.getElementById('app-limpieza')) fetchHoy();
            if (document.getElementById('app-limpieza-historial')) fetchHistorial();
        });

        return {
            loading, yaGenerado, lista, filtro, stats, listaFiltrada,
            generarLista, cambiarEstado, asignarResponsable, mostrarMenu,
            getTipoClass, getEstadoClass,
            // Historial
            listaHistorial, filtro: (document.getElementById('app-limpieza-historial') ? filtroHist : filtro),
            detalleDia, fechaDetalle, fetchHistorial, verDetalle, formatFecha
        };
    }
};

createApp(appConfig).mount(document.getElementById('app-limpieza') ? '#app-limpieza' : '#app-limpieza-historial');
