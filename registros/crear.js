/**
 * registros/crear.js — Check-in
 * Vue 3 Options API — PRE_HAB y PRE_CLIENTE inyectados por PHP
 */
Vue.createApp({
    data() {
        return {
            loading: true,
            guardando: false,
            error: '',
            habitaciones: [],
            clientes: [],
            clienteTipo: 'existente',
            form: {
                habitacion_id: (typeof PRE_HAB !== 'undefined' && PRE_HAB) ? PRE_HAB : '',
                cliente_id: (typeof PRE_CLIENTE !== 'undefined' && PRE_CLIENTE) ? PRE_CLIENTE : '',
                nombre: '', dni: '', telefono: '',
                fecha_ingreso: (() => {
                    const now = new Date();
                    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                    return now.toISOString().slice(0, 16);
                })(),
                precio: ''
            }
        };
    },

    methods: {
        async cargar() {
            this.loading = true;
            const [h, c] = await Promise.all([
                fetch('../api/habitaciones.php?libres=1').then(r => r.json()),
                fetch('../api/clientes.php').then(r => r.json())
            ]);
            this.habitaciones = h.data ?? [];
            this.clientes = c.data ?? [];
            this.loading = false;
            // Si hay habitación pre-seleccionada (venimos del dashboard), auto-llenar precio
            if (this.form.habitacion_id) this.autoFillPrecio();
        },

        autoFillPrecio() {
            const hab = this.habitaciones.find(h => h.id == this.form.habitacion_id);
            if (hab) this.form.precio = parseFloat(hab.precio_base).toFixed(2);
        },

        async registrar() {
            this.error = '';
            const { habitacion_id, precio, cliente_id, nombre, dni } = this.form;
            if (!habitacion_id) { this.error = 'Seleccione una habitación'; return; }
            if ((parseFloat(precio) || 0) <= 0) { this.error = 'El precio debe ser mayor a 0'; return; }
            if (this.clienteTipo === 'existente' && !cliente_id) { this.error = 'Seleccione un cliente'; return; }
            if (this.clienteTipo === 'nuevo' && !nombre.trim()) { this.error = 'El nombre es obligatorio'; return; }
            if (this.clienteTipo === 'nuevo' && !dni.trim()) { this.error = 'El DNI es obligatorio'; return; }

            this.guardando = true;
            const payload = {
                habitacion_id: parseInt(habitacion_id),
                fecha_ingreso: this.form.fecha_ingreso,
                precio: parseFloat(precio)
            };
            if (this.clienteTipo === 'existente') {
                payload.cliente_id = parseInt(cliente_id);
            } else {
                payload.nombre = this.form.nombre;
                payload.dni = this.form.dni;
                payload.telefono = this.form.telefono;
            }
            try {
                const res = await fetch('../api/registros.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (!json.ok) { this.error = json.message; return; }
                window.location.href = 'index.php?msg=checkin';
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
}).mount('#app-checkin');
