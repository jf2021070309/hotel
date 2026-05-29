<?php
/**
 * app/Views/limpieza/v2.php
 * Panel de Limpieza V2 — Vista tipo Excel con edición inline
 */
$base = '../../../';
$_projectRoot = defined('BASE_PATH') ? BASE_PATH : (rtrim(realpath(dirname(__DIR__, 3)), '\\/') . DIRECTORY_SEPARATOR);
require_once $_projectRoot . 'app/Middleware/session.php';
require_once $_projectRoot . 'app/Middleware/auth.php';
protegerPorRol('limpieza', 'limpieza');

$page_title = 'Limpieza V2 — Hotel Manager';
include $_projectRoot . 'app/Views/layouts/head.php';
?>

<div id="app-limpieza-v2" style="display:contents" v-cloak>
  <?php include $_projectRoot . 'app/Views/layouts/sidebar.php'; ?>

  <div class="main-content">

    <!-- ── TOPBAR PREMIUM DARK ──────────────────────────────────── -->
    <div class="topbar border-bottom-0 shadow-sm d-flex align-items-center"
         style="background: linear-gradient(to right, #0f172a, #1e293b); color:#fff; padding:10px 20px; min-height:56px;">

      <div class="d-flex align-items-center gap-3">
        <button class="btn-burger text-white border-0 bg-transparent" onclick="openSidebar()">
          <i class="bi bi-list fs-4"></i>
        </button>
        <div class="d-flex align-items-center gap-2">
          <div style="background:linear-gradient(135deg,#6366f1,#4f46e5);width:34px;height:34px;border-radius:8px;
                      display:flex;align-items:center;justify-content:center;color:#fff;
                      box-shadow:0 4px 10px rgba(99,102,241,.35);">
            <i class="bi bi-stars fs-5"></i>
          </div>
          <div>
            <h5 class="fw-bold mb-0 text-white" style="letter-spacing:-.5px;font-size:1.05rem;">Limpieza V2</h5>
            <p class="mb-0 d-none d-md-block" style="color:#94a3b8;font-size:10px;">
              Control de habitaciones estilo Excel · Edición directa
            </p>
          </div>
        </div>
      </div>

      <!-- Controles del topbar -->
      <div class="ms-auto d-flex align-items-center gap-2">

        <!-- Selector de fecha -->
        <input type="date" v-model="fecha" @change="cargarDatos"
               style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.15);
                      color:#fff;border-radius:6px;padding:4px 10px;font-size:12px;font-weight:600;
                      width:140px;cursor:pointer;" />

        <!-- Generar lista -->
        <button v-if="!yaGenerado" @click="generarLista" :disabled="loading"
                class="btn btn-sm fw-bold px-3"
                style="background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;
                       font-size:11px;border-radius:6px;box-shadow:0 3px 8px rgba(109,40,217,.4);">
          <i class="bi bi-magic me-1"></i>Generar Lista
        </button>

        <!-- Limpieza Diaria -->
        <button @click="resetNocturno" :disabled="loading"
                class="btn btn-sm fw-bold px-3"
                style="background:linear-gradient(135deg,#d97706,#b45309);color:#fff;border:none;
                       font-size:11px;border-radius:6px;box-shadow:0 3px 8px rgba(180,83,9,.4);"
                title="Marca habitaciones ocupadas como SUCIAS para limpieza diaria">
          <i class="bi bi-sun-fill me-1"></i>Limpieza Diaria
        </button>

        <!-- Exportar Excel -->
        <button @click="exportarExcel" :disabled="loading || lista.length === 0"
                class="btn btn-sm fw-bold px-3"
                style="background:linear-gradient(135deg,#059669,#047857);color:#fff;border:none;
                       font-size:11px;border-radius:6px;box-shadow:0 3px 8px rgba(5,150,105,.35);">
          <i class="bi bi-file-earmark-excel me-1"></i>Excel
        </button>

        <!-- Actualizar -->
        <button @click="cargarDatos" :disabled="loading"
                class="btn btn-sm text-white"
                style="background:transparent;border:1px solid rgba(255,255,255,.2);border-radius:6px;
                       padding:4px 10px;font-size:11px;font-weight:600;">
          <i class="bi bi-arrow-clockwise me-1" :class="{'spin-anim': loading}"></i>
          <span class="d-none d-sm-inline">Actualizar</span>
        </button>

      </div>
    </div><!-- /topbar -->

    <!-- ── PAGE BODY ─────────────────────────────────────────────── -->
    <div class="page-body pt-3">

      <!-- BARRA DE FILTROS / RESUMEN -->
      <div class="card border-0 shadow-sm mb-3" style="border-radius:12px;">
        <div class="card-body p-3">
          <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">

            <!-- Izquierda: búsqueda + conteo -->
            <div class="d-flex align-items-center gap-3 flex-wrap">
              <div class="input-group input-group-sm rounded shadow-sm" style="width:260px;">
                <span class="input-group-text bg-white border-end-0 text-muted px-2">
                  <i class="bi bi-search"></i>
                </span>
                <input type="text" class="form-control border-start-0 bg-white text-dark"
                       style="font-size:12px;" v-model="busqueda"
                       placeholder="Buscar habitación, estado...">
              </div>

              <!-- Pills de estado -->
              <div class="d-flex gap-1 flex-wrap">
                <span @click="filtroEstado='todos'"
                      class="badge px-3 py-2 cursor-pointer"
                      :style="filtroEstado==='todos' ? 'background:#1e293b;color:#fff;' : 'background:#e2e8f0;color:#475569;'"
                      style="font-size:10px;cursor:pointer;">Todos ({{ lista.length }})</span>
                <span @click="filtroEstado='pendiente'"
                      class="badge px-3 py-2"
                      :style="filtroEstado==='pendiente' ? 'background:#dc2626;color:#fff;' : 'background:#fee2e2;color:#dc2626;'"
                      style="font-size:10px;cursor:pointer;">Pendiente ({{ countEstado('pendiente') }})</span>
                <span @click="filtroEstado='en proceso'"
                      class="badge px-3 py-2"
                      :style="filtroEstado==='en proceso' ? 'background:#d97706;color:#fff;' : 'background:#fef3c7;color:#d97706;'"
                      style="font-size:10px;cursor:pointer;">En Proceso ({{ countEstado('en proceso') }})</span>
                <span @click="filtroEstado='lista'"
                      class="badge px-3 py-2"
                      :style="filtroEstado==='lista' ? 'background:#059669;color:#fff;' : 'background:#d1fae5;color:#059669;'"
                      style="font-size:10px;cursor:pointer;">Lista ({{ countEstado('lista') }})</span>
              </div>

              <div class="text-muted fw-semibold" style="font-size:12px;" v-if="!loading">
                <i class="bi bi-table me-1"></i>{{ listaFiltrada.length }} filas
              </div>
            </div>

            <!-- Derecha: barra de progreso global -->
            <div class="d-flex align-items-center gap-2" style="min-width:180px;" v-if="lista.length > 0">
              <span class="text-muted" style="font-size:11px;white-space:nowrap;">Completado:</span>
              <div class="progress flex-grow-1" style="height:8px;border-radius:999px;">
                <div class="progress-bar bg-success" :style="{ width: porcentajeGlobal + '%' }"
                     style="transition:width .4s;border-radius:999px;"></div>
              </div>
              <span class="fw-bold" style="font-size:11px;color:#059669;white-space:nowrap;">
                {{ porcentajeGlobal }}%
              </span>
            </div>

          </div>
        </div>
      </div>

      <!-- TABLA EXCEL -->
      <div class="card border-0 shadow-sm overflow-hidden" style="border-radius:12px;">
        <div class="lv2-grid-container">
          <table class="table table-bordered mb-0 lv2-table">
            <thead>
              <!-- Nivel 1: Grupos de columnas -->
              <tr class="text-center text-white text-uppercase"
                  style="font-size:10px;letter-spacing:.5px;font-weight:800;">
                <th colspan="2" style="background:#111827!important;position:sticky;left:0;z-index:15;">HABITACIÓN</th>
                <th colspan="2" style="background:#1e3a5f!important;">LIMPIEZA</th>
                <th colspan="2" style="background:#14532d!important;">HUÉSPED</th>
                <th colspan="1" style="background:#7c2d12!important;">ACCIÓN</th>
              </tr>
              <!-- Nivel 2: Sub-cabeceras -->
              <tr class="text-white text-uppercase" style="font-size:11px;letter-spacing:.4px;">
                <th class="lv2-sticky" style="width:50px;background:#111827!important;z-index:14;">HAB</th>
                <th style="width:90px;top:38px;background:#111827!important;">TIPO HAB</th>
                <th style="width:110px;top:38px;background:#1e3a5f!important;">TIPO LIMP.</th>
                <th style="width:110px;top:38px;background:#1e3a5f!important;">ESTADO</th>
                <th style="width:55px;top:38px;background:#14532d!important;">PAX</th>
                <th style="width:110px;top:38px;background:#14532d!important;">ROOM ESTADO</th>
                <th style="width:130px;top:38px;background:#7c2d12!important;">ACCIÓN RÁPIDA</th>
              </tr>
            </thead>

            <tbody>
              <!-- Cargando -->
              <tr v-if="loading">
                <td colspan="7" class="text-center py-5">
                  <div class="spinner-border text-primary me-2"></div>
                  <span class="text-muted fw-semibold">Cargando datos...</span>
                </td>
              </tr>

              <!-- Sin datos -->
              <tr v-else-if="listaFiltrada.length === 0">
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="bi bi-inbox fs-1 d-block opacity-25 mb-2"></i>
                  <span>No hay registros para esta fecha o filtro.</span>
                  <br>
                  <button v-if="!yaGenerado" @click="generarLista"
                          class="btn btn-sm btn-dark mt-3 fw-bold">
                    <i class="bi bi-magic me-1"></i>Generar Lista de Hoy
                  </button>
                </td>
              </tr>

              <!-- Filas de datos -->
              <tr v-else v-for="h in listaFiltrada" :key="h.id"
                  :class="getRowClass(h)">

                <!-- HAB (sticky) -->
                <td class="lv2-sticky text-center fw-black"
                    :style="{ borderLeft: '4px solid ' + getColorTipo(h.tipo_limpieza) }"
                    style="font-size:1.4rem;letter-spacing:-1px;color:#1e293b;line-height:1;">
                  {{ h.habitacion }}
                </td>

                <!-- TIPO HAB -->
                <td class="text-center text-muted" style="font-size:11px;">
                  {{ h.tipo_hab ? h.tipo_hab.toUpperCase() : '—' }}
                </td>

                <!-- TIPO LIMPIEZA (badge) -->
                <td class="text-center">
                  <span class="badge px-2 py-1 fw-bold" style="font-size:10px;"
                        :style="{ background: getColorTipo(h.tipo_limpieza), color: '#fff' }">
                    {{ labelTipo(h.tipo_limpieza) }}
                  </span>
                </td>

                <!-- ESTADO (select editable) -->
                <td class="px-1">
                  <select v-model="h.estado" @change="actualizarEstado(h)"
                          class="lv2-select fw-bold"
                          :class="getEstadoSelectClass(h.estado)">
                    <option value="pendiente">⏳ Pendiente</option>
                    <option value="en proceso">🧹 En Proceso</option>
                    <option value="lista">✅ Lista</option>
                    <option value="mantenimiento" v-if="h.estado==='mantenimiento'">🔧 Mantenimiento</option>
                  </select>
                </td>


                <!-- PAX -->
                <td class="text-center fw-bold" style="font-size:12px;color:#1e293b;">
                  {{ h.pax ?? h.ocupantes ?? '—' }}
                </td>

                <!-- ROOM ESTADO badge -->
                <td class="text-center">
                  <span v-if="h.room_estado" class="badge px-2 py-1" style="font-size:10px;"
                        :style="{ background: getRoomEstadoColor(h.room_estado), color: '#fff' }">
                    {{ h.room_estado.toUpperCase() }}
                  </span>
                  <span v-else class="text-muted" style="font-size:11px;">—</span>
                </td>

                <!-- ACCIÓN RÁPIDA -->
                <td class="text-center px-1">
                  <button v-if="h.estado !== 'lista' && h.estado !== 'mantenimiento'"
                          @click="toggleListo(h)" :disabled="loading"
                          class="btn btn-sm fw-bold w-100"
                          style="background:linear-gradient(135deg,#059669,#047857);color:#fff;
                                 font-size:10px;border:none;border-radius:6px;padding:5px 4px;">
                    <i class="bi bi-check2-circle me-1"></i>Marcar Lista
                  </button>
                  <button v-else-if="h.estado === 'lista'"
                          @click="toggleListo(h)" :disabled="loading"
                          class="btn btn-sm fw-bold w-100"
                          style="background:#d1fae5;color:#065f46;font-size:10px;
                                 border:1px solid #a7f3d0;border-radius:6px;padding:5px 4px;">
                    <i class="bi bi-check-all me-1"></i>Lista ✓
                  </button>
                  <span v-else class="badge bg-danger-subtle text-danger"
                        style="font-size:10px;padding:5px 8px;">
                    <i class="bi bi-tools me-1"></i>Bloqueada
                  </span>
                </td>

              </tr>
            </tbody>


          </table>
        </div>
      </div><!-- /card tabla -->

    </div><!-- /page-body -->
  </div><!-- /main-content -->
</div><!-- /app -->

<!-- ── ESTILOS ──────────────────────────────────────────────── -->
<style>
  [v-cloak] { display: none !important; }

  .lv2-grid-container {
    max-height: calc(100vh - 200px);
    overflow: auto;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
  }
  .lv2-grid-container::-webkit-scrollbar { width:10px; height:10px; }
  .lv2-grid-container::-webkit-scrollbar-track { background:#f1f5f9; border-radius:4px; }
  .lv2-grid-container::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; border:2px solid #f1f5f9; }
  .lv2-grid-container::-webkit-scrollbar-thumb:hover { background:#94a3b8; }

  .lv2-table {
    min-width: 900px;
    font-size: 12px;
    border-collapse: separate;
    border-spacing: 0;
  }
  .lv2-table thead th {
    position: sticky;
    top: 0;
    color: #fff !important;
    font-weight: 700;
    text-align: center;
    vertical-align: middle;
    padding: 9px 6px;
    border: 1px solid rgba(255,255,255,0.08);
    white-space: nowrap;
  }
  /* Segunda fila de thead */
  .lv2-table thead tr:nth-child(2) th {
    top: 38px;
  }

  /* Sticky primera columna */
  .lv2-sticky {
    position: sticky !important;
    left: 0;
    z-index: 6;
    background-color: #f8fafc;
    border-right: 2px solid #e2e8f0 !important;
  }
  .lv2-table thead th.lv2-sticky {
    z-index: 16 !important;
    top: 0;
  }

  .lv2-table td {
    padding: 4px 6px;
    vertical-align: middle;
    border: 1px solid #e2e8f0;
    background: #fff;
    white-space: nowrap;
  }
  .lv2-table tbody tr:hover td { background:#f1f5f9; }
  .lv2-table tbody tr:hover td.lv2-sticky { background:#e2e8f0; }

  /* Select editable estilo tabla */
  .lv2-select {
    border: 1px solid transparent;
    background: transparent;
    padding: 4px 6px;
    border-radius: 6px;
    font-size: 11px;
    width: 100%;
    cursor: pointer;
    transition: all .15s;
  }
  .lv2-select:hover { border-color: #cbd5e1; background: #f8fafc; }
  .lv2-select:focus { border-color: #6366f1; outline: none; box-shadow: 0 0 0 2px rgba(99,102,241,.2); }

  /* Colores de estado en el select */
  .lv2-select.estado-lista     { color: #065f46; background: #d1fae5; border-color: #a7f3d0; }
  .lv2-select.estado-pendiente { color: #991b1b; background: #fee2e2; border-color: #fca5a5; }
  .lv2-select.estado-proceso   { color: #92400e; background: #fef3c7; border-color: #fde68a; }

  /* Filas destacadas por estado */
  .row-lista    td { background: #f0fdf4 !important; }
  .row-lista    td.lv2-sticky { background: #dcfce7 !important; }
  .row-proceso  td { background: #fffbeb !important; }
  .row-mant     td { background: #fff1f2 !important; }

  /* fw-black utility */
  .fw-black { font-weight: 900 !important; }

  /* Spinner animación */
  @keyframes spin { to { transform: rotate(360deg); } }
  .spin-anim { animation: spin .6s linear infinite; display: inline-block; }
</style>

<!-- ── SERVER DATA ───────────────────────────────────────────── -->
<script>
  window.SERVER_DATA = {
    apiBase: <?= json_encode(project_base_url() . 'ajax/limpieza.php') ?>,
    hoy:     <?= json_encode(date('Y-m-d')) ?>
  };
</script>

<!-- ── LIBS ─────────────────────────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vue@3.3.4/dist/vue.global.prod.js"></script>
<script src="<?= $base ?>assets/js/limpieza_v2.js?v=<?= time() ?>"></script>

<?php include BASE_PATH . 'app/Views/layouts/footer.php'; ?>
