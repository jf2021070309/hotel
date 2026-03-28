/**
 * admin/usuarios.js
 * Vue 3 Options API
 */
Vue.createApp({
  data() {
    return {
      usuarios: [],
      loading: false,
      editMode: false,
      current: {
        id: null,
        usuario: '',
        nombre: '',
        rol: 'cajera',
        password: '',
        estado: 1
      },
      newPassword: '',
      authUser: window.authUser || {},
      // Permisos de módulos
      usuarioPermisos: null,
      permisosModulos: [],
      loadingPermisos: false,
      guardandoPermisos: false
    };
  },

  methods: {
    async fetchUsuarios() {
      this.loading = true;
      try {
        const res = await axios.get('../../../api/usuarios.php?action=listar');
        this.usuarios = res.data.data || [];
      } catch (err) {
        this.showToast('Error al cargar usuarios', 'error');
      } finally {
        this.loading = false;
      }
    },

    nuevaUsuario() {
      this.editMode = false;
      this.current = { id: null, usuario: '', nombre: '', rol: 'cajera', password: '', estado: 1 };
      const modal = new bootstrap.Modal(document.getElementById('modalUsuario'));
      modal.show();
    },

    abrirModalEditar(u) {
      this.editMode = true;
      this.current = { ...u, password: '' };
      const modal = new bootstrap.Modal(document.getElementById('modalUsuario'));
      modal.show();
    },

    abrirModalPass(u) {
      this.current = { ...u };
      this.newPassword = '';
      const modal = new bootstrap.Modal(document.getElementById('modalPass'));
      modal.show();
    },

    async guardarUsuario() {
      if (!this.current.usuario || !this.current.nombre) {
        return this.showToast('Completa los campos obligatorios', 'error');
      }
      
      this.loading = true;
      try {
        const action = this.editMode ? 'editar' : 'crear';
        const url = `../../../api/usuarios.php?action=${action}`;
        const res = await axios.post(url, this.current);
        
        if (res.data.ok) {
          this.showToast(res.data.msg, 'success');
          bootstrap.Modal.getInstance(document.getElementById('modalUsuario')).hide();
          this.fetchUsuarios();

          // Sincronizar sidebar si es mi perfil
          if (this.editMode && this.current.id === this.authUser.id) {
            this.syncSidebar();
          }
        }
      } catch (err) {
        this.showToast(err.response?.data?.msg || 'Error al guardar', 'error');
      } finally {
        this.loading = false;
      }
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

    async toggleEstado(u) {
      if (u.id === this.authUser.id) {
        return Swal.fire('Error', 'No puedes desactivar tu propia cuenta', 'error');
      }

      const accion = u.estado == 1 ? 'desactivar' : 'activar';
      const result = await Swal.fire({
        title: `¿${accion.charAt(0).toUpperCase() + accion.slice(1)} usuario?`,
        text: `Vas a ${accion} a ${u.nombre}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar'
      });

      if (result.isConfirmed) {
        try {
          const nuevoEstado = u.estado == 1 ? 0 : 1;
          await axios.post('../../../api/usuarios.php?action=editar', { ...u, estado: nuevoEstado });
          this.showToast('Estado actualizado', 'success');
          this.fetchUsuarios();
        } catch (err) {
          this.showToast('Error al cambiar estado', 'error');
        }
      }
    },

    syncSidebar() {
      const elName  = document.getElementById('sidebarUserName');
      const elLogin = document.getElementById('sidebarUserLogin');
      const elRole  = document.getElementById('sidebarUserRole');
      const elAvatar= document.getElementById('sidebarAvatarLetter');

      if (elName) elName.textContent = this.current.nombre;
      if (elLogin) elLogin.textContent = this.current.usuario;
      if (elRole) elRole.textContent = this.current.rol.charAt(0).toUpperCase() + this.current.rol.slice(1);
      if (elAvatar) elAvatar.textContent = this.current.nombre.charAt(0).toUpperCase();
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
        toast: true,
        position: 'top-end',
        icon: icon,
        title: msg,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
      });
    },

    getRolClass(rol) {
      switch(rol) {
        case 'admin': return 'bg-danger text-white';
        case 'supervisor': return 'bg-warning text-dark';
        case 'cajera': return 'bg-primary text-white';
        case 'limpieza': return 'bg-success text-white';
        default: return 'bg-secondary';
      }
    },

    fmtFecha(fecha) {
      return new Date(fecha).toLocaleDateString('es-PE', { 
        day:'2-digit', month:'2-digit', year:'numeric', 
        hour:'2-digit', minute:'2-digit' 
      });
    }
  },

  mounted() {
    this.fetchUsuarios();
  }
}).mount('#app-usuarios');
