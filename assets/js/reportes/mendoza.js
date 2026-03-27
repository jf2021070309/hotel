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
        const resumen = ref({ ingresos_hospedaje: 0, otros_ingresos: 0, egresos_operativos: 0, gastos_caja_chica: 0, gastos_yape: 0, utilidad_neta: 0 });
        const resumenDesglosado = ref({});
        const loading = ref(false);
        const colapsados = ref({}); // { '2026-03-27': false, '2026-03-26': true }

        const fetchData = async () => {
            loading.value = true;
            try {
                const res = await axios.get(`/hotel/api/reportes.php?action=mendoza&mes=${filtro.value.mes}&anio=${filtro.value.anio}`);
                if (res.data.ok) {
                    data.value = res.data.data;
                    resumen.value = res.data.resumen;
                    resumenDesglosado.value = res.data.resumen_desglosado;
                    
                    // Inicializar colapsados: hoy expandido, resto colapsado
                    const hoy = new Date().toISOString().split('T')[0];
                    const tempCol = {};
                    const fechasUnicas = [...new Set(data.value.map(d => d.pago_fecha))];
                    fechasUnicas.forEach(f => {
                        tempCol[f] = (f !== hoy);
                    });
                    colapsados.value = tempCol;
                }
            } catch (e) {
                console.error(e);
            }
            loading.value = false;
        };

        const groupedData = Vue.computed(() => {
            const groups = {};
            data.value.forEach(item => {
                const fecha = item.pago_fecha;
                if (!groups[fecha]) groups[fecha] = { MAÑANA: [], TARDE: [] };
                groups[fecha][item.turno].push(item);
            });
            return groups;
        });

        const toggleDia = (fecha) => {
            colapsados.value[fecha] = !colapsados.value[fecha];
        };

        const getSubtotalTurno = (items, key) => {
            return items.reduce((sum, i) => sum + parseFloat(i[key] || 0), 0).toFixed(2);
        };

        const getMesNombre = (m) => {
            const meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
            return meses[m - 1];
        };

        const formatCurrency = (val, symbol = 'S/') => {
            const n = parseFloat(val || 0);
            return n > 0 ? `${symbol} ${n.toFixed(2)}` : '-';
        };

        const getSym = (mon) => {
            if (mon === 'USD') return 'USD';
            if (mon === 'CLP') return 'CLP';
            return 'S/';
        };

        onMounted(fetchData);

        return { 
            filtro, data, groupedData, resumen, resumenDesglosado, colapsados, loading, 
            fetchData, toggleDia, getSubtotalTurno, getMesNombre, formatCurrency, getSym 
        };
    }
}).mount('#app-mendoza');
