/**
 * reportes/cuadre_diario.js
 * Vue 3 Options API
 */
Vue.createApp({
    data() {
        return {
            loading: true,
            data: null,
            fecha: new Date().toISOString().slice(0, 10)
        };
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
            const res = await fetch(`../api/reportes.php?tipo=diario&fecha=${this.fecha}`);
            const json = await res.json();
            this.data = json.data;
            this.loading = false;
        }
    },

    mounted() {
        this.cargar();
    }
}).mount('#app-cuadre');
