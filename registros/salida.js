/**
 * registros/salida.js — Checkout
 * Vue 3 Options API — PRE_REG_ID inyectado por PHP
 */
Vue.createApp({
    data() {
        return {
            loading: true,
            guardando: false,
            error: '',
            registros: [],
            form: {
                registro_id: (typeof PRE_REG_ID !== 'undefined' && PRE_REG_ID) ? PRE_REG_ID : '',
                fecha_salida: (() => {
                    const now = new Date();
                    const pad = n => String(n).padStart(2, '0');
                    return `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
                })()
            }
        };
    },

    computed: {
        seleccionado() {
            return this.registros.find(r => r.id == this.form.registro_id) ?? null;
        },
        noches() {
            if (!this.seleccionado || !this.form.fecha_salida) return 0;
            const diff = Math.ceil(
                (new Date(this.form.fecha_salida) - new Date(this.seleccionado.fecha_ingreso)) / 86400000
            );
            return Math.max(diff, 1);
        },
        totalEstimado() {
            return this.seleccionado ? this.noches * parseFloat(this.seleccionado.precio) : 0;
        }
    },

    methods: {
        fmt(v) { return 'S/ ' + parseFloat(v || 0).toFixed(2); },

        fmtFecha(d) {
            if (!d) return '—';
            const [fecha, hora] = d.split('T').length > 1 ? d.split('T') : d.split(' ');
            const [y, m, day] = fecha.split('-');
            const time = hora ? hora.slice(0, 5) : '';
            return time ? `${day}/${m}/${y} ${time}` : `${day}/${m}/${y}`;
        },

        async cargar() {
            this.loading = true;
            const res = await fetch('../api/registros.php?activos=1');
            const json = await res.json();
            this.registros = json.data ?? [];
            this.loading = false;
        },

        async procesarSalida() {
            this.error = '';
            if (!this.form.registro_id) { this.error = 'Seleccione un huésped activo'; return; }
            if (!this.form.fecha_salida) { this.error = 'Seleccione fecha de salida'; return; }
            this.guardando = true;
            try {
                const res = await fetch(`../api/registros.php?id=${this.form.registro_id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ fecha_salida: this.form.fecha_salida })
                });
                const json = await res.json();
                if (!json.ok) { this.error = json.message; return; }
                window.location.href = 'index.php?msg=salida';
            } catch (e) {
                this.error = 'Error de red: ' + e.message;
            } finally {
                this.guardando = false;
            }
        }
    },

    mounted() {
        this.cargar();
    }
}).mount('#app-salida');
