<?php
/**
 * app/Views/limpieza/index.php
 */
$base = '../../../';
require_once $base . 'auth/session.php';
$page_title = 'Panel de Limpieza — Hotel Manager';
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';
?>

<div class="main-content" id="app-limpieza" v-cloak>
    <div class="topbar border-bottom-0 shadow-sm" style="background: linear-gradient(to right, #ffffff, #f8f9fa);">
        <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list fs-4"></i></button>
        <div>
            <h4 class="fw-bold" style="color: #111; letter-spacing: -0.5px;"><i class="bi bi-stars me-2" style="color: #d4af37;"></i>Panel de Limpieza Diario</h4>
            <p class="mb-0 small text-muted fw-semibold">Gestión de estados y prioridades por habitación</p>
        </div>
        <div class="ms-auto d-flex align-items-center gap-2">
            <button v-if="!yaGenerado" class="btn-primary-custom shadow-sm" @click="generarLista()" :disabled="loading" style="border: 1px solid #111;">
                <i class="bi bi-magic me-1 text-warning"></i> Generar Lista de Hoy
            </button>
            <a href="<?= route('limpieza/reporte.php', $base) ?>" class="btn btn-outline-danger shadow-sm">
                <i class="bi bi-clipboard2-check me-1"></i> Reporte / Checklist
            </a>
            <a href="<?= route('limpieza/historial.php', $base) ?>" class="btn btn-outline-secondary shadow-sm">
                <i class="bi bi-clock-history me-1"></i> Ver Historial
            </a>
        </div>
    </div>

    <div class="page-body">
        
        <!-- RESUMEN SUPERIOR -->
        <div class="row g-3 mb-4" v-if="lista.length > 0">
            <div class="col-md-3">
                <div class="card shadow-sm border-0 border-start border-danger border-5">
                    <div class="card-body">
                        <div class="small fw-bold text-danger text-uppercase">Salida (Prioridad Alta)</div>
                        <div class="h3 mb-0 fw-bold">{{ stats.salida }} <span class="fs-6 text-muted">hab</span></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 border-start border-warning border-5">
                    <div class="card-body">
                        <div class="small fw-bold text-warning text-uppercase">Estadía (Rutinaria)</div>
                        <div class="h3 mb-0 fw-bold">{{ stats.estadia }} <span class="fs-6 text-muted">hab</span></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 border-start border-info border-5">
                    <div class="card-body">
                        <div class="small fw-bold text-info text-uppercase">Programada (Libre)</div>
                        <div class="h3 mb-0 fw-bold">{{ stats.programada }} <span class="fs-6 text-muted">hab</span></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 bg-dark text-white">
                    <div class="card-body">
                        <div class="small fw-bold text-uppercase opacity-75">Total Hoy</div>
                        <div class="h3 mb-0 fw-bold text-warning">{{ lista.length }} <span class="fs-6 text-muted">tareas</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="card shadow-sm border-0 mb-4" v-if="lista.length > 0">
            <div class="card-body py-2 d-flex flex-wrap gap-3 align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <label class="small fw-bold text-muted">Estado:</label>
                    <select class="form-select form-select-sm" v-model="filtro.estado">
                        <option value="todos">Todos</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="en_proceso">En Proceso</option>
                        <option value="lista">Lista</option>
                    </select>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label class="small fw-bold text-muted">Tipo:</label>
                    <select class="form-select form-select-sm" v-model="filtro.tipo">
                        <option value="todos">Todos</option>
                        <option value="salida">Salida</option>
                        <option value="estadía">Estadía</option>
                        <option value="programada">Programada</option>
                    </select>
                </div>
                <div class="ms-auto">
                    <span class="badge bg-light text-dark border px-3 py-2">
                        <i class="bi bi-calendar3 me-1"></i> <?= date('d/m/Y') ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- TABLA PRINCIPAL -->
        <div class="bg-white rounded shadow-sm overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 80px;">HAB.</th>
                            <th>TIPO</th>
                            <th>RESUMEN</th>
                            <th class="text-center">ESTADO</th>
                            <th>RESPONSABLE</th>
                            <th class="text-center">HORAS</th>
                            <th class="text-end pe-3">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading" class="text-center">
                            <td colspan="7" class="py-5">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2 text-muted">Sincronizando estados...</p>
                            </td>
                        </tr>
                        <tr v-if="!loading && listaFiltrada.length === 0" class="text-center">
                            <td colspan="7" class="py-5 text-muted fst-italic">
                                <i class="bi bi-inbox fs-2 d-block mb-3 opacity-25"></i>
                                No hay tareas de limpieza registradas para hoy o con este filtro.
                            </td>
                        </tr>
                        <tr v-for="h in listaFiltrada" :key="h.id" :class="{'table-success': h.estado === 'lista'}">
                            <td class="ps-3 fw-bold fs-5 text-primary">{{ h.habitacion }}</td>
                            <td>
                                <span class="badge" :class="getTipoClass(h.tipo_limpieza)">
                                    {{ h.tipo_limpieza.toUpperCase() }}
                                </span>
                                <div v-if="h.prioridad === 'alta'" class="mt-1 small text-danger fw-bold">
                                    <i class="bi bi-caret-up-fill"></i> PRIORIDAD ALTA
                                </div>
                            </td>
                            <td>
                                <div v-if="h.tipo_limpieza === 'salida'" class="small">
                                    <i class="bi bi-door-closed text-danger me-1"></i> Salida de Huésped
                                </div>
                                <div v-else-if="h.tipo_limpieza === 'estadía'" class="small">
                                    <i class="bi bi-person-check text-warning me-1"></i> Ocupada (Rutinario)
                                </div>
                                <div v-else class="small">
                                    <i class="bi bi-calendar-event text-info me-1"></i> Libre (Programada)
                                </div>
                                <div class="mt-1 text-muted small fst-italic" v-if="h.observacion">
                                    <i class="bi bi-chat-left-dots me-1"></i> {{ h.observacion }}
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill px-3 py-2 fs-6" :class="getEstadoClass(h.estado)">
                                    {{ h.estado === 'pendiente' ? '⏳ PENDIENTE' : (h.estado === 'en_proceso' ? '🧹 EN PROCESO' : '✅ LISTA') }}
                                </span>
                            </td>
                            <td>
                                <div v-if="h.responsable" class="fw-bold small">{{ h.responsable }}</div>
                                <button v-else class="btn btn-sm btn-light border text-muted py-0 px-2" @click="asignarResponsable(h)">
                                    Sin asignar
                                </button>
                            </td>
                            <td class="text-center small">
                                <div v-if="fmtHora(h.hora_inicio)">
                                    <span class="text-muted">Inicio:</span> <b>{{ fmtHora(h.hora_inicio) }}</b>
                                </div>
                                <div v-if="fmtHora(h.hora_fin)">
                                    <span class="text-muted">Fin:</span> <b class="text-success">{{ fmtHora(h.hora_fin) }}</b>
                                </div>
                                <span v-if="!fmtHora(h.hora_inicio)" class="text-muted mini">—</span>
                            </td>
                            <td class="text-end pe-3">
                                <div class="btn-group shadow-sm">
                                    <button v-if="h.estado === 'pendiente'" class="btn btn-sm btn-warning" @click="cambiarEstado(h, 'en_proceso')">
                                        INICIAR
                                    </button>
                                    <button v-if="h.estado === 'en_proceso'" class="btn btn-sm btn-success" @click="cambiarEstado(h, 'lista')">
                                        FINALIZAR
                                    </button>
                                    <button class="btn btn-sm btn-light border" @click="mostrarMenu(h)" title="Más opciones">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php include $base . 'includes/footer.php'; ?>
<script src="<?= $base ?>assets/js/limpieza.js?v=<?= time() ?>"></script>
