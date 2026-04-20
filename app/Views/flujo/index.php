<?php
/**
 * app/Views/flujo/index.php
 */
$base = '../../../';
$_projectRoot = dirname(__DIR__, 3);
require_once $_projectRoot . '/auth/middleware.php';
require_once $_projectRoot . '/rutas.php';
protegerPorRol('cajera', 'flujo');
require_once $_projectRoot . '/config/db.php'; // Asegurar PDO

// AUTO-REDIRECT: Si hay un turno abierto (borrador), entrar directo.
// Se permite bypass con ?noredirect=1 para ver el listado histórico.
if (!isset($_GET['noredirect'])) {
    require_once $_projectRoot . '/app/Models/FlujoModel.php';
    $fm = new FlujoModel($pdo);
    $activoId = $fm->getTurnoActivo();
    if ($activoId) {
        header("Location: " . route('flujo/form.php') . '?id=' . $activoId);
        exit;
    }
}

$page_title = 'Flujo de Caja — Hotel Manager';
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';
?>

<div class="main-content" id="app-flujo-index">
  <div class="topbar border-bottom-0 shadow-sm" style="background: linear-gradient(to right, #ffffff, #f8f9fa);">
    <div class="d-flex align-items-center gap-3">
      <button class="btn-burger" onclick="handleMenuClick()"><i class="bi bi-list fs-4"></i></button>
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-cash-stack fs-5 d-none d-sm-block" style="color: #d4af37;"></i>
        <div class="text-nowrap">
          <h5 class="fw-bold mb-0" style="color: #111; letter-spacing: -0.5px; font-size: 1.05rem;">Flujo de Caja</h5>
          <p class="mb-0 small text-muted fw-semibold d-none d-md-block" style="font-size: 10px;">Control de ingresos y egresos</p>
        </div>
      </div>
    </div>
    <div class="ms-auto d-flex align-items-center gap-2">
      <div class="d-flex bg-white border rounded shadow-sm p-1">
        <select class="form-select form-select-sm border-0 fw-bold text-dark bg-transparent py-0 px-2" v-model="filtros.mes" @change="listar" style="width: auto; font-size: 11px; cursor: pointer;">
          <option v-for="(m, i) in mesesShort" :key="i" :value="i+1">{{ m }}</option>
        </select>
        <div class="vr mx-1"></div>
        <input type="number" class="form-control form-control-sm border-0 fw-bold text-dark bg-transparent py-0 px-1 text-center" v-model="filtros.anio" @change="listar" style="width: 55px; font-size: 11px;" min="2020">
      </div>
    </div>
  </div>

  <div class="page-body">
    <!-- BARRA DE ACCIONES SUPERIOR -->
    <div class="row g-2 mb-3 align-items-center">
      <div class="col-12 col-sm-auto">
        <button class="btn btn-sm btn-primary w-100 shadow-sm fw-bold py-2" @click="nuevoTurno" :disabled="loadingCheck" style="border: 1px solid #111; font-size: 12px;">
          <span v-if="loadingCheck" class="spinner-border spinner-border-sm me-1"></span>
          <i v-else class="bi bi-plus-lg me-1 text-warning"></i>NUEVO TURNO
        </button>
      </div>
      <div class="col-6 col-sm-auto">
        <select class="form-select form-select-sm fw-bold text-secondary shadow-sm" v-model="filtros.estado" @change="listar" style="font-size: 12px;">
          <option value="todos">Todos los Estados</option>
          <option value="borrador">Borrador</option>
          <option value="cerrado">Cerrado</option>
          <option value="depositado">Depositado</option>
        </select>
      </div>
      <div class="col-6 col-sm-auto ms-sm-auto">
        <a href="<?= route('flujo/dia.php') ?>" class="btn btn-sm btn-outline-dark w-100 fw-bold shadow-sm" style="font-size: 11px;">
          <i class="bi bi-calendar2-range me-1"></i>RESUMEN DÍA
        </a>
      </div>
    </div>

    <!-- TABLA DE TURNOS -->
    <div class="card border-0 shadow-sm" style="border-radius:10px; overflow:hidden;">
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-success"></div>
        <div class="mt-2 text-muted small">Cargando flujos...</div>
      </div>

      <div class="table-responsive" v-else>
        <table class="table table-hover align-middle mb-0" style="font-size:13px;">
          <thead class="table-light text-secondary">
            <tr style="font-size:11px; text-transform:uppercase; letter-spacing:0.5px;">
              <th>ID</th>
              <th>Fecha</th>
              <th>Turno</th>
              <th>Operador</th>
              <th class="text-end">Ingresos</th>
              <th class="text-end">Egresos</th>
              <th class="text-end text-success">SE ENTREGA A ALEX</th>
              <th>Estado</th>
              <th class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="f in flujos" :key="f.id">
              <td class="text-muted fw-bold">#{{ f.id }}</td>
              <td class="fw-bold">{{ f.fecha }}</td>
              <td><span class="badge bg-secondary rounded-pill"><i class="bi" :class="f.turno==='MAÑANA'?'bi-sun-fill text-warning':'bi-moon-stars-fill'"></i> {{ f.turno }}</span></td>
              <td>{{ f.operador }}</td>
              <td class="text-end">S/ {{ parseFloat(f.total_ingresos).toFixed(2) }}</td>
              <td class="text-end">S/ {{ parseFloat(f.total_egresos).toFixed(2) }}</td>
              <td class="text-end fw-bold text-success">S/ {{ parseFloat(f.efectivo_sobre).toFixed(2) }}</td>
              <td>
                <span class="badge" :class="estadoClass(f.estado)">
                  {{ f.estado.toUpperCase() }}
                </span>
              </td>
              <td class="text-end">
                <a :href="FLUJO_ROUTES.form + '?id=' + f.id" class="btn btn-sm" :class="f.estado==='borrador'?'btn-primary':'btn-outline-dark'">
                  <i class="bi" :class="f.estado==='borrador'?'bi-pencil-square':'bi-eye'"></i>
                  {{ f.estado==='borrador' ? 'Editar' : 'Ver' }}
                </a>
              </td>
            </tr>
            <tr v-if="flujos.length === 0">
              <td colspan="9" class="text-center py-4 text-muted">No se encontraron turnos con los filtros aplicados.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>
<style>
  [v-cloak] { display: none !important; }
  .table th, .table td { padding: 0.7rem 0.6rem !important; }
  
  @media (max-width: 768px) {
    .main-content { padding: 6px !important; }
    .page-body { padding: 10px 5px !important; }
    .topbar h5 { font-size: 1rem !important; }
    .table td { font-size: 11px !important; }
    .badge { font-size: 8px !important; padding: 0.35em 0.5em !important; }
    .btn-sm { padding: 0.4rem 0.5rem !important; font-size: 10px !important; }
  }
</style>
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  window.FLUJO_ROUTES = {
    form: <?= json_encode(route('flujo/form.php')) ?>,
    dia: <?= json_encode(route('flujo/dia.php')) ?>
  };
</script>
<script src="<?= $base ?>app/Views/flujo/index.js?v=<?= time() ?>"></script>
