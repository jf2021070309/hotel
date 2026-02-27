<?php
// registros/index.php — Shell PHP para listado de registros
require_once '../config/conexion.php';
$base = '../'; $page_title = 'Registros — Hotel Manager'; $export_enabled = true;
include '../includes/head.php';
include '../includes/sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
    <div><h4><i class="bi bi-journal-text me-2 text-primary"></i>Registros</h4><p>Historial de ingresos y salidas</p></div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="crear.php" class="btn-primary-custom"><i class="bi bi-person-plus-fill"></i> Nuevo Ingreso</a>
      <button class="btn-outline-custom" onclick="__appReg?.exportarPDF()" title="Exportar PDF">
        <i class="bi bi-file-earmark-pdf-fill text-danger"></i> PDF
      </button>
      <button class="btn-outline-custom" onclick="__appReg?.exportarExcel()" title="Exportar Excel">
        <i class="bi bi-file-earmark-excel-fill text-success"></i> Excel
      </button>
    </div>
  </div>

  <div class="page-body" id="app-registros">
    <div class="text-center py-5" v-if="loading"><div class="spinner-border text-primary"></div></div>

    <div v-if="msg" class="alert-custom alert-success mb-3"><i class="bi bi-check-circle-fill"></i> {{ msg }}</div>

    <!-- Filtro -->
    <div class="report-card mb-4" style="padding:14px 20px" v-if="!loading">
      <div class="d-flex gap-2 align-items-center flex-wrap">
        <label style="font-size:13px;font-weight:600;color:#374151">Mostrar:</label>
        <button class="btn-outline-custom" :class="{active: filtro === 'todos'}"
                @click="filtro='todos'" style="padding:6px 14px">Todos</button>
        <button class="btn-outline-custom" :class="{active: filtro === 'activo'}"
                @click="filtro='activo'" style="padding:6px 14px">Activos</button>
        <button class="btn-outline-custom" :class="{active: filtro === 'finalizado'}"
                @click="filtro='finalizado'" style="padding:6px 14px">Finalizados</button>
      </div>
    </div>

    <div class="card-table" v-if="!loading">
      <table class="table">
        <thead>
          <tr><th>#</th><th>Habitación</th><th>Cliente</th><th>Ingreso</th>
              <th>Salida</th><th>Precio</th><th>Estado</th><th class="text-end">Acciones</th></tr>
        </thead>
        <tbody>
          <tr v-for="(r, i) in registrosFiltrados" :key="r.id">
            <td>{{ i+1 }}</td>
            <td><strong>Hab. {{ r.hab_num }}</strong> <small class="text-muted">{{ r.hab_tipo }}</small></td>
            <td>{{ r.cliente }}<br><small class="text-muted">{{ r.dni }}</small></td>
            <td>{{ fmtFecha(r.fecha_ingreso) }}</td>
            <td>{{ r.fecha_salida ? fmtFecha(r.fecha_salida) : '—' }}</td>
            <td>S/ {{ parseFloat(r.precio).toFixed(2) }}</td>
            <td>
              <span class="px-badge" :class="r.estado === 'activo' ? 'badge-ocupado' : 'badge-libre'">
                {{ r.estado === 'activo' ? 'Activo' : 'Finalizado' }}
              </span>
            </td>
            <td class="text-end d-flex gap-1 justify-content-end">
              <a v-if="r.estado === 'activo'" :href="'salida.php?id=' + r.id" class="btn-danger-custom btn-sm">
                <i class="bi bi-box-arrow-right"></i>
              </a>
              <a v-if="r.estado === 'activo'" :href="'../pagos/index.php?reg=' + r.id" class="btn-outline-custom btn-sm">
                <i class="bi bi-cash-coin"></i>
              </a>
            </td>
          </tr>
          <tr v-if="registrosFiltrados.length === 0">
            <td colspan="8" class="text-center py-4 text-muted">No hay registros.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script src="index.js"></script>
</body></html>
