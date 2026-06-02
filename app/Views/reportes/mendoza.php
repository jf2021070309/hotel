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
      --primary-gold: #d4af37;
      --transition: all 0.3s ease-in-out;
  }

  body {
      background-color: #f8f9fa !important;
      font-family: 'Inter', sans-serif;
      color: #374151;
  }

  .main-content {
      background-color: #f8f9fa;
      min-height: 100vh;
      padding-bottom: 4rem;
  }

  /* Date separator */
  .date-separator {
      font-family: 'Manrope', sans-serif;
      font-weight: 800;
      font-size: 1.1rem;
      padding: 1.25rem 1.5rem;
      background: #ffffff;
      border-left: 5px solid #293b95;
      color: #111827;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1rem;
      cursor: pointer;
      user-select: none;
      box-shadow: 0 2px 10px rgba(0,0,0,0.03);
      transition: all 0.2s;
  }
  .date-separator:hover {
      background: #f8fafc;
      transform: translateX(4px);
  }

  .turn-badge {
      font-family: 'Inter', sans-serif;
      font-weight: 800;
      font-size: 0.75rem;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      padding: 0.5rem 1rem;
      background: #f1f5f9;
      color: #334155;
      border: 1px solid #e2e8f0;
      border-radius: 20px;
      margin-bottom: 1rem;
      display: inline-block;
  }

  /* TABLE (Auditoria Style) */
  .audit-grid-container {
    overflow-x: auto;
    background: #fff;
    position: relative;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  }
  .table-mensual {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-bottom: 0;
  }
  .table-mensual thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    font-size: 12px;
    font-weight: 700;
    padding: 10px 8px;
    vertical-align: middle;
    color: #ffffff !important;
    box-shadow: 0 1px 0 rgba(0,0,0,0.1);
  }
  .table-mensual tbody td {
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
    background-color: #fff;
    padding: 0.75rem 1rem;
    font-size: 13px;
    transition: background-color 0.15s;
  }
  .table-mensual thead tr:first-child th { border-bottom: none !important; }
  .table-mensual thead tr:last-child th  { border-top: none !important; }
  .table-mensual thead th { border-left: none !important; border-right: none !important; }
  .table-mensual thead th[style*="border-left"] {
    border-left: 1px solid rgba(255,255,255,0.15) !important;
  }

  /* Filas Hover */
  .audit-row:hover td { background-color: #f8fafc !important; cursor: pointer; }
  .consumption-row td { background-color: #fffbeb !important; }
  .consumption-row:hover td { background-color: #fef3c7 !important; cursor: pointer; }

  /* Amounts & Text */
  .amount-font {
      font-family: 'Manrope', sans-serif;
      font-weight: 800;
      color: #111827;
  }
  
  /* Totals Pill */
  .total-pill {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.75rem 1.5rem;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      margin-bottom: 0.5rem;
      width: 100%;
      max-width: 300px;
  }
  .total-label {
      font-size: 0.75rem;
      font-weight: 700;
      color: #64748b;
      text-transform: uppercase;
  }
  .total-value {
      font-family: 'Manrope', sans-serif;
      font-weight: 800;
      font-size: 1.1rem;
      color: #111827;
  }

  /* Summary Grid */
  /* Summary Grid */
  /* Summary Grid */
  /* Summary Grid */
  .summary-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 1.5rem;
      margin-bottom: 3rem;
  }
  @media (max-width: 992px) {
      .summary-grid {
          grid-template-columns: 1fr;
          max-width: 100%;
          padding: 0 1rem;
      }
  }
  .summary-card {
      background: #fff;
      padding: 1.5rem;
      border-radius: 12px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 2px 10px rgba(0,0,0,0.03);
      transition: all 0.3s ease;
  }
  .summary-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(0,0,0,0.06);
  }
  .summary-card h5 {
      font-size: 1.05rem !important;
      font-weight: 800 !important;
      margin-bottom: 1.25rem !important;
      color: #111827 !important;
      text-transform: uppercase;
      letter-spacing: 0.5px;
  }
  .summary-card h5 i {
      font-size: 1.25rem !important;
      margin-right: 0.5rem !important;
      color: #3b82f6;
  }
  .summary-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.75rem 1rem;
      border-radius: 8px;
      margin-bottom: 0.5rem;
      background: #f8fafc;
      border: 1px solid #f1f5f9;
      transition: all 0.2s ease;
  }
  .summary-item:hover {
      background: #f1f5f9;
      border-color: #e2e8f0;
  }
  .summary-item span:first-child {
      font-size: 0.85rem;
      font-weight: 600;
      color: #64748b;
  }
  .summary-item .amount-font {
      font-size: 0.95rem !important;
      font-weight: 800 !important;
      color: #111827 !important;
  }

  /* Badges para métodos de pago (Auditoria Style) */
  .badge { padding: 5px 12px; border-radius: 10px; font-weight: 800; font-size: 10px; text-transform: uppercase; }
  .bg-pos { background-color: #fef9c3 !important; color: #854d0e !important; border: 1px solid #fde047 !important; }
  .bg-yape { background: #fee2e2 !important; color: #991b1b !important; border: 1px solid #fecaca !important; }
  .bg-transfer { background: #e0f2fe !important; color: #075985 !important; border: 1px solid #bae6fd !important; }
  .bg-cash { background: #dcfce7 !important; color: #166534 !important; border: 1px solid #bbf7d0 !important; }
  .badge.bg-light { background-color: #f3f4f6 !important; color: #374151 !important; border-color: #e5e7eb !important; }

  .export-btn {
      background-color: #059669 !important;
      color: #fff !important;
      border: 1px solid #047857 !important;
      transition: all 0.2s;
      border-radius: 8px;
  }
  .export-btn:hover { background-color: #047857 !important; }

  /* Input styles */
  .light-input {
      background: #fff !important;
      border: 1px solid #cbd5e1 !important;
      color: #1e293b !important;
  }
  .light-input:focus {
      border-color: #3b82f6 !important;
      box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25) !important;
  }

  /* Utility overrrides */
  .text-warning-emphasis { color: #854d0e !important; }
  .text-primary { color: #1d4ed8 !important; }
  .text-success { color: #15803d !important; }
  .text-muted { color: #64748b !important; }

    [v-cloak] { display: none; }
</style>

<script>
window.MENDOZA_CONFIG = {
    apiEndpoint: <?= json_encode(project_base_url() . 'api/reportes.php') ?>,
    roomingUrl: <?= json_encode(project_base_url() . 'rooming') ?>
};
</script>

<div class="main-content" id="app-mendoza" v-cloak>
    <!-- TOPBAR PREMIUM DARK -->
    <div class="topbar" style="background-color:#111827;padding:0.75rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.05);">
      <div class="d-flex align-items-center justify-content-between w-100">
        <div class="d-flex align-items-center gap-3">
          <button class="btn btn-dark btn-sm rounded-circle d-md-none" onclick="handleMenuClick()" style="width:32px;height:32px;padding:0;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.1);border:none;">
            <i class="bi bi-list text-white"></i>
          </button>
          <div class="d-flex align-items-center gap-3">
            <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#16a34a,#15803d);display:flex;align-items:center;justify-content:center;box-shadow:0 0 15px rgba(22,163,74,0.3);">
              <i class="bi bi-file-earmark-bar-graph text-white" style="font-size:20px;"></i>
            </div>
            <div>
              <h4 class="fw-bold mb-0 text-white" style="font-size:18px;letter-spacing:-0.5px;">Reporte Mendoza</h4>
              <div class="text-white-50" style="font-size:11px;">Control financiero y auditoría de ingresos</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- BODY -->
    <div class="page-body pt-3 px-4">

      <!-- FILTROS -->
      <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
        <div class="card-body p-3">
          <div class="d-flex flex-wrap gap-3 align-items-end justify-content-between">
            <div class="d-flex flex-wrap align-items-end gap-3">
              
              <!-- Buscar -->
              <div>
                <label class="form-label mb-1 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Buscar</label>
                <div class="input-group input-group-sm rounded shadow-sm" style="width: 250px;">
                  <span class="input-group-text bg-white border-end-0 text-muted px-2"><i class="bi bi-search"></i></span>
                  <input type="text" class="form-control border-start-0 bg-white text-dark" style="font-size: 13px;" v-model="filtroAvanzado.search" placeholder="Habitación, producto, método...">
                </div>
              </div>

              <!-- Método -->
              <div>
                <label class="form-label mb-1 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Método de Pago</label>
                <select class="form-select form-select-sm text-dark shadow-sm" v-model="filtroAvanzado.metodo" style="width: 200px; font-size: 13px;">
                  <option value="">TODOS</option>
                  <option value="EFECTIVO">EFECTIVO</option>
                  <option value="POS">POS (Tarjetas)</option>
                  <option value="YAPE">YAPE / PLIN</option>
                  <option value="TRANSFER">TRANSFER</option>
                </select>
              </div>

              <!-- Mes -->
              <div>
                <label class="form-label mb-1 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Mes</label>
                <select v-model="filtro.mes" class="form-select form-select-sm text-dark shadow-sm" style="width: 140px; font-size: 13px;" @change="fetchData">
                  <option v-for="m in 12" :key="m" :value="m">{{ getMesNombre(m) }}</option>
                </select>
              </div>

              <!-- Año -->
              <div>
                <label class="form-label mb-1 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Año</label>
                <select v-model="filtro.anio" class="form-select form-select-sm text-dark shadow-sm" style="width: 100px; font-size: 13px;" @change="fetchData">
                  <option v-for="y in [2024, 2025, 2026]" :key="y" :value="y">{{ y }}</option>
                </select>
              </div>

            </div>

            <!-- Acciones -->
            <div class="d-flex gap-2">
              <button class="btn btn-sm fw-bold px-3 shadow-sm text-white" @click="exportar" style="font-size: 12px; background-color: #059669; border: 1px solid #047857; transition: all 0.2s;">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
              </button>
            </div>
          </div>
        </div>
      </div>
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

                        <!-- Tabla Auditoria Style -->
                        <div class="audit-grid-container mb-3">
                          <table class="table table-bordered table-hover mb-0 align-middle table-mensual">
                              <thead>
                                  <!-- Fila 1: Grupos de color -->
                                  <tr class="text-center text-white text-uppercase" style="font-size: 10px; letter-spacing: 0.5px; font-weight: 800;">
                                      <th colspan="2" style="background-color: #111827 !important; border-bottom: none !important; z-index: 13;">ESTADÍA Y CLIENTE</th>
                                      <th colspan="3" style="background-color: #293b95 !important; border-bottom: none !important; border-left: 1px solid rgba(255,255,255,0.1) !important;">TIEMPOS</th>
                                      <th colspan="2" style="background-color: #0f766e !important; border-bottom: none !important; border-left: 1px solid rgba(255,255,255,0.1) !important;">FINANZAS</th>
                                  </tr>
                                  <!-- Fila 2: Sub-cabeceras -->
                                  <tr class="text-center text-white text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                                      <th style="width: 80px; top: 38px; background-color: #111827 !important; border-top: none !important;">HAB</th>
                                      <th style="top: 38px; background-color: #111827 !important; border-top: none !important;">CONCEPTO</th>
                                      
                                      <th style="width: 130px; top: 38px; background-color: #293b95 !important; border-top: none !important; border-left: 1px solid rgba(255,255,255,0.1) !important;">CHECK IN</th>
                                      <th style="width: 130px; top: 38px; background-color: #293b95 !important; border-top: none !important;">CHECK OUT</th>
                                      <th style="width: 90px; top: 38px; background-color: #293b95 !important; border-top: none !important;">NOCHES</th>

                                      <th style="width: 150px; top: 38px; background-color: #0f766e !important; border-top: none !important; border-left: 1px solid rgba(255,255,255,0.1) !important;">MÉTODO</th>
                                      <th style="width: 140px; top: 38px; background-color: #0f766e !important; border-top: none !important;">MONTO</th>
                                  </tr>
                              </thead>
                              <tbody>
                                  <!-- Hospedaje -->
                                  <tr v-for="i in info.hospedaje" :key="'h-'+i.pago_id" class="audit-row" @click="verDetalle(i.stay_id)">
                                      <td class="text-center px-2">
                                          <div class="fw-bold text-dark fs-6">{{ i.habitacion }}</div>
                                      </td>
                                      <td class="text-start px-3">
                                          <div class="fw-bold text-dark d-flex align-items-center" style="font-size: 13px;">
                                              {{ i.concept_override || 'Hospedaje' }}
                                              <i class="bi bi-box-arrow-up-right ms-2 opacity-25" style="font-size: 0.7rem;"></i>
                                          </div>
                                          <div class="text-muted" style="font-size: 11px;">
                                              {{ i.concept_override ? 'Servicio adicional' : 'Estancia activa' }}
                                          </div>
                                      </td>
                                      <td class="text-center px-2">
                                          <div class="text-muted" style="font-size: 12px;">{{ i.check_in }}</div>
                                      </td>
                                      <td class="text-center px-2">
                                          <div class="text-muted" style="font-size: 12px;">{{ i.check_out }}</div>
                                      </td>
                                      <td class="text-center px-2">
                                          <div class="fw-bold text-dark">{{ i.noches }}</div>
                                      </td>
                                      <td class="text-center px-2">
                                          <span class="badge" :class="getBadgeClass(i.medio_label)">
                                              {{ i.medio_label }}
                                          </span>
                                      </td>
                                      <td class="text-end px-3">
                                          <div class="fw-bold text-dark" style="font-size: 14px;">{{ formatNumber(i.monto, (i.moneda === 'CLP' ? 0 : 2)) }}</div>
                                          <div class="text-muted" style="font-size: 10px;">{{ i.moneda }}</div>
                                      </td>
                                  </tr>
                                  <!-- Consumos -->
                                  <tr v-for="c in info.consumos" :key="'c-'+c.id" class="consumption-row" @click="c.stay_id ? verDetalle(c.stay_id) : null">
                                      <td class="text-center px-2">
                                          <div class="fw-bold text-dark fs-6">{{ c.habitacion }}</div>
                                      </td>
                                      <td class="text-start px-3">
                                          <div class="fw-bold text-primary d-flex align-items-center" style="font-size: 13px;">
                                              Consumo Adicional
                                              <i v-if="c.stay_id" class="bi bi-box-arrow-up-right ms-2 opacity-25" style="font-size: 0.7rem;"></i>
                                          </div>
                                          <div class="text-muted" style="font-size: 11px;">{{ c.producto }} (x{{ c.cantidad }})</div>
                                      </td>
                                      <td colspan="3" class="text-center px-2">
                                          <div class="text-muted" style="font-size: 12px;">Venta Directa</div>
                                      </td>
                                      <td class="text-center px-2">
                                          <span class="badge bg-light text-dark border">
                                              {{ c.metodo_pago }}
                                          </span>
                                      </td>
                                      <td class="text-end px-3">
                                          <div class="fw-bold text-dark" style="font-size: 14px;">S/ {{ formatNumber(c.total) }}</div>
                                          <div class="text-muted" style="font-size: 10px;">PEN</div>
                                      </td>
                                  </tr>
                              </tbody>
                          </table>
                        </div>
                        
                        <!-- Totales del Turno -->
                        <div class="p-3 d-flex flex-column align-items-end" style="border-top: 1px solid #f1f5f9; background-color: #f8fafc;">
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
        <div class="container-fluid px-5">
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
</div>

<script src="<?= $_root ?>public/assets/js/reportes/mendoza.js?v=<?= time() ?>"></script>
</body></html>

