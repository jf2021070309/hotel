<?php
/**
 * app/Views/limpieza/historial.php
 */
$base = '../../../';
$_projectRoot = defined('BASE_PATH') ? BASE_PATH : (rtrim(realpath(dirname(__DIR__, 3)), '\\/') . DIRECTORY_SEPARATOR);
require_once $_projectRoot . 'app/Middleware/session.php';
require_once $_projectRoot . 'app/Middleware/auth.php';
protegerPorRol('limpieza', 'limpieza');
$page_title = 'Historial de Limpieza — Hotel Manager';
include $_projectRoot . 'app/Views/layouts/head.php';
include $_projectRoot . 'app/Views/layouts/sidebar.php';
?>

<div class="main-content" id="app-limpieza-historial" v-cloak>
    <div class="topbar">
        <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
        <div>
            <h4><i class="bi bi-calendar3 me-2 text-primary"></i>Historial de Limpieza</h4>
            <p class="mb-0 text-muted">Consulta de registros de días anteriores</p>
        </div>
        <div class="ms-auto">
            <a href="index.php" class="btn btn-light border shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver al Panel
            </a>
        </div>
    </div>

    <div class="page-body">
        
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body py-3 d-flex gap-3 align-items-center">
                <select class="form-select form-select-sm" style="width:150px" v-model="filtroHist.mes" @change="fetchHistorial()">
                    <option value="1">Enero</option><option value="2">Febrero</option>
                    <option value="3">Marzo</option><option value="4">Abril</option>
                    <option value="5">Mayo</option><option value="6">Junio</option>
                    <option value="7">Julio</option><option value="8">Agosto</option>
                    <option value="9">Septiembre</option><option value="10">Octubre</option>
                    <option value="11">Noviembre</option><option value="12">Diciembre</option>
                </select>
                <select class="form-select form-select-sm" style="width:120px" v-model="filtroHist.anio" @change="fetchHistorial()">
                    <option value="2024">2024</option><option value="2025">2025</option><option value="2026">2026</option>
                </select>
            </div>
        </div>

        <div class="table-responsive bg-white rounded shadow-sm table-mensual" style="border-radius: 12px; overflow: hidden;">
            <table class="table table-bordered table-hover align-middle mb-0" style="font-size: 12.5px;">
                <thead class="table-dark text-white text-uppercase text-center" style="font-size: 10px; letter-spacing: 0.5px;">
                    <tr>
                        <th style="padding: 12px 16px;">FECHA</th>
                        <th style="padding: 12px 16px;">TOTAL HABITACIONES</th>
                        <th style="padding: 12px 16px;">COMPLETADAS</th>
                        <th style="padding: 12px 16px;">PENDIENTES</th>
                        <th style="padding: 12px 16px;">EFECTIVIDAD</th>
                        <th style="padding: 12px 16px;">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading" class="text-center"><td colspan="6" class="py-5"><div class="spinner-border text-primary"></div></td></tr>
                    <tr v-if="!loading && listaHistorial.length === 0" class="text-center"><td colspan="6" class="py-5 text-muted">No se encontraron registros en este periodo.</td></tr>
                    <tr v-for="r in listaHistorial" class="hover-row bg-white">
                        <td class="text-center fw-bold">{{ formatFecha(r.fecha) }}</td>
                        <td class="text-center">{{ r.total }}</td>
                        <td class="text-center text-success fw-bold">{{ r.completadas }}</td>
                        <td class="text-center text-danger">{{ r.pendientes }}</td>
                        <td class="text-center px-3">
                             <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success" :style="{width: (r.completadas * 100 / r.total) + '%'}"></div>
                             </div>
                             <small class="fw-bold">{{ Math.round(r.completadas * 100 / r.total) }}%</small>
                        </td>
                        <td class="text-center">
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
                    <table class="table table-bordered table-sm table-hover mb-0 table-mensual" style="font-size: 12.5px;">
                        <thead class="table-dark text-white text-uppercase text-center" style="font-size: 10px; letter-spacing: 0.5px;">
                            <tr>
                                <th style="padding: 10px 12px;">HAB</th>
                                <th style="padding: 10px 12px;">TIPO</th>
                                <th style="padding: 10px 12px;">ESTADO</th>
                                <th style="padding: 10px 12px;">RESPONSABLE</th>
                                <th style="padding: 10px 12px;">FIN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="d in detalleDia" class="bg-white hover-row">
                                <td class="text-center fw-bold">{{ d.habitacion }}</td>
                                <td class="text-center">{{ d.tipo_limpieza }}</td>
                                <td class="text-center">{{ d.estado }}</td>
                                <td class="text-center">{{ d.responsable || '---' }}</td>
                                <td class="text-center">{{ d.hora_fin ? d.hora_fin.substring(0,5) : '---' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= $base ?>assets/js/limpieza.js"></script>
<?php include BASE_PATH . 'app/Views/layouts/footer.php'; ?>
