<?php
/**
 * app/Views/admin/usuarios.php
 */
require_once __DIR__ . '/../../../app/Middleware/auth.php';
protegerPorRol('cajera', 'gestion_usuarios');

require_once __DIR__ . '/../../../config/db.php';

$page_title = 'Gestión de Usuarios — Hotel Manager';
include __DIR__ . '/../layouts/head.php';
?>

<div id="app-usuarios" style="display:contents" v-cloak>
  <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
  
  <div class="main-content">
    <!-- TOPBAR PREMIUM DARK -->
    <div class="topbar" style="background-color:#111827;padding:0.75rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.05);">
      <div class="d-flex align-items-center justify-content-between w-100">
        <div class="d-flex align-items-center gap-3">
          <button class="btn btn-dark btn-sm rounded-circle d-md-none" onclick="handleMenuClick()" style="width:32px;height:32px;padding:0;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.1);border:none;">
            <i class="bi bi-list text-white"></i>
          </button>
          <div class="d-flex align-items-center gap-3">
            <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#f59e0b,#d97706);display:flex;align-items:center;justify-content:center;box-shadow:0 0 15px rgba(245,158,11,0.4);">
              <i class="bi bi-people-fill text-white fs-5"></i>
            </div>
            <div>
              <h4 class="fw-bold mb-0 text-white" style="font-size:18px;letter-spacing:-0.5px;">Gestión de Usuarios</h4>
              <div class="text-white-50" style="font-size:11px;">Administración de personal y permisos estilo Excel</div>
            </div>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-sm btn-outline-light d-flex align-items-center gap-2" @click="fetchUsuarios" :disabled="loading" style="font-size:12px;padding:4px 12px;border-color:rgba(255,255,255,0.2);">
            <i class="bi bi-arrow-clockwise" :class="{'spin-anim': loading}"></i>
            <span class="d-none d-md-inline">Actualizar</span>
          </button>
        </div>
      </div>
    </div>

    <!-- BODY -->
    <div class="page-body pt-3">
      <!-- CONTROL BAR & ACCIONES -->
      <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
        <div class="card-body p-3">
          <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
              <div class="input-group input-group-sm rounded shadow-sm" style="width: 300px;">
                <span class="input-group-text bg-white border-end-0 text-muted px-2"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-start-0 bg-white text-dark" 
                       style="font-size: 13px;" v-model="busqueda" placeholder="Buscar usuario...">
              </div>
              <div class="text-muted fw-semibold" style="font-size: 12px;" v-if="!loading">
                <i class="bi bi-list-ul me-1"></i>{{ usuariosFiltrados.length }} registros
              </div>
            </div>
            
            <div class="d-flex align-items-center gap-2">
              <button class="btn btn-sm btn-custom-blue fw-bold px-3 shadow-sm" @click="guardarCambiosMasivos" :disabled="loading || cambiosCount === 0" style="font-size: 12px;">
                <i class="bi bi-save me-1"></i>Guardar Cambios
                <span v-if="cambiosCount > 0" class="badge bg-warning text-dark ms-1" style="font-size: 10px;">{{ cambiosCount }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- GRID INTERACTIVE CONTAINER -->
      <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 12px;">
        <div class="mensual-grid-container">
          <table class="table table-bordered table-hover mb-0 align-middle table-mensual">
            <thead>
              <tr class="text-center text-white text-uppercase" style="font-size: 10px; letter-spacing: 0.5px; font-weight: 800;">
                <th colspan="4" class="sticky-col" style="background-color: #111827 !important; border-bottom: 1px solid rgba(255,255,255,0.1) !important; z-index: 13 !important;">DATOS DEL USUARIO</th>
                <th colspan="2" style="background-color: #293b95 !important; border-bottom: 1px solid rgba(255,255,255,0.1) !important; border-left: 1px solid rgba(255,255,255,0.1) !important;">SISTEMA Y ACCESO</th>
                <th colspan="1" style="background-color: #6a1b9a !important; border-bottom: 1px solid rgba(255,255,255,0.1) !important; border-left: 1px solid rgba(255,255,255,0.1) !important;">MÁS ACCIONES</th>
              </tr>
              <tr class="text-white text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                <th class="sticky-col text-center" style="width: 50px; top: 38px; z-index: 12 !important; background-color: #111827 !important; border-right: 1px solid rgba(255,255,255,0.1) !important;"><i class="bi bi-trash"></i></th>
                <th class="sticky-col text-center" style="width: 70px; top: 38px; z-index: 12 !important; background-color: #111827 !important; border-right: 1px solid rgba(255,255,255,0.1) !important;">ID</th>
                <th style="width: 250px; top: 38px; background-color: #111827 !important; border-right: 1px solid rgba(255,255,255,0.1) !important;">NOMBRE</th>
                <th style="width: 150px; top: 38px; background-color: #111827 !important; border-right: 1px solid rgba(255,255,255,0.1) !important;">LOGIN (USUARIO)</th>
                <th style="width: 150px; top: 38px; background-color: #293b95 !important; border-right: 1px solid rgba(255,255,255,0.1) !important;">ROL</th>
                <th style="width: 120px; top: 38px; background-color: #293b95 !important; border-right: 1px solid rgba(255,255,255,0.1) !important;">ESTADO</th>
                <th class="text-center" style="width: 150px; top: 38px; background-color: #6a1b9a !important;">SEGURIDAD</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="7" class="text-center py-5">
                  <div class="spinner-border text-primary me-2"></div>
                  <span class="text-muted fw-semibold">Cargando usuarios...</span>
                </td>
              </tr>
              <tr v-else-if="usuariosFiltrados.length === 0">
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="bi bi-people fs-1 d-block opacity-25 mb-2"></i>
                  <span>No se encontraron usuarios.</span>
                </td>
              </tr>
              <tr v-else v-for="(u, idx) in usuariosFiltrados" :key="u.id || u.temp_id" :class="{'unsaved-row': u.modificado || !u.id}">
                <!-- ELIMINAR -->
                <td class="sticky-col text-center px-1">
                  <button class="btn btn-sm btn-link text-danger p-0" @click="eliminarFila(u, idx)" title="Eliminar registro" :disabled="u.id == 1 || u.id == authUser.id">
                    <i class="bi bi-trash-fill fs-6"></i>
                  </button>
                </td>
                
                <!-- ID -->
                <td class="sticky-col text-center px-1">
                  <span v-if="u.id" class="badge bg-light text-dark border">#{{ u.id }}</span>
                  <span v-else class="badge bg-warning text-dark border">Nuevo</span>
                </td>
                
                <!-- NOMBRE -->
                <td class="px-1">
                  <input type="text" v-model="u.nombre" class="table-editable-input fw-bold text-dark text-uppercase" @input="marcarModificado(u)" :disabled="u.id == 1" style="width: 100%;">
                </td>

                <!-- LOGIN -->
                <td class="px-1">
                  <input type="text" v-model="u.usuario" class="table-editable-input text-dark fw-bold" @input="marcarModificado(u)" :disabled="u.id == 1" style="width: 100%;">
                </td>
                
                <!-- ROL -->
                <td class="px-1">
                  <select v-model="u.rol" class="table-editable-input fw-bold text-dark" @change="marcarModificado(u)" :disabled="u.id == 1" style="width: 100%; cursor: pointer;">
                    <option value="admin">Administrador</option>
                    <option value="supervisor">Supervisor</option>
                    <option value="cajera">Cajera</option>
                    <option value="limpieza">Limpieza</option>
                  </select>
                </td>
                
                <!-- ESTADO -->
                <td class="px-1">
                  <select v-model="u.estado" class="table-editable-input fw-bold text-dark" @change="marcarModificado(u)" :disabled="u.id == 1 || u.id == authUser.id" style="width: 100%; cursor: pointer;">
                    <option :value="1">Activo</option>
                    <option :value="0">Inactivo</option>
                  </select>
                </td>

                <!-- MÁS ACCIONES -->
                <td class="text-center px-1">
                  <div class="d-flex justify-content-center gap-1">
                    <button v-if="u.id && u.id != 1" @click="abrirModalPass(u)" class="btn btn-sm btn-white border hover-bg-premium text-dark fw-semibold d-flex align-items-center gap-1" style="font-size: 11px; padding: 4px 8px;" title="Cambiar Contraseña">
                      <i class="bi bi-key-fill text-warning" style="font-size: 13px;"></i>
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Fila adicional para "Añadir Fila" -->
              <tr v-if="!loading && busqueda === ''">
                <td class="sticky-col text-center px-1" style="background-color: #f8fafc;">
                  <button class="btn btn-sm text-primary p-0 w-100 d-flex align-items-center justify-content-center hover-bg-premium" 
                          @click="agregarFila" title="Añadir nuevo usuario" 
                          style="min-height: 28px; border: 2px dashed #93c5fd; background-color: #eff6ff; border-radius: 4px;">
                    <i class="bi bi-plus-lg fw-bold" style="font-size: 15px;"></i>
                  </button>
                </td>
                <td colspan="6" class="bg-light text-muted" style="vertical-align: middle; padding-left: 15px; font-size: 11px;">
                  Haga clic en el botón <i class="bi bi-plus-lg text-primary fw-bold"></i> para agregar un nuevo usuario al final de la tabla. Al guardar, se le asignará "123456" como contraseña por defecto.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- MODAL: CAMBIAR PASSWORD -->
    <div class="modal fade" id="modalPass" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
          <div class="modal-header border-0 px-4 pt-4 pb-3 bg-light">
            <div class="d-flex align-items-center gap-3">
              <div class="bg-white border d-flex align-items-center justify-content-center rounded-circle shadow-sm" style="width: 48px; height: 48px;">
                <i class="bi bi-key text-dark fs-4"></i>
              </div>
              <div>
                <h5 class="fw-bold mb-1 text-dark" style="letter-spacing: -0.5px;">Cambiar Contraseña</h5>
                <p class="text-muted mb-0" style="font-size: 13px;">Usuario: <strong class="text-dark">{{ current.usuario }}</strong></p>
              </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" style="align-self: flex-start;"></button>
          </div>
          <form @submit.prevent="cambiarPass">
            <div class="modal-body p-4 bg-white">
              <div class="mb-2">
                <label class="form-label text-muted small fw-bold mb-2">NUEVA CONTRASEÑA</label>
                <div class="input-group input-group-lg shadow-sm" style="border-radius: 10px;">
                  <span class="input-group-text bg-light border-end-0 text-muted" style="border-radius: 10px 0 0 10px;"><i class="bi bi-lock-fill"></i></span>
                  <input v-model="newPassword" type="password" class="form-control border-start-0 fs-6" required placeholder="••••••••" style="border-radius: 0 10px 10px 0;">
                </div>
              </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-3 bg-light d-flex flex-nowrap gap-2 justify-content-end">
              <button type="button" class="btn btn-white border px-4 fw-bold shadow-sm flex-fill" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-dark px-4 fw-bold shadow-sm flex-fill" :disabled="loading">Actualizar</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- MODAL: PERMISOS DE MÓDULOS -->
    <div class="modal fade" id="modalPermisos" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 500px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
          <div class="modal-header border-0 px-4 pt-4 pb-3 bg-light">
            <div class="d-flex align-items-center gap-3">
              <div class="bg-white border d-flex align-items-center justify-content-center rounded-circle shadow-sm" style="width: 48px; height: 48px;">
                <i class="bi bi-toggles text-dark fs-4"></i>
              </div>
              <div>
                <h5 class="fw-bold mb-1 text-dark" style="letter-spacing: -0.5px;">Acceso a Módulos</h5>
                <p class="text-muted mb-0" style="font-size: 13px;" v-if="usuarioPermisos">
                  <span class="fw-bold text-dark">{{ usuarioPermisos.nombre }}</span> &bull; {{ usuarioPermisos.rol.toUpperCase() }}
                </p>
              </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" style="align-self: flex-start;"></button>
          </div>
          <div class="modal-body p-4 bg-white">
            <div v-if="loadingPermisos" class="text-center py-4">
              <div class="spinner-border text-dark"></div>
            </div>
            <div v-else>
              <p class="text-muted small mb-4">
                <i class="bi bi-info-circle-fill me-1 text-dark"></i>
                Activa o desactiva los módulos que verá este usuario.
              </p>
              <div class="d-flex flex-column gap-2">
                <div v-for="p in permisosModulos" :key="p.modulo">
                  <div class="d-flex align-items-center justify-content-between p-3 rounded-3"
                       :style="p.activo ? 'border: 2px solid #1e293b; background-color: #f8fafc;' : 'border: 1px solid #e2e8f0; background-color: #ffffff; opacity: 0.7;'"
                       style="transition: all 0.2s ease; cursor: pointer;"
                       @click="p.activo = p.activo ? 0 : 1">
                    <div class="d-flex align-items-center gap-3">
                      <div class="d-flex align-items-center justify-content-center rounded-circle" 
                           :class="p.activo ? 'bg-dark text-white shadow-sm' : 'bg-light text-muted'"
                           style="width: 36px; height: 36px; transition: all 0.2s ease;">
                        <i class="bi fs-5" :class="p.icon"></i>
                      </div>
                      <span class="fw-bold" :class="p.activo ? 'text-dark' : 'text-muted'" style="font-size: 14px;">{{ p.label }}</span>
                    </div>
                    <div class="form-check form-switch mb-0" @click.stop>
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
          <div class="modal-footer border-0 p-4 pt-3 bg-light d-flex flex-nowrap gap-2 justify-content-end">
            <button type="button" class="btn btn-white border px-4 fw-bold shadow-sm flex-fill" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-dark px-4 fw-bold shadow-sm flex-fill" @click="guardarPermisos" :disabled="loadingPermisos || guardandoPermisos">
              <span v-if="guardandoPermisos" class="spinner-border spinner-border-sm me-1"></span>
              {{ guardandoPermisos ? 'Guardando...' : 'Guardar Permisos' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  [v-cloak] { display: none !important; }
  
  .mensual-grid-container {
    max-height: calc(100vh - 145px);
    overflow: auto;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    background-color: #f8fafc;
  }
  
  .mensual-grid-container::-webkit-scrollbar { width: 10px; height: 10px; }
  .mensual-grid-container::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
  .mensual-grid-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; border: 2px solid #f1f5f9; }
  .mensual-grid-container::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

  .table-mensual {
    min-width: 900px;
    font-size: 11.5px;
    border-collapse: separate;
    border-spacing: 0;
  }
  
  .table-mensual thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    color: #ffffff !important;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.5px;
    text-align: center;
    border: 1px solid #cbd5e1;
    vertical-align: middle;
    padding: 10px 6px;
  }

  .table-mensual th.sticky-col,
  .table-mensual td.sticky-col {
    position: sticky;
    left: 0;
    z-index: 6;
    background-color: #f8fafc;
    border-right: 1px solid #cbd5e1;
  }
  
  .table-mensual thead th.sticky-col { z-index: 12 !important; }
  .table-mensual td { padding: 3px 4px; vertical-align: middle; border: 1px solid #e2e8f0; background-color: #ffffff; }
  .table-mensual tbody tr:hover td { background-color: #f1f5f9; }
  .table-mensual tbody tr:hover td.sticky-col { background-color: #e2e8f0; }
  
  .unsaved-row td { background-color: #fefbeb !important; }
  .unsaved-row td.sticky-col { background-color: #fef3c7 !important; }
  .unsaved-row { border-left: 4px solid #f59e0b !important; }

  .table-editable-input {
    border: 1px solid transparent;
    background: transparent;
    padding: 4px 6px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: inherit;
    color: inherit;
    text-align: inherit;
    transition: all 0.15s ease;
  }
  .table-editable-input:hover:not(:disabled) { border-color: #cbd5e1; background: #f8fafc; }
  .table-editable-input:focus:not(:disabled) {
    border-color: #3b82f6;
    background: #ffffff;
    outline: none;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
  }
  .table-editable-input:disabled { opacity: 0.7; cursor: not-allowed; }

  .hover-bg-premium:hover { background-color: #f8fafc !important; border-color: #cbd5e1 !important; }
  .hover-bg-light:hover { background-color: rgba(255,255,255,0.15) !important; }

  .btn-custom-blue {
    background-color: #1a56db !important;
    color: #ffffff !important;
    border: 1px solid #1e429f !important;
    transition: all 0.2s ease-in-out;
  }
  .btn-custom-blue:hover:not(:disabled) { background-color: #1e429f !important; border-color: #1e429f !important; }
  .btn-custom-blue:disabled { opacity: 0.65; }
</style>

<script>
  window.authUser = <?= json_encode(['id' => $_SESSION['auth_id'], 'nombre' => $_SESSION['auth_nombre'], 'usuario' => $_SESSION['auth_usuario']]) ?>;
</script>
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="usuarios.js?v=<?= time() ?>"></script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
