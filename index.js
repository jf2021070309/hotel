/**
 * index.js — Dashboard
 * Vue 3 Options API
 */
Vue.createApp({
    data() {
        return {
            loading: true,
            error: '',
            stats: {
                total: 0, libres: 0, ocupadas: 0,
                ingresos_dia: 0, gastos_dia: 0, ganancia_dia: 0
            },
            habitaciones: []
        };
    },

    methods: {
        fmt(val) {
            return 'S/ ' + parseFloat(val || 0).toFixed(2);
        },

        async cargarDatos() {
            try {
                this.loading = true;
                const res = await fetch('api/dashboard.php');
                const json = await res.json();
                if (!json.ok) throw new Error(json.message);
                this.stats = json.data.stats;
                this.habitaciones = json.data.habitaciones;
            } catch (e) {
                this.error = e.message || 'Error al cargar datos';
            } finally {
                this.loading = false;
            }
        }
    },

    mounted() {
        this.cargarDatos();
    }
}).mount('#app-dashboard');
