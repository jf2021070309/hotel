<?php
$base = '../../../';
$_projectRoot = dirname(__DIR__, 3);
require_once $_projectRoot . '/app/Middleware/auth.php';
protegerPorRol('cajera', 'reporte_mendoza');

$page_title = 'Dashboard Analítico — Hotel Manager';
$chartjs_enabled = true;
include $_projectRoot . '/app/Views/layouts/head.php';
include $_projectRoot . '/app/Views/layouts/sidebar.php';
?>

<style>
  .dashboard-wrapper {
    padding: 1rem;
    height: calc(100vh - 60px);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    background-color: #f8f9fa;
  }
  .kpi-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1rem;
  }
  .kpi-card {
    background: #fff;
    border-radius: 10px;
    padding: 1rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    display: flex;
    align-items: center;
    gap: 1rem;
  }
  .kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
  }
  .kpi-info h4 {
    margin: 0;
    font-size: 0.85rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  .kpi-info h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: #212529;
  }
  
  .charts-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    grid-template-rows: 1fr 1fr;
    gap: 1rem;
    flex: 1;
    min-height: 0;
  }
  .chart-container {
    background: #fff;
    border-radius: 10px;
    padding: 1rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    display: flex;
    flex-direction: column;
    min-height: 0;
  }
  .chart-header {
    font-size: 0.95rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
  }
  .chart-body {
    flex: 1;
    position: relative;
    min-height: 0;
  }
  
  @media (max-width: 992px) {
    .dashboard-wrapper {
      overflow-y: auto;
      height: auto;
    }
    .kpi-cards { grid-template-columns: repeat(2, 1fr); }
    .charts-grid { grid-template-columns: 1fr; grid-template-rows: auto; }
    .chart-container { height: 300px; }
  }
</style>

<div class="main-content" id="app-dashboard">
  <div class="dashboard-wrapper">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="m-0 fw-bold"><i class="bi bi-pie-chart-fill me-2 text-primary"></i> Dashboard Analítico Diario</h4>
      <div>
        <button class="btn btn-sm btn-outline-secondary" @click="fetchData">
          <i class="bi bi-arrow-clockwise"></i> Actualizar
        </button>
      </div>
    </div>
    
    <!-- Loader -->
    <div v-if="loading" class="d-flex justify-content-center align-items-center flex-grow-1">
      <div class="spinner-border text-primary" role="status"></div>
      <span class="ms-2 fw-semibold">Cargando datos...</span>
    </div>

    <template v-else>
      <!-- KPI Cards -->
      <div class="kpi-cards">
        <div class="kpi-card">
          <div class="kpi-icon bg-primary text-white bg-opacity-75"><i class="bi bi-door-open-fill"></i></div>
          <div class="kpi-info">
            <h4>Ocupación</h4>
            <h2>{{ resumen.ocupadas }} <small class="text-muted fs-6">/ {{ resumen.total }}</small></h2>
          </div>
        </div>
        <div class="kpi-card">
          <div class="kpi-icon bg-success text-white bg-opacity-75"><i class="bi bi-cash-stack"></i></div>
          <div class="kpi-info">
            <h4>Ingresos Hoy</h4>
            <h2>S/ {{ resumen.ingresos_hoy.toFixed(2) }}</h2>
          </div>
        </div>
        <div class="kpi-card">
          <div class="kpi-icon bg-warning text-dark bg-opacity-75"><i class="bi bi-people-fill"></i></div>
          <div class="kpi-info">
            <h4>Huéspedes (Pax)</h4>
            <h2>{{ resumen.pax_total }}</h2>
          </div>
        </div>
        <div class="kpi-card">
          <div class="kpi-icon bg-danger text-white bg-opacity-75"><i class="bi bi-exclamation-triangle-fill"></i></div>
          <div class="kpi-info">
            <h4>Reservas Pendientes</h4>
            <h2>{{ resumen.cnt_pendiente }}</h2>
          </div>
        </div>
      </div>

      <!-- Charts Grid -->
      <div class="charts-grid">
        <div class="chart-container">
          <div class="chart-header">Estado de Pagos (Hoy)</div>
          <div class="chart-body">
            <canvas id="pagosChart"></canvas>
          </div>
        </div>
        <div class="chart-container">
          <div class="chart-header">Top 5 Canales de Reserva</div>
          <div class="chart-body">
            <canvas id="canalesChart"></canvas>
          </div>
        </div>
        <div class="chart-container">
          <div class="chart-header">Distribución de Tipos de Habitación</div>
          <div class="chart-body">
            <canvas id="tiposHabChart"></canvas>
          </div>
        </div>
        <div class="chart-container">
          <div class="chart-header">Flujo de Ingresos (Cobrado vs Faltante)</div>
          <div class="chart-body">
            <canvas id="ingresosChart"></canvas>
          </div>
        </div>
      </div>
    </template>
  </div>
</div>

<script>
const PROJECT_BASE_URL = '<?= project_base_url() ?>';
const { createApp, ref, onMounted, nextTick } = Vue;

createApp({
  setup() {
    const BASE_URL = PROJECT_BASE_URL + 'api/reservas.php?action=';
    const loading = ref(true);
    const resumen = ref({});
    const habitaciones = ref([]);
    
    // Chart instances to destroy/recreate
    let pagosChart = null;
    let canalesChart = null;
    let tiposHabChart = null;
    let ingresosChart = null;

    const fetchData = async () => {
      loading.value = true;
      try {
        const url = new URL(window.location.href);
        const anio = url.searchParams.get('anio') || new Date().getFullYear();
        const res = await axios.get(`${BASE_URL}datos&anio=${anio}`);
        if (res.data && res.data.ok) {
          resumen.value = res.data.resumen;
          habitaciones.value = res.data.habitaciones;
          await nextTick();
          renderCharts();
        }
      } catch (error) {
        console.error("Error cargando dashboard:", error);
      } finally {
        loading.value = false;
      }
    };

    const renderCharts = () => {
      // Configuraciones globales Chart.js
      Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
      Chart.defaults.plugins.legend.position = 'bottom';
      Chart.defaults.plugins.legend.labels.boxWidth = 12;
      Chart.defaults.maintainAspectRatio = false;

      // 1. Pagos Chart (Doughnut)
      if(pagosChart) pagosChart.destroy();
      const ctxPagos = document.getElementById('pagosChart').getContext('2d');
      pagosChart = new Chart(ctxPagos, {
        type: 'doughnut',
        data: {
          labels: ['Pendiente', 'Adelanto', 'Parcial', 'Pagado'],
          datasets: [{
            data: [
              resumen.value.cnt_pendiente,
              resumen.value.cnt_adelanto,
              resumen.value.cnt_parcial,
              resumen.value.cnt_pagado
            ],
            backgroundColor: ['#ef4444', '#f59e0b', '#3b82f6', '#10b981'],
            borderWidth: 0
          }]
        },
        options: { cutout: '65%' }
      });

      // Calcular stays activos de hoy (que cruzan el día actual)
      const hoyStr = new Date().toISOString().split('T')[0];
      let staysHoy = [];
      habitaciones.value.forEach(hab => {
        if(hab.stays) {
          hab.stays.forEach(stay => {
             if (stay.estado !== 'cancelado') {
                staysHoy.push({ ...stay, tipo_hab: hab.tipo });
             }
          });
        }
      });

      // 2. Canales Chart (Bar)
      let canalesCount = {};
      staysHoy.forEach(s => {
        let canal = s.canal || 'DIRECTO';
        canalesCount[canal] = (canalesCount[canal] || 0) + 1;
      });
      // Sort and take top 5
      let canalesSorted = Object.entries(canalesCount).sort((a,b) => b[1] - a[1]).slice(0, 5);
      
      if(canalesChart) canalesChart.destroy();
      const ctxCanales = document.getElementById('canalesChart').getContext('2d');
      canalesChart = new Chart(ctxCanales, {
        type: 'bar',
        data: {
          labels: canalesSorted.map(x => x[0]),
          datasets: [{
            label: 'Reservas',
            data: canalesSorted.map(x => x[1]),
            backgroundColor: 'rgba(99, 102, 241, 0.8)',
            borderRadius: 4
          }]
        },
        options: {
          plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
      });

      // 3. Tipos de Habitación Ocupadas (Pie)
      let tiposCount = {};
      staysHoy.forEach(s => {
        tiposCount[s.tipo_hab] = (tiposCount[s.tipo_hab] || 0) + 1;
      });
      if(tiposHabChart) tiposHabChart.destroy();
      const ctxTipos = document.getElementById('tiposHabChart').getContext('2d');
      tiposHabChart = new Chart(ctxTipos, {
        type: 'pie',
        data: {
          labels: Object.keys(tiposCount),
          datasets: [{
            data: Object.values(tiposCount),
            backgroundColor: ['#8b5cf6', '#ec4899', '#14b8a6', '#f97316', '#64748b'],
            borderWidth: 0
          }]
        }
      });

      // 4. Ingresos (Cobrado vs Faltante)
      let totalEsperado = 0;
      let totalCobrado = 0;
      staysHoy.forEach(s => {
        totalEsperado += parseFloat(s.total_pago) || 0;
        totalCobrado += parseFloat(s.total_cobrado) || 0;
      });
      let faltante = Math.max(0, totalEsperado - totalCobrado);
      
      if(ingresosChart) ingresosChart.destroy();
      const ctxIngresos = document.getElementById('ingresosChart').getContext('2d');
      ingresosChart = new Chart(ctxIngresos, {
        type: 'bar',
        data: {
          labels: ['Cobrado vs Por Cobrar (Reservas Activas)'],
          datasets: [
            {
              label: 'Monto Cobrado (S/)',
              data: [totalCobrado.toFixed(2)],
              backgroundColor: '#10b981',
              borderRadius: 4
            },
            {
              label: 'Monto Faltante (S/)',
              data: [faltante.toFixed(2)],
              backgroundColor: '#ef4444',
              borderRadius: 4
            }
          ]
        },
        options: {
          plugins: { tooltip: { mode: 'index', intersect: false } },
          scales: { y: { beginAtZero: true } }
        }
      });
    };

    onMounted(() => {
      fetchData();
    });

    return { loading, resumen, fetchData };
  }
}).mount('#app-dashboard');
</script>

<?php include $_projectRoot . '/app/Views/layouts/footer.php'; ?>
