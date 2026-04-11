<?php
$base = '../../../';
require_once $base . 'config/db.php';
require_once $base . 'auth/session.php';
require_once $base . 'auth/middleware.php';

protegerPorRol('cajera', 'yape');

// Detectar turno aproximado por default
$horaActual = (int) date('H');
$turnoDefault = ($horaActual >= 6 && $horaActual < 14) ? 'MAÑANA' : 'TARDE';
$mesActual = date('n');
$anioActual = date('Y');

$page_title = 'Gastos Yape — Hotel Manager';
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';
?>

<div class="main-content" id="app-yape-index" v-cloak>
  <div class="topbar border-bottom-0 shadow-sm" style="background: linear-gradient(to right, #ffffff, #f8f9fa);">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list fs-4"></i></button>
    <div>
      <h4 class="fw-bold" style="color: #111; letter-spacing: -0.5px;">
        <i class="bi bi-wallet2 me-2" style="color: #d4af37;"></i>Gastos Yape
      </h4>
      <p class="mb-0 small text-muted fw-semibold">Gestión de compras con dinero enviado mediante Yape</p>
    </div>
    <div class="ms-auto d-flex align-items-center gap-2">
      <!-- Filtro Turno -->
      <select v-model="filtros.turno" class="form-select form-select-sm bg-light fw-bold text-secondary border-0 shadow-sm" style="width: 125px; font-size: 11.5px;" @change="aplicarFiltrosFront()">
        <option value="">Turno: Todos</option>
        <option value="MAÑANA">MAÑANA</option>
        <option value="TARDE">TARDE</option>
      </select>
      <!-- Filtro Estado -->
      <select v-model="filtros.estado" class="form-select form-select-sm bg-light fw-bold text-secondary border-0 shadow-sm" style="width: 135px; font-size: 11.5px;" @change="aplicarFiltrosFront()">
        <option value="">Estado: Todos</option>
        <option value="borrador">Borrador</option>
        <option value="cerrado">Cerrado</option>
      </select>

      <div style="width:1px; height: 28px; background-color: #cbd5e1; margin: 0 4px;"></div>

      <!-- Filtro Mes -->
      <select v-model="filtros.mes" class="form-select form-select-sm bg-white fw-bold border-1 shadow-sm" style="width:115px; font-size: 12px;" @change="listar()">
        <option value="1">Enero</option>
        <option value="2">Febrero</option>
        <option value="3">Marzo</option>
        <option value="4">Abril</option>
        <option value="5">Mayo</option>
        <option value="6">Junio</option>
        <option value="7">Julio</option>
        <option value="8">Agosto</option>
        <option value="9">Septiembre</option>
        <option value="10">Octubre</option>
        <option value="11">Noviembre</option>
        <option value="12">Diciembre</option>
      </select>
      <input type="number" v-model="filtros.anio" class="form-control form-control-sm text-center fw-bold bg-white border-1 shadow-sm" style="width:80px; font-size: 12px;" @change="listar()">
    </div>
  </div>

  <div class="page-body">
    <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
      <button class="btn-primary-custom shadow-sm" @click="nuevoRegistro()" style="border: 1px solid #111;">
        <i class="bi bi-plus-lg text-warning"></i> Nuevo Registro Yape
      </button>
      <a :href="`reporte_mensual.php?mes=${filtros.mes}&anio=${filtros.anio}`" target="_blank"
        class="btn btn-success shadow-sm" style="font-weight: 600; font-size: 14px;">
        <i class="bi bi-printer-fill me-1"></i> Generar Reporte Mensual
      </a>
      <button class="btn btn-outline-secondary btn-sm ms-auto" @click="listar()">
        <i class="bi bi-arrow-clockwise"></i>
      </button>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:10px; overflow:hidden;">
      <div class="card-body p-0">

        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary"></div>
          <div class="mt-2 text-muted small">Cargando registros Yape...</div>
        </div>

        <div class="table-responsive" v-else>
          <table class="table table-bordered table-hover align-middle mb-0 text-sm" style="white-space: nowrap;">
            <thead class="table-dark text-white text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
              <tr>
                <th class="ps-3 text-center">TURNO</th>
                <th class="text-center">FECHA</th>
                <th class="text-end">YAPE REC.</th>
                <th class="text-end" v-for="cat in categoriasConfig" :key="cat">{{ cat }}</th>
                <th class="text-end" style="background-color: #334155;">TOTAL</th>
                <th class="text-end text-success">VUELTOS</th>
                <th class="text-center">ESTADO</th>
                <th class="text-center pe-3">ACCIONES</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="diasAgrupados.length === 0">
                <td :colspan="8 + categoriasConfig.length" class="text-center text-muted py-4">No se encontraron
                  registros Yape para el mes seleccionado.</td>
              </tr>
              <template v-for="grupo in diasAgrupados" :key="grupo.fecha">
                <!-- Filas de Turnos Individuales -->
                <tr v-for="y in grupo.turnos" :key="y.id" class="bg-white">
                  <td class="ps-3 fw-bold text-center">
                    <span :class="y.turno=='MAÑANA' ? 'text-info' : 'text-dark'">{{ y.turno }}</span>
                  </td>
                  <td class="text-center fw-bold text-secondary">{{ formatFecha(y.fecha) }}</td>
                  <td class="text-end text-primary fw-bold">{{ parseFloat(y.yape_recibido).toFixed(2) }}</td>
                  <td class="text-end" v-for="cat in categoriasConfig" :key="cat">
                    <template v-if="y.detalles_montos && y.detalles_montos[cat] > 0">
                      <span>{{ parseFloat(y.detalles_montos[cat]).toFixed(2) }}</span>
                      <i v-if="y.detalles_info && y.detalles_info[cat] && (y.detalles_info[cat].observacion || y.detalles_info[cat].documento)" 
                         class="bi bi-chat-dots-fill text-warning ms-1" 
                         style="cursor: pointer; font-size: 11px;"
                         @click.stop="verNota(cat, y.detalles_info[cat])"
                         title="Ver detalles"></i>
                    </template>
                    <span v-else class="text-muted" style="opacity:0.3">-</span>
                  </td>
                  <td class="text-end text-danger fw-bold" style="background-color: #f8fafc;">{{
                    parseFloat(y.total_gastado).toFixed(2) }}</td>
                  <td class="text-end text-success fw-bold">{{ parseFloat(y.vuelto).toFixed(2) }}</td>
                  <td class="text-center">
                    <span v-if="y.estado==='borrador'" class="badge bg-warning text-dark" style="font-size: 10px;"><i
                        class="bi bi-pencil-square"></i> Borrador</span>
                    <span v-else class="badge bg-success" style="font-size: 10px;"><i
                        class="bi bi-check-circle-fill"></i> Cerrado</span>
                  </td>
                  <td class="text-center pe-3">
                    <a :href="`form.php?id=${y.id}`" class="btn btn-sm"
                      :class="y.estado==='borrador'?'btn-primary':'btn-outline-secondary'">
                      <i class="bi" :class="y.estado==='borrador'?'bi-pencil':'bi-eye'"></i>
                    </a>
                  </td>
                </tr>
                <!-- Fila de Totales del Día -->
                <tr style="background-color: #fef08a; font-weight: bold; border-bottom: 2px solid #cbd5e1;">
                  <td class="ps-3 text-center text-dark" style="background-color: transparent;">TOTAL</td>
                  <td class="text-center" style="background-color: transparent;"></td>
                  <td class="text-end text-primary" style="background-color: transparent;">{{
                    grupo.totales.yape_recibido.toFixed(2) }}</td>
                  <td class="text-end text-secondary" v-for="cat in categoriasConfig" :key="cat"
                    style="background-color: transparent;">
                    <span v-if="grupo.totales.rubros[cat] > 0">{{ grupo.totales.rubros[cat].toFixed(2) }}</span>
                    <span v-else class="text-muted" style="opacity:0.6">-</span>
                  </td>
                  <td class="text-end text-danger" style="background-color: transparent;">{{
                    grupo.totales.total_gastado.toFixed(2) }}</td>
                  <td class="text-end text-success" style="background-color: transparent;">{{
                    grupo.totales.vuelto.toFixed(2) }}</td>
                  <td colspan="2" style="background-color: transparent;"></td>
                </tr>
              </template>
              <!-- TOTAL MENSUAL -->
              <tr v-if="diasAgrupados.length > 0"
                style="background-color: #e2e8f0; color: #0f172a; font-weight: bold; border-top: 3px solid #94a3b8;">
                <td class="ps-3 text-center py-3" colspan="2"
                  style="background-color: transparent; letter-spacing: 1px;">TOTAL MENSUAL</td>
                <td class="text-end text-primary py-3" style="background-color: transparent;">{{
                  globales.yape_recibido.toFixed(2) }}</td>
                <td class="text-end py-3" v-for="cat in categoriasConfig" :key="'global_'+cat"
                  style="background-color: transparent;">
                  <span v-if="globales.rubros[cat] > 0" class="text-secondary">{{ globales.rubros[cat].toFixed(2)
                    }}</span>
                  <span v-else class="text-muted" style="opacity:0.4">-</span>
                </td>
                <td class="text-end text-danger py-3" style="background-color: transparent;">{{
                  globales.total_gastado.toFixed(2) }}</td>
                <td class="text-end text-success py-3" style="background-color: transparent;">{{
                  globales.vuelto.toFixed(2) }}</td>
                <td colspan="2" class="py-3" style="background-color: transparent;"></td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>

  </div>

</div>

<style>
  [v-cloak] {
    display: none !important;
  }

  .table-hover tbody tr:hover {
    background-color: #f8f9fa;
  }

  .text-sm {
    font-size: 0.9rem;
  }
</style>

<script>
  window.TURNO_DEFAULT = <?= json_encode($turnoDefault) ?>;
  window.MES_ACTUAL = <?= $mesActual ?>;
  window.ANIO_ACTUAL = <?= $anioActual ?>;
</script>

<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $base ?>app/Views/yape/index.js"></script>