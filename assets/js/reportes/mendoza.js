/**
 * assets/js/reportes/mendoza.js
 */
const { createApp, ref, onMounted } = Vue;

createApp({
    setup() {
        const filtro = ref({
            mes: new Date().getMonth() + 1,
            anio: new Date().getFullYear()
        });
        const data = ref([]);
        const resumen = ref({
            ingresos_hospedaje: 0,
            otros_ingresos: 0,
            egresos_operativos: 0,
            utilidad_neta: 0
        });
        const loading = ref(false);

        const fetchData = async () => {
            loading.value = true;
            try {
                const res = await axios.get(`/hotel/api/reportes.php?action=mendoza&mes=${filtro.value.mes}&anio=${filtro.value.anio}`);
                if (res.data.ok) {
                    data.value = res.data.data;
                    resumen.value = res.data.resumen;
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

        const exportar = () => {
            window.print();
        };

        onMounted(fetchData);

        return { filtro, data, resumen, loading, fetchData, getMesNombre, exportar };
    }
}).mount('#app-mendoza');
