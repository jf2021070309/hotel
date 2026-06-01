<?php
/**
 * app/Views/caja_chica/detalle.php
 */
$base = '../../../';
$_projectRoot = dirname(__DIR__, 3);
require_once $_projectRoot . '/app/Middleware/auth.php';
protegerPorRol('cajera', 'caja_chica');

$page_title = 'Ciclo Activo Caja Chica — Hotel Manager';
include $_projectRoot . '/app/Views/layouts/head.php';
include $_projectRoot . '/app/Views/layouts/sidebar.php';
?>

<div class="main-content" id="app-cchica-detalle">
  <div class="topbar" style="background-color:#111827;padding:0.75rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.05);">
    <div class="d-flex align-items-center justify-content-between w-100">
      <div class="d-flex align-items-center gap-3">
        <button class="btn btn-dark btn-sm rounded-circle d-md-none" onclick="handleMenuClick()" style="width:32px;height:32px;padding:0;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.1);border:none;">
          <i class="bi bi-list text-white"></i>
        </button>
        <div class="d-flex align-items-center gap-3">
          <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#f59e0b,#d97706);display:flex;align-items:center;justify-content:center;box-shadow:0 0 15px rgba(245,158,11,0.4);">
            <i class="bi bi-piggy-bank text-white fs-5"></i>
          </div>
          <div>
            <h4 class="fw-bold mb-0 text-white" style="font-size:18px;letter-spacing:-0.5px;">Caja Chica en Curso</h4>
            <div class="text-white-50" style="font-size:11px;">Gestión de gastos menores sobre fondo fijo</div>
          </div>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="index.php" class="btn btn-sm btn-outline-light d-flex align-items-center gap-2" style="font-size:12px;padding:4px 12px;border-color:rgba(255,255,255,0.2);">
          <i class="bi bi-arrow-left"></i>
          <span class="d-none d-md-inline">Ver Historial</span>
        </a>
      </div>
    </div>
  </div>

  <div class="page-body">
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary"></div>
    </div>

    <div class="row g-3" v-else>
      <div class="col-12" v-if="!ciclo">
        <div class="card border-0 shadow-sm text-center py-5">
          <i class="bi bi-x-circle text-muted display-4 mb-3"></i>
          <h5>No hay ningún ciclo de caja chica abierto actualmente.</h5>
          <p class="text-muted">Vuelve al historial para crear uno nuevo.</p>
          <div class="mt-3">
            <a href="index.php" class="btn btn-primary">Ir al Historial</a>
          </div>
        </div>
      </div>

      <template v-else>
        <!-- COLUMNA RESUMEN Y CERRAR -->
        <div class="col-lg-4 col-xl-3">
          <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
              <h5 class="fw-bold fs-6 mb-3 text-secondary text-uppercase">{{ ciclo.nombre }}</h5>
              <p class="small text-muted mb-2"><i class="bi bi-calendar3 me-1"></i>Abierto: {{ ciclo.fecha_apertura }}
              </p>

              <div class="d-flex justify-content-between mb-1 mt-4">
                <span class="text-muted small fw-bold">SALDO INICIAL</span>
                <span class="fw-bold">S/ {{ parseFloat(ciclo.saldo_inicial).toFixed(2) }}</span>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted small fw-bold">GASTADO</span>
                <span class="fw-bold text-danger">- S/ {{ parseFloat(ciclo.total_gastado).toFixed(2) }}</span>
              </div>

              <div class="alert mt-3 text-center" :class="(ciclo.saldo_actual < 20) ? 'alert-danger' : 'alert-success'">
                <div class="small fw-bold mb-1">SALDO ACTUAL</div>
                <h2 class="mb-0 fw-bold">S/ {{ parseFloat(ciclo.saldo_actual).toFixed(2) }}</h2>
                <div class="small mt-1" v-if="ciclo.saldo_actual < 20"><i
                    class="bi bi-exclamation-triangle-fill me-1"></i>¡Fondo casi agotado!</div>
              </div>

              <!-- BARRA PROGRESO -->
              <div class="progress mt-3" style="height: 10px;">
                <div class="progress-bar" :class="(porcentaje_gastado > 80) ? 'bg-danger' : 'bg-primary'"
                  role="progressbar" :style="{width: porcentaje_gastado + '%'}" :aria-valuenow="porcentaje_gastado"
                  aria-valuemin="0" aria-valuemax="100">
                </div>
              </div>
              <div class="text-end small text-muted mt-1">{{ porcentaje_gastado.toFixed(0) }}% gastado</div>
            </div>
          </div>

          <!-- BOTON CERRAR -->
          <div class="card border-0 shadow-sm border-top border-danger border-3">
            <div class="card-body text-center">
              <p class="small text-muted mb-3">Si el fondo se agotó o el ciclo ha terminado, puedes cerrarlo aquí.</p>
              <button class="btn btn-danger fw-bold w-100 py-2" @click="cerrarCiclo">
                <i class="bi bi-lock-fill me-1"></i>Cerrar Ciclo Actual
              </button>
            </div>
          </div>
        </div>

        <!-- COLUMNA CENTRAL GASTOS -->
        <div class="col-lg-8 col-xl-9">

          <!-- FORM REGISTRO RÁPIDO -->
          <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-0 pt-3 pb-0">
              <h6 class="fw-bold text-primary"><i class="bi bi-cart-dash me-2"></i>Registrar Nuevo Gasto</h6>
            </div>
            <div class="card-body">
              <form @submit.prevent="registrarGasto" class="row g-2 align-items-end">
                <div class="col-md-3">
                  <label class="form-label small text-muted mb-1">Documento</label>
                  <input type="text" class="form-control form-control-sm fw-bold text-uppercase"
                    v-model="formg.documento" required placeholder="Local">
                </div>
                <div class="col-md-2">
                  <label class="form-label small text-muted mb-1">Monto (S/)</label>
                  <input type="number" class="form-control form-control-sm text-end fw-bold" v-model="formg.monto"
                    step="0.01" min="0.1" max="500" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label small text-muted mb-1">Motivo/Obs</label>
                  <input type="text" class="form-control form-control-sm text-danger" v-model="formg.observacion"
                    required placeholder="Compra de pan">
                </div>
                <div class="col-md-1 d-grid">
                  <button type="submit" class="btn btn-sm btn-primary" :disabled="guardandoGasto">
                    <i class="bi bi-save"></i>
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- LISTA MOVIMIENTOS -->
          <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom pt-3 pb-2 d-flex justify-content-between align-items-center">
              <h6 class="fw-bold mb-0 text-dark">Movimientos Registrados</h6>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0" style="font-size:13px;">
                <thead class="table-light text-secondary">
                  <tr style="font-size:11px;">
                    <th>Fecha</th>
                    <th>Operador</th>
                    <th>Documento</th>
                    <th>Observación</th>
                    <th class="text-end">Monto</th>
                    <th class="text-center">Acción</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="m in ciclo.movimientos" :key="m.id" :class="{'text-muted': m.anulado == 1}">
                    <td>{{ m.fecha }}</td>
                    <td><small>{{ m.operador }}</small></td>
                    <td :class="{'text-decoration-line-through': m.anulado == 1}">{{ m.documento || m.rubro }}</td>
                    <td>
                      <span :class="{'text-decoration-line-through': m.anulado == 1}">{{ m.observacion }}</span>
                      <div v-if="m.anulado == 1" class="text-danger small ms-1" style="font-size:10px;"><i
                          class="bi bi-x-circle me-1"></i>Anulado: {{ m.motivo_anulacion }}</div>
                    </td>
                    <td class="text-end fw-bold"
                      :class="(m.anulado==1)?'text-secondary text-decoration-line-through':'text-danger'">S/ {{
                      parseFloat(m.monto).toFixed(2) }}</td>
                    <td class="text-center">
                      <button v-if="m.anulado == 0" class="btn btn-sm text-secondary" title="Anular"
                        @click="anularGasto(m)">
                        <i class="bi bi-trash"></i>
                      </button>
                      <span v-else class="badge bg-light text-muted">ANULADO</span>
                    </td>
                  </tr>
                  <tr v-if="ciclo.movimientos && ciclo.movimientos.length === 0">
                    <td colspan="7" class="text-center py-4 text-muted">Ningún gasto registrado.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

        </div>
      </template>
    </div>
  </div>
</div>

<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $_root ?>app/Views/caja_chica/detalle.js?v=<?= filemtime(__DIR__ . '/detalle.js') ?>"></script>