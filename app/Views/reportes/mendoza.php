<?php
/**
 * app/Views/reportes/mendoza.php
 */
$base = '../../../';
require_once $base . 'auth/middleware.php';
protegerPorRol('admin');

$page_title = 'Reporte Sr. Mendoza — Hotel Manager';
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';
?>

<div class="main-content" id="app-mendoza" v-cloak>
    <div class="topbar">
        <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
        <div>
            <h1 class="h4 mb-0">Reporte Sr. Mendoza</h1>
            <p class="text-muted mb-0 small">Venta detallada de hospedaje por habitación</p>
        </div>
        <div class="ms-auto d-flex gap-2">
            <select v-model="filtro.mes" class="form-select form-select-sm" @change="fetchData">
                <option v-for="m in 12" :key="m" :value="m">{{ getMesNombre(m) }}</option>
            </select>
            <select v-model="filtro.anio" class="form-select form-select-sm" @change="fetchData">
                <option v-for="y in [2024, 2025, 2026]" :key="y" :value="y">{{ y }}</option>
            </select>
            <button @click="exportar" class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i> Excel
            </button>
        </div>
    </div>

    <div class="page-body">
        <!-- Resumen Regla de Oro -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center py-3" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                    <div class="small fw-bold opacity-75">VENTA HOSPEDAJE</div>
                    <div class="h3 mb-0 fw-bold">S/ {{ parseFloat(resumen.ingresos_hospedaje).toFixed(2) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center py-3 bg-white">
                    <div class="small fw-bold text-muted">OTROS INGRESOS</div>
                    <div class="h3 mb-0 fw-bold text-dark">S/ {{ parseFloat(resumen.otros_ingresos).toFixed(2) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center py-3 bg-white">
                    <div class="small fw-bold text-muted text-danger">EGRESOS (FLUJO)</div>
                    <div class="h3 mb-0 fw-bold text-danger">S/ {{ parseFloat(resumen.egresos_operativos).toFixed(2) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm text-center py-3" style="background: #fbbf24; color: #78350f;">
                    <div class="small fw-bold opacity-75">UTILIDAD NETA</div>
                    <div class="h3 mb-0 fw-bold">S/ {{ parseFloat(resumen.utilidad_neta).toFixed(2) }}</div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">HAB.</th>
                                <th>TIPO</th>
                                <th class="text-center">ESTADÍAS</th>
                                <th class="text-end">VENTA TEÓRICA</th>
                                <th class="text-end text-success">EFECTIVO</th>
                                <th class="text-end text-primary">POS</th>
                                <th class="text-end text-warning">YAPE</th>
                                <th class="text-end fw-bold pe-4">TOTAL COBRADO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="h in data" :key="h.habitacion">
                                <td class="ps-4"><strong>{{ h.habitacion }}</strong></td>
                                <td><span class="small text-muted">{{ h.tipo_hab }}</span></td>
                                <td class="text-center">{{ h.num_estadias }}</td>
                                <td class="text-end text-muted small">S/ {{ parseFloat(h.venta_teorica || 0).toFixed(2) }}</td>
                                <td class="text-end">S/ {{ parseFloat(h.cobrado_efectivo || 0).toFixed(2) }}</td>
                                <td class="text-end">S/ {{ parseFloat(h.cobrado_pos || 0).toFixed(2) }}</td>
                                <td class="text-end">S/ {{ parseFloat(h.cobrado_yape || 0).toFixed(2) }}</td>
                                <td class="text-end fw-bold pe-4 text-success">S/ {{ parseFloat(h.cobrado_total || 0).toFixed(2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= $base ?>assets/js/reportes/mendoza.js"></script>
</body></html>
