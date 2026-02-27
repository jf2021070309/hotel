/**
 * reportes/cuadre_diario.js
 * Vue 3 Options API
 */
window.__appCuadre = Vue.createApp({
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
        },

        exportarPDF() {
            if (!this.data) return;
            const cols = [
                { header: 'Habitación', key: 'hab', align: 'left', width: 32 },
                { header: 'Cliente', key: 'cliente', align: 'left', width: 70 },
                { header: 'Método', key: 'metodo', align: 'center', width: 32 },
                { header: 'Monto', key: 'monto', align: 'right', width: 35 }
            ];
            const filas = (this.data.detalle_pagos || []).map(p => ({
                hab: `Hab. ${p.hab_num}`,
                cliente: p.cliente,
                metodo: p.metodo,
                monto: `S/ ${parseFloat(p.monto).toFixed(2)}`
            }));
            exportarPDF(
                `Cuadre Diario — ${this.fmtFecha(this.fecha)}`,
                `Ingresos: ${this.fmt(this.data.total_ingresos)} | Gastos: ${this.fmt(this.data.total_gastos)} | Ganancia: ${this.fmt(this.data.ganancia_neta)}`,
                cols, filas, `cuadre_${this.fecha}`);
        },

        exportarExcel() {
            if (!this.data) return;
            // Hoja 1: Pagos
            const colsPagos = [
                { header: 'Habitación', key: 'hab' },
                { header: 'Cliente', key: 'cliente' },
                { header: 'Método', key: 'metodo' },
                { header: 'Monto', key: 'monto' }
            ];
            const filasPagos = (this.data.detalle_pagos || []).map(p => ({
                hab: `Hab. ${p.hab_num}`,
                cliente: p.cliente,
                metodo: p.metodo,
                monto: parseFloat(p.monto).toFixed(2)
            }));
            exportarExcel(`Cuadre ${this.fecha}`, colsPagos, filasPagos, `cuadre_${this.fecha}`);
        }
    },

    mounted() {
        this.cargar();
    }
}).mount('#app-cuadre');
