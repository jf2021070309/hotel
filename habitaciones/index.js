/**
 * habitaciones/index.js
 * Vue 3 Options API
 */
const __appHabs = Vue.createApp({
    data() {
        return {
            loading: true,
            habitaciones: [],
            msg: { text: '', ok: true },
            modal: {
                visible: false, guardando: false, error: '',
                id: null, numero: '', tipo: 'Simple', piso: 1, precio_base: ''
            }
        };
    },

    methods: {
        async cargar() {
            this.loading = true;
            const res = await fetch('../api/habitaciones.php');
            const json = await res.json();
            this.habitaciones = json.data ?? [];
            this.loading = false;
        },

        abrirModal(hab) {
            this.modal.error = '';
            this.modal.guardando = false;
            if (hab) {
                Object.assign(this.modal, {
                    id: hab.id, numero: hab.numero, tipo: hab.tipo,
                    piso: parseInt(hab.piso), precio_base: hab.precio_base
                });
            } else {
                Object.assign(this.modal, { id: null, numero: '', tipo: 'Simple', piso: 1, precio_base: '' });
            }
            this.modal.visible = true;
        },

        cerrarModal() {
            this.modal.visible = false;
        },

        async guardar() {
            this.modal.error = '';
            if (!this.modal.numero.trim()) { this.modal.error = 'El número es obligatorio'; return; }
            if (parseFloat(this.modal.precio_base) <= 0) { this.modal.error = 'El precio debe ser > 0'; return; }

            this.modal.guardando = true;
            const esEditar = !!this.modal.id;
            const url = esEditar ? `../api/habitaciones.php?id=${this.modal.id}` : '../api/habitaciones.php';
            const method = esEditar ? 'PUT' : 'POST';
            try {
                const res = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        numero: this.modal.numero, tipo: this.modal.tipo,
                        piso: this.modal.piso, precio_base: parseFloat(this.modal.precio_base)
                    })
                });
                const json = await res.json();
                if (!json.ok) { this.modal.error = json.message; return; }
                this.msg.text = esEditar ? 'Habitación actualizada.' : 'Habitación creada.';
                this.msg.ok = true;
                this.cerrarModal();
                await this.cargar();
                setTimeout(() => this.msg.text = '', 3000);
            } catch (e) {
                this.modal.error = 'Error de red: ' + e.message;
            } finally {
                this.modal.guardando = false;
            }
        },

        exportarPDF() {
            const cols = [
                { header: 'NÚMERO', key: 'numero', width: 20 },
                { header: 'TIPO', key: 'tipo', width: 30 },
                { header: 'PISO', key: 'piso', align: 'center', width: 15 },
                { header: 'PRECIO BASE', key: 'precio_base', align: 'right', width: 25 },
                { header: 'ESTADO', key: 'estado', align: 'center', width: 20 }
            ];
            const filas = this.habitaciones.map(h => ({
                ...h,
                precio_base: 'S/ ' + parseFloat(h.precio_base).toFixed(2),
                estado: h.estado.toUpperCase()
            }));
            window.exportarPDF('Listado de Habitaciones', 'Total: ' + this.habitaciones.length + ' habitaciones', cols, filas, 'habitaciones_hotel');
        },

        exportarExcel() {
            const cols = [
                { header: 'NÚMERO', key: 'numero' },
                { header: 'TIPO', key: 'tipo' },
                { header: 'PISO', key: 'piso' },
                { header: 'PRECIO BASE', key: 'precio_base' },
                { header: 'ESTADO', key: 'estado' }
            ];
            const filas = this.habitaciones.map(h => ({
                ...h,
                precio_base: parseFloat(h.precio_base),
                estado: h.estado.toUpperCase()
            }));
            window.exportarExcel('Habitaciones', cols, filas, 'habitaciones_hotel');
        }
    },

    mounted() {
        this.cargar();
    }
});

window.__appHabs = __appHabs.mount('#app-habitaciones');
