/**
 * registros/index.js
 * Vue 3 Options API
 */
window.__appRegs = Vue.createApp({
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
        },

        exportarPDF() {
            const cols = [
                { header: '#', key: 'num', align: 'center', width: 8 },
                { header: 'Habitación', key: 'hab', align: 'left', width: 32 },
                { header: 'Cliente', key: 'cliente', align: 'left', width: 55 },
                { header: 'DNI', key: 'dni', align: 'center', width: 22 },
                { header: 'Ingreso', key: 'ingreso', align: 'center', width: 32 },
                { header: 'Salida', key: 'salida', align: 'center', width: 32 },
                { header: 'Precio', key: 'precio', align: 'right', width: 25 },
                { header: 'Estado', key: 'estado', align: 'center', width: 22 }
            ];
            const filas = this.registrosFiltrados.map((r, i) => ({
                num: i + 1,
                hab: `Hab. ${r.hab_num} ${r.hab_tipo}`,
                cliente: r.cliente,
                dni: r.dni,
                ingreso: this.fmtFecha(r.fecha_ingreso),
                salida: r.fecha_salida ? this.fmtFecha(r.fecha_salida) : '—',
                precio: `S/ ${parseFloat(r.precio).toFixed(2)}`,
                estado: r.estado === 'activo' ? 'Activo' : 'Finalizado'
            }));
            exportarPDF('Registros de Huéspedes',
                `Filtro: ${this.filtro} — ${filas.length} registros`,
                cols, filas, `registros_${new Date().toISOString().slice(0, 10)}`);
        },

        exportarExcel() {
            const cols = [
                { header: '#', key: 'num' },
                { header: 'Habitación', key: 'hab' },
                { header: 'Cliente', key: 'cliente' },
                { header: 'DNI', key: 'dni' },
                { header: 'Ingreso', key: 'ingreso' },
                { header: 'Salida', key: 'salida' },
                { header: 'Precio', key: 'precio' },
                { header: 'Estado', key: 'estado' }
            ];
            const filas = this.registrosFiltrados.map((r, i) => ({
                num: i + 1,
                hab: `Hab. ${r.hab_num} ${r.hab_tipo}`,
                cliente: r.cliente,
                dni: r.dni,
                ingreso: this.fmtFecha(r.fecha_ingreso),
                salida: r.fecha_salida ? this.fmtFecha(r.fecha_salida) : '',
                precio: parseFloat(r.precio).toFixed(2),
                estado: r.estado === 'activo' ? 'Activo' : 'Finalizado'
            }));
            exportarExcel('Registros', cols, filas,
                `registros_${new Date().toISOString().slice(0, 10)}`);
        }
    },

    mounted() {
        this.cargar();
    }
}).mount('#app-registros');

