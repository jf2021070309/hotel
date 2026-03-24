<?php
$base = '../../../';
require_once $base . 'config/db.php';
require_once $base . 'auth/session.php';
require_once $base . 'auth/middleware.php';

protegerPorRol('cajera');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$esNuevo = isset($_GET['nuevo']) && $_GET['nuevo'] == 1;
$turnoGet = $_GET['turno'] ?? ''; 

$page_title = 'Registro Yape — Hotel Manager';
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';
?>

<div class="main-content" id="app-yape-form" v-cloak>
  
  <div class="topbar">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
    <div>
      <h4>
        <i class="bi bi-wallet2 me-2" style="color:#7b2cbf"></i> 
        <span v-if="esNuevo">Nuevo Registro Yape</span>
        <span v-else>Registro Yape #{{ id }}</span>
      </h4>
      <p class="mb-0 small text-muted">Rendición de compras realizadas con flujo Yape externo</p>
    </div>
  </div>

  <div class="page-body">
    <div class="d-flex justify-content-end mb-3">
      <a href="index.php" class="btn btn-outline-secondary btn-sm shadow-sm">
        <i class="bi bi-arrow-left"></i> Volver a Lista
      </a>
    </div>

  <div v-if="loading" class="text-center py-5">
    <div class="spinner-border text-primary"></div>
    <div class="mt-2 text-muted small">Cargando formulario...</div>
  </div>

  <div v-else>
    <div class="row">
      <!-- MAIN FORM -->
      <div class="col-xl-8 col-lg-7">
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-primary">Detalle de Gastos</h6>
            <span :class="{'badge bg-info text-dark': turno=='MAÑANA', 'badge bg-dark': turno=='TARDE'}">
               TURNO {{ turno }}
            </span>
          </div>
          <div class="card-body">
            <!-- CABECERA -->
            <div class="row mb-4 bg-light p-3 rounded mx-0 border">
              <div class="col-md-6 mb-3 mb-md-0">
                <label class="form-label fw-bold text-secondary small">FECHA DEL REGISTRO</label>
                <input type="date" class="form-control" v-model="fecha" :disabled="!esNuevo" />
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold text-primary small">MONTO YAPE RECIBIDO (S/)</label>
                <div class="input-group">
                  <span class="input-group-text bg-white text-primary fw-bold">S/</span>
                  <input type="number" step="0.01" class="form-control fw-bold text-primary" v-model.number="yape_recibido" :disabled="estado==='cerrado'" placeholder="0.00">
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-end border-bottom pb-2 mb-3">
              <h6 class="fw-bold text-secondary mb-0">Conceptos Comprados</h6>
              <button class="btn btn-sm btn-outline-primary fw-bold" @click="agregarFila()" v-if="estado==='borrador'">
                <i class="bi bi-plus-circle"></i> Agregar Gasto
              </button>
            </div>

            <!-- TABLA DE GASTOS -->
            <div class="table-responsive">
              <table class="table table-bordered table-sm align-middle text-sm mb-0">
                <thead class="table-light text-secondary text-center">
                  <tr>
                    <th style="width: 25%">Rubro</th>
                    <th style="width: 20%">Documento</th>
                    <th style="width: 35%">Observación</th>
                    <th style="width: 15%">Monto (S/)</th>
                    <th style="width: 5%" v-if="estado==='borrador'"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="detalles.length === 0">
                    <td :colspan="estado==='borrador'?5:4" class="text-center text-muted fst-italic py-3">No has agregado ningún gasto. Usa el botón superior.</td>
                  </tr>
                  <tr v-for="(det, idx) in detalles" :key="idx">
                    <td>
                      <select class="form-select form-select-sm" v-model="det.rubro" :disabled="estado==='cerrado'">
                        <option value="">-- SELECCIONAR --</option>
                        <option value="MERCADO">MERCADO</option>
                        <option value="MOVILIDAD">MOVILIDAD</option>
                        <option value="CAFETERÍA/VEA">CAFETERÍA/VEA</option>
                        <option value="LAVANDERÍA">LAVANDERÍA</option>
                        <option value="SERV. REPUESTOS">SERV. REPUESTOS</option>
                        <option value="OTROS">OTROS</option>
                      </select>
                    </td>
                    <td>
                      <input type="text" class="form-control form-control-sm" v-model="det.documento" :disabled="estado==='cerrado'" placeholder="Ref/Boleta">
                    </td>
                    <td>
                      <input type="text" class="form-control form-control-sm" v-model="det.observacion" :disabled="estado==='cerrado'" placeholder="Detalle la compra...">
                    </td>
                    <td>
                      <input type="number" step="0.01" class="form-control form-control-sm text-end fw-bold" v-model.number="det.monto" :disabled="estado==='cerrado'" placeholder="0.00">
                    </td>
                    <td class="text-center" v-if="estado==='borrador'">
                      <button class="btn btn-sm btn-outline-danger border-0" @click="eliminarFila(idx)" title="Remover">
                        <i class="bi bi-trash3-fill"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="mt-4">
              <label class="form-label fw-bold text-secondary small">OBSERVACIÓN GENERAL DEL TURNO</label>
              <textarea class="form-control" rows="2" v-model="observacion_general" :disabled="estado==='cerrado'" placeholder="Notas o incidencias del proceso de compra..."></textarea>
            </div>

          </div>
        </div>
      </div>

      <!-- PANEL RESUMEN LATERAL -->
      <div class="col-xl-4 col-lg-5">
        <div class="card shadow border-0" :class="estado==='cerrado' ? 'border-bottom border-success border-4' : 'border-bottom border-primary border-4'">
          <div class="card-header bg-white py-3 py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-dark">Panel de Resumen</h6>
            <span v-if="estado==='cerrado'" class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Cerrado</span>
            <span v-else class="badge bg-warning text-dark"><i class="bi bi-pencil-square"></i> Borrador</span>
          </div>
          <div class="card-body">
            
            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
              <span class="text-secondary fw-bold small">Yape Recibido:</span>
              <span class="text-dark fw-bold">S/ {{ yape_recibido.toFixed(2) }}</span>
            </div>
            
            <div class="d-flex justify-content-between mb-3 pb-2 border-bottom">
              <span class="text-secondary fw-bold small">Total Gastado:</span>
              <span class="text-danger fw-bold">S/ {{ totalGastado.toFixed(2) }}</span>
            </div>

            <div class="alert mb-0" :class="alertaVuelto.class">
              <div class="d-flex justify-content-between align-items-center border-bottom border-secondary border-opacity-25 pb-2 mb-2">
                <span class="fw-bold small">{{ alertaVuelto.label }}:</span>
                <h4 class="mb-0 fw-bold">S/ {{ vueltoComputed.toFixed(2) }}</h4>
              </div>
              <p class="mb-0 small" v-html="alertaVuelto.msg"></p>
            </div>

          </div>
          <div class="card-footer bg-light p-3" v-if="estado==='borrador'">
            <div class="d-grid gap-2">
              <button class="btn btn-primary fw-bold" @click="guardarBorrador(false)">
                <i class="bi bi-save me-1"></i> Guardar Borrador
              </button>
              <button class="btn btn-success fw-bold" @click="cerrarRegistro()" :disabled="vueltoComputed < 0">
                <i class="bi bi-lock-fill me-1"></i> CERRAR Y RENDIR CUENTAS
              </button>
            </div>
            <div class="text-center mt-2 small text-muted">
              <i class="bi bi-info-circle"></i> Al cerrar, el vuelto pasará a ser efectivo en el Flujo de Caja.
            </div>
          </div>
        </div>
        
        <div v-if="estado==='cerrado'" class="alert alert-success shadow-sm mt-3 border-0">
          <i class="bi bi-shield-check me-2"></i> Este registro está finalizado y fue transferido al flujo de caja. No permite alteraciones.
        </div>

      </div>
    </div>
  </div>
</div>

<style>
  [v-cloak] { display: none !important; }
  .text-sm { font-size: 0.85rem; }
  .card-header { border-bottom: 1px solid #e3e6f0; }
</style>

<script>
  window.ID_REGISTRO = <?= $id ?>;
  window.ES_NUEVO = <?= $esNuevo ? 'true' : 'false' ?>;
  window.TURNO_GET = <?= json_encode($turnoGet) ?>;
</script>

<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $base ?>app/Views/yape/form.js"></script>
