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
        <!-- Resumen Regla de Oro (Compacto) -->
        <div class="row g-3 mb-4">
            <div class="col-md-2">
                <div class="card h-100 border-0 shadow-sm text-center py-2" style="background: #10b981; color: white;">
                    <div class="small fw-bold opacity-75">HOSPEDAJE</div>
                    <div class="h5 mb-0 fw-bold">S/ {{ parseFloat(resumen.ingresos_hospedaje).toFixed(2) }}</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card h-100 border-0 shadow-sm text-center py-2 bg-white">
                    <div class="small fw-bold text-muted">OTROS ING.</div>
                    <div class="h5 mb-0 fw-bold text-dark">S/ {{ parseFloat(resumen.otros_ingresos).toFixed(2) }}</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card h-100 border-0 shadow-sm text-center py-2 bg-white text-danger">
                    <div class="small fw-bold opacity-75">EGR. FLUJO</div>
                    <div class="h5 mb-0 fw-bold">S/ {{ parseFloat(resumen.egresos_operativos).toFixed(2) }}</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card h-100 border-0 shadow-sm text-center py-2 bg-white text-danger border-start border-warning border-4">
                    <div class="small fw-bold opacity-75">C. CHICA</div>
                    <div class="h5 mb-0 fw-bold">S/ {{ parseFloat(resumen.gastos_caja_chica).toFixed(2) }}</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card h-100 border-0 shadow-sm text-center py-2 bg-white text-danger border-start border-primary border-4">
                    <div class="small fw-bold opacity-75">G. YAPE</div>
                    <div class="h5 mb-0 fw-bold">S/ {{ parseFloat(resumen.gastos_yape).toFixed(2) }}</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card h-100 border-0 shadow-sm text-center py-2" style="background: #fbbf24; color: #78350f;">
                    <div class="small fw-bold opacity-75">UTILIDAD</div>
                    <div class="h5 mb-0 fw-bold">S/ {{ parseFloat(resumen.utilidad_neta).toFixed(2) }}</div>
                </div>
            </div>
        </div>

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
                                    <th class="text-end text-success">EFECTIVO</th>
                                    <th class="text-end text-primary">POS</th>
                                    <th class="text-end text-warning">YAPE</th>
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
                                    <td class="text-end">{{ formatCurrency(i.cobrado_efectivo, getSym(i.moneda)) }}</td>
                                    <td class="text-end">{{ formatCurrency(i.cobrado_pos, getSym(i.moneda)) }}</td>
                                    <td class="text-end">{{ formatCurrency(i.cobrado_yape, getSym(i.moneda)) }}</td>
                                    <td class="text-end fw-bold">{{ getSym(i.moneda) }} {{ i.total_fila }}</td>
                                    <td class="pe-4 small">{{ i.comprobante }}</td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-light fw-bold">
                                <tr>
                                    <td colspan="7" class="ps-4 text-end">Subtotal {{ turno }}:</td>
                                    <td class="text-end text-success">S/ {{ getSubtotalTurno(items.filter(x => x.moneda === 'PEN'), 'cobrado_efectivo') }}</td>
                                    <td class="text-end text-primary">S/ {{ getSubtotalTurno(items.filter(x => x.moneda === 'PEN'), 'cobrado_pos') }}</td>
                                    <td class="text-end text-warning">S/ {{ getSubtotalTurno(items.filter(x => x.moneda === 'PEN'), 'cobrado_yape') }}</td>
                                    <td colspan="2"></td>
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
                                    <td class="pe-4 py-3 text-end fw-bold">S/ {{ resumenDesglosado.POS?.PEN.toFixed(2) }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-4 py-3 text-muted">POS Dólares:</td>
                                    <td class="pe-4 py-3 text-end fw-bold">USD {{ resumenDesglosado.POS?.USD.toFixed(2) }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-4 py-3 text-muted">POS Pesos:</td>
                                    <td class="pe-4 py-3 text-end fw-bold">CLP {{ Math.round(resumenDesglosado.POS?.CLP) }}</td>
                                </tr>
                                <tr class="bg-light">
                                    <td class="ps-4 py-3 text-muted">Yape / Plin:</td>
                                    <td class="pe-4 py-3 text-end fw-bold text-primary">S/ {{ resumenDesglosado.YAPE?.toFixed(2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr>
                                    <td class="ps-4 py-3 text-muted">Efectivo Soles:</td>
                                    <td class="pe-4 py-3 text-end fw-bold">S/ {{ resumenDesglosado.EFECTIVO?.PEN.toFixed(2) }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-4 py-3 text-muted">Efectivo Dólares:</td>
                                    <td class="pe-4 py-3 text-end fw-bold">USD {{ resumenDesglosado.EFECTIVO?.USD.toFixed(2) }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-4 py-3 text-muted">Efectivo Pesos:</td>
                                    <td class="pe-4 py-3 text-end fw-bold">CLP {{ Math.round(resumenDesglosado.EFECTIVO?.CLP) }}</td>
                                </tr>
                                <tr class="bg-light">
                                    <td class="ps-4 py-3 text-muted">Transferencia / Depósito:</td>
                                    <td class="pe-4 py-3 text-end fw-bold text-success">S/ {{ resumenDesglosado.TRANSFERENCIA?.toFixed(2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= $base ?>assets/js/reportes/mendoza.js"></script>
</body></html>
