<?php
/**
 * app/Views/desayunos/index.php
 */
$base = '../../../';
$_projectRoot = dirname(__DIR__, 3);
require_once $_projectRoot . '/app/Middleware/session.php';
require_once $_projectRoot . '/app/Middleware/auth.php';
protegerPorRol('limpieza', 'desayunos');
$page_title = 'Control de Desayunos — Hotel Manager';
$export_enabled = true;
include $_projectRoot . '/app/Views/layouts/head.php';
include $_projectRoot . '/app/Views/layouts/sidebar.php';
?>

<style>
  /* Botones premium con bordes finos y colores sólidos idénticos al resto del sistema */
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

  /* Tabla Estilo Excel Premium */
  .desayuno-grid-container {
    max-height: calc(100vh - 250px);
    overflow-y: auto;
    overflow-x: auto;
  }
  .desayuno-table {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
  }
  .desayuno-table th {
    position: sticky;
    top: 0;
    z-index: 10;
    background: #1e293b !important;
    color: #ffffff !important;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: 1px solid rgba(255,255,255,0.08) !important;
    padding: 8px 12px;
    text-align: center;
  }
  .desayuno-table td {
    padding: 6px 12px;
    vertical-align: middle;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    font-size: 12px;
    color: #334155;
    white-space: nowrap;
  }
  .desayuno-table tbody tr:hover td {
    background: #f8fafc;
  }
  .desayuno-table td.sticky-hab {
    position: sticky;
    left: 0;
    z-index: 8;
    background: #f1f5f9 !important;
    font-weight: 800;
    text-align: center;
    border-right: 2px solid #cbd5e1 !important;
    color: #1e293b;
  }
  .desayuno-table tbody tr:hover td.sticky-hab {
    background: #e2e8f0 !important;
  }
  /* Estilo select premium de desayuno */
  .desayuno-select {
    font-size: 11px;
    font-weight: 700;
    border-radius: 6px;
    width: 90px;
    height: 28px;
    margin: 0 auto;
    cursor: pointer;
    text-align: center;
    transition: all 0.15s ease-in-out;
  }
</style>

<div class="main-content" id="app-desayunos" v-cloak>
    <!-- TOPBAR PREMIUM DARK -->
    <div class="topbar border-bottom-0 shadow-sm d-flex align-items-center" style="background: linear-gradient(to right, #0f172a, #1e293b); color: #fff; padding: 12px 24px;">
        <div class="d-flex align-items-center gap-3">
            <button class="btn-burger text-white border-0 bg-transparent d-lg-none" onclick="openSidebar()"><i class="bi bi-list fs-4"></i></button>
            <div class="d-flex align-items-center gap-2">
                <!-- Icono de desayuno en amarillo/oro degradado -->
                <div class="brand-icon d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #eab308, #ca8a04); width: 34px; height: 34px; border-radius: 8px; color: #fff; box-shadow: 0 4px 10px rgba(234, 179, 8, 0.3);">
                    <i class="bi bi-egg-fried fs-5"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-white" style="letter-spacing: -0.5px; font-size: 1.1rem;">Gestión de Desayunos</h5>
                    <p class="mb-0 d-none d-md-block" style="color: #94a3b8; font-size: 10px;">Planificación diaria de desayunos estilo Excel según ocupación real</p>
                </div>
            </div>
        </div>
    </div>

    <!-- BODY -->
    <div class="page-body pt-3">
        <!-- BARRA DE FILTROS / ACCIONES UNIFICADOS -->
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
                               placeholder="Buscar habitación, titular...">
                    </div>

                    <!-- Selector de fecha -->
                    <div style="width: 140px;">
                        <input type="date" v-model="actual.fecha" @change="verDetallePorFecha"
                               class="form-control form-control-sm shadow-sm text-dark bg-white border-secondary-subtle"
                               style="font-size: 13px; cursor: pointer; height: 31px; border-radius: 6px; font-weight: 500;" />
                    </div>

                    <!-- Botones y badges alineados a la derecha -->
                    <div class="d-flex align-items-center gap-2 ms-md-auto">
                        <!-- Indicador de auto-guardado -->
                        <div class="d-flex align-items-center me-1">
                            <span v-if="guardando" class="badge bg-warning text-dark animate__animated animate__pulse animate__infinite shadow-sm border border-warning" style="font-size: 10px; font-weight: 800; padding: 6px 10px; border-radius: 6px;">
                                <i class="bi bi-cloud-arrow-up-fill me-1"></i>GUARDANDO...
                            </span>
                            <span v-else class="badge bg-success-subtle text-success border border-success opacity-75 shadow-sm" style="font-size: 10px; font-weight: 800; padding: 6px 10px; border-radius: 6px;">
                                <i class="bi bi-cloud-check-fill me-1"></i>SINCRONIZADO
                            </span>
                        </div>

                        <!-- Ficha PDF -->
                        <a :href="'<?= $base ?>app/Views/reportes/ficha_desayunos.php?fecha=' + actual.fecha" target="_blank"
                           class="btn btn-sm btn-custom-blue fw-bold px-3 d-flex align-items-center gap-1 shadow-sm"
                           style="font-size:12px;height:31px;border-radius:6px;text-decoration:none;">
                            <i class="bi bi-printer-fill"></i>Imprimir Ficha
                        </a>

                        <!-- Exportar Excel -->
                        <button class="btn btn-sm btn-custom-green fw-bold px-3 d-flex align-items-center gap-1 shadow-sm"
                                @click="exportarReporte()" :disabled="actual.detalles.length === 0"
                                style="font-size:12px;height:31px;border-radius:6px;">
                            <i class="bi bi-file-earmark-excel"></i>Exportar Excel
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <!-- Loader Global -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
            <h5 class="mt-3 text-muted fw-bold">Cargando control de desayunos...</h5>
        </div>

        <!-- GRID INTERACTIVO ESTILO EXCEL -->
        <div v-else class="card border-0 shadow-sm overflow-hidden" style="border-radius:12px;">
            <!-- Cabecera de totales -->
            <div class="card-header bg-dark text-white py-2 d-flex justify-content-between align-items-center" style="background:#1e293b!important;">
                <span class="fw-bold text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                    <i class="bi bi-grid-3x3 me-1"></i>Detalle de Desayunos por Habitación
                </span>
                <div class="d-flex gap-2">
                    <div class="bg-white/10 text-white px-2 py-1 rounded small border border-white/10 fw-bold shadow-sm" style="font-size: 11px;">
                         TOTAL PAX EN HOTEL: {{ totalHuespedes }}
                    </div>
                    <div class="bg-warning text-dark px-2 py-1 rounded small border border-warning/10 fw-bold shadow-sm" style="font-size: 11px;">
                         DESAYUNOS REQUERIDOS: {{ totalFinal }}
                    </div>
                </div>
            </div>

            <!-- Tabla de datos -->
            <div class="desayuno-grid-container">
                <table class="table mb-0 desayuno-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">N° HAB.</th>
                            <th>HUESPED TITULAR</th>
                            <th style="width: 100px;">CANT. PAX</th>
                            <th style="width: 160px;">INCLUYE DESAYUNO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="it in detallesFiltrados" :key="it.habitacion_id">
                            <!-- N° Habitación (Sticky) -->
                            <td class="sticky-hab">{{ it.habitacion }}</td>
                            
                            <!-- Titular -->
                            <td class="fw-semibold text-uppercase text-dark">{{ it.titular || '---' }}</td>
                            
                            <!-- Pax -->
                            <td class="text-center fw-bold">{{ it.pax }}</td>
                            
                            <!-- Switch editable como Select visual plano -->
                            <td>
                                <select v-model="it.incluye_desayuno" :disabled="soloLectura" @change="autoGuardar"
                                        class="form-select form-select-sm desayuno-select"
                                        :style="{
                                            backgroundColor: it.incluye_desayuno ? '#d1fae5' : '#fee2e2',
                                            color: it.incluye_desayuno ? '#065f46' : '#991b1b',
                                            border: it.incluye_desayuno ? '1px solid #a7f3d0' : '1px solid #fca5a5'
                                        }">
                                    <option :value="true">SÍ</option>
                                    <option :value="false">NO</option>
                                </select>
                            </td>
                        </tr>
                        
                        <!-- Sin registros -->
                        <tr v-if="detallesFiltrados.length === 0">
                            <td colspan="4" class="text-center py-5 text-muted fst-italic">
                                <i class="bi bi-info-circle me-2"></i>No se encontraron huéspedes registrados para esta fecha o búsqueda.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- NOTAS GENERALES -->
        <div v-if="!loading" class="card border-0 shadow-sm mt-3" style="border-radius:12px;">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-3">
                <label class="form-label fw-bold mb-0 text-uppercase text-muted" style="white-space: nowrap; font-size:11px; letter-spacing:0.5px;">
                    <i class="bi bi-pencil-square me-1"></i>Notas del Día:
                </label>
                <input type="text" class="form-control form-control-sm bg-white text-dark border-secondary-subtle" 
                       v-model="actual.observacion" 
                       placeholder="Indicaciones adicionales del desayuno..."
                       :disabled="soloLectura" @input="triggerAutoGuardarDebounced"
                       style="font-size:13px; border-radius:6px; height: 31px;">
            </div>
        </div>

        <!-- Alerta Solo Lectura -->
        <div v-if="!loading && soloLectura" class="alert alert-info border-0 shadow-sm d-flex align-items-center mt-3" style="border-radius:12px;">
            <i class="bi bi-info-circle-fill fs-4 me-3"></i>
            <div>
                <strong>Registro Finalizado:</strong> Este registro es histórico o ha superado el límite de las 12:00 PM para edición.
            </div>
        </div>

    </div>
</div>

<!-- SERVER DATA: URLs absolutas para el JS -->
<script>
  window.SERVER_DATA = {
    apiBase: <?= json_encode(project_base_url() . 'ajax/desayunos.php') ?>,
    hoy:     <?= json_encode(date('Y-m-d')) ?>
  };
</script>

<!-- LIBS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vue@3.3.4/dist/vue.global.prod.js"></script>
<script src="<?= $base ?>assets/js/desayunos.js?v=<?= time() ?>"></script>
<?php include $_projectRoot . '/app/Views/layouts/footer.php'; ?>
