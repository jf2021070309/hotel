/**
 * Módulo Frontend de Gestión de Usuarios V2.
 */
Vue.createApp({
  data() {
    return {
      usuarios: [],
      busqueda: '',
      loading: false,
      authUser: window.authUser || {},
      
      // Para contraseñas
      current: {},
      newPassword: '',
      
      // Permisos de módulos
      usuarioPermisos: null,
      permisosModulos: [],
      loadingPermisos: false,
      guardandoPermisos: false
    };
  },

  computed: {
    usuariosFiltrados() {
      let f = this.usuarios;
      if (this.busqueda) {
        const q = this.busqueda.toLowerCase();
        f = f.filter(u => 
          (u.nombre && u.nombre.toLowerCase().includes(q)) ||
          (u.usuario && u.usuario.toLowerCase().includes(q))
        );
      }
      return f;
    },
    cambiosCount() {
      return this.usuarios.filter(u => u.modificado || !u.id).length;
    }
  },

  methods: {
    async fetchUsuarios() {
      this.loading = true;
      try {
        const res = await axios.get('../../../api/usuarios.php?action=listar');
        const data = res.data.data || [];
        this.usuarios = data.map(u => ({
          ...u,
          modificado: false
        }));
      } catch (err) {
        this.showToast('Error al cargar usuarios', 'error');
      } finally {
        this.loading = false;
      }
    },

    agregarFila() {
      this.usuarios.push({
        id: null,
        temp_id: 'temp_' + Date.now(),
        nombre: '',
        usuario: '',
        rol: 'cajera',
        estado: 1,
        modificado: true
      });
      // Scroll to bottom
      setTimeout(() => {
        const container = document.querySelector('.mensual-grid-container');
        if (container) container.scrollTop = container.scrollHeight;
      }, 100);
    },

    marcarModificado(u) {
      if (u.id) {
        u.modificado = true;
      }
    },

    async eliminarFila(u, index) {
      if (!u.id) {
        this.usuarios.splice(this.usuarios.indexOf(u), 1);
        return;
      }
      
      if (u.id == 1 || u.id == this.authUser.id) {
        return this.showToast('No puedes desactivar este usuario', 'error');
      }

      const res = await Swal.fire({
        title: '¿Desactivar usuario?',
        text: `¿Deseas cambiar el estado de ${u.nombre} a Inactivo?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, desactivar'
      });

      if (res.isConfirmed) {
        u.estado = 0;
        this.marcarModificado(u);
        await this.guardarCambiosMasivos();
      }
    },

    async guardarCambiosMasivos() {
      const cambiados = this.usuarios.filter(u => u.modificado || !u.id);
      if (cambiados.length === 0) return;

      // Validate required fields
      for (const u of cambiados) {
        if (!u.nombre || !u.nombre.trim() || !u.usuario || !u.usuario.trim()) {
          return this.showToast('Todos los usuarios deben tener Nombre y Login', 'error');
        }
      }

      this.loading = true;
      try {
        // Enviar cada cambio uno por uno (o en bulk si el backend lo soporta, pero lo haremos uno por uno por seguridad con la API actual)
        let successCount = 0;
        for (const u of cambiados) {
          const action = u.id ? 'editar' : 'crear';
          if (!u.id && !u.password) {
            u.password = '123456'; // Password default para nuevos
          }
          
          await axios.post(`../../../api/usuarios.php?action=${action}`, u);
          successCount++;
        }
        
        this.showToast(`Se guardaron ${successCount} usuarios correctamente`, 'success');
        await this.fetchUsuarios();
      } catch (err) {
        this.showToast('Hubo un error al guardar algunos cambios', 'error');
        console.error(err);
      } finally {
        this.loading = false;
      }
    },

    abrirModalPass(u) {
      this.current = { ...u };
      this.newPassword = '';
      const modal = new bootstrap.Modal(document.getElementById('modalPass'));
      modal.show();
    },

    async cambiarPass() {
      if (!this.newPassword) return;
      this.loading = true;
      try {
        const res = await axios.post('../../../api/usuarios.php?action=cambiar_pass', { 
          id: this.current.id, 
          password: this.newPassword 
        });
        if (res.data.ok) {
          this.showToast('Contraseña actualizada', 'success');
          bootstrap.Modal.getInstance(document.getElementById('modalPass')).hide();
        }
      } catch (err) {
        this.showToast(err.response?.data?.msg || 'Error', 'error');
      } finally {
        this.loading = false;
      }
    },

    async abrirPermisos(u) {
      this.usuarioPermisos  = u;
      this.permisosModulos  = [];
      this.loadingPermisos  = true;
      new bootstrap.Modal(document.getElementById('modalPermisos')).show();
      try {
        const res = await axios.get(`../../../api/permisos.php?action=listar&usuario_id=${u.id}`);
        this.permisosModulos = res.data.data || [];
      } catch (e) {
        this.showToast('Error al cargar permisos', 'error');
      } finally {
        this.loadingPermisos = false;
      }
    },

    async guardarPermisos() {
      this.guardandoPermisos = true;
      try {
        const res = await axios.post('../../../api/permisos.php?action=guardar', {
          usuario_id: this.usuarioPermisos.id,
          permisos: this.permisosModulos.map(p => ({ modulo: p.modulo, activo: p.activo }))
        });
        if (res.data.ok) {
          bootstrap.Modal.getInstance(document.getElementById('modalPermisos')).hide();
          this.showToast('Permisos guardados correctamente', 'success');
        } else {
          this.showToast(res.data.message || 'Error al guardar', 'error');
        }
      } catch (e) {
        this.showToast('Error de red', 'error');
      } finally {
        this.guardandoPermisos = false;
      }
    },

    showToast(msg, icon) {
      Swal.fire({
        toast: true, position: 'top-end', icon: icon,
        title: msg, showConfirmButton: false, timer: 3000,
        timerProgressBar: true
      });
    },

    getRolTextClass(rol) {
      switch(rol) {
        case 'admin': return 'text-danger';
        case 'supervisor': return 'text-warning';
        case 'cajera': return 'text-primary';
        case 'limpieza': return 'text-success';
        default: return 'text-secondary';
      }
    }
  },

  mounted() {
    this.fetchUsuarios();
  }
}).mount('#app-usuarios');
