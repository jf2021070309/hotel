<?php
// registros/salida.php — Shell PHP para checkout
require_once '../config/conexion.php';
$base = '../'; $page_title = 'Registrar Salida — Hotel Manager';
$pre_id = (int)($_GET['id'] ?? 0);
include '../includes/head.php';
include '../includes/sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <div><h4><i class="bi bi-box-arrow-right me-2 text-primary"></i>Registrar Salida</h4><p>Registro de salida del huésped</p></div>
    <a href="index.php" class="btn-outline-custom"><i class="bi bi-arrow-left"></i> Volver</a>
  </div>

  <div class="page-body" id="app-salida">
    <div class="text-center py-5" v-if="loading"><div class="spinner-border text-primary"></div></div>

    <div class="form-card" v-if="!loading">
      <div class="mb-4 d-flex align-items-center gap-3">
        <div class="stat-icon red" style="width:44px;height:44px;font-size:18px"><i class="bi bi-box-arrow-right"></i></div>
        <div><h5 class="mb-0 fw-bold">Registro de salida</h5><small class="text-muted">Seleccione el registro activo</small></div>
      </div>

      <div v-if="error" class="alert-custom alert-error mb-3"><i class="bi bi-exclamation-triangle-fill"></i> {{ error }}</div>

      <div class="row g-3">
        <div class="col-12">
          <label class="form-label">Huésped Activo</label>
          <select v-model="form.registro_id" class="form-select" @change="onSelectRegistro">
            <option value="">Seleccionar huésped...</option>
            <option v-for="r in registros" :key="r.id" :value="r.id">
              Hab. {{ r.hab_num }} — {{ r.cliente }} (desde {{ fmtFecha(r.fecha_ingreso) }})
            </option>
          </select>
        </div>

        <!-- Info del huésped seleccionado -->
        <div class="col-12" v-if="seleccionado">
          <div class="report-card" style="background:#f0f9ff;border:1px solid #bae6fd">
            <div class="row g-2 text-sm">
              <div class="col-6 col-md-3"><small class="text-muted d-block">Habitación</small><strong>{{ seleccionado.hab_num }} — {{ seleccionado.hab_tipo }}</strong></div>
              <div class="col-6 col-md-3"><small class="text-muted d-block">Cliente</small><strong>{{ seleccionado.cliente }}</strong></div>
              <div class="col-6 col-md-3"><small class="text-muted d-block">Ingreso</small><strong>{{ fmtFecha(seleccionado.fecha_ingreso) }}</strong></div>
              <div class="col-6 col-md-3"><small class="text-muted d-block">Precio / noche</small><strong>{{ fmt(seleccionado.precio) }}</strong></div>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <label class="form-label">Fecha de Salida</label>
          <input v-model="form.fecha_salida" type="date" class="form-control" @change="calcularNoches">
        </div>

        <!-- Resumen de noches y total -->
        <div class="col-md-6" v-if="seleccionado && noches > 0">
          <div class="report-card" style="background:#f0fdf4;border:1px solid #bbf7d0;padding:16px">
            <div class="report-row">
              <span class="label">Noches</span>
              <span class="value"><strong>{{ noches }}</strong></span>
            </div>
            <div class="report-row">
              <span class="label">Total estimado</span>
              <span class="value positive"><strong>{{ fmt(totalEstimado) }}</strong></span>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-4">
        <button class="btn-danger-custom w-100 justify-content-center" @click="procesarSalida" :disabled="guardando">
          <i class="bi bi-box-arrow-right"></i> {{ guardando ? 'Procesando...' : 'Registrar Salida' }}
        </button>
      </div>
    </div>
  </div>
</div>

<script>const PRE_REG_ID = <?= $pre_id ?>;</script>
<script src="salida.js"></script>
</body></html>
