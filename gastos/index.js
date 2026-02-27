/**
 * gastos/index.js
 * Vue 3 Options API
 */
window.__appGastos = Vue.createApp({
    data() {
        return {
            loading: true,
            gastos: [],
            mostrarForm: false,
            filtroFecha: '',
            msg: { text: '', ok: true },
            form: {
                descripcion: '', monto: '',
                fecha: new Date().toISOString().slice(0, 10),
                error: '', guardando: false
            }
        };
    },

    computed: {
        gastosFiltrados() {
            if (!this.filtroFecha) return this.gastos;
            return this.gastos.filter(g => g.fecha === this.filtroFecha);
        },
        totalGastos() {
            return this.gastosFiltrados.reduce((s, g) => s + parseFloat(g.monto || 0), 0);
        }
    },

    methods: {
        fmt(v) { return 'S/ ' + parseFloat(v || 0).toFixed(2); },

        fmtFecha(d) {
            if (!d) return '—';
            const [y, m, day] = d.split('-');
            return `${day}/${m}/${y}`;
        },

        async cargar() {
            this.loading = true;
            const res = await fetch('../api/gastos.php');
            const json = await res.json();
            this.gastos = json.data ?? [];
            this.loading = false;
        },

        async guardar() {
            this.form.error = '';
            if (!this.form.descripcion.trim()) { this.form.error = 'La descripción es obligatoria'; return; }
            if ((parseFloat(this.form.monto) || 0) <= 0) { this.form.error = 'El monto debe ser mayor a 0'; return; }
            this.form.guardando = true;
            try {
                const res = await fetch('../api/gastos.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        descripcion: this.form.descripcion,
                        monto: parseFloat(this.form.monto),
                        fecha: this.form.fecha
                    })
                });
                const json = await res.json();
                if (!json.ok) { this.form.error = json.message; return; }
                this.msg.text = 'Gasto registrado.'; this.msg.ok = true;
                this.mostrarForm = false;
                this.form.descripcion = this.form.monto = '';
                await this.cargar();
                setTimeout(() => this.msg.text = '', 3000);
            } catch (e) {
                this.form.error = 'Error de red: ' + e.message;
            } finally {
                this.form.guardando = false;
            }
        },

        async eliminar(id) {
            if (!confirm('¿Eliminar este gasto?')) return;
            await fetch(`../api/gastos.php?id=${id}`, { method: 'DELETE' });
            await this.cargar();
            this.msg.text = 'Gasto eliminado.'; this.msg.ok = true;
            setTimeout(() => this.msg.text = '', 3000);
        },

        exportarPDF() {
            const cols = [
                { header: '#', key: 'num', align: 'center', width: 8 },
                { header: 'Descripción', key: 'descripcion', align: 'left', width: 140 },
                { header: 'Monto', key: 'monto', align: 'right', width: 32 },
                { header: 'Fecha', key: 'fecha', align: 'center', width: 28 }
            ];
            const filas = this.gastosFiltrados.map((g, i) => ({
                num: i + 1,
                descripcion: g.descripcion,
                monto: `S/ ${parseFloat(g.monto).toFixed(2)}`,
                fecha: this.fmtFecha(g.fecha)
            }));
            const filtro = this.filtroFecha ? ` — Fecha: ${this.fmtFecha(this.filtroFecha)}` : '';
            exportarPDF('Control de Gastos',
                `Total: S/ ${parseFloat(this.totalGastos).toFixed(2)}${filtro}`,
                cols, filas, `gastos_${new Date().toISOString().slice(0, 10)}`);
        },

        exportarExcel() {
            const cols = [
                { header: '#', key: 'num' },
                { header: 'Descripción', key: 'descripcion' },
                { header: 'Monto', key: 'monto' },
                { header: 'Fecha', key: 'fecha' }
            ];
            const filas = this.gastosFiltrados.map((g, i) => ({
                num: i + 1,
                descripcion: g.descripcion,
                monto: parseFloat(g.monto).toFixed(2),
                fecha: this.fmtFecha(g.fecha)
            }));
            exportarExcel('Gastos', cols, filas,
                `gastos_${new Date().toISOString().slice(0, 10)}`);
        }
    },

    mounted() {
        this.cargar();
    }
}).mount('#app-gastos');
