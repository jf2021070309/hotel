<?php
/**
 * app/Views/flujo/form.php
 */
$base = '../../../';
require_once $base . 'auth/middleware.php';
protegerPorRol('cajera'); 

$page_title = 'Turno Flujo de Caja — Hotel Manager';
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';

$id = $_GET['id'] ?? 'null';
$nuevo = $_GET['nuevo'] ?? '0';
$turnoQuery = $_GET['turno'] ?? 'MAÑANA';
?>

<div class="main-content" id="app-flujo-form">
  <div class="topbar">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
    <div>
      <h4><i class="bi bi-wallet2 me-2 text-primary"></i>Turno Flujo de Caja <span class="badge bg-secondary ms-2" style="font-size:0.5em">{{ cabecera.estado.toUpperCase() }}</span></h4>
      <p class="mb-0 small text-muted">Añade o edita movimientos de ingresos y egresos</p>
    </div>
    <div class="ms-auto">
      <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver</a>
    </div>
  </div>

  <div class="page-body border-0 bg-transparent p-0 mt-3 px-3">
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary"></div>
    </div>
    
    <div v-else class="row g-3">
      <!-- Columna Principal Formularios -->
      <div class="col-lg-8 col-xl-9">
        
        <!-- CABECERA -->
        <div class="card mb-3 border-0 shadow-sm border-top border-primary border-3">
          <div class="card-body">
            <div class="row g-3 align-items-center">
              <div class="col-md-4">
                <label class="form-label text-muted small fw-bold mb-1">FECHA</label>
                <input type="date" class="form-control" v-model="cabecera.fecha" :disabled="!esEditable || !esNuevo">
              </div>
              <div class="col-md-4">
                <label class="form-label text-muted small fw-bold mb-1">TURNO</label>
                <select class="form-select" v-model="cabecera.turno" :disabled="!esEditable || !esNuevo">
                  <option value="MAÑANA">MAÑANA (6am - 2pm)</option>
                  <option value="TARDE">TARDE (2pm - 10pm)</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label text-muted small fw-bold mb-1">OPERADOR</label>
                <input type="text" class="form-control bg-light" :value="cabecera.operador || 'Automático al guardar'" disabled>
              </div>
            </div>
          </div>
        </div>

        <!-- INGRESOS -->
        <div class="card mb-3 border-0 shadow-sm">
          <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-success"><i class="bi bi-arrow-down-circle-fill me-2"></i>INGRESOS</h6>
            <button class="btn btn-sm btn-outline-success" @click="agregarMovimiento('ingresos')" v-if="esEditable">
              <i class="bi bi-plus"></i> Fila
            </button>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-borderless table-striped align-middle mb-0" style="font-size:13px;">
                <thead class="table-light">
                  <tr class="text-secondary" style="font-size:11px;">
                    <th style="width:25%;">CATEGORÍA</th>
                    <th style="width:12%;">MONEDA</th>
                    <th style="width:15%;">MONTO</th>
                    <th style="width:18%;">MEDIO PAGO</th>
                    <th style="width:25%;">OBSERVACIÓN</th>
                    <th style="width:5%;" v-if="esEditable"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(mov, index) in ingresos" :key="'i'+index">
                    <td>
                      <select class="form-select form-select-sm" v-model="mov.categoria" :disabled="!esEditable">
                        <option value="">Seleccionar...</option>
                        <option v-for="cat in categorias.ingreso" :key="cat.id" :value="cat.nombre">{{ cat.nombre }}</option>
                        <option value="OTRO">OTRO (Especificar en obs)</option>
                      </select>
                    </td>
                    <td>
                      <select class="form-select form-select-sm" v-model="mov.moneda" :disabled="!esEditable">
                        <option value="PEN">S/ (PEN)</option>
                        <option value="USD">$ (USD)</option>
                        <option value="CLP">$ (CLP)</option>
                      </select>
                    </td>
                    <td>
                      <input type="number" class="form-control form-control-sm text-end fw-bold" v-model.number="mov.monto" step="0.01" min="0" :disabled="!esEditable" placeholder="0.00">
                    </td>
                    <td>
                      <select class="form-select form-select-sm" v-model="mov.medio_pago" :class="{'bg-success text-white': mov.medio_pago==='EFECTIVO', 'bg-light': mov.medio_pago!=='EFECTIVO'}" :disabled="!esEditable">
                        <option value="EFECTIVO" class="bg-success text-white">EFECTIVO</option>
                        <option value="NO EFECTIVO" class="bg-light text-dark">NO EFECTIVO (Pos/Transf)</option>
                      </select>
                    </td>
                    <td>
                      <input type="text" class="form-control form-control-sm text-danger" v-model="mov.observacion" :disabled="!esEditable" placeholder="Nota rojita...">
                    </td>
                    <td v-if="esEditable" class="text-center">
                      <button class="btn btn-sm text-danger" @click="eliminarMovimiento('ingresos', index)"><i class="bi bi-trash"></i></button>
                    </td>
                  </tr>
                  <tr v-if="ingresos.length === 0">
                    <td colspan="6" class="text-center text-muted py-3">No hay ingresos registrados. Pulse "+ Fila".</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- EGRESOS -->
        <div class="card mb-3 border-0 shadow-sm">
          <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-danger"><i class="bi bi-arrow-up-circle-fill me-2"></i>EGRESOS</h6>
            <button class="btn btn-sm btn-outline-danger" @click="agregarMovimiento('egresos')" v-if="esEditable">
              <i class="bi bi-plus"></i> Fila
            </button>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-borderless table-striped align-middle mb-0" style="font-size:13px;">
                <thead class="table-light">
                  <tr class="text-secondary" style="font-size:11px;">
                    <th style="width:25%;">CATEGORÍA</th>
                    <th style="width:12%;">MONEDA</th>
                    <th style="width:15%;">MONTO</th>
                    <th style="width:18%;">MEDIO PAGO</th>
                    <th style="width:25%;">OBSERVACIÓN</th>
                    <th style="width:5%;" v-if="esEditable"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(mov, index) in egresos" :key="'e'+index">
                    <td>
                      <select class="form-select form-select-sm" v-model="mov.categoria" :disabled="!esEditable">
                        <option value="">Seleccionar...</option>
                        <option v-for="cat in categorias.egreso" :key="cat.id" :value="cat.nombre">{{ cat.nombre }}</option>
                        <option value="OTRO">OTRO (Especificar en obs)</option>
                      </select>
                    </td>
                    <td>
                      <select class="form-select form-select-sm" v-model="mov.moneda" :disabled="!esEditable">
                        <option value="PEN">S/ (PEN)</option>
                        <option value="USD">$ (USD)</option>
                        <option value="CLP">$ (CLP)</option>
                      </select>
                    </td>
                    <td>
                      <input type="number" class="form-control form-control-sm text-end fw-bold" v-model.number="mov.monto" step="0.01" min="0" :disabled="!esEditable" placeholder="0.00">
                    </td>
                    <td>
                      <select class="form-select form-select-sm" v-model="mov.medio_pago" :class="{'bg-danger text-white': mov.medio_pago==='EFECTIVO', 'bg-light': mov.medio_pago!=='EFECTIVO'}" :disabled="!esEditable">
                        <option value="EFECTIVO" class="bg-danger text-white">EFECTIVO</option>
                        <option value="NO EFECTIVO" class="bg-light text-dark">NO EFECTIVO (Pos/Transf)</option>
                      </select>
                    </td>
                    <td>
                      <input type="text" class="form-control form-control-sm text-danger" v-model="mov.observacion" :disabled="!esEditable" placeholder="Nota rojita...">
                    </td>
                    <td v-if="esEditable" class="text-center">
                      <button class="btn btn-sm text-danger" @click="eliminarMovimiento('egresos', index)"><i class="bi bi-trash"></i></button>
                    </td>
                  </tr>
                  <tr v-if="egresos.length === 0">
                    <td colspan="6" class="text-center text-muted py-3">No hay egresos registrados. Pulse "+ Fila".</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- NOTA DEL SOBRE -->
        <div class="card mb-3 border-0 shadow-sm">
          <div class="card-body">
            <label class="form-label text-muted fw-bold">NOTA DE ENTREGA (PARA EL SOBRE FÍSICO)</label>
            <textarea class="form-control" rows="3" v-model="cabecera.nota_entrega" :disabled="!esEditable" placeholder="Ej: Turno tarde efectivo PEN 1500 + USD 100... entregado a Alex"></textarea>
          </div>
        </div>

      </div>

      <!-- PANEL LATERAL RESUMEN -->
      <div class="col-lg-4 col-xl-3">
        <div class="card border-0 shadow-sm sticky-top" style="top: 80px; border-radius:12px;">
          <div class="card-header bg-dark text-white border-0 text-center py-3" style="border-radius:12px 12px 0 0;">
            <h5 class="mb-0"><i class="bi bi-calculator me-2"></i>RESUMEN</h5>
          </div>
          <div class="card-body bg-light p-4">
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Total Ingresos:</span>
              <span class="fw-bold fs-6">S/ {{ totalesDia.ingreso_pen }}</span>
            </div>
            <div class="d-flex justify-content-between mb-3 border-bottom pb-3">
              <span class="text-muted">Total Egresos:</span>
              <span class="fw-bold fs-6 text-danger">- S/ {{ totalesDia.egreso_pen }}</span>
            </div>
            
            <div class="alert alert-success border-2 text-center py-3 mb-4">
              <div class="small fw-bold text-success mb-1" style="letter-spacing:1px;">SE ENTREGA A ALEX (S/ SOBRE)</div>
              <h2 class="mb-0 fw-bold">S/ {{ efectivoEnSobrePEN }}</h2>
              <div class="small text-muted mt-1">(Neto del sobre en soles)</div>
            </div>

            <div class="mb-4">
              <div class="text-muted small fw-bold mb-2">EXTRANJERO FÍSICO (SOBRE):</div>
              <div class="d-flex justify-content-between align-items-center mb-1 bg-white p-2 rounded">
                <span class="fw-bold text-success"><i class="bi bi-cash me-1"></i>USD Dólares</span>
                <span class="fw-bold">$ {{ efectivoEnSobreUSD }}</span>
              </div>
              <div class="d-flex justify-content-between align-items-center bg-white p-2 rounded">
                <span class="fw-bold text-primary"><i class="bi bi-cash-stack me-1"></i>CLP Pesos</span>
                <span class="fw-bold">$ {{ efectivoEnSobreCLP }}</span>
              </div>
            </div>

            <div class="d-grid gap-2" v-if="esEditable">
              <button class="btn btn-outline-primary py-2 fw-bold" @click="guardarTurno(false)" :disabled="isSaving">
                <span v-if="isSaving" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="bi bi-save me-1"></i>Guardar Borrador
              </button>
              <button class="btn btn-primary py-2 fw-bold" @click="guardarTurno(true)" :disabled="isSaving">
                <i class="bi bi-lock-fill me-1"></i>CERRAR TURNO
              </button>
            </div>
            
            <!-- Reabrir si está cerrado o depositado (ADMIN ONLY) -->
            <div class="d-grid gap-2 mt-2" v-if="cabecera.estado !== 'borrador' && SERVER_DATA.canEditClosed">
              <button class="btn btn-outline-danger py-2 fw-bold" @click="reabrirTurno" :disabled="isSaving">
                <i class="bi bi-unlock-fill me-1"></i>Habilitar Edición / Reabrir
              </button>
            </div>

            <!-- ADMIN ONLY: Depositar si está cerrado -->
            <div class="d-grid gap-2 mt-2" v-if="cabecera.estado === 'cerrado' && SERVER_DATA.canEditClosed">
              <button class="btn btn-success py-2 fw-bold" @click="marcarDepositado" :disabled="isSaving">
                <i class="bi bi-bank me-1"></i>Marcar como Depositado
              </button>
            </div>

          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
  const SERVER_DATA = {
    id: <?= $id ?>,
    nuevo: <?= $nuevo ?>,
    turnoDefault: '<?= $turnoQuery ?>',
    userRol: '<?= $_SESSION['auth_rol'] ?? 'cajera' ?>',
    canEditClosed: <?= in_array($_SESSION['auth_rol'] ?? '', ['admin', 'supervisor']) ? 'true' : 'false' ?>
  };
</script>
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $base ?>app/Views/flujo/form.js"></script>
