<?php
/**
 * app/Views/caja_chica/detalle.php
 */
$base = '../../../';
require_once $base . 'auth/middleware.php';
protegerPorRol('cajera'); 

$page_title = 'Ciclo Activo Caja Chica — Hotel Manager';
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';
?>

<div class="main-content" id="app-cchica-detalle">
  <div class="topbar">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
    <div>
      <h4><i class="bi bi-piggy-bank me-2 text-primary"></i>Caja Chica en Curso</h4>
      <p class="mb-0 small text-muted">Gestión de gastos menores sobre fondo fijo</p>
    </div>
    <div class="ms-auto">
      <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Ver Historial</a>
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
              <p class="small text-muted mb-2"><i class="bi bi-calendar3 me-1"></i>Abierto: {{ ciclo.fecha_apertura }}</p>

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
                <div class="small mt-1" v-if="ciclo.saldo_actual < 20"><i class="bi bi-exclamation-triangle-fill me-1"></i>¡Fondo casi agotado!</div>
              </div>

              <!-- BARRA PROGRESO -->
              <div class="progress mt-3" style="height: 10px;">
                <div class="progress-bar" 
                     :class="(porcentaje_gastado > 80) ? 'bg-danger' : 'bg-primary'"
                     role="progressbar" 
                     :style="{width: porcentaje_gastado + '%'}" 
                     :aria-valuenow="porcentaje_gastado" aria-valuemin="0" aria-valuemax="100">
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
                  <label class="form-label small text-muted mb-1">Rubro</label>
                  <select class="form-select form-select-sm" v-model="formg.rubro" required>
                    <option value="" disabled>Seleccione...</option>
                    <option v-for="c in categorias" :key="c.id" :value="c.nombre">{{ c.nombre }}</option>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label small text-muted mb-1">Monto (S/)</label>
                  <input type="number" class="form-control form-control-sm text-end fw-bold" v-model="formg.monto" step="0.01" min="0.1" max="500" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label small text-muted mb-1">Doc/Entidad (opc)</label>
                  <input type="text" class="form-control form-control-sm" v-model="formg.documento" placeholder="PANADERÍA ROJAS">
                </div>
                <div class="col-md-3">
                  <label class="form-label small text-muted mb-1">Motivo/Obs</label>
                  <input type="text" class="form-control form-control-sm text-danger" v-model="formg.observacion" required placeholder="Compra de pan">
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
                    <th>Rubro</th>
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
                    <td :class="{'text-decoration-line-through': m.anulado == 1}">{{ m.rubro }}</td>
                    <td :class="{'text-decoration-line-through': m.anulado == 1}">{{ m.documento }}</td>
                    <td>
                       <span :class="{'text-decoration-line-through': m.anulado == 1}">{{ m.observacion }}</span>
                       <div v-if="m.anulado == 1" class="text-danger small ms-1" style="font-size:10px;"><i class="bi bi-x-circle me-1"></i>Anulado: {{ m.motivo_anulacion }}</div>
                    </td>
                    <td class="text-end fw-bold" :class="(m.anulado==1)?'text-secondary text-decoration-line-through':'text-danger'">S/ {{ parseFloat(m.monto).toFixed(2) }}</td>
                    <td class="text-center">
                      <button v-if="m.anulado == 0" class="btn btn-sm text-secondary" title="Anular" @click="anularGasto(m)">
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
<script src="<?= $base ?>app/Views/caja_chica/detalle.js"></script>
