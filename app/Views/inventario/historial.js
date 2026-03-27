/**
 * app/Views/inventario/historial.js
 */
const { createApp, ref, reactive, computed, onMounted } = Vue;

createApp({
    setup() {
        const movimientos = ref([]);
        const productos = ref([]);
        const loading = ref(false);

        const hoy = new Date().toISOString().split('T')[0];
        const filtros = reactive({
            producto_id: '',
            tipo: '',
            fecha_desde: hoy,
            fecha_hasta: hoy
        });

        const ciForm = reactive({
            producto_id: '',
            cantidad: 1,
            referencia: ''
        });

        const resumen = computed(() => ({
            ventas:   movimientos.value.filter(m => m.tipo === 'VENTA').reduce((s, m) => s + parseInt(m.cantidad), 0),
            internos: movimientos.value.filter(m => m.tipo === 'CONSUMO_INTERNO').reduce((s, m) => s + parseInt(m.cantidad), 0),
            recargas: movimientos.value.filter(m => m.tipo === 'RECARGA').reduce((s, m) => s + parseInt(m.cantidad), 0),
        }));

        const cargarProductos = async () => {
            const res = await axios.get('../../../api/inventario.php?action=listar');
            productos.value = res.data.data || [];
        };

        const cargarHistorial = async () => {
            loading.value = true;
            try {
                const params = new URLSearchParams({ action: 'historial', ...filtros }).toString();
                const res = await axios.get(`../../../api/inventario.php?${params}`);
                movimientos.value = res.data.data || [];
            } catch (err) {
                showToast('Error al cargar historial', 'error');
            } finally {
                loading.value = false;
            }
        };

        const limpiarFiltros = () => {
            filtros.producto_id = '';
            filtros.tipo = '';
            filtros.fecha_desde = hoy;
            filtros.fecha_hasta = hoy;
            cargarHistorial();
        };

        const abrirConsumoInterno = () => {
            Object.assign(ciForm, { producto_id: '', cantidad: 1, referencia: '' });
            new bootstrap.Modal('#modalConsumoInterno').show();
        };

        const guardarConsumoInterno = async () => {
            try {
                const res = await axios.post('../../../api/inventario.php?action=consumo_interno', ciForm);
                if (res.data.ok) {
                    showToast(res.data.msg, 'success');
                    bootstrap.Modal.getInstance('#modalConsumoInterno').hide();
                    cargarProductos();
                    cargarHistorial();
                } else {
                    showToast(res.data.msg, 'error');
                }
            } catch (err) {
                showToast('Error al registrar', 'error');
            }
        };

        const tipoBadge = (tipo) => ({
            'VENTA':            'bg-danger',
            'CONSUMO_INTERNO':  'bg-warning text-dark',
            'RECARGA':          'bg-success',
            'AJUSTE':           'bg-secondary',
        }[tipo] || 'bg-light text-dark');

        const tipoLabel = (tipo) => ({
            'VENTA':            'Venta Hab.',
            'CONSUMO_INTERNO':  'Uso Interno',
            'RECARGA':          'Recarga',
            'AJUSTE':           'Ajuste',
        }[tipo] || tipo);

        const showToast = (msg, icon) => {
            Swal.fire({ toast: true, position: 'top-end', icon, title: msg, showConfirmButton: false, timer: 3000 });
        };

        onMounted(async () => {
            await cargarProductos();
            await cargarHistorial();
        });

        return {
            movimientos, productos, loading, filtros, ciForm, resumen,
            cargarHistorial, limpiarFiltros, abrirConsumoInterno, guardarConsumoInterno,
            tipoBadge, tipoLabel
        };
    }
}).mount('#app-kardex');
