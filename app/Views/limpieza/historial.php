<?php
/**
 * app/Views/limpieza/historial.php
 */
$base = '../../../';
require_once $base . 'auth/session.php';
$page_title = 'Historial de Limpieza — Hotel Manager';
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';
?>

<div class="main-content" id="app-limpieza-historial" v-cloak>
    <div class="topbar">
        <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
        <div>
            <h4><i class="bi bi-calendar3 me-2 text-primary"></i>Historial de Limpieza</h4>
            <p class="mb-0 text-muted">Consulta de registros de días anteriores</p>
        </div>
        <div class="ms-auto">
            <a href="app/Views/limpieza/index.php" class="btn btn-light border shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver al Panel
            </a>
        </div>
    </div>

    <div class="page-body">
        
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body py-3 d-flex gap-3 align-items-center">
                <select class="form-select form-select-sm" style="width:150px" v-model="filtro.mes" @change="fetchHistorial()">
                    <option value="1">Enero</option><option value="2">Febrero</option>
                    <option value="3">Marzo</option><option value="4">Abril</option>
                    <option value="5">Mayo</option><option value="6">Junio</option>
                    <option value="7">Julio</option><option value="8">Agosto</option>
                    <option value="9">Septiembre</option><option value="10">Octubre</option>
                    <option value="11">Noviembre</option><option value="12">Diciembre</option>
                </select>
                <select class="form-select form-select-sm" style="width:120px" v-model="filtro.anio" @change="fetchHistorial()">
                    <option value="2024">2024</option><option value="2025">2025</option><option value="2026">2026</option>
                </select>
            </div>
        </div>

        <div class="table-responsive bg-white rounded shadow-sm">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">FECHA</th>
                        <th class="text-center">TOTAL HABITACIONES</th>
                        <th class="text-center">COMPLETADAS</th>
                        <th class="text-center">PENDIENTES</th>
                        <th class="text-center">EFECTIVIDAD</th>
                        <th class="text-end pe-3">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading" class="text-center"><td colspan="6" class="py-5"><div class="spinner-border text-primary"></div></td></tr>
                    <tr v-if="!loading && lista.length === 0" class="text-center"><td colspan="6" class="py-5 text-muted">No se encontraron registros en este periodo.</td></tr>
                    <tr v-for="r in lista">
                        <td class="ps-3 fw-bold">{{ formatFecha(r.fecha) }}</td>
                        <td class="text-center">{{ r.total }}</td>
                        <td class="text-center text-success fw-bold">{{ r.completadas }}</td>
                        <td class="text-center text-danger">{{ r.pendientes }}</td>
                        <td class="text-center">
                             <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success" :style="{width: (r.completadas * 100 / r.total) + '%'}"></div>
                             </div>
                             <small class="fw-bold">{{ Math.round(r.completadas * 100 / r.total) }}%</small>
                        </td>
                        <td class="text-end pe-3">
                            <button class="btn btn-sm btn-outline-primary" @click="verDetalle(r.fecha)">
                                <i class="bi bi-eye me-1"></i> Ver Detalle
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

    <!-- MODAL DETALLE DIA -->
    <div class="modal fade" id="modalDetalle" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Limpieza del {{ formatFecha(fechaDetalle) }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">HAB</th>
                                <th>TIPO</th>
                                <th>ESTADO</th>
                                <th>RESPONSABLE</th>
                                <th>FIN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="d in detalleDia">
                                <td class="ps-3 fw-bold">{{ d.habitacion }}</td>
                                <td>{{ d.tipo_limpieza }}</td>
                                <td>{{ d.estado }}</td>
                                <td>{{ d.responsable || '---' }}</td>
                                <td>{{ d.hora_fin ? d.hora_fin.substring(0,5) : '---' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= $base ?>assets/js/limpieza.js"></script>
<?php include $base . 'includes/footer.php'; ?>
