<?php
/**
 * app/Views/admin/medios_pago.php
 */
$base = '../../../';
require_once $base . 'auth/middleware.php';
protegerPorRol('admin', 'medios_pago');

$page_title = 'Medios de Pago — Hotel Manager';
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';
?>

<div class="main-content" id="app-medios-pago">
  <div class="topbar">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
    <div>
      <h4><i class="bi bi-credit-card-2-back-fill me-2 text-primary"></i>Medios de Pago</h4>
      <p class="mb-0 small text-muted">Configuración de métodos de ingreso para el hotel</p>
    </div>
    <button class="btn btn-primary d-flex align-items-center gap-2" @click="abrirNuevo">
      <i class="bi bi-plus-lg"></i> Nuevo Medio
    </button>
  </div>

  <div class="page-body">
    <div class="row">
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius:12px;">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="bg-light">
                <tr>
                  <th class="ps-4" style="width: 80px;">ORDEN</th>
                  <th>DESCRIPCIÓN</th>
                  <th style="width: 150px;">ESTADO</th>
                  <th class="text-end pe-4" style="width: 150px;">ACCIONES</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loading" ><td colspan="4" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>
                <tr v-else v-for="m in medios" :key="m.id">
                  <td class="ps-4">
                    <span class="badge bg-light text-dark border">{{ m.orden }}</span>
                  </td>
                  <td>
                    <div class="fw-bold">{{ m.nombre }}</div>
                    <div class="text-muted mini text-uppercase">ID Categoría: {{ m.id }}</div>
                  </td>
                  <td>
                    <div class="form-check form-switch cursor-pointer" @click.prevent="toggleEstado(m)">
                      <input class="form-check-input" type="checkbox" :checked="m.activo == 1">
                      <label class="form-check-label small" :class="m.activo == 1 ? 'text-success' : 'text-danger'">
                        {{ m.activo == 1 ? 'Activo' : 'Inactivo' }}
                      </label>
                    </div>
                  </td>
                  <td class="text-end pe-4">
                    <div class="btn-group">
                      <button class="btn btn-sm btn-light" @click="editar(m)" title="Editar">
                        <i class="bi bi-pencil-square text-primary"></i>
                      </button>
                      <button class="btn btn-sm btn-light" @click="eliminar(m)" title="Eliminar">
                        <i class="bi bi-trash text-danger"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="!loading && medios.length === 0">
                  <td colspan="4" class="text-center py-5 text-muted">No se encontraron medios de pago configurados.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      
      <div class="col-lg-4 mt-4 mt-lg-0">
        <div class="card border-0 shadow-sm sticky-top" style="top:90px; border-radius:12px;">
            <div class="card-body p-4 text-center">
                <i class="bi bi-info-circle fs-1 text-primary mb-3 d-block"></i>
                <h6 class="fw-bold">Gestión de Medios</h6>
                <p class="small text-muted mb-0">
                    Estos medios aparecerán en los formularios de Rooming y Flujo de Caja. 
                    El orden determina su posición en las listas desplegables.
                </p>
            </div>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL FORMULARIO -->
  <div class="modal fade" id="modalMedio" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow" style="border-radius:16px;">
        <div class="modal-header bg-light border-0 py-3" style="border-radius:16px 16px 0 0;">
          <h5 class="modal-title fw-bold">{{ form.id ? 'Editar Medio' : 'Nuevo Medio de Pago' }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form @submit.prevent="guardar">
          <div class="modal-body p-4">
            <div class="mb-3">
              <label class="form-label small fw-bold text-muted">DESCRIPCIÓN (Eje: POS SOLES, YAPE, etc.)</label>
              <input type="text" class="form-control" v-model="form.nombre" required placeholder="Nombre del medio de pago">
            </div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small fw-bold text-muted">ORDEN</label>
                <input type="number" class="form-control" v-model="form.orden" min="0">
              </div>
              <div class="col-md-6">
                  <label class="form-label small fw-bold text-muted">ESTADO</label>
                  <select class="form-select" v-model="form.activo">
                    <option :value="1">ACTIVO</option>
                    <option :value="0">INACTIVO</option>
                  </select>
              </div>
            </div>
          </div>
          <div class="modal-footer border-0 p-4 pt-0">
            <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary px-4" :disabled="isSaving">
                <span v-if="isSaving" class="spinner-border spinner-border-sm me-1"></span>
                {{ form.id ? 'Actualizar' : 'Guardar Medio' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="medios_pago.js?v=<?= time() ?>"></script>

<style>
.badge { font-family: monospace; }
.cursor-pointer { cursor: pointer; }
.mini { font-size: 10px; }
</style>

</body></html>
