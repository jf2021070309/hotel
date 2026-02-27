<?php
// ============================================================
// reportes/graficos.php — Dashboard de gráficos
// ============================================================
$page_title      = 'Gráficos — Hotel Manager';
$base            = '../';
$chartjs_enabled = true;
require_once '../includes/head.php';
?>
<div class="wrapper">
<?php require_once '../includes/sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
    <div><h4><i class="bi bi-graph-up me-2 text-primary"></i>Gráficos</h4><p>Análisis visual del hotel</p></div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
      <select id="selMes" class="form-select form-select-sm" style="width:130px" onchange="__appGraficos?.cambiarFecha()">
        <option value="1">Enero</option><option value="2">Febrero</option><option value="3">Marzo</option>
        <option value="4">Abril</option><option value="5">Mayo</option><option value="6">Junio</option>
        <option value="7">Julio</option><option value="8">Agosto</option><option value="9">Septiembre</option>
        <option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option>
      </select>
      <select id="selAnio" class="form-select form-select-sm" style="width:90px" onchange="__appGraficos?.cambiarFecha()">
        <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
          <option value="<?= $y ?>"><?= $y ?></option>
        <?php endfor; ?>
      </select>
    </div>
  </div>

  <div class="page-body pt-2" id="app-graficos">

    <!-- Spinner de carga -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="mt-2 text-muted">Cargando datos...</p>
    </div>

    <template v-else>

      <!-- KPIs rápidos -->
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon" style="background:rgba(37,99,235,.12);color:#2563eb"><i class="bi bi-house-fill"></i></div>
            <div class="stat-info">
              <div class="stat-value">{{ graficos?.hab_total ?? 0 }}</div>
              <div class="stat-label">Total Habs.</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon" style="background:rgba(16,185,129,.12);color:#10b981"><i class="bi bi-house-check-fill"></i></div>
            <div class="stat-info">
              <div class="stat-value">{{ graficos?.hab_libres ?? 0 }}</div>
              <div class="stat-label">Disponibles</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon" style="background:rgba(245,158,11,.12);color:#f59e0b"><i class="bi bi-house-lock-fill"></i></div>
            <div class="stat-info">
              <div class="stat-value">{{ graficos?.hab_ocupadas ?? 0 }}</div>
              <div class="stat-label">Ocupadas</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon" style="background:rgba(99,102,241,.12);color:#6366f1"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-info">
              <div class="stat-value">S/ {{ fmtNum(mensual?.total_ingresos) }}</div>
              <div class="stat-label">Ingresos del mes</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Row 1: Línea + Dona -->
      <div class="row g-3 mb-3">
        <div class="col-12 col-lg-8">
          <div class="card-table p-3">
            <h6 class="fw-semibold mb-3"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Ingresos vs Gastos por día</h6>
            <canvas id="chartLinea" height="100"></canvas>
          </div>
        </div>
        <div class="col-12 col-lg-4">
          <div class="card-table p-3 h-100 d-flex flex-column">
            <h6 class="fw-semibold mb-3"><i class="bi bi-pie-chart-fill me-2 text-primary"></i>Ocupación actual</h6>
            <div class="flex-grow-1 d-flex align-items-center justify-content-center">
              <canvas id="chartDona" style="max-height:220px"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Row 2: Barras método + Top habitaciones -->
      <div class="row g-3">
        <div class="col-12 col-md-5">
          <div class="card-table p-3">
            <h6 class="fw-semibold mb-3"><i class="bi bi-credit-card-fill me-2 text-primary"></i>Método de pago del mes</h6>
            <canvas id="chartMetodo" height="160"></canvas>
          </div>
        </div>
        <div class="col-12 col-md-7">
          <div class="card-table p-3">
            <h6 class="fw-semibold mb-3"><i class="bi bi-trophy-fill me-2 text-primary"></i>Top habitaciones por ingresos</h6>
            <canvas id="chartTopHab" height="160"></canvas>
          </div>
        </div>
      </div>

    </template>
  </div><!-- #app-graficos -->
</div><!-- .main-content -->
</div><!-- .wrapper -->

<script src="graficos.js"></script>
</body>
</html>
