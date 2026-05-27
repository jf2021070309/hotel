<?php
/**
 * app/Views/reportes/mendoza.php
 * Premium 'Digital Concierge' Edition
 */
$base = '../../../';
$_projectRoot = dirname(__DIR__, 3);
require_once $_projectRoot . '/app/Middleware/auth.php';
protegerPorRol('admin', 'reporte_mendoza');

$page_title      = 'Mendoza Luxury Report — Hotel Manager';
$export_enabled  = true;
include $_projectRoot . '/app/Views/layouts/head.php';
include $_projectRoot . '/app/Views/layouts/sidebar.php';
?>

<!-- Google Fonts: Manrope & Inter -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Manrope:wght@500;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --base-bg: #faf9f6;
        --surface-bg: #f4f3f1;
        --primary-gold: #745b20;
        --on-surface: #1a1c1a;
        --deep-charcoal: #121212;
        --soft-shadow: 0 10px 30px rgba(116, 91, 32, 0.05);
        --transition: all 0.3s ease-in-out;
    }

    body {
        background-color: var(--base-bg) !important;
        font-family: 'Inter', sans-serif;
        color: var(--on-surface);
    }

    .main-content {
        background-color: var(--base-bg);
        min-height: 100vh;
        padding-bottom: 4rem;
    }

    .premium-topbar {
        background: transparent;
        padding: 2rem 3rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .premium-topbar h1 {
        font-family: 'Manrope', sans-serif;
        font-weight: 700;
        letter-spacing: -0.02em;
        color: var(--deep-charcoal);
        margin-bottom: 0.25rem;
    }

    .premium-card {
        background: var(--surface-bg);
        border-radius: 24px;
        border: none;
        box-shadow: var(--soft-shadow);
        transition: var(--transition);
        margin-bottom: 2rem;
        overflow: hidden;
    }

    .premium-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 40px rgba(116, 91, 32, 0.08);
    }

    .date-separator {
        font-family: 'Manrope', sans-serif;
        font-weight: 600;
        font-size: 1.1rem;
        padding: 1.5rem 2rem;
        background: var(--deep-charcoal);
        color: #fff;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        cursor: pointer;
        user-select: none;
        transition: var(--transition);
    }

    .date-separator:hover {
        background: #000;
        transform: scale(1.01);
    }

    .turn-badge {
        font-family: 'Inter', sans-serif;
        font-weight: 600;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        padding: 0.5rem 1rem;
        background: var(--primary-gold);
        color: #fff;
        border-radius: 8px;
        margin-bottom: 1rem;
        display: inline-block;
    }

    .luxury-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .luxury-table th {
        font-family: 'Inter', sans-serif;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #7f7668;
        padding: 1rem 1.5rem;
        background: rgba(127, 118, 104, 0.05);
    }

    .luxury-table td {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid rgba(127, 118, 104, 0.05);
        font-size: 0.95rem;
    }

    .luxury-table tr:last-child td {
        border-bottom: none;
    }

    .luxury-table tr:hover td {
        background: rgba(116, 91, 32, 0.02);
    }

    .amount-font {
        font-family: 'Manrope', sans-serif;
        font-weight: 600;
        color: var(--deep-charcoal);
    }

    .consumption-row {
        background-color: rgba(245, 158, 11, 0.03) !important;
    }

    .clickable-row {
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .clickable-row:hover {
        background-color: rgba(0, 0, 0, 0.03) !important;
        transform: translateX(4px);
    }

    .clickable-row:active {
        transform: scale(0.995);
    }

    .total-pill {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 1.5rem;
        background: #fff;
        border-radius: 12px;
        margin-bottom: 0.5rem;
        width: 100%;
        max-width: 300px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
    }

    .total-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: #7f7668;
        text-transform: uppercase;
    }

    .total-value {
        font-family: 'Manrope', sans-serif;
        font-weight: 700;
        font-size: 1.1rem;
        color: var(--primary-gold);
    }

    .export-btn {
        background: var(--deep-charcoal);
        color: #fff;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: var(--transition);
    }

    .export-btn:hover {
        background: #000;
        transform: scale(1.05);
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-top: 3rem;
    }

    .summary-card {
        background: #fff;
        padding: 2rem;
        border-radius: 24px;
        box-shadow: var(--soft-shadow);
    }

    .summary-item {
        display: flex;
        justify-content: space-between;
        padding: 1rem 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .summary-item:last-child {
        border-bottom: none;
    }

    .bg-pos { background: #5a4409 !important; color: #fff !important; }
    .bg-yape { background: #0081a7 !important; color: #fff !important; }
    .bg-transfer { background: #386641 !important; color: #fff !important; }
    .bg-cash { background: #4d4639 !important; color: #fff !important; }

    [v-cloak] { display: none; }
</style>

<script>
window.MENDOZA_CONFIG = {
    apiEndpoint: <?= json_encode(project_base_url() . 'api/reportes.php') ?>,
    roomingUrl: <?= json_encode(project_base_url() . 'rooming') ?>
};
</script>

<div class="main-content" id="app-mendoza" v-cloak>
    <div class="premium-topbar">
        <div>
            <h1>Reporte Mendoza</h1>
            <p class="text-muted mb-0">Control financiero y auditoría de ingresos</p>
        </div>
        <div class="d-flex gap-3 align-items-center">
            <select v-model="filtro.mes" class="form-select border-0 shadow-sm" style="border-radius: 12px; padding: 0.6rem 1.2rem;" @change="fetchData">
                <option v-for="m in 12" :key="m" :value="m">{{ getMesNombre(m) }}</option>
            </select>
            <select v-model="filtro.anio" class="form-select border-0 shadow-sm" style="border-radius: 12px; padding: 0.6rem 1.2rem;" @change="fetchData">
                <option v-for="y in [2024, 2025, 2026]" :key="y" :value="y">{{ y }}</option>
            </select>
            <button @click="exportar" class="export-btn">
                <i class="bi bi-file-earmark-excel"></i> Exportar
            </button>
        </div>
    </div>

    <!-- Filtros Avanzados (Sin afectar el estado actual) -->
    <div class="container-fluid px-5 mb-5">
        <div class="premium-card p-3 d-flex gap-4 align-items-center" style="background: rgba(255,255,255,0.6); backdrop-filter: blur(10px); border-radius: 20px;">
            <div class="flex-grow-1 position-relative">
                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <input type="text" v-model="filtroAvanzado.search" 
                       class="form-control border-0 bg-white shadow-sm" 
                       placeholder="Buscar por habitación, producto o método..."
                       style="border-radius: 12px; padding: 0.7rem 1rem 0.7rem 2.8rem;">
            </div>
            <div style="min-width: 220px;">
                <select v-model="filtroAvanzado.metodo" class="form-select border-0 bg-white shadow-sm" style="border-radius: 12px; padding: 0.7rem 1.2rem;">
                    <option value="">Todos los métodos</option>
                    <option value="EFECTIVO">Efectivo</option>
                    <option value="POS">POS (Tarjetas)</option>
                    <option value="YAPE">Yape / Plin</option>
                    <option value="TRANSFER">Transferencias</option>
                </select>
            </div>
            <div class="text-muted small d-none d-lg-block">
                <i class="bi bi-funnel me-1"></i> Filtros inteligentes
            </div>
        </div>
    </div>

    <div class="container-fluid px-5">
        <!-- Loop de Días -->
        <div v-for="(turnos, fecha) in groupedData" :key="fecha" class="mb-5">
            <div class="date-separator" @click="toggleDia(fecha)">
                <div><i class="bi bi-stars me-2"></i> {{ fecha }}</div>
                <div style="font-size: 0.8rem;">
                    {{ colapsados[fecha] ? 'EXPANDIR VISTA' : 'COLAPSAR VISTA' }} 
                    <i class="bi ms-2" :class="colapsados[fecha] ? 'bi-chevron-down' : 'bi-chevron-up'"></i>
                </div>
            </div>

            <div v-show="!colapsados[fecha]" v-for="(info, turno) in turnos" :key="turno">
                <div v-if="info.hospedaje.length > 0 || info.consumos.length > 0" class="mb-5">
                    <div class="turn-badge">Turno {{ turno }}</div>

                    <div class="premium-card">
                        <table class="luxury-table">
                            <thead>
                                <tr>
                                    <th width="80">Hab</th>
                                    <th>Concepto</th>
                                    <th class="text-center">Check In</th>
                                    <th class="text-center">Check Out</th>
                                    <th class="text-center">Noches</th>
                                    <th class="text-center">Método</th>
                                    <th class="text-end">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Hospedaje -->
                                <tr v-for="i in info.hospedaje" :key="'h-'+i.pago_id" class="clickable-row" @click="verDetalle(i.stay_id)">
                                    <td class="amount-font text-center">{{ i.habitacion }}</td>
                                    <td>
                                        <div class="fw-bold d-flex align-items-center" :class="{'text-warning-emphasis': i.concept_override}">
                                            {{ i.concept_override || 'Hospedaje' }}
                                            <i class="bi bi-box-arrow-up-right ms-2 opacity-25" style="font-size: 0.7rem;"></i>
                                        </div>
                                        <div class="small text-muted">
                                            {{ i.concept_override ? 'Servicio adicional' : 'Estancia activa' }}
                                        </div>
                                    </td>
                                    <td class="text-center small text-muted">{{ i.check_in }}</td>
                                    <td class="text-center small text-muted">{{ i.check_out }}</td>
                                    <td class="text-center">{{ i.noches }}</td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill px-3" :class="getBadgeClass(i.medio_label)">
                                            {{ i.medio_label }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="amount-font">{{ formatNumber(i.monto, (i.moneda === 'CLP' ? 0 : 2)) }}</div>
                                        <div class="small text-muted">{{ i.moneda }}</div>
                                    </td>
                                </tr>
                                <!-- Consumos -->
                                <tr v-for="c in info.consumos" :key="'c-'+c.id" class="consumption-row" :class="{'clickable-row': c.stay_id}" @click="c.stay_id ? verDetalle(c.stay_id) : null">
                                    <td class="amount-font text-center">{{ c.habitacion }}</td>
                                    <td>
                                        <div class="fw-bold text-warning-emphasis d-flex align-items-center">
                                            Consumo Adicional
                                            <i v-if="c.stay_id" class="bi bi-box-arrow-up-right ms-2 opacity-25" style="font-size: 0.7rem;"></i>
                                        </div>
                                        <div class="small text-muted">{{ c.producto }} (x{{ c.cantidad }})</div>
                                    </td>
                                    <td colspan="3" class="text-center small text-muted">Venta Directa</td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark rounded-pill border px-3">
                                            {{ c.metodo_pago }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="amount-font">S/ {{ formatNumber(c.total) }}</div>
                                        <div class="small text-muted">PEN</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <!-- Totales del Turno -->
                        <div class="p-4 d-flex flex-column align-items-end" style="background: rgba(116, 91, 32, 0.05);">
                            <div v-for="(val, label) in info.totales" :key="label" class="total-pill">
                                <span class="total-label">Total {{ label }}</span>
                                <span class="total-value">{{ getPrefix(label) }} {{ formatNumber(val, (label.includes('P$') || label.includes('CLP')) ? 0 : 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumen Mensual -->
        <div class="summary-grid">
            <div class="summary-card">
                <h5 class="amount-font mb-4"><i class="bi bi-credit-card me-2"></i> Transacciones Digitales</h5>
                <div class="summary-item">
                    <span class="text-muted">POS Soles</span>
                    <span class="amount-font">S/ {{ formatNumber(resumenDesglosado.POS?.PEN) }}</span>
                </div>
                <div class="summary-item">
                    <span class="text-muted">POS Dólares</span>
                    <span class="amount-font">USD {{ formatNumber(resumenDesglosado.POS?.USD) }}</span>
                </div>
                <div class="summary-item">
                    <span class="text-muted">POS Pesos</span>
                    <span class="amount-font">CLP {{ formatNumber(resumenDesglosado.POS?.CLP, 0) }}</span>
                </div>
                <div class="summary-item">
                    <span class="text-muted">Yape / Plin</span>
                    <span class="amount-font text-primary">S/ {{ formatNumber(resumenDesglosado.YAPE) }}</span>
                </div>
            </div>

            <div class="summary-card">
                <h5 class="amount-font mb-4"><i class="bi bi-wallet2 me-2"></i> Efectivo & Bancos</h5>
                <div class="summary-item">
                    <span class="text-muted">Efectivo Soles</span>
                    <span class="amount-font">S/ {{ formatNumber(resumenDesglosado.EFECTIVO?.PEN) }}</span>
                </div>
                <div class="summary-item">
                    <span class="text-muted">Efectivo Dólares</span>
                    <span class="amount-font">USD {{ formatNumber(resumenDesglosado.EFECTIVO?.USD) }}</span>
                </div>
                <div class="summary-item">
                    <span class="text-muted">Efectivo Pesos</span>
                    <span class="amount-font">CLP {{ formatNumber(resumenDesglosado.EFECTIVO?.CLP, 0) }}</span>
                </div>
                <div class="summary-item">
                    <span class="text-muted">Transferencias</span>
                    <span class="amount-font text-success">S/ {{ formatNumber(resumenDesglosado.TRANSFERENCIA) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= $_root ?>public/assets/js/reportes/mendoza.js?v=<?= time() ?>"></script>
</body></html>

