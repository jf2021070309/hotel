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
    <div class="topbar" style="background-color:#111827;padding:0.75rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.05);">
      <div class="d-flex align-items-center justify-content-between w-100">
        <div class="d-flex align-items-center gap-3">
          <button class="btn btn-dark btn-sm rounded-circle d-md-none" onclick="handleMenuClick()" style="width:32px;height:32px;padding:0;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.1);border:none;">
            <i class="bi bi-list text-white"></i>
          </button>
          <div class="d-flex align-items-center gap-3">
            <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#f8fafc,#94a3b8);display:flex;align-items:center;justify-content:center;box-shadow:0 0 15px rgba(148,163,184,0.4);">
              <i class="bi bi-stars text-dark fs-5"></i>
            </div>
            <div>
              <h4 class="fw-bold mb-0 text-white" style="font-size:18px;letter-spacing:-0.5px;">Limpieza V2</h4>
              <div class="text-white-50" style="font-size:11px;">Control de habitaciones estilo Excel &middot; Edición directa</div>
            </div>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-sm btn-outline-light d-flex align-items-center gap-2" @click="cargarDatos" :disabled="loading" style="font-size:12px;padding:4px 12px;border-color:rgba(255,255,255,0.2);">
            <i class="bi bi-arrow-clockwise" :class="{'spin-anim':loading}"></i>
            <span class="d-none d-md-inline">Actualizar</span>
          </button>
        </div>
      </div>
    </div><!-- /topbar -->

    <!-- ── PAGE BODY ─────────────────────────────────────────────── -->
    <div class="page-body pt-3">

      <!-- BARRA DE FILTROS / RESUMEN -->
      <div class="card border-0 shadow-sm mb-3" style="border-radius:12px;">
        <div class="card-body p-3">
          <div class="d-flex flex-wrap gap-2 align-items-center w-100">

            <!-- Búsqueda -->
            <div class="input-group input-group-sm rounded shadow-sm" style="width: 320px;">
              <span class="input-group-text bg-white border-end-0 text-muted px-2">
                <i class="bi bi-search"></i>
              </span>
              <input type="text" class="form-control border-start-0 bg-white text-dark"
                     style="font-size: 13px;" v-model="busqueda"
                     placeholder="Buscar habitación, estado...">
            </div>

            <!-- Filtro de estado (Combobox) -->
            <div style="width: 160px;">
              <select v-model="filtroEstado" class="form-select form-select-sm shadow-sm text-dark bg-white border-secondary-subtle" style="font-size: 13px; cursor: pointer; height: 31px; border-radius: 6px;">
                <option value="todos">Todos ({{ lista.length }})</option>
                <option value="pendiente">Pendiente ({{ countEstado('pendiente') }})</option>
                <option value="en proceso">En Proceso ({{ countEstado('en proceso') }})</option>
                <option value="lista">Lista ({{ countEstado('lista') }})</option>
              </select>
            </div>

            <!-- Selector de fecha -->
            <div style="width: 140px;">
              <input type="date" v-model="fecha" @change="cargarDatos"
                     class="form-control form-control-sm shadow-sm text-dark bg-white border-secondary-subtle"
                     style="font-size: 13px; cursor: pointer; height: 31px; border-radius: 6px; font-weight: 500;" />
            </div>

            <!-- Botones de Acción alineados a la derecha -->
            <div class="d-flex align-items-center gap-2 ms-md-auto">
              <!-- Generar lista -->
              <button v-if="!yaGenerado" @click="generarLista" :disabled="loading"
                      class="btn btn-sm btn-custom-blue fw-bold px-3 d-flex align-items-center gap-1 shadow-sm"
                      style="font-size:12px;height:31px;border-radius:6px;">
                <i class="bi bi-magic"></i>Generar Lista
              </button>

              <!-- Limpieza Diaria (Azul celeste) -->
              <button @click="resetNocturno" :disabled="loading"
                      class="btn btn-sm btn-custom-blue fw-bold px-3 d-flex align-items-center gap-1 shadow-sm"
                      style="font-size:12px;height:31px;border-radius:6px;"
                      title="Marca habitaciones ocupadas como SUCIAS para limpieza diaria">
                <i class="bi bi-sun-fill"></i>Limpieza Diaria
              </button>

              <!-- Exportar Excel -->
              <button @click="exportarExcel" :disabled="loading || lista.length === 0"
                      class="btn btn-sm btn-custom-green fw-bold px-3 d-flex align-items-center gap-1 shadow-sm"
                      style="font-size:12px;height:31px;border-radius:6px;">
                <i class="bi bi-file-earmark-excel"></i>Exportar Excel
              </button>
            </div>

          </div>
        </div>
      </div>

      <!-- TABLA EXCEL -->
      <div class="card border-0 shadow-sm overflow-hidden" style="border-radius:12px;">
        <div class="lv2-grid-container">
          <table class="table table-bordered mb-0 lv2-table">
            <thead>
              <tr class="table-dark text-white text-uppercase text-center" style="font-size: 10px; letter-spacing: 0.5px;">
                <th class="lv2-sticky" style="padding: 12px 16px; width: 50px; z-index: 15 !important;">HAB</th>
                <th style="padding: 12px 16px; width: 90px;">TIPO HAB</th>
                <th style="padding: 12px 16px; width: 110px;">ESTADO</th>
                <th style="padding: 12px 16px; width: 55px;">PAX</th>
                <th style="padding: 12px 16px; width: 110px;">ROOM ESTADO</th>
                <th style="padding: 12px 16px; width: 130px;">ACCIÓN RÁPIDA</th>
              </tr>
            </thead>

            <tbody>
              <!-- Cargando -->
              <tr v-if="loading">
                <td colspan="6" class="text-center py-5">
                  <div class="spinner-border text-primary me-2"></div>
                  <span class="text-muted fw-semibold">Cargando datos...</span>
                </td>
              </tr>

              <!-- Sin datos -->
              <tr v-else-if="listaFiltrada.length === 0">
                <td colspan="6" class="text-center py-5 text-muted">
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
                <td class="lv2-sticky text-center fw-bold"
                    :style="{ borderLeft: '4px solid ' + getColorTipo(h.tipo_limpieza) }"
                    style="font-size:12px;color:#1e293b;">
                  {{ h.habitacion }}
                </td>

                <!-- TIPO HAB -->
                <td class="text-center text-muted fw-semibold" style="font-size:12px;">
                  {{ h.tipo_hab ? h.tipo_hab.toUpperCase() : '—' }}
                </td>

                <!-- ESTADO (select editable) -->
                <td class="px-1">
                  <select v-model="h.estado" @change="actualizarEstado(h)"
                          class="lv2-select fw-bold"
                          :class="getEstadoSelectClass(h.estado)">
                    <option value="pendiente">Pendiente</option>
                    <option value="en proceso">En Proceso</option>
                    <option value="lista">Lista</option>
                    <option value="mantenimiento" v-if="h.estado==='mantenimiento'">Mantenimiento</option>
                  </select>
                </td>


                <!-- PAX -->
                <td class="text-center fw-bold" style="font-size:12px;color:#1e293b;">
                  {{ h.pax ?? h.ocupantes ?? '—' }}
                </td>

                <!-- ROOM ESTADO (texto con color, sin badge de fondo) -->
                <td class="text-center">
                  <span v-if="h.room_estado" class="fw-bold text-uppercase" style="font-size:12px;"
                        :style="{ color: getRoomEstadoColor(h.room_estado) }">
                    {{ h.room_estado }}
                  </span>
                  <span v-else class="text-muted" style="font-size:12px;">—</span>
                </td>

                <!-- ACCIÓN RÁPIDA -->
                <td class="text-center px-1">
                  <button v-if="h.estado !== 'lista' && h.estado !== 'mantenimiento'"
                          @click="toggleListo(h)" :disabled="loading"
                          class="btn btn-sm fw-bold w-100"
                          style="background:linear-gradient(135deg,#059669,#047857);color:#fff;
                                 font-size:12px;border:none;border-radius:6px;padding:5px 4px;">
                    <i class="bi bi-check2-circle me-1"></i>Marcar Lista
                  </button>
                  <button v-else-if="h.estado === 'lista'"
                          @click="toggleListo(h)" :disabled="loading"
                          class="btn btn-sm fw-bold w-100"
                          style="background:#d1fae5;color:#065f46;font-size:12px;
                                 border:1px solid #a7f3d0;border-radius:6px;padding:5px 4px;">
                    <i class="bi bi-check-all me-1"></i>Lista ✓
                  </button>
                  <span v-else class="badge bg-danger-subtle text-danger"
                        style="font-size:12px;padding:5px 8px;">
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
    z-index: 10;
    color: #ffffff !important;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.5px;
    text-align: center;
    border: 1px solid #cbd5e1;
    vertical-align: middle;
    padding: 10px 6px;
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
    border-right: 1px solid #cbd5e1 !important;
  }
  .lv2-table thead th.lv2-sticky {
    z-index: 16 !important;
    top: 0;
  }

  .lv2-table td {
    padding: 3px 4px;
    vertical-align: middle;
    border: 1px solid #cbd5e1;
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
    font-size: 12px;
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

  /* Botones premium con bordes finos y colores sólidos idénticos a Clientes V2 */
  .btn-custom-blue {
    background-color: #1a56db !important; /* Azul vibrante corporativo */
    color: #ffffff !important;
    border: 1px solid #1e429f !important;
    transition: all 0.2s ease-in-out;
  }
  .btn-custom-blue:hover:not(:disabled) {
    background-color: #1e429f !important;
    border-color: #1e429f !important;
  }
  .btn-custom-blue:disabled {
    opacity: 0.65;
  }

  .btn-custom-green {
    background-color: #059669 !important; /* Verde sólido oscuro */
    color: #ffffff !important;
    border: 1px solid #047857 !important;
    transition: all 0.2s ease-in-out;
  }
  .btn-custom-green:hover:not(:disabled) {
    background-color: #047857 !important;
    border-color: #047857 !important;
  }
  .btn-custom-green:disabled {
    opacity: 0.65;
  }

  .btn-custom-orange {
    background-color: #d97706 !important; /* Naranja/Ámbar sólido */
    color: #ffffff !important;
    border: 1px solid #b45309 !important;
    transition: all 0.2s ease-in-out;
  }
  .btn-custom-orange:hover:not(:disabled) {
    background-color: #b45309 !important;
    border-color: #b45309 !important;
  }
  .btn-custom-orange:disabled {
    opacity: 0.65;
  }
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
