/**
 * app/Views/admin/medios_pago.js
 */
const { createApp, ref, onMounted, reactive } = Vue;

createApp({
  setup() {
    const medios = ref([]);
    const loading = ref(false);
    const isSaving = ref(false);
    const modal = ref(null);

    const form = reactive({
      id: null,
      nombre: '',
      orden: 0,
      activo: 1
    });

    const cargarMedios = async () => {
      loading.value = true;
      try {
        const res = await axios.get('../../../api/medios_pago.php?action=listar');
        if (res.data.ok) {
          medios.value = res.data.data;
        }
      } catch (err) {
        showToast('Error al cargar medios', 'error');
      } finally {
        loading.value = false;
      }
    };

    const abrirNuevo = () => {
      resetForm();
      if (!modal.value) modal.value = new bootstrap.Modal('#modalMedio');
      modal.value.show();
    };

    const resetForm = () => {
      form.id = null;
      form.nombre = '';
      form.orden = medios.value.length + 1;
      form.activo = 1;
    };

    const editar = (m) => {
      form.id = m.id;
      form.nombre = m.nombre;
      form.orden = m.orden;
      form.activo = m.activo;
      if (!modal.value) modal.value = new bootstrap.Modal('#modalMedio');
      modal.value.show();
    };

    const guardar = async () => {
      isSaving.value = true;
      try {
        const res = await axios.post('../../../api/medios_pago.php?action=guardar', form);
        if (res.data.ok) {
          showToast(res.data.msg, 'success');
          modal.value.hide();
          cargarMedios();
        } else {
          showToast(res.data.msg, 'error');
        }
      } catch (err) {
        showToast('Error al guardar', 'error');
      } finally {
        isSaving.value = false;
      }
    };

    const toggleEstado = async (m) => {
      try {
        const res = await axios.get(`../../../api/medios_pago.php?action=toggle&id=${m.id}`);
        if (res.data.ok) {
          showToast(res.data.msg, 'success');
          m.activo = 1 - m.activo;
        }
      } catch (err) {
        showToast('Error al cambiar estado', 'error');
      }
    };

    const eliminar = async (m) => {
      const { isConfirmed } = await Swal.fire({
        title: '¿Eliminar medio de pago?',
        text: `Se eliminará "${m.nombre}". Esta acción puede fallar si ya tiene movimientos asociados.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
      });

      if (isConfirmed) {
        try {
          const res = await axios.get(`../../../api/medios_pago.php?action=eliminar&id=${m.id}`);
          if (res.data.ok) {
            showToast(res.data.msg, 'success');
            cargarMedios();
          } else {
            showToast(res.data.msg, 'error');
          }
        } catch (err) {
          showToast('Error al eliminar', 'error');
        }
      }
    };

    const showToast = (msg, icon) => {
      Swal.fire({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        icon,
        title: msg
      });
    };

    onMounted(cargarMedios);

    return {
      medios, loading, isSaving, form,
      abrirNuevo, editar, guardar, toggleEstado, eliminar
    };
  }
}).mount('#app-medios-pago');
