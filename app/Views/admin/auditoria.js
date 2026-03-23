/**
 * admin/auditoria.js
 * Vue 3 Options API
 */
Vue.createApp({
  data() {
    return {
      logs: [],
      loading: false
    };
  },

  methods: {
    async fetchLogs() {
      this.loading = true;
      try {
        const res = await axios.get('../../../api/auditoria.php?action=listar');
        this.logs = Array.isArray(res.data.data) ? res.data.data : [];
      } catch (err) {
        console.error(err);
        this.logs = [];
      } finally {
        this.loading = false;
      }
    },

    getAccionClass(acc) {
      if (!acc) return 'badge bg-light text-dark border';
      const a = acc.toUpperCase();
      if (a.includes('CREAD')) return 'badge bg-success bg-opacity-10 text-success';
      if (a.includes('EDIT')) return 'badge bg-primary bg-opacity-10 text-primary';
      if (a.includes('FAIL') || a.includes('ERROR') || a.includes('DENI')) return 'badge bg-danger bg-opacity-10 text-danger';
      if (a.includes('PASS') || a.includes('LOGIN')) return 'badge bg-warning bg-opacity-10 text-warning text-dark';
      return 'badge bg-light text-dark border';
    },

    fmtFecha(f) {
      return new Date(f).toLocaleString('es-PE', { 
        day:'2-digit', month:'2-digit', year:'numeric', 
        hour:'2-digit', minute:'2-digit', second:'2-digit' 
      });
    }
  },

  mounted() {
    this.fetchLogs();
  }
}).mount('#app-auditoria');
