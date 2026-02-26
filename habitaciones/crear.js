/**
 * habitaciones/crear.js
 * Vue 3 Options API
 */
Vue.createApp({
    data() {
        return {
            guardando: false,
            error: '',
            form: { numero: '', tipo: 'Simple', piso: 1, precio_base: '' }
        };
    },

    methods: {
        async guardar() {
            this.error = '';
            if (!this.form.numero.trim()) { this.error = 'El número es obligatorio'; return; }
            if ((parseFloat(this.form.precio_base) || 0) <= 0) { this.error = 'El precio debe ser mayor a 0'; return; }
            this.guardando = true;
            try {
                const res = await fetch('../api/habitaciones.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        numero: this.form.numero, tipo: this.form.tipo,
                        piso: this.form.piso, precio_base: parseFloat(this.form.precio_base)
                    })
                });
                const json = await res.json();
                if (!json.ok) { this.error = json.message; return; }
                window.location.href = 'index.php?msg=creado';
            } catch (e) {
                this.error = 'Error de red: ' + e.message;
            } finally {
                this.guardando = false;
            }
        }
    }
}).mount('#app-hab-crear');
