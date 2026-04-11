/**
 * habitaciones/index.js
 * Vue 3 Options API
 */
const __appHabs = Vue.createApp({
    data() {
        return {
            loading: true,
            habitaciones: [],
            searchQuery: '',
            filtros: { estado: '', tipo: '', piso: '' },
            msg: { text: '', ok: true },
            modal: {
                visible: false, guardando: false, error: '',
                id: null, numero: '', tipo: 'Simple', piso: 1, precio_base: ''
            }
        };
    },

    computed: {
        tiposUnicos() {
            return [...new Set(this.habitaciones.map(h => h.tipo))].filter(Boolean).sort();
        },
        pisosUnicos() {
            return [...new Set(this.habitaciones.map(h => parseInt(h.piso)))].filter(Boolean).sort((a,b)=>a-b);
        },
        habitacionesFiltradas() {
            let res = this.habitaciones;
            
            if (this.filtros.estado) res = res.filter(h => h.estado === this.filtros.estado);
            if (this.filtros.tipo) res = res.filter(h => h.tipo === this.filtros.tipo);
            if (this.filtros.piso) res = res.filter(h => parseInt(h.piso) === parseInt(this.filtros.piso));

            if (this.searchQuery) {
                const q = this.searchQuery.toLowerCase();
                res = res.filter(h => {
                    return (h.numero && h.numero.toLowerCase().includes(q)) ||
                           (h.tipo && h.tipo.toLowerCase().includes(q)) ||
                           (h.estado && h.estado.toLowerCase().includes(q));
                });
            }
            return res;
        }
    },

    methods: {
        colorPiso(piso) {
            const p = parseInt(piso);
            const palettes = {
                1: 'background: linear-gradient(135deg, #e0e7ff, #c7d2fe); color: #3730a3; border: 1px solid #a5b4fc;',   // Indigo premium
                2: 'background: linear-gradient(135deg, #ecfeff, #cffafe); color: #155e75; border: 1px solid #67e8f9;',   // Cyan
                3: 'background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e; border: 1px solid #fcd34d;',   // Amber
                4: 'background: linear-gradient(135deg, #fce7f3, #fbcfe8); color: #9d174d; border: 1px solid #f9a8d4;',   // Pink
                5: 'background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #166534; border: 1px solid #86efac;',   // Green
                6: 'background: linear-gradient(135deg, #f3e8ff, #e9d5ff); color: #6b21a8; border: 1px solid #d8b4fe;',   // Purple
                7: 'background: linear-gradient(135deg, #ffedd5, #fed7aa); color: #9a3412; border: 1px solid #fdba74;',   // Orange
                8: 'background: linear-gradient(135deg, #fee2e2, #fecaca); color: #991b1b; border: 1px solid #fca5a5;'    // Red
            };
            return palettes[p] || 'background: linear-gradient(135deg, #f1f5f9, #e2e8f0); color: #334155; border: 1px solid #cbd5e1;';
        },

        async cargar() {
            this.loading = true;
            const res = await fetch('../../../api/habitaciones.php?action=todos');
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
                    piso: parseInt(hab.piso), precio_base: hab.precio_base,
                    estado: hab.estado
                });
            } else {
                Object.assign(this.modal, { id: null, numero: '', tipo: 'SIMPLE', piso: 1, precio_base: '', estado: 'libre' });
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
            const url = esEditar ? `../../../api/habitaciones.php?id=${this.modal.id}` : '../../../api/habitaciones.php';
            const method = esEditar ? 'PUT' : 'POST';
            try {
                const res = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        numero: this.modal.numero, tipo: this.modal.tipo,
                        piso: this.modal.piso, precio_base: parseFloat(this.modal.precio_base),
                        estado: this.modal.estado
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
            const filas = this.habitacionesFiltradas.map(h => ({
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
            const filas = this.habitacionesFiltradas.map(h => ({
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


