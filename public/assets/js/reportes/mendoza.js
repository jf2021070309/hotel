/**
 * assets/js/reportes/mendoza.js
 */
const { createApp, ref, computed, onMounted, onUnmounted } = Vue;

createApp({
    setup() {
        const filtro = ref({
            mes: new Date().getMonth() + 1,
            anio: new Date().getFullYear()
        });
        const filtroAvanzado = ref({
            search: '',
            metodo: ''
        });
        const data = ref([]);
        const resumen = ref({ ingresos_hospedaje: 0, otros_ingresos: 0, egresos_operativos: 0, gastos_caja_chica: 0, gastos_yape: 0, utilidad_neta: 0 });
        const resumenDesglosado = ref({});
        const egresos = ref({});
        const consumos = ref([]);
        const loading = ref(false);
        const colapsados = ref({});
        let pollingTimer = null;

        const fetchData = async (silent = false) => {
            if (!silent) loading.value = true;
            try {
                const res = await axios.get(`${window.MENDOZA_CONFIG.apiEndpoint}?action=mendoza&mes=${filtro.value.mes}&anio=${filtro.value.anio}`);
                if (res.data.ok) {
                    const payload = res.data.data;
                    data.value = Array.isArray(payload.data) ? payload.data : [];
                    consumos.value = Array.isArray(payload.consumos) ? payload.consumos : [];
                    resumen.value = payload.resumen || {};
                    resumenDesglosado.value = payload.resumen_desglosado || {};
                    egresos.value = payload.egresos || {};
                    
                    // Inicializar colapsados: solo si la fecha es nueva preservamos el estado actual
                    const hoy = new Date().toISOString().split('T')[0];
                    const tempCol = { ...colapsados.value };
                    const fechasUnicas = [...new Set([...data.value.map(d => d.pago_fecha), ...consumos.value.map(c => c.fecha)])];
                    
                    fechasUnicas.forEach(f => {
                        if (tempCol[f] === undefined) {
                            tempCol[f] = (f !== hoy);
                        }
                    });
                    colapsados.value = tempCol;
                }
            } catch (e) {
                console.error(e);
            } finally {
                if (!silent) loading.value = false;
            }
        };

        const filteredHospedaje = computed(() => {
            return data.value.filter(item => {
                const matchesSearch = !filtroAvanzado.value.search || 
                                     item.habitacion.toLowerCase().includes(filtroAvanzado.value.search.toLowerCase()) ||
                                     item.medio_label.toLowerCase().includes(filtroAvanzado.value.search.toLowerCase());
                const matchesMetodo = !filtroAvanzado.value.metodo || 
                                     item.medio_label.toUpperCase().includes(filtroAvanzado.value.metodo.toUpperCase());
                return matchesSearch && matchesMetodo;
            });
        });

        const filteredConsumos = computed(() => {
            return consumos.value.filter(item => {
                const matchesSearch = !filtroAvanzado.value.search || 
                                     item.habitacion.toLowerCase().includes(filtroAvanzado.value.search.toLowerCase()) ||
                                     item.producto.toLowerCase().includes(filtroAvanzado.value.search.toLowerCase());
                const matchesMetodo = !filtroAvanzado.value.metodo || 
                                     item.metodo_pago.toUpperCase().includes(filtroAvanzado.value.metodo.toUpperCase());
                return matchesSearch && matchesMetodo;
            });
        });

        const groupedData = computed(() => {
            const groups = {};
            const consumosUsados = new Set();
            
            // 1. Procesar Hospedaje (Anticipos)
            filteredHospedaje.value.forEach(item => {
                const fecha = item.pago_fecha;
                if (!groups[fecha]) groups[fecha] = { hospedaje: [], consumos: [], totales: {}, totales_manana: {}, totales_tarde: {} };
                
                // Smart Merge: ¿Es un pago de consumo?
                const match = filteredConsumos.value.find(c => 
                    !consumosUsados.has(c.id) && 
                    c.stay_id == item.stay_id && 
                    parseFloat(c.total) == parseFloat(item.total_fila) &&
                    c.fecha == item.pago_fecha
                );

                if (match) {
                    item.concept_override = `Consumo: ${match.producto} (x${match.match_cantidad || match.cantidad})`;
                    consumosUsados.add(match.id);
                }

                groups[fecha].hospedaje.push(item);
                
                // Sumar a totales del turno
                const label = item.medio_label;
                const monto = parseFloat(item.total_fila || 0);
                groups[fecha].totales[label] = (groups[fecha].totales[label] || 0) + monto;
                if (item.turno === 'MAÑANA') {
                    groups[fecha].totales_manana[label] = (groups[fecha].totales_manana[label] || 0) + monto;
                } else if (item.turno === 'TARDE') {
                    groups[fecha].totales_tarde[label] = (groups[fecha].totales_tarde[label] || 0) + monto;
                }
            });

            // 2. Procesar Consumos (Ventas Directas o no vinculadas)
            filteredConsumos.value.forEach(item => {
                if (consumosUsados.has(item.id)) return;

                const fecha = item.fecha;
                if (!groups[fecha]) groups[fecha] = { hospedaje: [], consumos: [], totales: {}, totales_manana: {}, totales_tarde: {} };
                groups[fecha].consumos.push(item);

                // Mapear medio de pago de consumo a label estándar si es necesario
                const label = item.metodo_pago || 'EFECTIVO'; 
                // Normalizar label (Ej: 'POS' -> 'POS S/')
                let standardLabel = label;
                if (label === 'POS') standardLabel = 'POS S/';
                if (label === 'EFECTIVO') standardLabel = 'EFEC S/';

                const monto = parseFloat(item.total || 0);
                groups[fecha].totales[standardLabel] = (groups[fecha].totales[standardLabel] || 0) + monto;
                if (item.turno === 'MAÑANA') {
                    groups[fecha].totales_manana[standardLabel] = (groups[fecha].totales_manana[standardLabel] || 0) + monto;
                } else if (item.turno === 'TARDE') {
                    groups[fecha].totales_tarde[standardLabel] = (groups[fecha].totales_tarde[standardLabel] || 0) + monto;
                }
            });

            // 3. Procesar Egresos (para asegurar que el día aparezca si solo hubo egresos)
            if (egresos.value) {
                Object.keys(egresos.value).forEach(fecha => {
                    let hasEgresos = false;
                    ['MAÑANA', 'TARDE'].forEach(t => {
                        if (egresos.value[fecha] && egresos.value[fecha][t]) {
                            Object.values(egresos.value[fecha][t]).forEach(val => {
                                if (parseFloat(val) > 0) hasEgresos = true;
                            });
                        }
                    });
                    if (hasEgresos && !groups[fecha]) {
                        groups[fecha] = { hospedaje: [], consumos: [], totales: {}, totales_manana: {}, totales_tarde: {} };
                    }
                });
            }

            // Ordenar por fecha descendente
            const sortedGroups = {};
            Object.keys(groups).sort((a, b) => new Date(b) - new Date(a)).forEach(key => {
                sortedGroups[key] = groups[key];
            });

            return sortedGroups;
        });

        const toggleDia = (fecha) => {
            colapsados.value[fecha] = !colapsados.value[fecha];
        };

        const getBadgeClass = (label, isText = false) => {
            if (label.includes('POS')) return isText ? 'text-primary' : 'bg-pos';
            if (label.includes('YAPE')) return isText ? 'text-info' : 'bg-yape';
            if (label.includes('TRANSFER')) return isText ? 'text-success' : 'bg-transfer';
            return isText ? 'text-dark' : 'bg-cash';
        };

        const getPrefix = (label) => {
            if (label.includes('CLP')) return 'CLP';
            if (label.includes('$') || label.includes('USD')) return 'USD';
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

        const verDetalle = (stayId) => {
            if (!stayId) return;
            // Redireccionar al rooming con el stay_id para que el deep-linking lo abra
            window.location.href = `${window.MENDOZA_CONFIG.roomingUrl}?stay_id=${stayId}`;
        };

        const exportar = () => {
            const columnas = [
                { header: 'FECHA', key: 'pago_fecha' },
                { header: 'TURNO', key: 'turno' },
                { header: 'HAB', key: 'habitacion' },
                { header: 'TIPO', key: 'tipo_hab' },
                { header: 'PAX', key: 'pax' },
                { header: 'CHECK IN', key: 'check_in' },
                { header: 'CHECK OUT', key: 'check_out' },
                { header: 'NOCHES', key: 'noches' },
                { header: 'CANAL', key: 'canal' },
                { header: 'MEDIO', key: 'medio_label' },
                { header: 'MONEDA', key: 'moneda' },
                { header: 'TOTAL', key: 'monto' },
                { header: 'COMPROBANTE', key: 'comprobante' }
            ];
            const titulo = `Reporte Mendoza ${getMesNombre(filtro.value.mes)} ${filtro.value.anio}`;
            exportarExcel(titulo, columnas, data.value, titulo);
        };

        onMounted(() => {
            fetchData();
            pollingTimer = setInterval(() => fetchData(true), 10000);
        });

        onUnmounted(() => {
            if (pollingTimer) clearInterval(pollingTimer);
        });

        return { 
            filtro, data, groupedData, resumen, resumenDesglosado, egresos, colapsados, loading, 
            fetchData, toggleDia, getResumenTurno, getBadgeClass, getPrefix, getMesNombre, formatCurrency, formatNumber, getSym, exportar,
            filtroAvanzado,
            verDetalle
        };
    }
}).mount('#app-mendoza');
