<?php
/**
 * app/Views/admin/usuarios.php
 */
$base = '../../../';
require_once $base . 'auth/middleware.php';
protegerPorRol('admin');

$page_title = 'Gestión de Usuarios — Hotel Manager';
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';
?>

<div class="main-content">
  <div class="topbar">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
    <div>
      <h4><i class="bi bi-people-fill me-2 text-primary"></i>Gestión de Usuarios</h4>
      <p>Administración de personal y permisos</p>
    </div>
    <button class="btn btn-primary d-flex align-items-center gap-2" @click="abrirModalCrear">
      <i class="bi bi-person-plus-fill"></i> Nuevo Usuario
    </button>
  </div>

  <div class="page-body" id="app-usuarios">
    <!-- Alertas via SweetAlert2 (eliminamos div feedback) -->

    <!-- LISTA DE USUARIOS -->
    <div class="card border-0 shadow-sm overflow-hidden" style="border-radius:12px;">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light">
            <tr>
              <th class="ps-4">ID</th>
              <th>Nombre</th>
              <th>Usuario</th>
              <th>Rol</th>
              <th>Estado</th>
              <th class="text-end pe-4">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in usuarios" :key="u.id">
              <td class="ps-4 text-muted small">#{{ u.id }}</td>
              <td>
                <div class="fw-bold">{{ u.nombre }}</div>
                <div class="text-muted small">Creado: {{ fmtFecha(u.created_at) }}</div>
              </td>
              <td><code class="text-primary fw-bold">{{ u.usuario }}</code></td>
              <td>
                <span class="badge" :class="getRolClass(u.rol)">{{ u.rol.toUpperCase() }}</span>
              </td>
              <td>
                <span :class="u.estado == 1 ? 'text-success' : 'text-danger'">
                  <i class="bi bi-circle-fill me-1" style="font-size:8px"></i> {{ u.estado == 1 ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td class="text-end pe-4">
                <div class="btn-group shadow-sm" style="border-radius:8px; overflow:hidden;">
                  <button class="btn btn-white btn-sm border" title="Editar" @click="abrirModalEditar(u)">
                    <i class="bi bi-pencil-square text-primary"></i>
                  </button>
                  <button class="btn btn-white btn-sm border" title="Cambiar Contraseña" @click="abrirModalPass(u)">
                    <i class="bi bi-key text-warning"></i>
                  </button>
                  <button class="btn btn-white btn-sm border" :title="u.estado == 1 ? 'Desactivar' : 'Activar'" @click="toggleEstado(u)">
                    <i class="bi" :class="u.estado == 1 ? 'bi-person-x text-danger' : 'bi-person-check text-success'"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODAL: CREAR/EDITAR -->
    <div class="modal fade" id="modalUsuario" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius:16px;">
          <div class="modal-header border-0 pb-0 ps-4 pe-4 pt-4">
            <h5 class="fw-bold">{{ editMode ? 'Editar Usuario' : 'Nuevo Usuario' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <form @submit.prevent="guardarUsuario">
            <div class="modal-body p-4">
              <div class="mb-3">
                <label class="form-label text-muted small fw-bold">NOMBRE COMPLETO</label>
                <input v-model="current.nombre" type="text" class="form-control" required placeholder="Ej: Kari Quiroz">
              </div>
              <div class="mb-3">
                <label class="form-label text-muted small fw-bold">USUARIO (Login)</label>
                <input v-model="current.usuario" type="text" class="form-control" required placeholder="Ej: karian" :disabled="editMode && current.id === 1">
              </div>
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label text-muted small fw-bold">ROL</label>
                  <select v-model="current.rol" class="form-select" required :disabled="editMode && current.id === 1">
                    <option value="admin">Administrador</option>
                    <option value="supervisor">Supervisor</option>
                    <option value="cajera">Cajera</option>
                    <option value="limpieza">Limpieza</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label text-muted small fw-bold">ESTADO</label>
                  <select v-model="current.estado" class="form-select" required :disabled="editMode && current.id === authUser.id">
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                  </select>
                </div>
              </div>
              <div class="mb-3" v-if="!editMode">
                <label class="form-label text-muted small fw-bold">CONTRASEÑA</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-0"><i class="bi bi-lock"></i></span>
                  <input v-model="current.password" type="password" class="form-control" required placeholder="••••••••">
                </div>
              </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
              <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary px-4" :disabled="loading">
                {{ loading ? 'Guardando...' : 'Guardar Cambios' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- MODAL: CAMBIAR PASSWORD -->
    <div class="modal fade" id="modalPass" tabindex="-1">
      <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius:16px;">
          <div class="modal-header border-0 pb-0">
            <h6 class="fw-bold">Cambiar Contraseña</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <form @submit.prevent="cambiarPass">
            <div class="modal-body p-4">
              <p class="small text-muted mb-3">Usuario: <strong>{{ current.usuario }}</strong></p>
              <div class="mb-3">
                <label class="form-label text-muted small fw-bold">NUEVA CONTRASEÑA</label>
                <input v-model="newPassword" type="password" class="form-control" required placeholder="••••••••">
              </div>
            </div>
            <div class="modal-footer border-0 pb-4">
              <button type="submit" class="btn btn-warning w-100 fw-bold" :disabled="loading">Actualizar</button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>
</div>

<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  const { createApp, ref, reactive, onMounted } = Vue;

  createApp({
    setup() {
      const usuarios = ref([]);
      const loading  = ref(false);
      const editMode = ref(false);
      const newPassword = ref('');
      const current  = reactive({ id: null, nombre: '', usuario: '', rol: 'cajera', estado: 1, password: '' });
      const authUser = <?= json_encode(obtenerUsuarioActual()) ?>;

      let modalBS = null;
      let modalPassBS = null;

      const fetchUsuarios = async () => {
        try {
          const res = await axios.get('../../../api/usuarios/listar.php');
          usuarios.value = res.data.data;
        } catch (err) {
          showToast('Error al cargar usuarios', 'error');
        }
      };

      const abrirModalCrear = () => {
        editMode.value = false;
        Object.assign(current, { id: null, nombre: '', usuario: '', rol: 'cajera', estado: 1, password: '' });
        modalBS.show();
      };

      const abrirModalEditar = (u) => {
        editMode.value = true;
        Object.assign(current, { ...u });
        modalBS.show();
      };

      const abrirModalPass = (u) => {
        Object.assign(current, { ...u });
        newPassword.value = '';
        modalPassBS.show();
      };

      const guardarUsuario = async () => {
        loading.value = true;
        try {
          const url = editMode.value ? '../../../api/usuarios/editar.php' : '../../../api/usuarios/crear.php';
          const res = await axios.post(url, current);
          if (res.data.ok) {
            showToast(res.data.msg, 'success');
            modalBS.hide();
            fetchUsuarios();
          }
        } catch (err) {
          showToast(err.response?.data?.msg || 'Error al guardar', 'error');
        } finally {
          loading.value = false;
        }
      };

      const cambiarPass = async () => {
        loading.value = true;
        try {
          const res = await axios.post('../../../api/usuarios/cambiar_pass.php', { id: current.id, password: newPassword.value });
          if (res.data.ok) {
            showToast('Contraseña actualizada', 'success');
            modalPassBS.hide();
          }
        } catch (err) {
          showToast('Error al cambiar contraseña', 'error');
        } finally {
          loading.value = false;
        }
      };

      const toggleEstado = async (u) => {
        if (u.id === authUser.id) return showToast('No puedes desactivar tu propio usuario', 'warning');
        
        const resSwal = await Swal.fire({
          title: '¿Estás seguro?',
          text: `Vas a ${u.estado == 1 ? 'desactivar' : 'activar'} a ${u.nombre}`,
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Sí, continuar',
          cancelButtonText: 'Cancelar'
        });

        if (!resSwal.isConfirmed) return;
        
        try {
          const nuevoEstado = u.estado == 1 ? 0 : 1;
          await axios.post('../../../api/usuarios/editar.php', { ...u, estado: nuevoEstado });
          showToast('Estado actualizado', 'success');
          fetchUsuarios();
        } catch (err) {
          showToast(err.response?.data?.msg || 'Error al cambiar estado', 'error');
        }
      };

      const getRolClass = (rol) => {
        switch(rol) {
          case 'admin': return 'bg-danger text-white';
          case 'supervisor': return 'bg-warning text-dark';
          case 'cajera': return 'bg-primary text-white';
          case 'limpieza': return 'bg-success text-white';
          default: return 'bg-secondary';
        }
      };

      const showToast = (msg, tipo = 'success') => {
        Swal.fire({
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
          icon: tipo,
          title: msg
        });
      };

      const fmtFecha = (fecha) => new Date(fecha).toLocaleDateString('es-PE', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' });

      onMounted(() => {
        fetchUsuarios();
        modalBS = new bootstrap.Modal(document.getElementById('modalUsuario'));
        modalPassBS = new bootstrap.Modal(document.getElementById('modalPass'));
      });

      return { usuarios, current, loading, editMode, newPassword, authUser, 
               abrirModalCrear, abrirModalEditar, abrirModalPass, guardarUsuario, 
               cambiarPass, toggleEstado, getRolClass, fmtFecha, fetchUsuarios };
    }
  }).mount('#app-usuarios');
</script>

<style>
  .btn-white { background: white; }
  .btn-white:hover { background: #f8f9fa; }
  .badge { padding: 8px 12px; border-radius: 8px; font-weight: 600; font-size: 11px; }
  .table thead th { font-size: 11px; letter-spacing: 0.5px; color: #6c757d; border-bottom: none; }
  .form-control, .form-select { padding: 10px 14px; border-radius: 8px; border: 1px solid #e0e0e0; }
  .form-control:focus, .form-select:focus { box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.05); }
</style>

</body></html>
