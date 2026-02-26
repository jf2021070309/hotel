/**
 * registros/index.js
 * Vue 3 Options API
 */
Vue.createApp({
    data() {
        return {
            loading: true,
            registros: [],
            filtro: 'todos',
            msg: new URLSearchParams(location.search).get('msg') === 'checkin'
                ? 'Ingreso registrado exitosamente.' : ''
        };
    },

    computed: {
        registrosFiltrados() {
            if (this.filtro === 'todos') return this.registros;
            return this.registros.filter(r => r.estado === this.filtro);
        }
    },

    methods: {
        fmtFecha(d) {
            if (!d) return '—';
            const [fecha, hora] = d.split('T').length > 1 ? d.split('T') : d.split(' ');
            const [y, m, day] = fecha.split('-');
            const time = hora ? hora.slice(0, 5) : '';
            return time ? `${day}/${m}/${y} ${time}` : `${day}/${m}/${y}`;
        },

        async cargar() {
            this.loading = true;
            const res = await fetch('../api/registros.php');
            const json = await res.json();
            this.registros = json.data ?? [];
            this.loading = false;
        }
    },

    mounted() {
        this.cargar();
    }
}).mount('#app-registros');
