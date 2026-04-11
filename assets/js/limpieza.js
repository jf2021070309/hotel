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
        const personalLimpieza = ref([]);

        // Historial
        const listaHistorial = ref([]);
        const filtroHist = ref({ mes: new Date().getMonth() + 1, anio: new Date().getFullYear() });
        const detalleDia = ref([]);
        const fechaDetalle = ref('');

        const stats = computed(() => ({
            salida:     lista.value.filter(h => h.tipo_limpieza === 'salida').length,
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

        const fetchPersonal = async () => {
            try {
                const res = await axios.get('/hotel/api/usuarios.php?action=personal_limpieza');
                personalLimpieza.value = res.data.data || [];
            } catch (e) { /* silencio si no hay usuario limpieza aún */ }
        };

        const fetchHoy = async () => {
            loading.value = true;
            try {
                const res = await axios.get('/hotel/api/limpieza.php?action=hoy');
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
                const res = await axios.post('/hotel/api/limpieza.php?action=generar');
                if (res.data.ok) {
                    Swal.fire('¡Listo!', res.data.msg, 'success');
                    fetchHoy();
                }
            } catch (e) {
                Swal.fire('Error', 'No se pudo generar el cálculo.', 'error');
            }
            loading.value = false;
        };

        const tareaEdit = ref({});
        let tareaTarget = null;

        const abrirEdicion = (h) => {
            tareaTarget = h;
            tareaEdit.value = {
                id: h.id,
                habitacion: h.habitacion,
                estado: h.estado,
                usuario_id: h.usuario_id || '',
                observacion: h.observacion || ''
            };
            new bootstrap.Modal(document.getElementById('modalEdicionLimpieza')).show();
        };

        const guardarEdicion = async () => {
            const formData = new FormData();
            formData.append('id', tareaEdit.value.id);
            formData.append('estado', tareaEdit.value.estado);
            formData.append('observacion', tareaEdit.value.observacion);
            formData.append('usuario_id', tareaEdit.value.usuario_id);

            loading.value = true;
            try {
                const res = await axios.post('/hotel/api/limpieza.php?action=actualizar', formData);
                if (res.data.ok) {
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Limpieza actualizada', showConfirmButton: false, timer: 3000 });
                    bootstrap.Modal.getInstance(document.getElementById('modalEdicionLimpieza')).hide();
                    
                    // Actualizar UI
                    if(tareaTarget) {
                        tareaTarget.estado = tareaEdit.value.estado;
                        tareaTarget.usuario_id = tareaEdit.value.usuario_id;
                        tareaTarget.observacion = tareaEdit.value.observacion;
                        
                        // Actualizar nombre mostrado buscando en la lista de personal
                        const p = personalLimpieza.value.find(x => x.id == tareaEdit.value.usuario_id);
                        tareaTarget.responsable_nombre = p ? p.nombre : '';
                        
                        // Actualizar horas internamente (simplificado - ideal recargar o inyectar del API)
                        if (res.data.data.hora_inicio) tareaTarget.hora_inicio = res.data.data.hora_inicio;
                        if (res.data.data.hora_fin)    tareaTarget.hora_fin    = res.data.data.hora_fin;
                    }
                } else {
                    Swal.fire('Error', res.data.msg, 'error');
                }
            } catch (e) { 
                Swal.fire('Error', 'Hubo un problema de conexión', 'error');
            }
            loading.value = false;
        };

        const marcarListaRapido = async (h) => {
            const formData = new FormData();
            formData.append('id', h.id);
            formData.append('estado', 'lista');
            formData.append('observacion', h.observacion || '');
            formData.append('usuario_id', h.usuario_id || '');

            loading.value = true;
            try {
                const res = await axios.post('/hotel/api/limpieza.php?action=actualizar', formData);
                if (res.data.ok) {
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Habitación marcada como lista', showConfirmButton: false, timer: 2000 });
                    h.estado = 'lista';
                    if (res.data.data && res.data.data.hora_fin) h.hora_fin = res.data.data.hora_fin;
                } else {
                    Swal.fire('Error', res.data.msg, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Hubo un problema de conexión', 'error');
            }
            loading.value = false;
        };

        const getColorTop = (h) => {
            if (h.estado === 'mantenimiento') return '#343a40'; 
            if (h.estado === 'lista') return '#198754';
            if (h.tipo_limpieza === 'salida') return '#dc3545';
            if (h.tipo_limpieza === 'estadía') return '#ffc107';
            return '#0dcaf0';
        };

        const getTipoClass = (t) => {
            if (t === 'salida')    return 'bg-danger';
            if (t === 'estadía')   return 'bg-warning text-dark';
            return 'bg-info text-dark';
        };

        const getEstadoClass = (e) => {
            let est = String(e).toLowerCase();
            if (est === 'pendiente')  return 'bg-light text-dark border';
            if (est === 'en proceso' || est === 'en_proceso') return 'bg-warning text-dark';
            if (est === 'mantenimiento') return 'bg-danger text-white border border-danger';
            return 'bg-success text-white';
        };

        // HISTORIAL
        const fetchHistorial = async () => {
            loading.value = true;
            try {
                const res = await axios.get(`/hotel/api/limpieza.php?action=listar&mes=${filtroHist.value.mes}&anio=${filtroHist.value.anio}`);
                if (res.data.ok) listaHistorial.value = res.data.data;
            } catch (e) { console.error(e); }
            loading.value = false;
        };

        const verDetalle = (fecha) => {
            fechaDetalle.value = fecha;
            axios.get('/hotel/api/limpieza.php?action=detalle&fecha=' + fecha).then(res => {
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
            loading, yaGenerado, lista, filtro, stats, listaFiltrada, personalLimpieza,
            generarLista, tareaEdit, abrirEdicion, guardarEdicion, fmtHora,
            getTipoClass, getEstadoClass, getColorTop, marcarListaRapido,
            listaHistorial, filtroHist,
            detalleDia, fechaDetalle, fetchHistorial, verDetalle, formatFecha, formatFechaHora
        };
    }
};

createApp(appConfig).mount(
    document.getElementById('app-limpieza') ? '#app-limpieza' : '#app-limpieza-historial'
);
