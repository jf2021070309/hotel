<?php
/**
 * app/Views/yape/form.php — Modo Cuadrícula (inspirado en el Excel original)
 */
$base = '../../../';
require_once $base . 'config/db.php';
require_once $base . 'auth/session.php';
require_once $base . 'auth/middleware.php';

protegerPorRol('cajera', 'yape');

$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$esNuevo = isset($_GET['nuevo']) && $_GET['nuevo'] == 1;
$turnoGet = $_GET['turno'] ?? '';
$fechaGet = $_GET['fecha'] ?? date('Y-m-d');

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

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary"></div>
    </div>

    <div v-else>

      <!-- BARRA SUPERIOR: fecha, turno, estado -->
      <div class="card shadow-sm border-0 mb-3" style="border-radius:10px;">
        <div class="card-body py-3 px-4">
          <div class="row g-3 align-items-center">
            <div class="col-auto">
              <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Volver
              </a>
            </div>
            <div class="col-auto">
              <label class="form-label small fw-bold mb-1 d-block">FECHA</label>
              <input type="date" v-model="fecha" class="form-control form-control-sm fw-bold" :disabled="estado==='cerrado'" style="width:150px;">
            </div>
            <div class="col-auto">
              <label class="form-label small fw-bold mb-1 d-block">TURNO</label>
              <div class="btn-group btn-group-sm">
                <button class="btn fw-bold" :class="turno==='MAÑANA' ? 'btn-info text-dark' : 'btn-outline-secondary'"
                        @click="turno='MAÑANA'" :disabled="estado==='cerrado'">☀️ MAÑANA</button>
                <button class="btn fw-bold" :class="turno==='TARDE' ? 'btn-dark text-white' : 'btn-outline-secondary'"
                        @click="turno='TARDE'" :disabled="estado==='cerrado'">🌙 TARDE</button>
              </div>
            </div>
            <div class="col-auto ms-auto">
              <span v-if="estado==='cerrado'" class="badge bg-success fs-6 px-3 py-2">
                <i class="bi bi-check-circle-fill me-1"></i>Cerrado
              </span>
              <span v-else class="badge bg-warning text-dark fs-6 px-3 py-2">
                <i class="bi bi-pencil-square me-1"></i>Borrador
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- CUADRÍCULA PRINCIPAL -->
      <div class="card shadow-sm border-0 mb-3" style="border-radius:10px; overflow:hidden;">
        <div class="card-body p-0">
          <table class="table table-bordered mb-0 align-middle" style="font-size:14px;">
            <thead style="background:#1e293b; color:#fff;">
              <tr>
                <th class="py-3 px-3" style="min-width:160px;">CONCEPTO</th>
                <th class="text-center py-3" style="min-width:130px;">MONTO (S/)</th>
                <th class="py-3 px-3">REFERENCIA / DOC</th>
                <th class="py-3 px-3">OBSERVACIÓN</th>
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
                <td class="px-3 py-3 text-uppercase" style="color:#475569; font-size:12px; letter-spacing:.5px;">Total Gastado</td>
                <td class="text-end pe-3 py-3" style="font-size:18px; color:#dc2626;">
                  S/ {{ totalGastado.toFixed(2) }}
                </td>
                <td colspan="2"></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- FILA INFERIOR: YAPE, OBSERVACIÓN Y RESUMEN -->
      <div class="row g-3">

        <!-- Yape recibido + Observación -->
        <div class="col-md-7">
          <div class="card shadow-sm border-0" style="border-radius:10px;">
            <div class="card-body">
              <div class="row g-3">
                <div class="col-sm-5">
                  <label class="form-label fw-bold text-primary small">YAPE RECIBIDO (S/)</label>
                  <div class="input-group">
                    <span class="input-group-text fw-bold text-primary bg-white">S/</span>
                    <input type="number" step="0.01" min="0"
                           class="form-control fw-bold text-primary"
                           style="font-size:20px;"
                           v-model.number="yape_recibido"
                           :disabled="estado==='cerrado'"
                           placeholder="0.00">
                  </div>
                </div>
                <div class="col-sm-7">
                  <label class="form-label fw-bold text-secondary small">OBSERVACIÓN GENERAL</label>
                  <textarea class="form-control" rows="2"
                            v-model="observacion_general"
                            :disabled="estado==='cerrado'"
                            placeholder="Notas del turno..."></textarea>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Panel Vuelto + Acciones -->
        <div class="col-md-5">
          <div class="card shadow-sm border-0 h-100" style="border-radius:10px;"
               :style="vueltoComputed >= 0 ? 'border-bottom:4px solid #16a34a' : 'border-bottom:4px solid #dc2626'">
            <div class="card-body d-flex flex-column justify-content-between">
              <div>
                <div class="d-flex justify-content-between mb-1">
                  <span class="small text-muted fw-bold">Yape Recibido:</span>
                  <span class="fw-bold text-primary">S/ {{ yape_recibido.toFixed(2) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                  <span class="small text-muted fw-bold">Total Gastado:</span>
                  <span class="fw-bold text-danger">S/ {{ totalGastado.toFixed(2) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                  <span class="fw-bold" :class="vueltoComputed >= 0 ? 'text-success' : 'text-danger'">
                    {{ vueltoComputed >= 0 ? 'VUELTO:' : '⚠️ FALTANTE:' }}
                  </span>
                  <span class="fw-bold" style="font-size:26px;"
                        :class="vueltoComputed >= 0 ? 'text-success' : 'text-danger'">
                    S/ {{ Math.abs(vueltoComputed).toFixed(2) }}
                  </span>
                </div>
                <div v-if="vueltoComputed > 0" class="small text-muted mt-1" style="font-size:11px;">
                  <i class="bi bi-arrow-right-circle-fill text-success me-1"></i>Se inyectará al Flujo de Caja al cerrar.
                </div>
              </div>

              <div v-if="estado==='borrador'" class="d-grid gap-2 mt-3">
                <button class="btn btn-primary fw-bold" @click="guardarBorrador(false)">
                  <i class="bi bi-save me-1"></i> Guardar Borrador
                </button>
                <button class="btn btn-success fw-bold" @click="cerrarRegistro()" :disabled="vueltoComputed < 0">
                  <i class="bi bi-lock-fill me-1"></i> CERRAR Y RENDIR CUENTAS
                </button>
              </div>

              <div v-if="!esNuevo" class="d-grid mt-2">
                <a :href="`imprimir.php?id=${id}`" target="_blank" class="btn btn-outline-dark btn-sm fw-bold">
                  <i class="bi bi-printer me-1"></i> Imprimir / Enviar a Mendoza
                </a>
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
  .table td, .table th { vertical-align: middle; }
  .form-control:focus { box-shadow: none; }
  input[type=number]::-webkit-inner-spin-button { opacity: 0.5; }
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
