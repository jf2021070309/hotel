/**
 * Módulo Frontend de Medios de Pago V2.
 */
Vue.createApp({
  data() {
    return {
      medios: [],
      busqueda: '',
      loading: false,
      authUser: window.authUser || {}
    };
  },

  computed: {
    mediosFiltrados() {
      let f = this.medios;
      if (this.busqueda) {
        const q = this.busqueda.toLowerCase();
        f = f.filter(m => 
          (m.nombre && m.nombre.toLowerCase().includes(q)) ||
          (m.id && m.id.toString().includes(q))
        );
      }
      return f;
    },
    cambiosCount() {
      return this.medios.filter(m => m.modificado || !m.id).length;
    }
  },

  methods: {
    async fetchMedios() {
      this.loading = true;
      try {
        const res = await axios.get('../../../api/medios_pago.php?action=listar');
        const data = res.data.data || [];
        this.medios = data.map(m => ({
          ...m,
          modificado: false
        }));
      } catch (err) {
        this.showToast('Error al cargar medios', 'error');
      } finally {
        this.loading = false;
      }
    },

    agregarFila() {
      let maxOrden = 0;
      if (this.medios.length > 0) {
        maxOrden = Math.max(...this.medios.map(m => Number(m.orden) || 0));
      }

      this.medios.push({
        id: null,
        temp_id: 'temp_' + Date.now(),
        nombre: '',
        orden: maxOrden + 1,
        activo: 1,
        modificado: true
      });
      // Scroll to bottom
      setTimeout(() => {
        const container = document.querySelector('.mensual-grid-container');
        if (container) container.scrollTop = container.scrollHeight;
      }, 100);
    },

    marcarModificado(m) {
      if (m.id) {
        m.modificado = true;
      }
    },

    async eliminarFila(m, index) {
      if (!m.id) {
        this.medios.splice(this.medios.indexOf(m), 1);
        return;
      }
      
      const res = await Swal.fire({
        title: '¿Eliminar medio de pago?',
        text: `¿Deseas eliminar "${m.nombre}"? Fallará si tiene registros asociados.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
      });

      if (res.isConfirmed) {
        try {
          const apiRes = await axios.get(`../../../api/medios_pago.php?action=eliminar&id=${m.id}`);
          if (apiRes.data.ok) {
            this.showToast(apiRes.data.msg, 'success');
            await this.fetchMedios();
          } else {
            this.showToast(apiRes.data.msg || 'Error al eliminar', 'error');
          }
        } catch (err) {
          this.showToast('Error al eliminar', 'error');
        }
      }
    },

    async guardarCambiosMasivos() {
      const cambiados = this.medios.filter(m => m.modificado || !m.id);
      if (cambiados.length === 0) return;

      // Validate required fields
      for (const m of cambiados) {
        if (!m.nombre || !m.nombre.trim()) {
          return this.showToast('Todos los medios deben tener una Descripción', 'error');
        }
      }

      this.loading = true;
      try {
        let successCount = 0;
        for (const m of cambiados) {
          const res = await axios.post(`../../../api/medios_pago.php?action=guardar`, m);
          if (res.data.ok) {
            successCount++;
          }
        }
        
        this.showToast(`Se guardaron ${successCount} medios de pago correctamente`, 'success');
        await this.fetchMedios();
      } catch (err) {
        this.showToast('Hubo un error al guardar algunos cambios', 'error');
        console.error(err);
      } finally {
        this.loading = false;
      }
    },

    showToast(msg, icon) {
      Swal.fire({
        toast: true, position: 'top-end', icon: icon,
        title: msg, showConfirmButton: false, timer: 3000,
        timerProgressBar: true
      });
    }
  },

  mounted() {
    this.fetchMedios();
  }
}).mount('#app-medios-pago');
