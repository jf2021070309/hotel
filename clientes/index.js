/**
 * clientes/index.js
 * Vue 3 Options API
 */
Vue.createApp({
    data() {
        return {
            loading: true,
            clientes: [],
            buscar: '',
            msg: new URLSearchParams(location.search).get('msg') === 'creado'
                ? 'Cliente registrado correctamente.' : ''
        };
    },

    computed: {
        clientesFiltrados() {
            const q = this.buscar.toLowerCase();
            if (!q) return this.clientes;
            return this.clientes.filter(c =>
                c.nombre.toLowerCase().includes(q) || c.dni.includes(q)
            );
        }
    },

    methods: {
        async cargar() {
            this.loading = true;
            const res = await fetch('../api/clientes.php');
            const json = await res.json();
            this.clientes = json.data ?? [];
            this.loading = false;
        }
    },

    mounted() {
        this.cargar();
    }
}).mount('#app-clientes');
