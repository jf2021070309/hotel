<?php
/**
 * app/Views/desayunos/index.php
 */
$base = '../../';
$page_title = 'Control de Desayunos — Hotel Manager';
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';
?>

<div class="main-content" id="app-desayunos" v-cloak>
    <div class="topbar">
        <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
        <div>
            <h4><i class="bi bi-egg-fried me-2 text-warning"></i>Gestión de Desayunos</h4>
            <p class="mb-0 text-muted">Planificación diaria según ocupación real</p>
        </div>
        <div class="ms-auto" v-if="tab === 'lista'">
            <button class="btn btn-primary shadow-sm" @click="nuevoRegistro()">
                <i class="bi bi-plus-circle me-1"></i> Generar Desayuno de Hoy
            </button>
        </div>
        <div class="ms-auto" v-else>
            <button class="btn btn-light border shadow-sm me-2" @click="tab = 'lista'">
                <i class="bi bi-arrow-left me-1"></i> Volver a la Lista
            </button>
            <button v-if="!soloLectura" class="btn btn-success shadow-sm" @click="guardar()" :disabled="guardando">
                <i class="bi bi-save me-1"></i> {{ guardando ? 'Guardando...' : 'Guardar Registro' }}
            </button>
        </div>
    </div>

    <div class="page-body">
        <!-- VISTA DE LISTA (HISTORIAL) -->
        <div v-if="tab === 'lista'">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body py-3 d-flex justify-content-between align-items-center bg-light rounded">
                    <div class="d-flex gap-2 align-items-center">
                        <select class="form-select form-select-sm" v-model="filtro.mes" @change="fetchLista()">
                            <option value="1">Enero</option><option value="2">Febrero</option>
                            <option value="3">Marzo</option><option value="4">Abril</option>
                            <option value="5">Mayo</option><option value="6">Junio</option>
                            <option value="7">Julio</option><option value="8">Agosto</option>
                            <option value="9">Septiembre</option><option value="10">Octubre</option>
                            <option value="11">Noviembre</option><option value="12">Diciembre</option>
                        </select>
                        <select class="form-select form-select-sm" v-model="filtro.anio" @change="fetchLista()">
                            <option v-for="a in [2024,2025,2026]" :value="a">{{ a }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="table-responsive bg-white rounded shadow-sm">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">FECHA</th>
                            <th class="text-center">CALCULADO</th>
                            <th class="text-center">AJUSTADO</th>
                            <th class="text-center">TOTAL FINAL</th>
                            <th>OBSERVACIÓN</th>
                            <th class="text-end pe-3">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="lista.length === 0">
                            <td colspan="6" class="text-center py-5 text-muted">No hay registros para este mes.</td>
                        </tr>
                        <tr v-for="r in lista" :key="r.id">
                            <td class="ps-3 fw-bold">{{ formatFecha(r.fecha) }}</td>
                            <td class="text-center text-muted">{{ r.pax_calculado }} pax</td>
                            <td class="text-center">{{ r.pax_ajustado || '---' }}</td>
                            <td class="text-center">
                                <span class="badge bg-warning text-dark fs-6">{{ r.pax_ajustado || r.pax_calculado }} pax</span>
                            </td>
                            <td><small class="text-muted text-truncate d-inline-block" style="max-width:200px">{{ r.observacion }}</small></td>
                            <td class="text-end pe-3">
                                <button class="btn btn-sm btn-outline-primary" @click="verDetalle(r)">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary ms-1" @click="imprimir(r.id)">
                                    <i class="bi bi-printer"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- VISTA DE DETALLE / FORMULARIO -->
        <div v-else>
            <!-- Panel Resumen Superior -->
            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="row text-center align-items-center">
                                <div class="col border-end">
                                    <div class="small text-muted text-uppercase fw-bold">Fecha</div>
                                    <div class="h5 mb-0 fw-bold">{{ formatFecha(actual.fecha) }}</div>
                                </div>
                                <div class="col border-end text-muted">
                                    <div class="small text-uppercase fw-bold">Pax Calculado</div>
                                    <div class="h5 mb-0">{{ actual.pax_calculado }}</div>
                                </div>
                                <div class="col border-end text-primary">
                                    <div class="small text-uppercase fw-bold">Pax Ajustado</div>
                                    <div class="h5 mb-0 fw-bold">{{ totalFinal }}</div>
                                </div>
                                <div class="col text-warning">
                                    <div class="small text-uppercase fw-bold">TOTAL FINAL</div>
                                    <div class="h2 mb-0 fw-bold">{{ totalFinal }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 bg-warning text-dark h-100">
                        <div class="card-body d-flex flex-column justify-content-center">
                            <div class="small text-uppercase fw-bold opacity-75">Nota de Preparación</div>
                            <div class="h6 mb-0 fst-italic">"{{ totalFinal }} personas desayunarán hoy según ocupación."</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Habitaciones -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">DETALLE POR HABITACIÓN</h6>
                    <span class="badge bg-light text-dark border">Habitaciones Ocupadas: {{ actual.detalles.length }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">HAB.</th>
                                    <th>TITULAR</th>
                                    <th class="text-center">PAX</th>
                                    <th class="text-center">¿INCLUYE DESAYUNO?</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="it in actual.detalles">
                                    <td class="ps-3 fw-bold fs-5 text-primary">{{ it.habitacion }}</td>
                                    <td>{{ it.titular }}</td>
                                    <td class="text-center">{{ it.pax }} pax</td>
                                    <td class="text-center">
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input" type="checkbox" v-model="it.incluye_desayuno" :disabled="soloLectura">
                                        </div>
                                        <span class="ms-2 fw-bold" :class="it.incluye_desayuno ? 'text-success' : 'text-danger'">
                                            {{ it.incluye_desayuno ? 'SÍ' : 'NO' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="actual.detalles.length === 0">
                                    <td colspan="4" class="text-center py-4 text-muted fst-italic">Sin huéspedes registrados para esta fecha.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Observaciones -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <label class="form-label fw-bold">Observaciones Generales</label>
                    <textarea class="form-control" rows="2" v-model="actual.observacion" 
                              placeholder="Ej: Solo pan de molde, agregar huevo extra, etc."
                              :disabled="soloLectura"></textarea>
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
