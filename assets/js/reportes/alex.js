/**
 * assets/js/reportes/alex.js
 */
const { createApp, ref, computed, onMounted } = Vue;

createApp({
    setup() {
        const filtro = ref({
            mes: new Date().getMonth() + 1,
            anio: new Date().getFullYear()
        });
        const data = ref([]);
        const loading = ref(false);

        const totalGastos = computed(() => {
            return data.value.reduce((acc, g) => acc + parseFloat(g.monto), 0);
        });

        const fetchData = async () => {
            loading.value = true;
            try {
                const res = await axios.get(`/hotel/api/reportes.php?action=alex&mes=${filtro.value.mes}&anio=${filtro.value.anio}`);
                if (res.data.ok) {
                    data.value = res.data.data;
                }
            } catch (e) {
                console.error(e);
            }
            loading.value = false;
        };

        const getMesNombre = (m) => {
            const meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
            return meses[m - 1];
        };

        const formatFecha = (f) => {
            if (!f) return '';
            const d = f.split('-');
            return `${d[2]}/${d[1]}/${d[0]}`;
        };

        onMounted(fetchData);

        return { filtro, data, loading, totalGastos, fetchData, getMesNombre, formatFecha };
    }
}).mount('#app-alex');
