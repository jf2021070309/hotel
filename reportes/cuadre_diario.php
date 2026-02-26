<?php
// reportes/cuadre_diario.php — Shell PHP para cuadre diario
require_once '../config/conexion.php';
$base = '../'; $page_title = 'Cuadre Diario — Hotel Manager';
include '../includes/head.php';
include '../includes/sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <div><h4><i class="bi bi-bar-chart-line-fill me-2 text-primary"></i>Cuadre Diario</h4><p>Resumen financiero del día</p></div>
    <button onclick="window.print()" class="btn-outline-custom"><i class="bi bi-printer-fill"></i> Imprimir</button>
  </div>

  <div class="page-body" id="app-cuadre">
    <div class="text-center py-5" v-if="loading"><div class="spinner-border text-primary"></div></div>

    <!-- Selector de fecha -->
    <div class="report-card mb-4" style="padding:16px 20px" v-if="!loading">
      <div class="d-flex gap-2 align-items-center flex-wrap">
        <label style="font-size:13px;font-weight:600"><i class="bi bi-calendar3 me-1"></i>Fecha:</label>
        <input v-model="fecha" type="date" class="form-control" style="max-width:200px" @change="cargar">
        <button class="btn-primary-custom" style="padding:8px 18px" @click="cargar">
          <i class="bi bi-search"></i> Consultar
        </button>
      </div>
    </div>

    <div v-if="data && !loading">
      <!-- Stats -->
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <div class="stat-card"><div class="stat-icon green"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-info"><label>Efectivo</label><span>{{ fmt(data.efectivo) }}</span></div></div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card"><div class="stat-icon purple"><i class="bi bi-credit-card-fill"></i></div>
            <div class="stat-info"><label>Tarjeta</label><span>{{ fmt(data.tarjeta) }}</span></div></div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card"><div class="stat-icon cyan"><i class="bi bi-graph-up"></i></div>
            <div class="stat-info"><label>Ingresos</label><span>{{ fmt(data.total_ingresos) }}</span></div></div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card"><div class="stat-icon amber"><i class="bi bi-graph-down"></i></div>
            <div class="stat-info"><label>Gastos</label><span>{{ fmt(data.total_gastos) }}</span></div></div>
        </div>
      </div>

      <!-- Ganancia neta -->
      <div class="report-card mb-4"
           :style="{background: data.ganancia_neta >= 0 ? '#f0fdf4' : '#fef2f2',
                    border: '2px solid ' + (data.ganancia_neta >= 0 ? '#bbf7d0' : '#fecaca')}">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="stat-icon" :class="data.ganancia_neta >= 0 ? 'green' : 'red'"
                 style="width:52px;height:52px;font-size:22px">
              <i :class="data.ganancia_neta >= 0 ? 'bi bi-trophy-fill' : 'bi bi-exclamation-triangle-fill'"></i>
            </div>
            <div>
              <div style="font-size:13px;color:#64748b;font-weight:600">GANANCIA NETA DEL DÍA</div>
              <div style="font-size:13px;color:#94a3b8">{{ fmtFecha(fecha) }} — {{ data.hab_ocupadas }} hab. ocupadas</div>
            </div>
          </div>
          <div style="font-size:32px;font-weight:800"
               :style="{color: data.ganancia_neta >= 0 ? 'var(--success)' : 'var(--danger)'}">
            {{ fmt(data.ganancia_neta) }}
          </div>
        </div>
      </div>

      <div class="row g-4">
        <!-- Pagos del día -->
        <div class="col-md-7">
          <div class="card-table">
            <div style="padding:16px 20px;border-bottom:1px solid #e2e8f0">
              <h6 class="mb-0 fw-bold"><i class="bi bi-cash-coin me-2 text-success"></i>Pagos del Día</h6>
            </div>
            <table class="table">
              <thead><tr><th>Habitación</th><th>Cliente</th><th>Método</th><th>Monto</th></tr></thead>
              <tbody>
                <tr v-for="p in data.detalle_pagos" :key="p.hab_num+p.monto">
                  <td><strong>Hab. {{ p.hab_num }}</strong></td>
                  <td>{{ p.cliente }}</td>
                  <td><span class="px-badge" :class="p.metodo==='efectivo'?'badge-efectivo':'badge-tarjeta'">{{ p.metodo }}</span></td>
                  <td><strong>{{ fmt(p.monto) }}</strong></td>
                </tr>
                <tr v-if="!data.detalle_pagos.length"><td colspan="4" class="text-center py-3 text-muted">Sin pagos.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <!-- Gastos del día -->
        <div class="col-md-5">
          <div class="card-table">
            <div style="padding:16px 20px;border-bottom:1px solid #e2e8f0">
              <h6 class="mb-0 fw-bold"><i class="bi bi-receipt me-2 text-danger"></i>Gastos del Día</h6>
            </div>
            <table class="table">
              <thead><tr><th>Descripción</th><th>Monto</th></tr></thead>
              <tbody>
                <tr v-for="g in data.detalle_gastos" :key="g.descripcion+g.monto">
                  <td>{{ g.descripcion }}</td>
                  <td><strong style="color:var(--danger)">{{ fmt(g.monto) }}</strong></td>
                </tr>
                <tr v-if="!data.detalle_gastos.length"><td colspan="2" class="text-center py-3 text-muted">Sin gastos.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div><!-- /data -->
  </div>
</div>

<style>@media print { .sidebar, .topbar button, input, button { display:none!important; } .main-content {margin-left:0!important;} }</style>
<script src="cuadre_diario.js"></script>
</body></html>
