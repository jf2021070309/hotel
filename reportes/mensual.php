<?php
// reportes/mensual.php — Shell PHP para reporte mensual
require_once '../config/conexion.php';
$base = '../'; $page_title = 'Reporte Mensual — Hotel Manager'; $export_enabled = true;
include '../includes/head.php';
include '../includes/sidebar.php';
$meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
?>
<div class="main-content">
  <div class="topbar">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
    <div><h4><i class="bi bi-calendar-month-fill me-2 text-primary"></i>Reporte Mensual</h4><p>Resumen por mes</p></div>
    <div class="d-flex gap-2 flex-wrap">
      <button onclick="window.print()" class="btn-outline-custom"><i class="bi bi-printer-fill"></i> Imprimir</button>
      <button class="btn-outline-custom" onclick="__appMensual?.exportarPDF()" title="Exportar PDF">
        <i class="bi bi-file-earmark-pdf-fill text-danger"></i> PDF
      </button>
      <button class="btn-outline-custom" onclick="__appMensual?.exportarExcel()" title="Exportar Excel">
        <i class="bi bi-file-earmark-excel-fill text-success"></i> Excel
      </button>
    </div>
  </div>


  <div class="page-body" id="app-mensual">
    <div class="text-center py-5" v-if="loading"><div class="spinner-border text-primary"></div></div>

    <!-- Navegación de mes -->
    <div class="report-card mb-4" style="padding:16px 20px" v-if="!loading">
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <button class="btn-outline-custom" style="padding:8px 14px" @click="cambiarMes(-1)">
          <i class="bi bi-chevron-left"></i>
        </button>
        <select v-model="month" class="form-select" style="max-width:160px" @change="cargar">
          <?php foreach ($meses as $n => $nm): if ($n===0) continue; ?>
            <option value="<?= $n ?>"><?= $nm ?></option>
          <?php endforeach; ?>
        </select>
        <select v-model="year" class="form-select" style="max-width:100px" @change="cargar">
          <?php for ($y = date('Y'); $y >= 2024; $y--): ?>
            <option value="<?= $y ?>"><?= $y ?></option>
          <?php endfor; ?>
        </select>
        <button class="btn-primary-custom" style="padding:8px 18px" @click="cargar">
          <i class="bi bi-search"></i>
        </button>
        <button class="btn-outline-custom" style="padding:8px 14px" @click="cambiarMes(1)">
          <i class="bi bi-chevron-right"></i>
        </button>
      </div>
    </div>

    <div v-if="data && !loading">
      <!-- Stats principales -->
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <div class="stat-card"><div class="stat-icon cyan"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="stat-info"><label>Ingresos del Mes</label><span>{{ fmt(data.total_ingresos) }}</span></div></div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card"><div class="stat-icon amber"><i class="bi bi-graph-down-arrow"></i></div>
            <div class="stat-info"><label>Gastos del Mes</label><span>{{ fmt(data.total_gastos) }}</span></div></div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card"><div class="stat-icon" :class="data.ganancia_mes>=0?'green':'red'">
              <i :class="data.ganancia_mes>=0?'bi bi-trophy-fill':'bi bi-exclamation-triangle-fill'"></i></div>
            <div class="stat-info"><label>Ganancia Neta</label>
              <span :style="{color:data.ganancia_mes>=0?'var(--success)':'var(--danger)'}">{{ fmt(data.ganancia_mes) }}</span></div></div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card"><div class="stat-icon blue"><i class="bi bi-journal-check"></i></div>
            <div class="stat-info"><label>Total Ingresos</label><span>{{ data.total_registros }}</span></div></div>
        </div>
      </div>

      <div class="row g-4">
        <!-- Resumen por método -->
        <div class="col-md-5">
          <div class="report-card">
            <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart-fill me-2 text-primary"></i>Resumen Financiero</h6>
            <div class="report-row">
              <span class="label"><i class="bi bi-cash me-2 text-success"></i>Efectivo</span>
              <span class="value positive">{{ fmt(data.efectivo) }}</span>
            </div>
            <div class="report-row">
              <span class="label"><i class="bi bi-credit-card me-2" style="color:#9333ea"></i>Tarjeta</span>
              <span class="value" style="color:#9333ea">{{ fmt(data.tarjeta) }}</span>
            </div>
            <div class="report-row">
              <span class="label"><i class="bi bi-plus-circle me-2 text-info"></i>Total Ingresos</span>
              <span class="value">{{ fmt(data.total_ingresos) }}</span>
            </div>
            <div class="report-row">
              <span class="label"><i class="bi bi-dash-circle me-2 text-danger"></i>Total Gastos</span>
              <span class="value negative">{{ fmt(data.total_gastos) }}</span>
            </div>
            <div class="report-row" style="border-radius:8px;padding:14px;margin-top:8px"
                 :style="{background:data.ganancia_mes>=0?'#f0fdf4':'#fef2f2'}">
              <span class="label" style="font-size:15px;font-weight:700"><i class="bi bi-bar-chart-fill me-2"></i>Ganancia Neta</span>
              <span style="font-size:18px;font-weight:800" :style="{color:data.ganancia_mes>=0?'var(--success)':'var(--danger)'}">
                {{ fmt(data.ganancia_mes) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Desglose diario -->
        <div class="col-md-7">
          <div class="card-table">
            <div style="padding:16px 20px;border-bottom:1px solid #e2e8f0">
              <h6 class="mb-0 fw-bold"><i class="bi bi-calendar3 me-2 text-primary"></i>Desglose Diario</h6>
            </div>
            <table class="table">
              <thead><tr><th>Fecha</th><th>Ingresos</th><th>Gastos</th><th>Balance</th></tr></thead>
              <tbody>
                <tr v-for="d in diasConMovimiento" :key="d.dia">
                  <td>{{ fmtFecha(d.dia) }}</td>
                  <td><span class="text-success fw-bold">{{ fmt(d.ing) }}</span></td>
                  <td><span class="text-danger">{{ fmt(d.gas) }}</span></td>
                  <td><strong :style="{color:(d.ing-d.gas)>=0?'var(--success)':'var(--danger)'}">{{ fmt(d.ing-d.gas) }}</strong></td>
                </tr>
                <tr v-if="!diasConMovimiento.length"><td colspan="4" class="text-center py-4 text-muted">Sin movimientos.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>@media print { .sidebar, .topbar button, select, button { display:none!important; } .main-content {margin-left:0!important;} }</style>
<script src="mensual.js"></script>
</body></html>
