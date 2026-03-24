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

        const totalFinal = computed(() => {
            let total = 0;
            actual.value.detalles.forEach(d => {
                if (d.incluye_desayuno) total += parseInt(d.pax);
            });
            return total;
        });

        const fetchLista = async () => {
            loading.value = true;
            try {
                const res = await axios.get(`api/desayunos/listar.php?mes=${filtro.value.mes}&anio=${filtro.value.anio}`);
                if (res.data.ok) lista.value = res.data.data;
            } catch (e) { console.error(e); }
            loading.value = false;
        };

        const nuevoRegistro = async () => {
            loading.value = true;
            try {
                const res = await axios.get('api/desayunos/hoy.php');
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
            // we need to fetch the full detail with sub-rows
            // ... for now, if the item passed is the header, we'll fetch its details
            // I'll simulate or add an endpoint for details if needed
            // Actually, my Controllers getHoy handled header + detail if existing.
            // Let's create a dedicated detail fetch or reuse hoy.php by passing date
            // I'll just fetch by date since it's UNIQUE
            loading.value = true;
            axios.get(`api/desayunos/hoy.php?fecha=${item.fecha}`)
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

        const guardar = async () => {
            guardando.value = true;
            const payload = {
                ...actual.value,
                pax_ajustado: totalFinal.value
            };
            try {
                const res = await axios.post('api/desayunos/guardar.php', payload);
                if (res.data.ok) {
                    Swal.fire({
                        title: '¡Guardado!',
                        text: res.data.msg,
                        icon: 'success',
                        timer: 1500
                    });
                    tab.value = 'lista';
                    fetchLista();
                } else {
                    Swal.fire('Atención', res.data.msg, 'warning');
                }
            } catch (e) {
                Swal.fire('Error', 'Error de conexión al servidor.', 'error');
            }
            guardando.value = false;
        };

        const formatFecha = (f) => {
            if (!f) return '';
            const d = f.split('-');
            return `${d[2]}/${d[1]}/${d[0]}`;
        };

        const imprimir = (id) => {
            window.open(`api/desayunos/imprimir.php?id=${id}`, '_blank');
        };

        onMounted(fetchLista);

        return {
            tab, lista, filtro, actual, loading, guardando, soloLectura,
            totalFinal, fetchLista, nuevoRegistro, verDetalle, guardar, formatFecha, imprimir
        };
    }
}).mount('#app-desayunos');
