<?php
/**
 * app/Views/reportes/mendoza.php
 */
$base = '../../../';
require_once $base . 'auth/middleware.php';
protegerPorRol('admin', 'reporte_mendoza');

$page_title      = 'Reporte Sr. Mendoza — Hotel Manager';
$export_enabled  = true;
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


        <!-- Agrupación por Día -->
        <div v-for="(turnos, fecha) in groupedData" :key="fecha" class="mb-4">
            <div class="d-flex justify-content-between align-items-center bg-dark text-white p-2 px-4 shadow-sm" style="border-radius: 8px; cursor: pointer" @click="toggleDia(fecha)">
                <h6 class="mb-0"><i class="bi bi-calendar3 me-2"></i> {{ fecha }}</h6>
                <span class="small">{{ colapsados[fecha] ? 'EXPANDIR ▼' : 'COLAPSAR ▲' }}</span>
            </div>

            <div v-show="!colapsados[fecha]" class="mt-2">
                <!-- Por cada Turno -->
                <div v-for="(items, turno) in turnos" :key="turno" v-show="items.length > 0" class="mb-3">
                    <div class="p-2 px-4 bg-secondary bg-opacity-10 fw-bold border-start border-4 border-secondary small text-uppercase">
                        TURNO {{ turno }}
                    </div>
                    <div class="table-responsive bg-white shadow-sm" style="border-radius: 0 0 8px 8px;">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.85rem;">
                            <thead class="bg-light text-muted" style="font-size: 0.7rem;">
                                <tr>
                                    <th class="ps-4">HAB</th>
                                    <th>TIPO</th>
                                    <th class="text-center">PAX</th>
                                    <th class="text-center">CHECK IN</th>
                                    <th class="text-center">CHECK OUT</th>
                                    <th class="text-center">N</th>
                                    <th class="text-center">CANAL</th>
                                    <th class="text-center">MEDIO</th>
                                    <th class="text-end fw-bold">TOTAL</th>
                                    <th class="pe-4">COMPROBANTE</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="i in items" :key="i.pago_id">
                                    <td class="ps-4"><strong>{{ i.habitacion }}</strong></td>
                                    <td><span class="text-muted small">{{ i.tipo_hab }}</span></td>
                                    <td class="text-center">{{ i.pax }}</td>
                                    <td class="text-center">{{ i.check_in }}</td>
                                    <td class="text-center">{{ i.check_out }}</td>
                                    <td class="text-center">{{ i.noches }}</td>
                                    <td class="text-center"><span class="badge bg-light text-dark border">{{ i.canal }}</span></td>
                                    <td class="text-center">
                                        <span class="badge px-2 py-1" :class="getBadgeClass(i.medio_label)">
                                            {{ i.medio_label }}
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold">{{ getSym(i.moneda) }} {{ formatNumber(i.monto, (i.moneda === 'CLP' ? 0 : 2)) }}</td>
                                    <td class="pe-4 small">{{ i.comprobante }}</td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-light border-top">
                                <tr>
                                    <td colspan="10" class="p-3">
                                        <div class="d-flex flex-wrap gap-4 align-items-center justify-content-end">
                                            <div class="small fw-bold text-muted text-uppercase">Liquidación {{ turno }}:</div>
                                            <div v-for="(val, label) in getResumenTurno(items)" :key="label" class="border-start ps-3" v-if="val > 0">
                                                <div class="micro-text text-muted fw-bold">{{ label }}</div>
                                                <div class="fw-bold" :class="getBadgeClass(label, true)">
                                                    {{ getPrefix(label) }} {{ formatNumber(val, (label.includes('P$') || label.includes('CLP')) ? 0 : 2) }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Desglosado por Moneda -->
        <div class="card border-0 shadow-sm mt-5 mb-4 overflow-hidden" style="border-radius: 12px; border: 2px solid #e2e8f0 !important;">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="mb-0 fw-bold text-uppercase text-muted" style="letter-spacing: 1px;">
                    <i class="bi bi-cash-stack me-2"></i> Resumen del Mes — {{ getMesNombre(filtro.mes) }} {{ filtro.anio }}
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="row g-0">
                    <div class="col-md-6 border-end">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr>
                                    <td class="ps-4 py-3 text-muted">POS Soles:</td>
                                    <td class="pe-4 py-3 text-end fw-bold">S/ {{ formatNumber(resumenDesglosado.POS?.PEN) }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-4 py-3 text-muted">POS Dólares:</td>
                                    <td class="pe-4 py-3 text-end fw-bold">USD {{ formatNumber(resumenDesglosado.POS?.USD) }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-4 py-3 text-muted">POS Pesos:</td>
                                    <td class="pe-4 py-3 text-end fw-bold">CLP {{ formatNumber(resumenDesglosado.POS?.CLP, 0) }}</td>
                                </tr>
                                <tr class="bg-light">
                                    <td class="ps-4 py-3 text-muted">Yape / Plin:</td>
                                    <td class="pe-4 py-3 text-end fw-bold text-primary">S/ {{ formatNumber(resumenDesglosado.YAPE) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr>
                                    <td class="ps-4 py-3 text-muted">Efectivo Soles:</td>
                                    <td class="pe-4 py-3 text-end fw-bold">S/ {{ formatNumber(resumenDesglosado.EFECTIVO?.PEN) }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-4 py-3 text-muted">Efectivo Dólares:</td>
                                    <td class="pe-4 py-3 text-end fw-bold">USD {{ formatNumber(resumenDesglosado.EFECTIVO?.USD) }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-4 py-3 text-muted">Efectivo Pesos:</td>
                                    <td class="pe-4 py-3 text-end fw-bold">CLP {{ formatNumber(resumenDesglosado.EFECTIVO?.CLP, 0) }}</td>
                                </tr>
                                <tr class="bg-light">
                                    <td class="ps-4 py-3 text-muted">Transferencia / Depósito:</td>
                                    <td class="pe-4 py-3 text-end fw-bold text-success">S/ {{ formatNumber(resumenDesglosado.TRANSFERENCIA) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= $base ?>assets/js/reportes/mendoza.js?v=<?= time() ?>"></script>
</body></html>
