<?php
/**
 * app/Views/reportes/alex.php
 */
$base = '../../../';
require_once $base . 'auth/middleware.php';
protegerPorRol('admin');

$page_title = 'Reporte Alex (Yape) — Hotel Manager';
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';
?>

<div class="main-content" id="app-alex" v-cloak>
    <div class="topbar">
        <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
        <div>
            <h1 class="h4 mb-0">Reporte Alex (Yape/Plin)</h1>
            <p class="text-muted mb-0 small">Detalle de gastos pagados con Yape/Plin (Excluye Hospedaje)</p>
        </div>
        <div class="ms-auto d-flex gap-2">
            <select v-model="filtro.mes" class="form-select form-select-sm" @change="fetchData">
                <option v-for="m in 12" :key="m" :value="m">{{ getMesNombre(m) }}</option>
            </select>
            <select v-model="filtro.anio" class="form-select form-select-sm" @change="fetchData">
                <option v-for="y in [2024, 2025, 2026]" :key="y" :value="y">{{ y }}</option>
            </select>
        </div>
    </div>

    <div class="page-body">
        <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">FECHA</th>
                                <th>TURNO</th>
                                <th>RUBRO / CONCEPTO</th>
                                <th>OBSERVACIÓN</th>
                                <th>DOC</th>
                                <th class="text-end pe-4">MONTO (S/)</th>
                            </tr>
                        </thead>
                        <tbody v-if="data.length > 0">
                            <tr v-for="g in data" :key="g.id">
                                <td class="ps-4"><strong>{{ formatFecha(g.fecha) }}</strong></td>
                                <td><span class="badge bg-light text-dark">{{ g.turno }}</span></td>
                                <td>{{ g.rubro }}</td>
                                <td><small class="text-muted">{{ g.observacion }}</small></td>
                                <td><span class="badge bg-secondary opacity-50">{{ g.documento || '-' }}</span></td>
                                <td class="text-end fw-bold pe-4 text-danger">S/ {{ parseFloat(g.monto).toFixed(2) }}</td>
                            </tr>
                        </tbody>
                        <tbody v-else>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">No se encontraron gastos Yape cerrados en este periodo.</td>
                            </tr>
                        </tbody>
                        <tfoot v-if="data.length > 0" class="bg-light fw-bold">
                            <tr>
                                <td colspan="5" class="text-end">TOTAL GASTOS YAPE:</td>
                                <td class="text-end pe-4 text-danger">S/ {{ totalGastos.toFixed(2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= $base ?>assets/js/reportes/alex.js"></script>
</body></html>
