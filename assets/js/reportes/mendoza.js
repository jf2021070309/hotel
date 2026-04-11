/**
 * assets/js/reportes/mendoza.js
 */
const { createApp, ref, onMounted, onUnmounted } = Vue;

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
        let pollingTimer = null;

        const fetchData = async (silent = false) => {
            if (!silent) loading.value = true;
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
            } catch (e) {
                console.error(e);
            } finally {
                if (!silent) loading.value = false;
            }
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

        const getBadgeClass = (label, isText = false) => {
            if (label.includes('POS')) return isText ? 'text-primary' : 'bg-primary text-white';
            if (label.includes('YAPE')) return isText ? 'text-info' : 'bg-info text-white';
            if (label.includes('TRANSFER')) return isText ? 'text-success' : 'bg-success text-white';
            return isText ? 'text-dark' : 'bg-dark text-white';
        };

        const getPrefix = (label) => {
            if (label.includes('$') || label.includes('USD')) return 'USD';
            if (label.includes('P$') || label.includes('CLP')) return 'CLP';
            return 'S/';
        };

        const getResumenTurno = (items) => {
            const res = {};
            items.forEach(i => {
                const label = i.medio_label;
                const monto = parseFloat(i.total_fila || 0);
                res[label] = (res[label] || 0) + monto;
            });
            return res;
        };

        const getMesNombre = (m) => {
            const meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
            return meses[m - 1];
        };

        const formatNumber = (val, decimals = 2) => {
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(parseFloat(val || 0));
        };

        const formatCurrency = (val, symbol = 'S/') => {
            const n = parseFloat(val || 0);
            return n > 0 ? `${symbol} ${formatNumber(n)}` : '-';
        };

        const getSym = (mon) => {
            if (mon === 'USD') return 'USD';
            if (mon === 'CLP') return 'CLP';
            return 'S/';
        };

        onMounted(() => {
            fetchData();
            pollingTimer = setInterval(() => fetchData(true), 10000);
        });

        onUnmounted(() => {
            if (pollingTimer) clearInterval(pollingTimer);
        });

        return { 
            filtro, data, groupedData, resumen, resumenDesglosado, colapsados, loading, 
            fetchData, toggleDia, getResumenTurno, getBadgeClass, getPrefix, getMesNombre, formatCurrency, formatNumber, getSym 
        };
    }
}).mount('#app-mendoza');
