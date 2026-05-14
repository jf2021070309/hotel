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

    .card-hover:hover {
        transform: translateY(-5px);
        transition: all 0.3s ease;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
    }

    .main-content {
        background-color: #f4f7fa;
        min-height: 100vh;
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

        <!-- RESUMEN SUPERIOR - DISEÑO PREMIUM & ORDENADO -->
        <div class="row g-3 mb-4" v-if="lista.length > 0">
            <!-- SALIDAS -->
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow card-hover position-relative overflow-hidden" 
                    style="border-radius: 1.5rem; background: linear-gradient(135deg, #f87171 0%, #dc2626 100%); color: white; min-height: 130px;">
                    <div class="card-body p-4 d-flex flex-column justify-content-center">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white fw-bold text-uppercase mb-1" style="font-size: 0.75rem; letter-spacing: 1.5px; opacity: 0.9;">Salidas Hoy</h6>
                                <div class="d-flex align-items-baseline gap-1">
                                    <h2 class="fw-black mb-0 text-white" style="font-size: 2.3rem;">{{ stats.salida }}</h2>
                                    <span class="text-white small fw-bold opacity-75">habs</span>
                                </div>
                            </div>
                            <div class="rounded-4 d-flex align-items-center justify-content-center shadow-lg" 
                                style="width: 58px; height: 58px; background: rgba(0,0,0,0.15); border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(4px);">
                                <i class="bi bi-door-open-fill text-white fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ESTADÍAS -->
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow card-hover position-relative overflow-hidden" 
                    style="border-radius: 1.5rem; background: linear-gradient(135deg, #fbbf24 0%, #d97706 100%); color: white; min-height: 130px;">
                    <div class="card-body p-4 d-flex flex-column justify-content-center">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white fw-bold text-uppercase mb-1" style="font-size: 0.75rem; letter-spacing: 1.5px; opacity: 0.9;">Repaso / Stay</h6>
                                <div class="d-flex align-items-baseline gap-1">
                                    <h2 class="fw-black mb-0 text-white" style="font-size: 2.3rem;">{{ stats.estadia }}</h2>
                                    <span class="text-white small fw-bold opacity-75">habs</span>
                                </div>
                            </div>
                            <div class="rounded-4 d-flex align-items-center justify-content-center shadow-lg" 
                                style="width: 58px; height: 58px; background: rgba(0,0,0,0.15); border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(4px);">
                                <i class="bi bi-person-walking text-white fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PROGRAMADAS -->
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow card-hover position-relative overflow-hidden" 
                    style="border-radius: 1.5rem; background: linear-gradient(135deg, #22d3ee 0%, #0891b2 100%); color: white; min-height: 130px;">
                    <div class="card-body p-4 d-flex flex-column justify-content-center">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white fw-bold text-uppercase mb-1" style="font-size: 0.75rem; letter-spacing: 1.5px; opacity: 0.9;">Reservas Act.</h6>
                                <div class="d-flex align-items-baseline gap-1">
                                    <h2 class="fw-black mb-0 text-white" style="font-size: 2.3rem;">{{ stats.programada }}</h2>
                                    <span class="text-white small fw-bold opacity-75">habs</span>
                                </div>
                            </div>
                            <div class="rounded-4 d-flex align-items-center justify-content-center shadow-lg" 
                                style="width: 58px; height: 58px; background: rgba(0,0,0,0.15); border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(4px);">
                                <i class="bi bi-calendar-check-fill text-white fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TOTAL -->
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow card-hover position-relative overflow-hidden" 
                    style="border-radius: 1.5rem; background: linear-gradient(135deg, #444 0%, #111 100%); color: white; min-height: 130px;">
                    <div class="card-body p-4 d-flex flex-column justify-content-center">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-warning fw-bold text-uppercase mb-1" style="font-size: 0.75rem; letter-spacing: 1.5px; color: #fbbf24 !important;">Carga Total</h6>
                                <div class="d-flex align-items-baseline gap-1">
                                    <h2 class="fw-black mb-0 text-warning" style="font-size: 2.3rem;">{{ lista.length }}</h2>
                                    <span class="text-white small fw-bold opacity-75">trabajos</span>
                                </div>
                            </div>
                            <div class="rounded-4 bg-warning d-flex align-items-center justify-content-center shadow-lg" style="width: 58px; height: 58px; border: 1px solid rgba(255,255,255,0.1);">
                                <i class="bi bi-stars text-dark fs-2"></i>
                            </div>
                        </div>
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
                        <option value="en proceso">En Proceso</option>
                        <option value="lista">Lista</option>
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
                <div class="ms-auto">
                    <span class="badge bg-light text-dark border px-3 py-2">
                        <i class="bi bi-calendar3 me-1"></i>
                        <?= date('d/m/Y') ?>
                    </span>
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
                <div class="card shadow-sm border-0 h-100 position-relative"
                    :class="{'bg-light opacity-75': h.estado === 'lista'}"
                    style="border-radius: 1rem; border-top: 5px solid transparent !important;"
                    :style="'border-top-color: ' + getColorTop(h) + ' !important;'">


                    <div class="card-body d-flex flex-column">
                        <!-- Cabecera de Tarjeta -->
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div>
                                <h1 class="fw-black mb-0"
                                    style="font-size: 3rem; letter-spacing: -2px; color: #1e293b; line-height: 1;">{{
                                    h.habitacion }}</h1>
                            </div>
                            <div class="d-flex flex-column gap-1">
                                <span class="badge py-2 px-3 fw-bold shadow-sm" :class="getEstadoClass(h.estado)"
                                    style="font-size: 0.85rem;">
                                    {{ h.estado.toUpperCase() }}
                                </span>
                                <div class="d-flex gap-1">
                                    <span class="badge" :class="getTipoClass(h.tipo_limpieza)"
                                        style="font-size: 0.75rem;">
                                        {{ h.tipo_limpieza.toUpperCase() }}
                                    </span>
                                    <span class="badge bg-secondary" style="font-size: 0.75rem;"
                                        title="Ocupantes esperados">
                                        <i class="bi bi-people-fill"></i> {{ h.pax || h.ocupantes || '?' }} PAX
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Detalles -->
                        <div class="mb-3 flex-grow-1">
                            <div v-if="h.tipo_limpieza === 'salida' && h.estado !== 'lista'"
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