/**
 * assets/js/desayunos.js
 */
const { createApp, ref, computed, onMounted } = Vue;

createApp({
    setup() {
        const tab = ref('lista'); // 'lista' | 'detalle'
        const loading = ref(false);
        const guardando = ref(false);
        const soloLectura = ref(false);
        
        const filtro = ref({
            mes: new Date().getMonth() + 1,
            anio: new Date().getFullYear()
        });

        const lista = ref([]);
        const actual = ref({
            id: null,
            fecha: '',
            pax_calculado: 0,
            pax_ajustado: 0,
            observacion: '',
            detalles: []
        });

        const totalHuespedes = computed(() => {
            let total = 0;
            actual.value.detalles.forEach(d => {
                total += parseInt(d.pax || 0);
            });
            return total;
        });

        const totalFinal = computed(() => {
            let total = 0;
            actual.value.detalles.forEach(d => {
                if (d.incluye_desayuno) total += parseInt(d.pax || 0);
            });
            return total;
        });

        const fetchLista = async () => {
            loading.value = true;
            try {
                const res = await axios.get(`../../../api/desayunos.php?action=listar&mes=${filtro.value.mes}&anio=${filtro.value.anio}&t=${Date.now()}`);
                if (res.data.ok) lista.value = res.data.data;
            } catch (e) { console.error(e); }
            loading.value = false;
        };

        const nuevoRegistro = async () => {
            loading.value = true;
            try {
                const res = await axios.get(`../../../api/desayunos.php?action=hoy&t=${Date.now()}`);
                if (res.data.ok) {
                    actual.value = res.data.data;
                    tab.value = 'detalle';
                    verificarSoloLectura(actual.value.fecha);
                }
            } catch (e) {
                Swal.fire('Error', 'No se pudo generar el cálculo automático.', 'error');
            }
            loading.value = false;
        };

        const verDetalle = (item) => {
            loading.value = true;
            
            // Sincronizar URL
            const url = new URL(window.location);
            url.searchParams.set('fecha', item.fecha);
            window.history.pushState({}, '', url);

            axios.get(`../../../api/desayunos.php?action=hoy&fecha=${item.fecha}&t=${Date.now()}`)
                .then(res => {
                    if (res.data.ok) {
                        actual.value = res.data.data;
                        tab.value = 'detalle';
                        verificarSoloLectura(actual.value.fecha);
                    }
                })
                .catch(e => console.error(e))
                .finally(() => loading.value = false);
        };

        const verDetallePorFecha = () => {
            if (!actual.value.fecha) return;
            // Actualizar URL sin recargar
            const url = new URL(window.location);
            url.searchParams.set('fecha', actual.value.fecha);
            window.history.pushState({}, '', url);

            verDetalle({ fecha: actual.value.fecha });
        };

        const verificarSoloLectura = (fecha) => {
            const hoy = new Date().toISOString().split('T')[0];
            const horaActual = new Date().getHours();
            
            if (fecha < hoy) {
                soloLectura.value = true;
            } else if (fecha === hoy && horaActual >= 12) {
                soloLectura.value = true;
            } else {
                soloLectura.value = false;
            }
        };

        let autoGuardarTimer = null;
        const autoGuardar = async () => {
            if (soloLectura.value) return;
            guardando.value = true;
            
            const payload = {
                ...actual.value,
                pax_ajustado: totalFinal.value
            };
            
            try {
                const res = await axios.post('../../../api/desayunos.php?action=guardar', payload);
                if (res.data.ok) {
                    if (res.data.id) actual.value.id = res.data.id;
                }
            } catch (e) {
                console.error("Error en auto-guardado", e);
            }
            
            // Simular un pequeño delay para que el usuario vea el estado "Guardando"
            setTimeout(() => {
                guardando.value = false;
            }, 500);
        };

        const triggerAutoGuardarDebounced = () => {
            if (autoGuardarTimer) clearTimeout(autoGuardarTimer);
            autoGuardarTimer = setTimeout(autoGuardar, 1000);
        };

        const guardar = async () => {
            guardando.value = true;
            const payload = {
                ...actual.value,
                pax_ajustado: totalFinal.value
            };
            try {
                const res = await axios.post('../../../api/desayunos.php?action=guardar', payload);
                if (res.data.ok) {
                    // Actualizar ID local
                    if (res.data.id) actual.value.id = res.data.id;

                    Swal.fire({
                        title: '¡Guardado!',
                        text: res.data.msg,
                        icon: 'success',
                        timer: 1500
                    });
                    tab.value = 'lista';
                    
                    // Limpiar URL
                    const url = new URL(window.location);
                    url.searchParams.delete('fecha');
                    window.history.pushState({}, '', url);

                    fetchLista();
                } else {
                    Swal.fire('Atención', res.data.msg, 'warning');
                }
            } catch (e) {
                Swal.fire('Error', 'Error de conexión al servidor.', 'error');
            }
            guardando.value = false;
        };

        const exportarReporte = () => {
            const columnas = [
                { header: 'N° HAB.', key: 'habitacion' },
                { header: 'HUESPED TITULAR', key: 'titular' },
                { header: 'CANT. PAX', key: 'pax' },
                { header: 'ESTADO DESAYUNO', key: 'estado_str' }
            ];

            const filas = actual.value.detalles.map(d => ({
                ...d,
                estado_str: d.incluye_desayuno ? 'SÍ' : 'NO'
            }));

            const nombreArchivo = `Reporte_Desayunos_${actual.value.fecha}`;
            const tituloTabla = `REPORTE DIARIO DE DESAYUNOS (${actual.value.fecha})`;
            exportarExcel(tituloTabla, columnas, filas, nombreArchivo);
        };

        const volverALista = () => {
            tab.value = 'lista';
            const url = new URL(window.location);
            url.searchParams.delete('fecha');
            window.history.pushState({}, '', url);
            fetchLista();
        };

        const formatFecha = (f) => {
            if (!f) return '';
            const d = f.split('-');
            return `${d[2]}/${d[1]}/${d[0]}`;
        };

        const imprimir = (id) => {
            window.open(`../../../api/desayunos.php?action=imprimir&id=${id}`, '_blank');
        };

        onMounted(async () => {
            await fetchLista();
            
            // Si hay fecha en la URL, cargar esa. Si no, hoy.
            const params = new URLSearchParams(window.location.search);
            const fechaUrl = params.get('fecha');
            
            if (fechaUrl) {
                verDetalle({ fecha: fechaUrl });
            } else {
                nuevoRegistro();
            }
        });

        return {
            tab, lista, filtro, actual, loading, guardando, soloLectura,
            totalHuespedes, totalFinal, fetchLista, nuevoRegistro, verDetalle, verDetallePorFecha, guardar, autoGuardar, triggerAutoGuardarDebounced, volverALista, formatFecha, imprimir, exportarReporte
        };
    }
}).mount('#app-desayunos');
