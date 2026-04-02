<?php
/**
 * app/Views/admin/usuarios.php
 */
$base = '../../../';
require_once $base . 'auth/middleware.php';
protegerPorRol('cajera', 'gestion_usuarios');

$page_title = 'Gestión de Usuarios — Hotel Manager';
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';
?>

<div class="main-content" id="app-usuarios">
  <div class="topbar">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
    <div>
      <h4><i class="bi bi-people-fill me-2 text-primary"></i>Gestión de Usuarios</h4>
      <p>Administración de personal y permisos</p>
    </div>
    <button class="btn btn-primary d-flex align-items-center gap-2" @click="nuevaUsuario">
      <i class="bi bi-person-plus-fill"></i> Nuevo Usuario
    </button>
  </div>

  <div class="page-body">
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
                <button v-if="u.rol !== 'admin'" class="btn btn-white btn-sm border" title="Gestionar Módulos" @click="abrirPermisos(u)">
                  <i class="bi bi-toggles text-info"></i>
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

    <!-- MODAL: PERMISOS DE MÓDULOS -->
    <div class="modal fade" id="modalPermisos" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow" style="border-radius:16px;">
          <div class="modal-header border-0 p-4 pb-0">
            <div>
              <h5 class="fw-bold mb-0"><i class="bi bi-toggles text-info me-2"></i>Acceso a Módulos</h5>
              <p class="text-muted small mb-0" v-if="usuarioPermisos">{{ usuarioPermisos.nombre }} — {{ usuarioPermisos.rol.toUpperCase() }}</p>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-4">
            <div v-if="loadingPermisos" class="text-center py-4">
              <div class="spinner-border text-primary"></div>
            </div>
            <div v-else>
              <p class="text-muted small mb-3">
                <i class="bi bi-info-circle me-1"></i>
                Activa o desactiva los módulos que verá este usuario en el menú lateral.
              </p>
              <div class="row g-3">
                <div class="col-md-6" v-for="p in permisosModulos" :key="p.modulo">
                  <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border"
                       :class="p.activo ? 'border-primary bg-primary bg-opacity-5' : 'border-secondary bg-light'">
                    <div class="d-flex align-items-center gap-2">
                      <i class="bi fs-5" :class="[p.icon, p.activo ? 'text-primary' : 'text-muted']"></i>
                      <span class="small fw-bold" :class="p.activo ? 'text-dark' : 'text-muted'">{{ p.label }}</span>
                    </div>
                    <div class="form-check form-switch mb-0">
                      <input class="form-check-input" type="checkbox"
                             :id="'perm_' + p.modulo"
                             v-model="p.activo"
                             :true-value="1" :false-value="0"
                             style="cursor:pointer; width:2.5em; height:1.3em;">
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-0 p-4 pt-0">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-primary px-5" @click="guardarPermisos" :disabled="loadingPermisos || guardandoPermisos">
              <span v-if="guardandoPermisos" class="spinner-border spinner-border-sm me-1"></span>
              {{ guardandoPermisos ? 'Guardando...' : 'Guardar Permisos' }}
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
  window.authUser = <?= json_encode(['id' => $_SESSION['auth_id'], 'nombre' => $_SESSION['auth_nombre'], 'usuario' => $_SESSION['auth_usuario']]) ?>;
</script>
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="usuarios.js?v=<?= time() ?>"></script>




<style>
  .btn-white { background: white; }
  .btn-white:hover { background: #f8f9fa; }
  .badge { padding: 8px 12px; border-radius: 8px; font-weight: 600; font-size: 11px; }
  .table thead th { font-size: 11px; letter-spacing: 0.5px; color: #6c757d; border-bottom: none; }
  .form-control, .form-select { padding: 10px 14px; border-radius: 8px; border: 1px solid #e0e0e0; }
  .form-control:focus, .form-select:focus { box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.05); }
</style>

</body></html>
