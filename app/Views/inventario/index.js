/**
 * app/Views/inventario/index.js
 * Patrón inline Excel-style (igual que clientes/limpieza)
 */
Vue.createApp({
  data() {
    return {
      productos: [],
      loading: false,
      busqueda: ''
    };
  },

  computed: {
    productosFiltrados() {
      if (!this.busqueda) return this.productos;
      const q = this.busqueda.toLowerCase();
      return this.productos.filter(p =>
        (p.nombre    && p.nombre.toLowerCase().includes(q)) ||
        (p.categoria && p.categoria.toLowerCase().includes(q)) ||
        (p.id        && p.id.toString().includes(q))
      );
    },
    cambiosCount() {
      return this.productos.filter(p => p.modificado || !p.id).length;
    }
  },

  methods: {
    /* ── CARGA ── */
    async fetchInventario() {
      this.loading = true;
      try {
        const res = await axios.get('../../../api/inventario.php?action=listar');
        const data = res.data.data || [];
        this.productos = data.map(p => ({ ...p, modificado: false }));
      } catch (err) {
        this.showToast('Error al cargar inventario', 'error');
      } finally {
        this.loading = false;
      }
    },

    /* ── INLINE EDITING ── */
    marcarModificado(p) {
      if (p.id) p.modificado = true;
    },

    agregarFila() {
      this.productos.push({
        id: null,
        temp_id: 'tmp_' + Date.now(),
        nombre: '',
        categoria: 'BEBIDA',
        precio_venta: 0,
        stock_actual: 0,
        refrigeradora: 1,
        modificado: true
      });
      this.$nextTick(() => {
        const container = document.querySelector('.inv-grid-container');
        if (container) container.scrollTop = container.scrollHeight;
      });
    },

    async eliminarFila(p) {
      // Fila nueva sin guardar → eliminar de la lista sin petición
      if (!p.id) {
        const idx = this.productos.indexOf(p);
        if (idx > -1) this.productos.splice(idx, 1);
        return;
      }
      const res = await Swal.fire({
        title: '¿Eliminar producto?',
        text: `Se eliminará "${p.nombre}" del inventario.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
      });
      if (res.isConfirmed) {
        try {
          const apiRes = await axios.post(
            `../../../api/inventario.php?action=eliminar&id=${p.id}`
          );
          this.showToast(apiRes.data.msg || 'Producto eliminado', 'success');
          await this.fetchInventario();
        } catch (err) {
          this.showToast('Error al eliminar', 'error');
        }
      }
    },

    /* ── GUARDADO MASIVO ── */
    async guardarCambiosMasivos() {
      const cambiados = this.productos.filter(p => p.modificado || !p.id);
      if (!cambiados.length) return;

      // Validar campos obligatorios
      for (const p of cambiados) {
        if (!p.nombre || !p.nombre.trim()) {
          return this.showToast('Todos los productos deben tener un nombre', 'error');
        }
        if (!p.precio_venta || p.precio_venta < 0) {
          return this.showToast(`El precio de "${p.nombre}" no es válido`, 'error');
        }
      }

      this.loading = true;
      try {
        let ok = 0;
        for (const p of cambiados) {
          const action = p.id ? `actualizar&id=${p.id}` : 'crear';
          const res = await axios.post(
            `../../../api/inventario.php?action=${action}`, p
          );
          if (res.data.ok) ok++;
        }
        this.showToast(`${ok} producto(s) guardados correctamente`, 'success');
        await this.fetchInventario();
      } catch (err) {
        this.showToast('Error al guardar algunos cambios', 'error');
        console.error(err);
      } finally {
        this.loading = false;
      }
    },

    /* ── UTILIDADES ── */
    showToast(msg, icon) {
      Swal.fire({
        toast: true, position: 'top-end', icon, title: msg,
        showConfirmButton: false, timer: 3000, timerProgressBar: true
      });
    }
  },

  mounted() {
    this.fetchInventario();
  }
}).mount('#app-inventario');
