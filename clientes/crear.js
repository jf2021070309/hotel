/**
 * clientes/crear.js
 * Vue 3 Options API
 */
Vue.createApp({
    data() {
        return {
            guardando: false,
            error: '',
            form: { nombre: '', dni: '', telefono: '' }
        };
    },

    methods: {
        async guardar() {
            this.error = '';
            if (!this.form.nombre.trim()) { this.error = 'El nombre es obligatorio'; return; }
            if (!this.form.dni.trim()) { this.error = 'El DNI es obligatorio'; return; }
            this.guardando = true;
            try {
                const res = await fetch('../api/clientes.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ nombre: this.form.nombre, dni: this.form.dni, telefono: this.form.telefono })
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
}).mount('#app-cli-crear');
