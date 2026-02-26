/**
 * habitaciones/editar.js
 * Vue 3 Options API — HAB_ID inyectado por PHP
 */
Vue.createApp({
    data() {
        return {
            loading: true,
            guardando: false,
            form: { numero: '', tipo: 'Simple', piso: 1, precio_base: '', estado: '' },
            msg: { text: '', ok: true }
        };
    },

    methods: {
        async cargar() {
            this.loading = true;
            const res = await fetch(`../api/habitaciones.php?id=${HAB_ID}`);
            const json = await res.json();
            if (json.ok && json.data) Object.assign(this.form, json.data);
            this.loading = false;
        },

        async guardar() {
            this.msg.text = '';
            if (!this.form.numero.trim()) { this.msg.text = 'El número es obligatorio'; this.msg.ok = false; return; }
            if ((parseFloat(this.form.precio_base) || 0) <= 0) { this.msg.text = 'El precio debe ser > 0'; this.msg.ok = false; return; }

            this.guardando = true;
            try {
                const res = await fetch(`../api/habitaciones.php?id=${HAB_ID}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        numero: this.form.numero, tipo: this.form.tipo,
                        piso: parseInt(this.form.piso), precio_base: parseFloat(this.form.precio_base)
                    })
                });
                const json = await res.json();
                if (!json.ok) { this.msg.text = json.message; this.msg.ok = false; return; }
                this.msg.text = 'Cambios guardados.'; this.msg.ok = true;
                setTimeout(() => window.location.href = 'index.php?msg=actualizado', 1200);
            } catch (e) {
                this.msg.text = 'Error de red: ' + e.message; this.msg.ok = false;
            } finally {
                this.guardando = false;
            }
        }
    },

    mounted() {
        this.cargar();
    }
}).mount('#app-hab-editar');
