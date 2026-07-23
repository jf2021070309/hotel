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
            metodo: '',
            activeTab: 'GENERAL'
        });
        const data = ref([]);
        const resumen = ref({ ingresos_hospedaje: 0, otros_ingresos: 0, egresos_operativos: 0, gastos_caja_chica: 0, gastos_yape: 0, utilidad_neta: 0 });
        const resumenDesglosado = ref({});
        const egresos = ref({});
        const consumos = ref([]);
        const loading = ref(false);
        const colapsados = ref({});
        
        const voucherActual = ref({});
        const loadingVoucher = ref(false);
        let modalVoucherInstance = null;
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
                if (filtroAvanzado.value.activeTab === 'SUNAT') {
                    const comp = item.comprobante ? item.comprobante.toUpperCase() : '';
                    if (!comp || comp === '' || comp === '-' || comp.includes('NINGUN')) {
                        return false;
                    }
                }
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

            // 3. Procesar Egresos (para asegurar que el día aparezca si solo hubo egresos y sumar totales)
            if (egresos.value) {
                Object.keys(egresos.value).forEach(fecha => {
                    let hasEgresos = false;
                    let totalManana = 0;
                    let totalTarde = 0;
                    
                    if (egresos.value[fecha] && egresos.value[fecha]['MAÑANA']) {
                        Object.values(egresos.value[fecha]['MAÑANA']).forEach(val => {
                            let m = parseFloat(val) || 0;
                            if (m > 0) hasEgresos = true;
                            totalManana += m;
                        });
                    }
                    if (egresos.value[fecha] && egresos.value[fecha]['TARDE']) {
                        Object.values(egresos.value[fecha]['TARDE']).forEach(val => {
                            let m = parseFloat(val) || 0;
                            if (m > 0) hasEgresos = true;
                            totalTarde += m;
                        });
                    }
                    
                    if (hasEgresos && !groups[fecha]) {
                        groups[fecha] = { hospedaje: [], consumos: [], totales: {}, totales_manana: {}, totales_tarde: {} };
                    }
                    
                    if (groups[fecha]) {
                        groups[fecha].egresos_manana = totalManana;
                        groups[fecha].egresos_tarde = totalTarde;
                        groups[fecha].egresos_total = totalManana + totalTarde;
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

        const tomarCaptura = async (fecha) => {
            const el = document.getElementById('dia-container-' + fecha);
            if (!el) return;
            try {
                if (colapsados.value[fecha]) {
                    colapsados.value[fecha] = false;
                    await Vue.nextTick();
                }
                const canvas = await html2canvas(el, { scale: 2, backgroundColor: '#f8f9fa' });
                const link = document.createElement('a');
                link.download = `Detalle-Dia-${fecha}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
            } catch (e) {
                console.error("Error al capturar", e);
                alert("Error al tomar captura");
            }
        };

        const tomarCapturaTurno = async (fecha, turno) => {
            const el = document.getElementById('resumen-dia-' + fecha);
            if (!el) return;
            try {
                // Hacer un clon temporal si queremos ocultar filas, o simplemente tomar la tabla de resumen
                const canvas = await html2canvas(el, { scale: 2, backgroundColor: '#ffffff' });
                const link = document.createElement('a');
                link.download = `Resumen-${turno.toUpperCase()}-${fecha}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
            } catch (e) {
                console.error("Error al capturar turno", e);
                alert("Error al tomar captura del resumen");
            }
        };

        const abrirVoucher = (item, tipo) => {
            voucherActual.value = {
                id: item.pago_id || item.id,
                tipo: tipo,
                comprobante_b64: item.comprobante_b64,
                preview: null
            };
            const input = document.getElementById('voucherInput');
            if (input) input.value = '';
            if (modalVoucherInstance) modalVoucherInstance.show();
        };

        const onVoucherSelect = (e) => {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (ev) => {
                voucherActual.value.preview = ev.target.result;
            };
            reader.readAsDataURL(file);
        };

        const subirVoucher = async () => {
            if (!voucherActual.value.preview) return;
            loadingVoucher.value = true;
            try {
                const res = await axios.post(`${window.MENDOZA_CONFIG.apiEndpoint}?action=subir_voucher`, {
                    tipo: voucherActual.value.tipo,
                    id: voucherActual.value.id,
                    b64: voucherActual.value.preview
                });
                if (res.data.ok) {
                    alert("Comprobante guardado correctamente");
                    if (modalVoucherInstance) modalVoucherInstance.hide();
                    fetchData(true);
                } else {
                    alert(res.data.msg || "Error al guardar comprobante");
                }
            } catch (e) {
                console.error(e);
                alert("Error al guardar comprobante");
            } finally {
                loadingVoucher.value = false;
            }
        };

        onMounted(() => {
            fetchData();
            pollingTimer = setInterval(() => fetchData(true), 10000);
            const el = document.getElementById('modalVoucher');
            if (el) modalVoucherInstance = new bootstrap.Modal(el);
        });

        onUnmounted(() => {
            if (pollingTimer) clearInterval(pollingTimer);
        });

        return { 
            filtro, data, groupedData, resumen, resumenDesglosado, egresos, colapsados, loading, 
            fetchData, toggleDia, getResumenTurno, getBadgeClass, getPrefix, getMesNombre, formatCurrency, formatNumber, getSym, exportar,
            filtroAvanzado,
            verDetalle,
            voucherActual, loadingVoucher,
            tomarCaptura, tomarCapturaTurno, abrirVoucher, onVoucherSelect, subirVoucher
        };
    }
}).mount('#app-mendoza');
