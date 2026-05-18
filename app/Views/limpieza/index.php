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

<style>
    .fw-black {
        font-weight: 900;
    }

    .main-content {
        background-color: #f4f7fa;
        min-height: 100vh;
    }
    /* Evitar que el contenido interno sobresalga del card */
    #app-limpieza .card {
        overflow: hidden;
    }
    #app-limpieza .card .card-body {
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    /* Permitir que badges y líneas se envuelvan sin forzar ancho */
    #app-limpieza .card .d-flex {
        flex-wrap: wrap;
        gap: .5rem;
        align-items: center;
    }
    #app-limpieza .badge {
        white-space: nowrap;
        max-width: 100%;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }
    #app-limpieza .rounded-full {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>

<div class="main-content" id="app-limpieza" v-cloak>
    <div class="topbar border-bottom-0 shadow-sm" style="background: linear-gradient(to right, #ffffff, #f8f9fa);">
        <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list fs-4"></i></button>
        <div>
            <h4 class="fw-bold" style="color: #111; letter-spacing: -0.5px;"><i class="bi bi-stars me-2"
                    style="color: #d4af37;"></i>Panel de Limpieza Diario</h4>
            <p class="mb-0 small text-muted fw-semibold">Gestión de estados y prioridades por habitación</p>
        </div>
        <div class="ms-auto d-flex align-items-center gap-2">
            <button v-if="!yaGenerado" class="btn-primary-custom shadow-sm" @click="generarLista()" :disabled="loading"
                style="border: 1px solid #111;">
                <i class="bi bi-magic me-1 text-warning"></i> Generar Lista de Hoy
            </button>
            <a href="<?= route('limpieza/reporte.php', $base) ?>" class="btn btn-outline-danger shadow-sm">
                <i class="bi bi-clipboard2-check me-1"></i> Reporte / Checklist
            </a>
            <a href="<?= route('reportes/ficha_servicio.php', $base) ?>" class="btn btn-dark shadow-sm">
                <i class="bi bi-printer me-1 text-warning"></i> Ficha de Servicio
            </a>
            <a href="<?= route('limpieza/historial.php', $base) ?>" class="btn btn-outline-secondary shadow-sm">
                <i class="bi bi-clock-history me-1"></i> Ver Historial
            </a>
        </div>
    </div>

    <div class="page-body">

        <!-- FILTROS -->
        <div class="card shadow-sm border-0 mb-4" v-if="lista.length > 0">
            <div class="card-body py-2 d-flex flex-wrap gap-3 align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <label class="small fw-bold text-muted">Estado:</label>
                    <select class="form-select form-select-sm" v-model="filtro.estado">
                        <option value="todos">Todos</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="en proceso">En Proceso</option>
                        <option value="lista">Lista</option>
                        <option value="sucio">Sucio</option>
                        <option value="mantenimiento">Mantenimiento</option>
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
                <div class="ms-auto d-flex align-items-center gap-2">
                    <label class="small fw-bold text-muted">Fecha:</label>
                    <input type="date" v-model="filtroFecha" @change="fetchPorFecha" class="form-control form-control-sm" style="width: 140px;">
                </div>
            </div>
        </div>

        <!-- GRID PRINCIPAL DE LIMPIEZA (Optimizada para móviles y tablets) -->
        <div class="row g-3">
            <div v-if="loading" class="col-12 text-center py-5 bg-white rounded shadow-sm">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
                <p class="mt-3 text-muted fw-bold fs-5">Sincronizando estados...</p>
            </div>

            <div v-if="!loading && listaFiltrada.length === 0"
                class="col-12 text-center py-5 bg-white rounded shadow-sm">
                <i class="bi bi-inbox fs-1 d-block mb-3 opacity-25"></i>
                <h4 class="text-muted">No hay tareas de limpieza para hoy o bajo este filtro.</h4>
            </div>

            <div v-for="h in listaFiltrada" :key="h.id" class="col-12 col-md-6 col-lg-4">
                <div class="card shadow-sm border-0 h-100 position-relative p-4"
                    :class="[getRoomBgTwClass(h.room_estado), {'bg-light opacity-75': h.estado === 'lista'}, getColorTopTwClass(h)]"
                    style="border-radius: 1rem;">


                    <div class="card-body d-flex flex-column">
                        <!-- Cabecera de Tarjeta -->
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <h1 class="fw-black mb-0"
                                    style="font-size: 2rem; letter-spacing: -1px; color: #1e293b; line-height: 1;">{{ h.habitacion }}</h1>
                                <div v-if="h.room_estado" class="rounded-full px-2 py-0.5 text-xs font-bold" :class="getRoomStateTwClass(h.room_estado)" style="display:inline-flex; align-items:center; gap:6px;">
                                    <i v-if="h.room_estado.toLowerCase() === 'mantenimiento'" class="bi bi-tools me-1"></i>
                                    <i v-else-if="h.room_estado.toLowerCase() === 'sucio'" class="bi bi-droplet-half me-1"></i>
                                        <i v-else-if="h.room_estado.toLowerCase() === 'limpieza'" class="bi bi-stars" style="margin-right:4px;"></i>
                                    {{ h.room_estado.toUpperCase() }}
                                </div>
                            </div>
                            <div class="d-flex flex-column gap-1">
                                <span class="badge py-1 px-2 fw-bold" :class="getEstadoClass(h.estado)"
                                    style="font-size: 0.7rem;">
                                    {{ h.estado.toUpperCase() }}
                                </span>
                                <div class="d-flex gap-1">
                                    <span class="badge" :class="[getTipoClass(h.tipo_limpieza), getTipoTwClass(h.tipo_limpieza)]"
                                        style="font-size: 0.65rem; padding: 0.25rem 0.5rem;">
                                        {{ h.tipo_limpieza.toUpperCase() }}
                                    </span>
                                        <span class="badge bg-secondary" style="font-size: 0.75rem;"
                                            title="Ocupantes esperados">
                                            <i class="bi bi-people-fill"></i> {{ (h.pax !== null && h.pax !== undefined) ? h.pax : (h.ocupantes || '?') }} PAX
                                        </span>
                                </div>
                            </div>
                        </div>

                        <!-- Detalles -->
                        <div class="mb-3 flex-grow-1">
                            <div v-if="h.tipo_limpieza === 'salida' && h.estado !== 'lista' && h.estado !== 'limpieza' && h.estado !== 'sucio'"
                                class="small text-danger fw-bold mb-1">
                                <i class="bi bi-exclamation-triangle-fill"></i> Prioridad: Limpieza Profunda
                            </div>
                            <div v-if="h.estado === 'mantenimiento'" class="small text-danger fw-bold mb-1">
                                <i class="bi bi-tools"></i> FUERA DE SERVICIO
                            </div>


                        </div>

                        <!-- Botón 1-Clic -->
                        <!-- Botón Toggle -->
                        <div class="mt-auto border-top pt-3 d-flex gap-2">
                            <button v-if="h.estado !== 'lista' && h.estado !== 'mantenimiento'"
                                class="btn btn-success flex-grow-1 fw-bold fs-5 px-3 py-2 shadow rounded-3"
                                style="line-height: 1.2;" @click="toggleListo(h)"
                                :disabled="loading">
                                <i class="bi bi-check2-circle d-block fs-3 mb-1"></i> MARCAR LISTA
                            </button>
                            <button v-else-if="h.estado === 'lista'"
                                class="btn btn-outline-success flex-grow-1 fw-bold fs-5 px-3 py-2 rounded-3"
                                style="line-height: 1.2;" @click="toggleListo(h)"
                                :disabled="loading">
                                <i class="bi bi-check-all d-block fs-3 mb-1"></i> HABITACIÓN LISTA<br>
                                <small v-if="h.hora_fin && !h.hora_fin.startsWith('0000')" class="text-muted">{{
                                    formatFechaHora(h.hora_fin, h.fecha) }}</small>
                            </button>
                            <div v-else-if="h.estado === 'mantenimiento'"
                                class="w-100 text-center py-2 text-danger fw-bold bg-danger bg-opacity-10 rounded-3 border border-danger border-opacity-25">
                                <i class="bi bi-exclamation-octagon fs-4 d-block mb-1"></i> BLOQUEADA
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include $base . 'includes/footer.php'; ?>
<script src="<?= $base ?>assets/js/limpieza.js?v=<?= time() ?>"></script>
