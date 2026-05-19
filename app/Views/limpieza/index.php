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

    .btn-action-custom {
        border-radius: 50rem;
        padding: 0.4rem 1.1rem;
        font-weight: 700;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        transition: all 0.2s ease-in-out;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        text-decoration: none;
    }
    .btn-action-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .btn-action-warning {
        background-color: #fff8e1;
        color: #b78103;
        border: 1px solid #ffe082;
    }
    .btn-action-warning:hover {
        background-color: #ffecb3;
        color: #b78103;
    }
    .btn-action-dark {
        background: linear-gradient(135deg, #212529, #343a40);
        color: #fff;
        border: none;
    }
    .btn-action-dark:hover {
        background: linear-gradient(135deg, #111, #212529);
        color: #fff;
    }
    .btn-action-light {
        background-color: #fff;
        color: #495057;
        border: 1px solid #dee2e6;
    }
    .btn-action-light:hover {
        background-color: #f8f9fa;
        color: #212529;
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
            <button v-if="!yaGenerado" class="btn btn-action-custom btn-action-dark" @click="generarLista()" :disabled="loading">
                <i class="bi bi-magic text-warning"></i> Generar Lista
            </button>
            <button class="btn btn-action-custom btn-action-warning" @click="resetNocturno()" :disabled="loading"
                title="Marca todas las habitaciones ocupadas como SUCIAS (limpieza de noche)">
                <i class="bi bi-moon-stars-fill text-warning"></i> Reset Nocturno
            </button>
            <a href="<?= route('reportes/ficha_servicio.php', $base) ?>" class="btn btn-action-custom btn-action-dark">
                <i class="bi bi-printer-fill text-warning"></i> Ficha de Servicio
            </a>
            <a href="<?= route('limpieza/historial.php', $base) ?>" class="btn btn-action-custom btn-action-light">
                <i class="bi bi-clock-history text-muted"></i> Historial
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
                        <option value="reposo">Reposo</option>
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
                                <div class="d-flex flex-wrap gap-1">
                                    <span class="badge" :class="getTipoClass(h.tipo_limpieza)"
                                        style="font-size: 0.75rem;">
                                        {{ h.tipo_limpieza.toUpperCase() }}
                                    </span>
                                        <span class="badge bg-secondary" style="font-size: 0.75rem;"
                                            title="Ocupantes esperados">
                                            <i class="bi bi-people-fill"></i> {{ (h.pax !== null && h.pax !== undefined) ? h.pax : (h.ocupantes || '?') }} PAX
                                        </span>
                                        <span v-if="h.room_estado" class="badge" :class="getRoomStateClass(h.room_estado)" style="font-size:0.75rem;">
                                            {{ h.room_estado.toUpperCase() }}
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
