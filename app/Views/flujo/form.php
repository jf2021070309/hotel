<?php
/**
 * app/Views/flujo/form.php
 */
$base = '../../../';
$_projectRoot = dirname(__DIR__, 3);
require_once $_projectRoot . '/app/Middleware/auth.php';
require_once $_projectRoot . '/app/Helpers/url.php';
protegerPorRol('cajera', 'flujo');
require_once $_projectRoot . '/config/db.php';

$page_title = 'Turno Flujo de Caja — Hotel Manager';
include $_projectRoot . '/app/Views/layouts/head.php';
include $_projectRoot . '/app/Views/layouts/sidebar.php';

$id = $_GET['id'] ?? 'null';
$nuevo = $_GET['nuevo'] ?? '0';
$turnoQuery = $_GET['turno'] ?? 'MAÑANA';
$fechaQuery = $_GET['fecha'] ?? date('Y-m-d');
?>

<div class="main-content">
  <div class="topbar" style="background-color:#111827;padding:0.75rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.05);">
    <div class="d-flex align-items-center justify-content-between w-100">
      <div class="d-flex align-items-center gap-3">
        <button class="btn btn-dark btn-sm rounded-circle d-md-none" onclick="handleMenuClick()" style="width:32px;height:32px;padding:0;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.1);border:none;">
          <i class="bi bi-list text-white"></i>
        </button>
        <div class="d-flex align-items-center gap-3">
          <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#f59e0b,#d97706);display:flex;align-items:center;justify-content:center;box-shadow:0 0 15px rgba(245,158,11,0.4);">
            <i class="bi bi-wallet2 text-white fs-5"></i>
          </div>
          <div>
            <h4 class="fw-bold mb-0 text-white" style="font-size:18px;letter-spacing:-0.5px;">Flujo de Caja
              <span class="badge bg-secondary ms-2 p-1 d-none" style="font-size:9px" id="badge-estado"></span>
            </h4>
            <div class="text-white-50" style="font-size:11px;">Registro detallado de ingresos y egresos del turno</div>
          </div>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= route('flujo/index.php') ?>?noredirect=1" class="btn btn-sm btn-outline-light d-flex align-items-center gap-2" style="font-size:12px;padding:4px 12px;border-color:rgba(255,255,255,0.2);">
          <i class="bi bi-arrow-left"></i>
          <span class="d-none d-md-inline">Listado</span>
        </a>
      </div>
    </div>
  </div>

  <div id="app-flujo-form" v-cloak style="display:contents">

  <div class="page-body border-0 bg-transparent p-0 mt-3 px-3">
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary"></div>
    </div>
    
    <div v-else class="row g-3">
      <!-- Columna Principal Formularios -->
      <div class="col-lg-8 col-xl-9">
        
        <!-- CABECERA -->
        <div class="card mb-3 border-0 shadow-sm border-top border-primary border-3">
          <div class="card-body py-2 px-3">
            <div class="row g-2">
              <div class="col-6 col-md-4">
                <label class="form-label text-muted fw-bold mb-1" style="font-size: 10px;">FECHA</label>
                <input type="date" class="form-control form-control-sm fw-bold border-0 bg-light" v-model="cabecera.fecha" :disabled="!esEditable || !esNuevo" style="font-size: 12px;">
              </div>
              <div class="col-6 col-md-4">
                <label class="form-label text-muted fw-bold mb-1" style="font-size: 10px;">TURNO</label>
                <select class="form-select form-select-sm fw-bold border-0 bg-light" v-model="cabecera.turno" :disabled="!esEditable || !esNuevo" style="font-size: 11px;">
                  <option value="MAÑANA">MAÑANA</option>
                  <option value="TARDE">TARDE</option>
                </select>
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
                    <th style="width:22%;">CATEGORÍA</th>
                    <th style="width:15%;">MONEDA</th>
                    <th style="width:13%;">MONTO</th>
                    <th style="width:45%;">OBSERVACIÓN</th>
                    <th style="width:5%;" v-if="esEditable"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(mov, index) in ingresos" :key="'i'+index">
                    <td>
                      <select class="form-select form-select-sm" v-model="mov.categoria" :disabled="!esEditable" @change="onCategoriaChange(mov)">
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
                      <div class="position-relative">
                        <input v-if="focusedField === 'i' + index && esEditable" 
                               type="number" step="0.01" 
                               class="form-control form-control-sm text-end fw-bold border-primary shadow-sm" 
                               v-model.number="mov.monto" 
                               @blur="focusedField = null" 
                               v-focus>
                        <input v-else 
                               type="text" 
                               class="form-control form-control-sm text-end fw-bold" 
                               :class="{'bg-white': esEditable, 'bg-light': !esEditable}"
                               :value="fmtMonto(mov.monto, mov.moneda)" 
                               @focus="focusedField = 'i' + index" 
                               readonly>
                      </div>
                    </td>
                    <td>
                      <div class="input-group input-group-sm">
                        <textarea class="form-control text-danger border-end-0 py-1" v-model="mov.observacion" :disabled="!esEditable" placeholder="Nota..." rows="2" style="resize: none; font-size: 11px; overflow: hidden; line-height: 1.2;"></textarea>
                        <span class="input-group-text bg-white border-start-0" v-if="mov.observacion.includes('#')">
                           <a :href="SERVER_DATA.roomingIndex + '?stay_id=' + (mov.observacion.match(/#(\d+)/) || [])[1]" class="text-primary" title="Ver Registro" target="_blank">
                             <i class="bi bi-box-arrow-up-right"></i>
                           </a>
                        </span>
                      </div>
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
                    <th style="width:20%;">CATEGORÍA</th>
                    <th style="width:12%;">MONEDA</th>
                    <th style="width:12%;">MONTO</th>
                    <th style="width:30%;">OBSERVACIÓN</th>
                    <th style="width:21%;">DESCONTA DE FONDO</th>
                    <th style="width:5%;" v-if="esEditable"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(mov, index) in egresos" :key="'e'+index">
                    <td>
                      <select class="form-select form-select-sm" v-model="mov.categoria" :disabled="!esEditable" @change="onCategoriaChange(mov)">
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
                      <div class="position-relative">
                        <input v-if="focusedField === 'e' + index && esEditable" 
                               type="number" step="0.01" 
                               class="form-control form-control-sm text-end fw-bold border-danger shadow-sm" 
                               v-model.number="mov.monto" 
                               @blur="focusedField = null" 
                               v-focus>
                        <input v-else 
                               type="text" 
                               class="form-control form-control-sm text-end fw-bold" 
                               :class="{'bg-white': esEditable, 'bg-light': !esEditable}"
                               :value="fmtMonto(mov.monto, mov.moneda)" 
                               @focus="focusedField = 'e' + index" 
                               readonly>
                      </div>
                    </td>
                    <td>
                      <div class="input-group input-group-sm">
                        <textarea class="form-control text-danger border-end-0 py-1" v-model="mov.observacion" :disabled="!esEditable" placeholder="Nota..." rows="2" style="resize: none; font-size: 11px; overflow: hidden; line-height: 1.2;"></textarea>
                        <span class="input-group-text bg-white border-start-0" v-if="mov.observacion && mov.observacion.includes('#')">
                           <a :href="SERVER_DATA.roomingIndex + '?stay_id=' + (mov.observacion.match(/#(\d+)/) || [])[1]" class="text-primary" title="Ver Registro" target="_blank">
                             <i class="bi bi-box-arrow-up-right"></i>
                           </a>
                        </span>
                      </div>
                    </td>
                    <td>
                        <div v-if="mov.medio_pago === 'EFECTIVO'" class="d-flex align-items-center gap-2">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" :id="'chkSobre' + index" v-model="mov._usaSobre" :disabled="!esEditable">
                                <label class="form-check-label fw-bold" :for="'chkSobre' + index" style="font-size: 11px;">
                                    <span v-if="mov._usaSobre" class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Fondo Mensual</span>
                                    <span v-else class="text-muted"><i class="bi bi-x-circle me-1"></i>No descontar</span>
                                </label>
                            </div>
                        </div>
                        <div v-else class="text-muted small italic" style="font-size: 10px;">Solo efectivo</div>
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
        <div class="card border-0 shadow-sm sticky-top" style="top: 15px; border-radius:12px;">
          <div class="card-header bg-dark text-white border-0 text-center py-3" style="border-radius:12px 12px 0 0;">
            <h5 class="mb-0"><i class="bi bi-calculator me-2"></i>RESUMEN</h5>
          </div>
          <div class="card-body bg-light p-3">
            <!-- INGRESOS Y EGRESOS MULTIDIVISA (MISMO TAMAÑO) -->
            <div class="mb-3">
              <div class="text-muted small fw-bold mb-2 pb-1 border-bottom" style="letter-spacing:0.5px; text-transform:uppercase; font-size:11px;">
                <i class="bi bi-wallet2 me-1"></i> Total Turno (Ingresos / Egresos)
              </div>
              
              <div class="row g-2">
                <!-- PEN Card -->
                <div class="col-12">
                  <div class="bg-white rounded shadow-sm border border-light" style="border-radius: 10px; padding: 10px 12px;">
                    <div class="d-flex align-items-center mb-1">
                      <span class="badge bg-dark text-white fw-bold me-2" style="font-size:9px; padding: 3px 6px;">PEN</span>
                      <span class="fw-bold text-dark" style="font-size:12px;">Soles Peruanos</span>
                    </div>
                    <div class="d-flex justify-content-between small mb-0.5" style="font-size: 11.5px;">
                      <span class="text-muted">Total Ingresos:</span>
                      <span class="fw-bold text-success">S/ {{ totalesMonedas.PEN.ingresos }}</span>
                    </div>
                    <div class="d-flex justify-content-between small" style="font-size: 11.5px;">
                      <span class="text-muted">Total Egresos:</span>
                      <span class="fw-bold text-danger">- S/ {{ totalesMonedas.PEN.egresos }}</span>
                    </div>
                  </div>
                </div>

                <!-- USD Card -->
                <div class="col-12">
                  <div class="bg-white rounded shadow-sm border border-light" style="border-radius: 10px; padding: 10px 12px;">
                    <div class="d-flex align-items-center mb-1">
                      <span class="badge bg-success text-white fw-bold me-2" style="font-size:9px; padding: 3px 6px;">USD</span>
                      <span class="fw-bold text-dark" style="font-size:12px;">Dólares</span>
                    </div>
                    <div class="d-flex justify-content-between small mb-0.5" style="font-size: 11.5px;">
                      <span class="text-muted">Total Ingresos:</span>
                      <span class="fw-bold text-success">$ {{ totalesMonedas.USD.ingresos }}</span>
                    </div>
                    <div class="d-flex justify-content-between small" style="font-size: 11.5px;">
                      <span class="text-muted">Total Egresos:</span>
                      <span class="fw-bold text-danger">- $ {{ totalesMonedas.USD.egresos }}</span>
                    </div>
                  </div>
                </div>

                <!-- CLP Card -->
                <div class="col-12">
                  <div class="bg-white rounded shadow-sm border border-light" style="border-radius: 10px; padding: 10px 12px;">
                    <div class="d-flex align-items-center mb-1">
                      <span class="badge bg-primary text-white fw-bold me-2" style="font-size:9px; padding: 3px 6px;">CLP</span>
                      <span class="fw-bold text-dark" style="font-size:12px;">Pesos Chilenos</span>
                    </div>
                    <div class="d-flex justify-content-between small mb-0.5" style="font-size: 11.5px;">
                      <span class="text-muted">Total Ingresos:</span>
                      <span class="fw-bold text-success">$ {{ totalesMonedas.CLP.ingresos }}</span>
                    </div>
                    <div class="d-flex justify-content-between small" style="font-size: 11.5px;">
                      <span class="text-muted">Total Egresos:</span>
                      <span class="fw-bold text-danger">- $ {{ totalesMonedas.CLP.egresos }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>



            <div class="mb-3 text-center" v-if="esEditable && !loading">
              <span v-if="isSaving" class="badge bg-info-subtle text-info border border-info px-3 py-2" style="font-size: 11px;">
                <span class="spinner-border spinner-border-sm me-2" style="width: 12px; height: 12px;"></span>Guardando cambios...
              </span>
              <span v-else class="badge bg-success-subtle text-success border border-success px-3 py-2" style="font-size: 11px;">
                <i class="bi bi-check-circle-fill me-1"></i> Todo está sincronizado
              </span>
            </div>

            <div class="d-grid gap-2" v-if="esEditable">
              <button class="btn btn-primary py-2 fw-bold shadow-sm" @click="guardarTurno(true)" :disabled="isSaving">
                <i class="bi bi-lock-fill me-1 text-warning"></i> CERRAR TURNO Y FINALIZAR
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

            <!-- IMPRIMIR REPORTE ALEX -->
            <div class="d-grid gap-2 mt-3 pt-3 border-top" v-if="!esNuevo && cabecera.id">
              <a :href="SERVER_DATA.reporteSobre + '?id=' + cabecera.id" target="_blank" class="btn btn-dark py-2 fw-bold">
                <i class="bi bi-printer-fill me-1"></i>Imprimir Reporte de Sobre (Alex)
              </a>
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
    fechaDefault: '<?= $fechaQuery ?>',
    userRol: '<?= $_SESSION['auth_rol'] ?? 'cajera' ?>',
    canEditClosed: <?= in_array($_SESSION['auth_rol'] ?? '', ['admin', 'supervisor']) ? 'true' : 'false' ?>,
    flujoIndex: <?= json_encode(route('flujo/index.php')) ?>,
    flujoForm: <?= json_encode(route('flujo/form.php')) ?>,
    roomingIndex: <?= json_encode(route('rooming/index.php')) ?>,
    reporteSobre: <?= json_encode(route('flujo/reporte_sobre.php')) ?>
  };
</script>
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $_root ?>app/Views/flujo/form.js?v=<?= time() ?>"></script>

<style>
  /* Forzar que las glosas se vean rojas incluso si el campo está deshabilitado */
  .text-danger:disabled, .text-danger[disabled], textarea.text-danger:read-only {
    color: #dc3545 !important;
    opacity: 1;
    -webkit-text-fill-color: #dc3545;
  }

  @media (max-width: 768px) {
    .main-content { padding: 8px !important; }
    .page-body { padding: 0 !important; }
    .topbar h4 { font-size: 1.1rem; }
    .card { border-radius: 4px !important; }
    .table td { padding: 0.4rem 0.3rem !important; }
    .form-select-sm, .form-control-sm { font-size: 12px; }
    .badge { font-size: 9px; }
    h2 { font-size: 1.5rem !important; }
    .sticky-top { position: relative !important; top: 0 !important; }
  }
</style>
