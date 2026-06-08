<?php
/**
 * app/Views/yape/form.php — Modo Cuadrícula (inspirado en el Excel original)
 */
$base = '../../../';
require_once $base . 'config/db.php';
require_once $base . 'app/Middleware/session.php';
require_once $base . 'app/Middleware/auth.php';

protegerPorRol('cajera', 'yape');

$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$esNuevo = isset($_GET['nuevo']) && $_GET['nuevo'] == 1;
$turnoGet = $_GET['turno'] ?? '';
$fechaGet = $_GET['fecha'] ?? date('Y-m-d');

$page_title = 'Registro Yape — Hotel Manager';
include $base . 'app/Views/layouts/head.php';
include $base . 'app/Views/layouts/sidebar.php';
?>

<div class="main-content" id="app-yape-form" v-cloak>

  <!-- TOPBAR PREMIUM DARK -->
  <div class="topbar" style="background-color:#111827;padding:0.75rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.05);">
    <div class="d-flex align-items-center justify-content-between w-100">
      <div class="d-flex align-items-center gap-3">
        <button class="btn btn-dark btn-sm rounded-circle d-md-none" onclick="openSidebar()" style="width:32px;height:32px;padding:0;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.1);border:none;">
          <i class="bi bi-list text-white"></i>
        </button>
        <div class="d-flex align-items-center gap-3">
          <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#f8fafc,#94a3b8);display:flex;align-items:center;justify-content:center;box-shadow:0 0 15px rgba(148,163,184,0.4);">
            <i class="bi bi-wallet2 text-dark fs-5"></i>
          </div>
          <div>
            <h4 class="fw-bold mb-0 text-white" style="font-size:18px;letter-spacing:-0.5px;">
              <span v-if="esNuevo">Nuevo Registro</span>
              <span v-else>Registro #{{ id }}</span>
            </h4>
            <div class="text-white-50" style="font-size:11px;">Rendición de compras realizadas con flujo Yape externo</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="page-body">

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary"></div>
    </div>

    <div v-else>

      <!-- BARRA SUPERIOR: fecha, turno, estado -->
      <div class="card shadow-sm border-0 mb-2" style="border-radius:6px;">
        <div class="card-body py-2 px-3">
          <div class="d-flex flex-wrap gap-2 align-items-center">
            <div>
              <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> <span class="d-none d-sm-inline">Volver</span>
              </a>
            </div>
            <div class="ms-sm-2">
              <input type="date" v-model="fecha" class="form-control form-control-sm fw-bold border-0 bg-light" :disabled="estado==='cerrado'" style="width:130px; font-size: 13px;">
            </div>
            <div class="ms-sm-auto">
              <div class="btn-group btn-group-sm">
                <button class="btn fw-bold px-2" :class="turno==='MAÑANA' ? 'btn-info text-dark' : 'btn-outline-secondary'"
                        @click="turno='MAÑANA'" :disabled="estado==='cerrado'">☀️ <span class="d-none d-md-inline">MAÑANA</span></button>
                <button class="btn fw-bold px-2" :class="turno==='TARDE' ? 'btn-dark text-white' : 'btn-outline-secondary'"
                        @click="turno='TARDE'" :disabled="estado==='cerrado'">🌙 <span class="d-none d-md-inline">TARDE</span></button>
              </div>
            </div>
            <div class="ms-auto ms-sm-2">
              <span v-if="estado==='cerrado'" class="badge bg-success px-2 py-2" style="font-size: 10px;">
                <i class="bi bi-check-circle-fill me-1"></i>CERRADO
              </span>
              <span v-else class="badge bg-warning text-dark px-2 py-2" style="font-size: 10px;">
                <i class="bi bi-pencil-square me-1"></i>BORRADOR
              </span>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-2">
        <!-- COLUMNA IZQUIERDA: Tablas -->
        <div class="col-lg-9 col-md-8">
          <!-- CUADRÍCULA PRINCIPAL -->
          <div class="card shadow-sm border-0 mb-2" style="border-radius:6px; overflow:hidden;">
            <div class="card-body p-0">
              <div class="table-responsive">
              <table class="table table-bordered mb-0 align-middle" style="font-size:13px; white-space: nowrap;">
                <thead style="background:#1e293b; color:#fff; font-size: 12px; letter-spacing: 0.5px;">
                  <tr>
                    <th class="py-2 px-2" style="min-width:140px;">CONCEPTO</th>
                    <th class="text-center py-2" style="min-width:120px;">MONTO (S/)</th>
                    <th class="py-2 px-2">REFERENCIA / DOC</th>
                    <th class="py-2 px-2">OBSERVACIÓN</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(cat, idx) in categorias" :key="cat.key"
                      :style="idx % 2 === 0 ? 'background:#f8fafc' : 'background:#fff'">
                    <td class="px-3 fw-bold" style="color:#374151;">
                      <i class="bi me-2" :class="cat.icon" :style="'color:'+cat.color"></i>
                      {{ cat.label }}
                    </td>
                    <td>
                      <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted fw-bold border-0">S/</span>
                        <input type="number" step="0.01" min="0"
                               class="form-control text-end fw-bold border-0"
                               style="background:transparent; font-size:15px;"
                               :class="montos[cat.key] > 0 ? 'text-danger' : 'text-muted'"
                               v-model.number="montos[cat.key]"
                               :disabled="estado==='cerrado'"
                               placeholder="—">
                      </div>
                    </td>
                    <td>
                      <input type="text" class="form-control form-control-sm border-0"
                             style="background:transparent;"
                             v-model="refs[cat.key]"
                             :disabled="estado==='cerrado'"
                             placeholder="Boleta, ticket...">
                    </td>
                    <td>
                      <input type="text" class="form-control form-control-sm border-0"
                             style="background:transparent;"
                             v-model="obs[cat.key]"
                             :disabled="estado==='cerrado'"
                             placeholder="Detalle...">
                    </td>
                  </tr>
                  <!-- FILA OTROS (desplegable) -->
                  <tr style="background:#fffbeb;">
                    <td class="px-3 fw-bold text-warning">
                      <i class="bi bi-plus-circle me-2"></i>OTROS
                    </td>
                    <td>
                      <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted fw-bold border-0">S/</span>
                        <input type="number" step="0.01" min="0"
                               class="form-control text-end fw-bold border-0"
                               style="background:transparent; font-size:15px;"
                               :class="montos['OTROS'] > 0 ? 'text-danger' : 'text-muted'"
                               v-model.number="montos['OTROS']"
                               :disabled="estado==='cerrado'"
                               placeholder="—">
                      </div>
                    </td>
                    <td>
                      <input type="text" class="form-control form-control-sm border-0"
                             style="background:transparent;"
                             v-model="refs['OTROS']"
                             :disabled="estado==='cerrado'"
                             placeholder="Boleta, ticket...">
                    </td>
                    <td>
                      <input type="text" class="form-control form-control-sm border-0"
                             style="background:transparent;"
                             v-model="obs['OTROS']"
                             :disabled="estado==='cerrado'"
                             placeholder="Especificar...">
                    </td>
                  </tr>
                </tbody>
                <!-- FILA TOTALES -->
                <tfoot style="background:#f1f5f9; font-weight:800;">
                  <tr>
                    <td class="px-2 py-2 text-uppercase" style="color:#475569; font-size:11px; letter-spacing:.5px;">Total Gastado</td>
                    <td class="text-end pe-2 py-2" style="font-size:16px; color:#dc2626;">
                      S/ {{ totalGastado.toFixed(2) }}
                    </td>
                    <td colspan="2"></td>
                  </tr>
                </tfoot>
              </table>
              </div>
            </div>
          </div>
        </div>

        <!-- COLUMNA DERECHA: Resumen y Acciones -->
        <div class="col-lg-3 col-md-4">
          <!-- Panel Lateral Unificado -->
          <div class="card shadow-sm border-0 mb-2" style="border-radius:6px;" :style="vueltoComputed >= 0 ? 'border-top:4px solid #16a34a' : 'border-top:4px solid #dc2626'">
            <div class="card-body p-3 d-flex flex-column">
              
              <!-- Yape y Obs -->
              <div class="mb-3">
                <label class="form-label fw-bold text-primary small mb-1" style="font-size: 11px;">YAPE RECIBIDO (S/)</label>
                <div class="input-group input-group-sm mb-3">
                  <span class="input-group-text fw-bold text-primary bg-white">S/</span>
                  <input type="number" step="0.01" min="0" class="form-control fw-bold text-primary" style="font-size:18px;" v-model.number="yape_recibido" :disabled="estado==='cerrado'" placeholder="0.00">
                </div>
                
                <label class="form-label fw-bold text-secondary small mb-1" style="font-size: 11px;">OBSERVACIÓN GENERAL</label>
                <textarea class="form-control form-control-sm" rows="3" v-model="observacion_general" :disabled="estado==='cerrado'" placeholder="Ej. Hubo un faltante, etc."></textarea>
              </div>

              <!-- Resumen Vuelto -->
              <div class="p-2 bg-light rounded mb-3" style="border: 1px solid #e2e8f0;">
                <div class="d-flex justify-content-between mb-1">
                  <span class="small text-muted fw-bold" style="font-size: 11px;">Gasto Total:</span>
                  <span class="fw-bold text-danger" style="font-size: 11.5px;">S/ {{ totalGastado.toFixed(2) }}</span>
                </div>
                <hr class="my-1">
                <div class="text-center mt-2 pb-1">
                  <span class="fw-bold d-block" style="font-size: 11px;" :class="vueltoComputed >= 0 ? 'text-success' : 'text-danger'">
                    {{ vueltoComputed >= 0 ? 'VUELTO / BALANCE:' : '⚠️ FALTANTE:' }}
                  </span>
                  <span class="fw-bold d-block" style="font-size:26px; line-height: 1.1;" :class="vueltoComputed >= 0 ? 'text-success' : 'text-danger'">
                    S/ {{ Math.abs(vueltoComputed).toFixed(2) }}
                  </span>
                </div>
              </div>

              <!-- Acciones -->
              <div class="d-flex flex-column gap-2">
                <button v-if="estado==='borrador'" class="btn btn-primary btn-sm fw-bold w-100 py-2 shadow-sm" @click="guardarBorrador(false)">
                   <i class="bi bi-save me-1"></i> Guardar Modificaciones
                </button>
              </div>
              
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  [v-cloak] { display: none !important; }
  .table td, .table th { vertical-align: middle; padding: .5rem .6rem !important;}
  .form-control:focus { box-shadow: none; }
  input[type=number]::-webkit-inner-spin-button { opacity: 0.5; }

  @media (max-width: 768px) {
    .main-content { padding: 10px !important; }
    .page-body { padding: 0 !important; }
    .topbar h4 { font-size: 1.15rem; }
    .card { border-radius: 4px !important; }
    .table td { padding: 0.4rem 0.3rem !important; }
    .input-group-text { padding: 0.2rem 0.4rem; font-size: 12px; }
    input.form-control { font-size: 13px !important; }
  }
</style>

<script>
  window.ID_REGISTRO = <?= $id ?>;
  window.ES_NUEVO    = <?= $esNuevo ? 'true' : 'false' ?>;
  window.TURNO_GET   = <?= json_encode($turnoGet) ?>;
  window.FECHA_GET   = <?= json_encode($fechaGet) ?>;
</script>

<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $base ?>app/Views/yape/form.js?v=<?= time() ?>"></script>
