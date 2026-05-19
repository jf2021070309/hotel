<?php
/**
 * app/Views/desayunos/index.php
 */
$base = '../../../';
require_once $base . 'auth/session.php';
require_once $base . 'auth/middleware.php';
protegerPorRol('limpieza', 'desayunos');
$page_title = 'Control de Desayunos — Hotel Manager';
$export_enabled = true;
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';
?>

<div class="main-content" id="app-desayunos" v-cloak>
    <div class="topbar border-bottom-0 shadow-sm" style="background: linear-gradient(to right, #ffffff, #f8f9fa);">
        <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list fs-4"></i></button>
        <div>
            <h4 class="fw-bold" style="color: #111; letter-spacing: -0.5px;"><i class="bi bi-egg-fried me-2" style="color: #d4af37;"></i>Gestión de Desayunos</h4>
            <p class="mb-0 small text-muted fw-semibold">Planificación diaria según ocupación real</p>
        </div>

        <!-- Controles de la Vista de Detalle -->
        <div class="ms-auto d-flex align-items-center gap-3" v-if="tab !== 'lista'">
            <!-- Grupo Izquierdo: Navegación de Fecha -->
            <div class="d-flex align-items-center gap-2 border-end pe-3">
                <div class="d-flex align-items-center bg-white border rounded px-2 shadow-sm" style="height: 31px;">
                    <i class="bi bi-calendar-event text-muted me-1 small"></i>
                    <input type="date" class="form-control form-control-sm border-0 bg-transparent p-0" 
                           v-model="actual.fecha" @change="verDetallePorFecha"
                           style="width: 125px; font-weight: bold; font-size: 13px;">
                </div>
            </div>

            <!-- Grupo Derecho: Sincronización y Salida -->
            <div class="d-flex align-items-center gap-2">
                <!-- Indicador de auto-guardado -->
                <div class="d-flex align-items-center me-1">
                    <span v-if="guardando" class="badge bg-warning text-dark animate__animated animate__pulse animate__infinite shadow-sm border border-warning" style="font-size: 9px; font-weight: 800;">
                        <i class="bi bi-cloud-arrow-up-fill me-1"></i> GUARDANDO...
                    </span>
                    <span v-else class="badge bg-success-subtle text-success border border-success opacity-75 shadow-sm" style="font-size: 9px; font-weight: 800;">
                        <i class="bi bi-cloud-check-fill me-1"></i> SINCRONIZADO
                    </span>
                </div>

                <button class="btn btn-success btn-sm shadow-sm fw-bold px-3 border border-dark" 
                        @click="exportarReporte()" :disabled="actual.detalles.length === 0" style="height: 31px;">
                    <i class="bi bi-file-earmark-excel-fill me-1"></i> EXCEL
                </button>

                <a href="<?= route('reportes/ficha_servicio.php', $base) ?>" class="btn btn-dark btn-sm shadow-sm fw-bold px-3 border border-warning" style="height: 31px;">
                    <i class="bi bi-printer-fill me-1 text-warning"></i> FICHA
                </a>

                <button class="btn btn-primary btn-sm border-dark shadow-sm fw-bold px-3" @click="volverALista()" style="height: 31px;">
                    <i class="bi bi-view-list me-1"></i> VER LISTA
                </button>
            </div>
        </div>
    </div>

    <div class="page-body">
        <!-- Loader Global -->
        <div v-if="loading" class="text-center py-5 mt-5">
            <div class="spinner-border text-warning" style="width: 3rem; height: 3rem;" role="status"></div>
            <h5 class="mt-3 text-muted fw-bold">Sincronizando Padrón de Desayunos...</h5>
        </div>

        <!-- VISTA DE LISTA (HISTORIAL) -->
        <div v-else-if="tab === 'lista'">
            <!-- Filtros de Historial (Estilo Panel Pro) -->
            <div class="card shadow-sm border-0 mb-4 bg-white" style="border-radius: 12px; border: 1px solid #eee !important;">
                <div class="card-body py-3 px-4 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-dark text-white p-2 rounded-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi bi-calendar3 fs-5 text-warning"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold text-dark" style="letter-spacing: -0.3px;">Historial de Reportes</h6>
                            <p class="mb-0 small text-muted">Seleccione el período de consulta</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2 bg-light p-2 rounded-pill border shadow-sm">
                        <span class="ps-2 text-uppercase fw-bold text-muted" style="font-size: 10px;">MES:</span>
                        <select class="form-select form-select-sm border-0 bg-transparent fw-bold text-primary pe-4" v-model="filtro.mes" @change="fetchLista()" style="width: 140px; cursor: pointer;">
                            <option value="1">Enero</option><option value="2">Febrero</option>
                            <option value="3">Marzo</option><option value="4">Abril</option>
                            <option value="5">Mayo</option><option value="6">Junio</option>
                            <option value="7">Julio</option><option value="8">Agosto</option>
                            <option value="9">Septiembre</option><option value="10">Octubre</option>
                            <option value="11">Noviembre</option><option value="12">Diciembre</option>
                        </select>
                        <span class="text-muted opacity-50">|</span>
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 10px;">AÑO:</span>
                        <select class="form-select form-select-sm border-0 bg-transparent fw-bold text-dark pe-4" v-model="filtro.anio" @change="fetchLista()" style="width: 100px; cursor: pointer;">
                            <option v-for="a in [2024,2025,2026]" :value="a">{{ a }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Tabla Simplificada Estilo Screenshot -->
            <div class="table-responsive bg-white rounded-3 shadow-sm border overflow-hidden">
                <table class="table table-bordered align-middle mb-0 text-nowrap" style="font-size: 13.5px;">
                    <thead class="bg-light">
                        <tr class="text-dark">
                            <th class="ps-4 py-3 text-uppercase small fw-bold" style="background-color: #f8f9fa; letter-spacing: 0.5px;">FECHA</th>
                            <th class="text-center py-3 text-uppercase small fw-bold" style="background-color: #f8f9fa; letter-spacing: 0.5px;">TOTAL FINAL</th>
                            <th class="py-3 text-uppercase small fw-bold" style="background-color: #f8f9fa; letter-spacing: 0.5px;">OBSERVACIÓN</th>
                            <th class="text-center py-3 text-uppercase small fw-bold" style="background-color: #f8f9fa; width: 100px; letter-spacing: 0.5px;">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="lista.length === 0">
                            <td colspan="4" class="text-center py-5 text-muted fst-italic">No hay registros para este período.</td>
                        </tr>
                        <tr v-for="r in lista" :key="r.id">
                            <td class="ps-4 fw-bold text-primary">
                                <i class="bi bi-calendar-check me-2 opacity-50"></i>{{ formatFecha(r.fecha) }}
                            </td>
                            <td class="text-center">
                                <span class="badge border border-dark rounded-1 px-3 py-2" 
                                      style="background-color: #ffc107; color: #000; font-size: 11px; font-weight: 800; letter-spacing: 0.5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                    {{ r.pax_ajustado || r.pax_calculado }} PAX
                                </span>
                            </td>
                            <td>
                                <div class="text-muted text-truncate ps-2" style="max-width:400px; font-size: 12.5px;">
                                    {{ r.observacion || '---' }}
                                </div>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-dark shadow-sm px-3 py-1 border border-dark transform-scale-hover" @click="verDetalle(r)" style="background: #212529;">
                                    <i class="bi bi-search text-warning" style="font-size: 12px;"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- VISTA DE DETALLE / FORMULARIO -->
        <div v-else>
            <!-- Tabla de Habitaciones Estilo Excel -->
            <div class="card shadow-sm border-0 mb-4 overflow-hidden">
                <div class="card-header bg-dark text-white py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold small text-uppercase">
                        <i class="bi bi-grid-3x3 me-2 text-warning"></i>Detalle de Desayunos por Habitación
                    </h6>
                    <div class="d-flex gap-2">
                        <div class="bg-white text-dark px-2 py-1 rounded small border fw-bold shadow-sm" style="font-size: 11px;">
                             TOTAL HUÉSPEDES: {{ totalHuespedes }}
                        </div>
                        <div class="bg-warning text-dark px-2 py-1 rounded small border border-dark fw-bold shadow-sm" style="font-size: 11px;">
                             TOTAL DESAYUNOS: {{ totalFinal }}
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover align-middle mb-0 text-nowrap" style="font-size: 13px; border: 1px solid #dee2e6;">
                            <thead class="table-light">
                                <tr class="text-secondary">
                                    <th class="text-center py-2 bg-light" style="width: 80px; border-bottom: 2px solid #dee2e6;">N° HAB.</th>
                                    <th class="py-2 bg-light" style="border-bottom: 2px solid #dee2e6;">HUESPED TITULAR</th>
                                    <th class="text-center py-2 bg-light" style="width: 100px; border-bottom: 2px solid #dee2e6;">CANT. PAX</th>
                                    <th class="text-center py-2 bg-light" style="width: 180px; border-bottom: 2px solid #dee2e6;">ESTADO DESAYUNO</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="it in actual.detalles" :key="it.habitacion_id">
                                    <td class="text-center fw-bold text-primary bg-light" style="border-right: 2px solid #dee2e6;">
                                        {{ it.habitacion }}
                                    </td>
                                    <td class="fw-medium px-3">{{ it.titular }}</td>
                                    <td class="text-center fw-bold">{{ it.pax }}</td>
                                    <td class="text-center py-1">
                                        <div class="d-inline-flex align-items-center gap-2">
                                            <div class="form-check form-switch m-0 p-0" style="min-height: 20px;">
                                                <input class="form-check-input ms-0" type="checkbox" 
                                                       style="width: 36px; height: 18px; cursor: pointer;"
                                                       v-model="it.incluye_desayuno" :disabled="soloLectura"
                                                       @change="autoGuardar">
                                            </div>
                                            <span class="badge" style="width: 40px; font-size: 10px;"
                                                  :class="it.incluye_desayuno ? 'bg-success-subtle text-success border border-success' : 'bg-danger-subtle text-danger border border-danger'">
                                                {{ it.incluye_desayuno ? 'SÍ' : 'NO' }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="actual.detalles.length === 0">
                                    <td colspan="4" class="text-center py-5 text-muted fst-italic bg-white">
                                        <i class="bi bi-info-circle me-2"></i>No se encontraron huéspedes registrados para esta fecha.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4 bg-light">
                <div class="card-body py-2 px-3 d-flex align-items-center gap-3">
                    <label class="form-label fw-bold mb-0 small text-uppercase" style="white-space: nowrap;">Notas:</label>
                    <input type="text" class="form-control form-control-sm bg-white border" 
                           v-model="actual.observacion" 
                           placeholder="Indicaciones adicionales..."
                           :disabled="soloLectura" @input="triggerAutoGuardarDebounced">
                </div>
            </div>

            <div v-if="soloLectura" class="alert alert-info border-0 shadow-sm d-flex align-items-center">
                <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                <div>
                    <strong>Registro Finalizado:</strong> Este registro es histórico o ha superado el límite de las 12:00 PM para edición.
                </div>
            </div>

        </div>
    </div>
</div>

<script src="<?= $base ?>assets/js/desayunos.js"></script>
<?php include $base . 'includes/footer.php'; ?>
