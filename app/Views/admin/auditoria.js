/**
 * app/Views/admin/auditoria.js
 * Vue 3 Options API optimizado
 */
Vue.createApp({
  data() {
    return {
      logs: [],
      loading: false,
      currentPage: 1,
      itemsPerPage: 20,
      filters: {
        nombre: '',
        rol: 'TODOS',
        desde: '',
        hasta: ''
      }
    };
  },

  computed: {
    totalPages() {
      return Math.ceil(this.logs.length / this.itemsPerPage) || 1;
    },
    paginatedLogs() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      return this.logs.slice(start, start + this.itemsPerPage);
    }
  },

  methods: {
    async fetchLogs() {
      this.loading = true;
      this.currentPage = 1;
      try {
        const params = new URLSearchParams(this.filters);
        const res = await axios.get('../../../api/auditoria.php?action=listar&' + params.toString());
        this.logs = res.data && Array.isArray(res.data.data) ? res.data.data : [];
      } catch (err) {
        console.error("Error al cargar auditoría:", err);
        this.logs = [];
      } finally {
        this.loading = false;
      }
    },

    getAccionClass(acc) {
      if (!acc) return 'badge-gray';
      const a = acc.toUpperCase();
      if (a.includes('CREA') || a.includes('ADD') || a.includes('REGISTRAR')) return 'badge-green';
      if (a.includes('ACTU') || a.includes('EDIT') || a.includes('UPDATE')) return 'badge-yellow';
      if (a.includes('BORR') || a.includes('ELIM') || a.includes('DELETE') || a.includes('BAJA')) return 'badge-red';
      if (a.includes('SESION') || a.includes('LOGIN') || a.includes('LOGOUT') || a.includes('SEGURIDAD')) return 'badge-blue';
      return 'badge-gray';
    },

    fmtFechaSolo(f) {
      if (!f) return '---';
      return new Date(f).toLocaleDateString('es-PE', { 
        day: '2-digit', month: '2-digit', year: 'numeric' 
      });
    },

    fmtHoraSolo(f) {
      if (!f) return '---';
      return new Date(f).toLocaleTimeString('es-PE', { 
        hour: '2-digit', minute: '2-digit', second: '2-digit' 
      });
    },

    esJson(str) {
      if (!str || typeof str !== 'string') return false;
      try {
        const parsed = JSON.parse(str);
        return parsed && typeof parsed === 'object';
      } catch (e) {
        return false;
      }
    },

    parseDetalle(str) {
      try {
        return JSON.parse(str);
      } catch (e) {
        return { mensaje: str, cambios: null };
      }
    },

    exportarExcel() {
      const params = new URLSearchParams(this.filters);
      window.open('../../../api/auditoria.php?action=exportar&' + params.toString(), '_blank');
    }
  },

  mounted() {
    this.fetchLogs();
  }
}).mount('#app-auditoria');
