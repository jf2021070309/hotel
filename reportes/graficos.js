/**
 * reportes/graficos.js — Dashboard de gráficos con Chart.js
 * Vue 3 Options API
 */
window.__appGraficos = Vue.createApp({
    data() {
        return {
            loading: true,
            mensual: null,  // datos de api/reportes.php?tipo=mensual
            graficos: null,  // datos de api/reportes.php?tipo=graficos
            charts: {}     // instancias de Chart.js activas
        };
    },

    methods: {
        // ── Formatos ──────────────────────────────────────────────
        fmtNum(n) {
            return parseFloat(n || 0).toFixed(2);
        },

        // ── Red de datos ──────────────────────────────────────────
        anio() { return parseInt(document.getElementById('selAnio')?.value || new Date().getFullYear()); },
        mes() { return parseInt(document.getElementById('selMes')?.value || (new Date().getMonth() + 1)); },

        async cargar() {
            this.loading = true;
            this.destruirCharts();
            try {
                const [rMensual, rGraficos] = await Promise.all([
                    fetch(`../api/reportes.php?tipo=mensual&year=${this.anio()}&month=${this.mes()}`).then(r => r.json()),
                    fetch(`../api/reportes.php?tipo=graficos&year=${this.anio()}&month=${this.mes()}`).then(r => r.json())
                ]);
                this.mensual = rMensual.data ?? null;
                this.graficos = rGraficos.data ?? null;
            } catch (e) {
                console.error('Error al cargar gráficos:', e);
            } finally {
                this.loading = false;
                await this.$nextTick();
                this.renderCharts();
            }
        },

        cambiarFecha() {
            this.cargar();
        },

        destruirCharts() {
            Object.values(this.charts).forEach(c => { if (c) c.destroy(); });
            this.charts = {};
        },

        // ── Renderizado de charts ─────────────────────────────────
        renderCharts() {
            this.renderLinea();
            this.renderDona();
            this.renderMetodo();
            this.renderTopHab();
        },

        // 1. Gráfico de línea: Ingresos vs Gastos por día
        renderLinea() {
            const ctx = document.getElementById('chartLinea');
            if (!ctx || !this.mensual) return;

            const ing = this.mensual.ingresos_por_dia || [];
            const gas = this.mensual.gastos_por_dia || [];

            // Construir set de fechas unificado
            const dias = [...new Set([...ing.map(d => d.dia), ...gas.map(d => d.dia)])].sort();
            const ingMap = Object.fromEntries(ing.map(d => [d.dia, parseFloat(d.total)]));
            const gasMap = Object.fromEntries(gas.map(d => [d.dia, parseFloat(d.total)]));

            const labels = dias.map(d => d.substring(8)); // solo el día dd

            this.charts.linea = new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Ingresos',
                            data: dias.map(d => ingMap[d] || 0),
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37,99,235,.12)',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4
                        },
                        {
                            label: 'Gastos',
                            data: dias.map(d => gasMap[d] || 0),
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239,68,68,.10)',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'top' } },
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: v => 'S/ ' + v } }
                    }
                }
            });
        },

        // 2. Gráfico de dona: Ocupación actual
        renderDona() {
            const ctx = document.getElementById('chartDona');
            if (!ctx || !this.graficos) return;
            const { hab_libres, hab_ocupadas } = this.graficos;

            this.charts.dona = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Libre', 'Ocupada'],
                    datasets: [{
                        data: [hab_libres || 0, hab_ocupadas || 0],
                        backgroundColor: ['#10b981', '#f59e0b'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    cutout: '65%',
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        },

        // 3. Barras: Método de pago
        renderMetodo() {
            const ctx = document.getElementById('chartMetodo');
            if (!ctx || !this.mensual) return;

            this.charts.metodo = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Efectivo', 'Tarjeta'],
                    datasets: [{
                        label: 'Monto (S/)',
                        data: [
                            parseFloat(this.mensual.efectivo || 0),
                            parseFloat(this.mensual.tarjeta || 0)
                        ],
                        backgroundColor: ['#10b981', '#2563eb'],
                        borderRadius: 6,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: v => 'S/ ' + v } }
                    }
                }
            });
        },

        // 4. Barras horizontales: Top habitaciones
        renderTopHab() {
            const ctx = document.getElementById('chartTopHab');
            if (!ctx || !this.graficos?.top_hab) return;
            const top = this.graficos.top_hab;

            this.charts.topHab = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: top.map(h => h.habitacion),
                    datasets: [{
                        label: 'Ingresos (S/)',
                        data: top.map(h => parseFloat(h.total)),
                        backgroundColor: [
                            '#2563eb', '#7c3aed', '#10b981', '#f59e0b', '#ef4444', '#6366f1'
                        ],
                        borderRadius: 6,
                        borderSkipped: false
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { beginAtZero: true, ticks: { callback: v => 'S/ ' + v } }
                    }
                }
            });
        }
    },

    mounted() {
        // Seleccionar mes/año actual en los selectores
        const now = new Date();
        const selMes = document.getElementById('selMes');
        const selAnio = document.getElementById('selAnio');
        if (selMes) selMes.value = now.getMonth() + 1;
        if (selAnio) selAnio.value = now.getFullYear();
        this.cargar();
    }
}).mount('#app-graficos');
