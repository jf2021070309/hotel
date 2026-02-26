/**
 * reportes/mensual.js
 * Vue 3 Options API
 */
Vue.createApp({
    data() {
        return {
            loading: true,
            data: null,
            year: new Date().getFullYear(),
            month: new Date().getMonth() + 1
        };
    },

    computed: {
        diasConMovimiento() {
            if (!this.data) return [];
            const ingMap = {}, gasMap = {};
            (this.data.ingresos_por_dia ?? []).forEach(d => ingMap[d.dia] = parseFloat(d.total));
            (this.data.gastos_por_dia ?? []).forEach(d => gasMap[d.dia] = parseFloat(d.total));
            const dias = [...new Set([...Object.keys(ingMap), ...Object.keys(gasMap)])].sort();
            return dias.map(dia => ({ dia, ing: ingMap[dia] ?? 0, gas: gasMap[dia] ?? 0 }));
        }
    },

    methods: {
        fmt(v) { return 'S/ ' + parseFloat(v || 0).toFixed(2); },

        fmtFecha(d) {
            if (!d) return '';
            const [y, m, day] = d.split('-');
            return `${day}/${m}/${y}`;
        },

        async cargar() {
            this.loading = true;
            const res = await fetch(`../api/reportes.php?tipo=mensual&year=${this.year}&month=${this.month}`);
            const json = await res.json();
            this.data = json.data;
            this.loading = false;
        },

        cambiarMes(delta) {
            this.month += delta;
            if (this.month < 1) { this.month = 12; this.year--; }
            if (this.month > 12) { this.month = 1; this.year++; }
            this.cargar();
        }
    },

    mounted() {
        this.cargar();
    }
}).mount('#app-mensual');
