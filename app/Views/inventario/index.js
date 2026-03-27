/**
 * app/Views/inventario/index.js
 */
const { createApp, ref, reactive, onMounted } = Vue;

createApp({
    setup() {
        const productos = ref([]);
        const loading = ref(false);
        const editando = ref(false);
        const selectedProd = ref(null);
        const cantidadRecarga = ref(10);
        
        const form = reactive({
            id: null,
            nombre: '',
            categoria: 'BEBIDA',
            precio_venta: 5.00,
            stock_actual: 24,
            refrigeradora: 1
        });

        const cargarInventario = async () => {
            loading.value = true;
            try {
                const res = await axios.get('../../../api/inventario.php?action=listar');
                productos.value = res.data.data || [];
            } catch (err) {
                showToast('Error al cargar inventario', 'error');
            } finally {
                loading.value = false;
            }
        };

        const abrirNuevo = () => {
            editando.value = false;
            Object.assign(form, {
                id: null,
                nombre: '',
                categoria: 'BEBIDA',
                precio_venta: 5.00,
                stock_actual: 24,
                refrigeradora: 1
            });
            new bootstrap.Modal('#modalProducto').show();
        };

        const abrirEditar = (p) => {
            editando.value = true;
            Object.assign(form, { ...p });
            new bootstrap.Modal('#modalProducto').show();
        };

        const guardar = async () => {
            loading.value = true;
            try {
                const action = editando.value ? `actualizar&id=${form.id}` : 'crear';
                const res = await axios.post(`../../../api/inventario.php?action=${action}`, form);
                if (res.data.ok) {
                    showToast(res.data.msg, 'success');
                    bootstrap.Modal.getInstance('#modalProducto').hide();
                    cargarInventario();
                }
            } catch (err) {
                showToast('Error al guardar producto', 'error');
            } finally {
                loading.value = false;
            }
        };

        const abrirRecarga = (p) => {
            selectedProd.value = p;
            cantidadRecarga.value = 10;
            new bootstrap.Modal('#modalRecarga').show();
        };

        const confirmarRecarga = async () => {
            try {
                const res = await axios.post(`../../../api/inventario.php?action=recargar&id=${selectedProd.value.id}`, {
                    cantidad: cantidadRecarga.value
                });
                if (res.data.ok) {
                    showToast(res.data.msg, 'success');
                    bootstrap.Modal.getInstance('#modalRecarga').hide();
                    cargarInventario();
                }
            } catch (err) {
                showToast('Error al recargar stock', 'error');
            }
        };

        const confirmarEliminar = async (p) => {
            const res = await Swal.fire({
                title: '¿Eliminar producto?',
                text: `Se desactivará "${p.nombre}" del inventario.`,
                icon: 'warning',
                showCancelButton: true
            });
            if (res.isConfirmed) {
                try {
                    await axios.post(`../../../api/inventario.php?action=eliminar&id=${p.id}`);
                    showToast('Producto eliminado', 'success');
                    cargarInventario();
                } catch (err) {
                    showToast('Error al eliminar', 'error');
                }
            }
        };

        const showToast = (msg, icon) => {
            Swal.fire({ toast: true, position: 'top-end', icon, title: msg, showConfirmButton: false, timer: 3000 });
        };

        onMounted(cargarInventario);

        return {
            productos, loading, editando, form, selectedProd, cantidadRecarga,
            abrirNuevo, abrirEditar, guardar, abrirRecarga, confirmarRecarga, confirmarEliminar
        };
    }
}).mount('#app-inventario');
