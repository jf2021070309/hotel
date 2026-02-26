/**
 * pagos/index.js
 * Vue 3 Options API
 */
Vue.createApp({
    data() {
        return {
            loading: true,
            pagos: [],
            activos: [],
            mostrarForm: false,
            msg: { text: '', ok: true },
            form: {
                registro_id: '', monto: '', metodo: 'efectivo',
                fecha: new Date().toISOString().slice(0, 10),
                error: '', guardando: false
            }
        };
    },

    computed: {
        totalPagos() {
            return this.pagos.reduce((s, p) => s + parseFloat(p.monto || 0), 0);
        }
    },

    methods: {
        fmt(v) { return 'S/ ' + parseFloat(v || 0).toFixed(2); },

        fmtFecha(d) {
            if (!d) return '—';
            const [y, m, day] = d.split('-');
            return `${day}/${m}/${y}`;
        },

        autoFill() {
            const r = this.activos.find(a => a.id == this.form.registro_id);
            if (r) this.form.monto = parseFloat(r.precio).toFixed(2);
        },

        async cargar() {
            this.loading = true;
            const [p, r] = await Promise.all([
                fetch('../api/pagos.php').then(r => r.json()),
                fetch('../api/registros.php?activos=1').then(r => r.json())
            ]);
            this.pagos = p.data ?? [];
            this.activos = r.data ?? [];
            this.loading = false;
        },

        async guardar() {
            this.form.error = '';
            if (!this.form.registro_id) { this.form.error = 'Seleccione un huésped'; return; }
            if ((parseFloat(this.form.monto) || 0) <= 0) { this.form.error = 'El monto debe ser mayor a 0'; return; }
            this.form.guardando = true;
            try {
                const res = await fetch('../api/pagos.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        registro_id: parseInt(this.form.registro_id),
                        monto: parseFloat(this.form.monto),
                        metodo: this.form.metodo,
                        fecha: this.form.fecha
                    })
                });
                const json = await res.json();
                if (!json.ok) { this.form.error = json.message; return; }
                this.msg.text = 'Pago registrado.'; this.msg.ok = true;
                this.mostrarForm = false;
                await this.cargar();
                setTimeout(() => this.msg.text = '', 3000);
            } catch (e) {
                this.form.error = 'Error de red: ' + e.message;
            } finally {
                this.form.guardando = false;
            }
        }
    },

    mounted() {
        this.cargar();
    }
}).mount('#app-pagos');
