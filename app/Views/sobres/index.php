<?php
$base = '../../../';
$_projectRoot = dirname(__DIR__, 3);
require_once $_projectRoot . '/app/Middleware/auth.php';
require_once $_projectRoot . '/app/Helpers/url.php';
protegerPorRol('cajera', 'sobres');
require_once $_projectRoot . '/config/db.php';

$page_title = 'Sobre de Alex — Control de Efectivo Físico';
include $_projectRoot . '/app/Views/layouts/head.php';
include $_projectRoot . '/app/Views/layouts/sidebar.php';

$fecha = date('Y-m-d');
?>

<!-- Importar Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        fontFamily: {
          sans: ['Inter', 'system-ui', 'sans-serif'],
        },
        colors: {
          emerald: {
            50: '#ecfdf5',
            500: '#10b981',
            600: '#059669',
            700: '#047857',
            800: '#065f46',
            900: '#064e3b',
          }
        }
      }
    }
  }
</script>

<div class="main-content bg-slate-50 min-h-screen font-sans font-normal text-slate-800" id="app-sobres" v-cloak>
  <!-- TOPBAR HEADER CON GLASSMORPHISM -->
  <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-slate-200/80 px-6 py-4 transition-all shadow-sm">
    <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
      <div class="flex items-center gap-3 w-full sm:w-auto">
        <button class="btn-burger p-2 hover:bg-slate-100 rounded-xl transition-colors text-slate-700" onclick="handleMenuClick()">
          <i class="bi bi-list text-2xl"></i>
        </button>
        <div class="flex flex-col">
          <div class="flex items-center gap-2.5">
            <h1 class="text-2xl font-black bg-gradient-to-r from-emerald-600 via-teal-600 to-indigo-600 bg-clip-text text-transparent tracking-tight">
              Sobre de Alex
            </h1>
            <span class="bg-emerald-50 text-emerald-700 border border-emerald-200/80 px-3 py-0.5 rounded-full text-xs font-bold tracking-wide shadow-xs flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
              {{ dias.length }} DÍAS ACTIVOS
            </span>
          </div>
          <p class="text-xs text-slate-500 font-medium tracking-wide uppercase mt-0.5">Control Financiero de Efectivo Físico en Caja</p>
        </div>
      </div>

      <!-- CONTROLES Y FILTROS -->
      <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto justify-end">
        <!-- Selector Mes / Año -->
        <div class="flex items-center bg-white border border-slate-200/80 rounded-2xl p-1 shadow-xs hover:border-slate-300 transition-colors">
          <select class="bg-transparent text-sm font-bold text-slate-700 pl-3 pr-2 py-1.5 focus:outline-none cursor-pointer" v-model="mesFiltro" @change="consultar">
            <option v-for="(n, i) in meses" :key="i" :value="i+1">{{ n }}</option>
          </select>
          <div class="h-4 w-[1px] bg-slate-200 mx-1"></div>
          <select class="bg-transparent text-sm font-bold text-slate-700 pl-2 pr-3 py-1.5 focus:outline-none cursor-pointer" v-model="anioFiltro" @change="consultar">
            <option v-for="a in anios" :key="a" :value="a">{{ a }}</option>
          </select>
        </div>

        <!-- Botón Debug -->
        <button @click="toggleDebug" class="flex items-center gap-2 bg-white hover:bg-slate-50 text-slate-600 hover:text-slate-900 border border-slate-200 px-3.5 py-2 rounded-2xl text-xs font-bold transition-all shadow-xs hover:shadow-sm">
          <i class="bi bi-bug-fill text-indigo-500 text-sm"></i>
          <span class="hidden md:inline">Debug</span>
        </button>

        <!-- Botón Imprimir -->
        <button @click="imprimirReporte" class="flex items-center gap-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white px-5 py-2 rounded-2xl text-xs font-bold transition-all shadow-lg shadow-emerald-600/20 hover:shadow-xl hover:shadow-emerald-600/30 active:scale-95">
          <i class="bi bi-printer-fill text-sm"></i>
          <span>Imprimir Reporte</span>
        </button>
      </div>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-6 py-8">
    <div v-if="loading" class="min-h-[400px] flex flex-col items-center justify-center gap-4">
      <div class="w-12 h-12 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
      <p class="text-sm font-bold text-slate-500 tracking-wide animate-pulse">Sincronizando flujos y cierres de caja...</p>
    </div>

    <div v-else class="space-y-8">
      <!-- LAS 3 GRANDES TARJETAS RESUMEN DEL MES -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Ingresos -->
        <div class="bg-white border border-slate-200/60 rounded-3xl p-6 shadow-xl shadow-slate-100 relative overflow-hidden transition-all duration-300 hover:shadow-2xl hover:-translate-y-1">
          <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-500/10 rounded-full blur-xl pointer-events-none"></div>
          <div class="flex items-center justify-between mb-4">
            <span class="bg-blue-50 text-blue-600 border border-blue-200/60 px-3 py-1 rounded-full text-xs font-extrabold tracking-wider uppercase flex items-center gap-1.5">
              <i class="bi bi-arrow-down-left-circle-fill"></i> Ingresos Efectivo
            </span>
            <div class="w-10 h-10 rounded-2xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600">
              <i class="bi bi-cash-stack text-lg"></i>
            </div>
          </div>
          <div class="text-3xl font-black text-slate-900 tracking-tight">
            S/ {{ formatMoney(totalesMes.ingresos.PEN) }}
          </div>
          <div class="mt-3 flex items-center gap-3 text-xs font-bold text-slate-500 bg-slate-50/80 p-2.5 rounded-xl border border-slate-100">
            <span class="flex items-center gap-1"><span class="text-sm">🇺🇸</span> $ {{ formatMoney(totalesMes.ingresos.USD) }} USD</span>
            <span class="text-slate-300">|</span>
            <span class="flex items-center gap-1"><span class="text-sm">🇨🇱</span> $ {{ formatMoney(totalesMes.ingresos.CLP, 0) }} CLP</span>
          </div>
        </div>

        <!-- Extracciones -->
        <div class="bg-white border border-slate-200/60 rounded-3xl p-6 shadow-xl shadow-slate-100 relative overflow-hidden transition-all duration-300 hover:shadow-2xl hover:-translate-y-1">
          <div class="absolute -right-6 -top-6 w-24 h-24 bg-rose-500/10 rounded-full blur-xl pointer-events-none"></div>
          <div class="flex items-center justify-between mb-4">
            <span class="bg-rose-50 text-rose-600 border border-rose-200/60 px-3 py-1 rounded-full text-xs font-extrabold tracking-wider uppercase flex items-center gap-1.5">
              <i class="bi bi-arrow-up-right-circle-fill"></i> Retiros y Egresos
            </span>
            <div class="w-10 h-10 rounded-2xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-600">
              <i class="bi bi-wallet2 text-lg"></i>
            </div>
          </div>
          <div class="text-3xl font-black text-rose-600 tracking-tight">
            - S/ {{ formatMoney(totalesMes.egresos.PEN) }}
          </div>
          <div class="mt-3 flex items-center gap-3 text-xs font-bold text-slate-500 bg-rose-50/50 p-2.5 rounded-xl border border-rose-100/50">
            <span class="flex items-center gap-1"><span class="text-sm">🇺🇸</span> - $ {{ formatMoney(totalesMes.egresos.USD) }} USD</span>
            <span class="text-rose-200">|</span>
            <span class="flex items-center gap-1"><span class="text-sm">🇨🇱</span> - $ {{ formatMoney(totalesMes.egresos.CLP, 0) }} CLP</span>
          </div>
        </div>

        <!-- Fondo Neto Acumulado -->
        <div class="bg-gradient-to-br from-emerald-900 via-emerald-800 to-teal-900 text-white rounded-3xl p-7 shadow-2xl shadow-emerald-900/30 relative overflow-hidden transition-all duration-300 hover:scale-[1.02]">
          <div class="absolute right-0 top-0 w-64 h-64 bg-emerald-500/20 rounded-full blur-3xl pointer-events-none"></div>
          <div class="absolute -left-10 -bottom-10 w-48 h-48 bg-teal-500/20 rounded-full blur-2xl pointer-events-none"></div>
          <div class="flex items-center justify-between mb-4 relative z-10">
            <span class="bg-white/10 backdrop-blur-md text-emerald-200 border border-white/20 px-3.5 py-1 rounded-full text-xs font-extrabold tracking-wider uppercase flex items-center gap-2">
              <i class="bi bi-shield-check text-emerald-400"></i> Fondo Neto en Caja
            </span>
            <div class="w-10 h-10 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 flex items-center justify-center text-emerald-300">
              <i class="bi bi-safe2 text-xl"></i>
            </div>
          </div>
          <div class="text-4xl font-black text-white tracking-tight relative z-10 flex items-baseline gap-2">
            <span class="text-emerald-400 font-bold text-2xl">S/</span> {{ formatMoney(totalesMes.neto.PEN) }}
          </div>
          <div class="mt-4 flex items-center gap-3 text-xs font-bold text-emerald-100/80 bg-white/10 backdrop-blur-md p-3 rounded-2xl border border-white/10 relative z-10">
            <span class="flex items-center gap-1.5"><span class="text-sm">🇺🇸</span> $ {{ formatMoney(totalesMes.neto.USD) }} USD</span>
            <span class="text-emerald-500/60">|</span>
            <span class="flex items-center gap-1.5"><span class="text-sm">🇨🇱</span> $ {{ formatMoney(totalesMes.neto.CLP, 0) }} CLP</span>
          </div>
        </div>
      </div>

      <!-- BANNER DE TÍTULO DE LA MATRIZ DE DÍAS -->
      <div class="flex items-center justify-between pt-4 border-b border-slate-200/80 pb-3">
        <div class="flex items-center gap-3">
          <div class="w-3 h-8 rounded-lg bg-emerald-500"></div>
          <h2 class="text-xl font-bold text-slate-900 tracking-tight">Desglose Diario Consolidado</h2>
        </div>
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Matriz de 31 Bloques</span>
      </div>

      <!-- MATRIZ COMPACTA DE 31 TARJETAS DIARIAS (ESTILO CALENDARIO PREMIUM) -->
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-7 gap-3.5">
        <div v-for="dia in dias" :key="dia.fecha" 
             class="bg-white rounded-2xl border border-slate-200/80 p-3.5 shadow-xs flex flex-col justify-between transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md hover:border-emerald-500/40 group relative overflow-hidden min-w-0"
             :class="{'ring-1 ring-emerald-500/30 bg-gradient-to-b from-emerald-50/30 via-white to-white': dia.TOTAL.PEN > 0 || dia.TOTAL.USD > 0 || dia.TOTAL.CLP > 0}">
          
          <div v-if="dia.TOTAL.PEN > 0 || dia.TOTAL.USD > 0 || dia.TOTAL.CLP > 0" class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-500"></div>
          
          <div>
            <!-- Encabezado de la Tarjeta -->
            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
              <span class="font-extrabold text-slate-900 text-xs group-hover:text-emerald-600 transition-colors whitespace-nowrap tracking-tight">
                {{ formatDateShort(dia.fecha) }}
              </span>
              <span class="px-2 py-0.5 rounded-md text-[9px] font-black tracking-wider uppercase whitespace-nowrap flex items-center gap-1"
                    :class="(dia.TOTAL.PEN > 0 || dia.TOTAL.USD > 0 || dia.TOTAL.CLP > 0) ? 'bg-emerald-100/80 text-emerald-700' : 'bg-slate-100 text-slate-400'">
                <i class="bi" :class="(dia.TOTAL.PEN > 0 || dia.TOTAL.USD > 0 || dia.TOTAL.CLP > 0) ? 'bi-check-circle-fill text-[8px]' : 'bi-dash-circle text-[8px]'"></i>
                {{ (dia.TOTAL.PEN > 0 || dia.TOTAL.USD > 0 || dia.TOTAL.CLP > 0) ? 'ACTIVO' : 'SIN MOVS' }}
              </span>
            </div>

            <!-- Métricas Comprimidas -->
            <div class="space-y-1.5 text-[11px] font-medium">
              <!-- Soles -->
              <div class="flex items-center justify-between p-1.5 px-2 rounded-xl bg-slate-50/90 group-hover:bg-emerald-50/60 transition-colors">
                <span class="flex items-center gap-1 text-slate-500 font-semibold whitespace-nowrap flex-shrink-0 text-[10.5px]">
                  <span>🇵🇪</span> Soles
                </span>
                <span class="font-black text-slate-900 text-[11.5px] whitespace-nowrap pl-1">
                  S/ {{ formatMoney(dia.TOTAL.PEN) }}
                </span>
              </div>

              <!-- Dólares -->
              <div class="flex items-center justify-between p-1.5 px-2 rounded-xl bg-slate-50/90 group-hover:bg-emerald-50/60 transition-colors">
                <span class="flex items-center gap-1 text-slate-500 font-semibold whitespace-nowrap flex-shrink-0 text-[10.5px]">
                  <span>🇺🇸</span> Dólares
                </span>
                <span class="font-black text-blue-600 text-[11.5px] whitespace-nowrap pl-1">
                  $ {{ formatMoney(dia.TOTAL.USD) }}
                </span>
              </div>

              <!-- Pesos -->
              <div class="flex items-center justify-between p-1.5 px-2 rounded-xl bg-slate-50/90 group-hover:bg-emerald-50/60 transition-colors">
                <span class="flex items-center gap-1 text-slate-500 font-semibold whitespace-nowrap flex-shrink-0 text-[10.5px]">
                  <span>🇨🇱</span> Pesos
                </span>
                <span class="font-black text-emerald-600 text-[11.5px] whitespace-nowrap pl-1">
                  $ {{ formatMoney(dia.TOTAL.CLP, 0) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Retiros / Extracciones Comprimidos -->
          <div v-if="getDetalleExtractions(dia)" class="mt-3 pt-2 border-t border-dashed border-rose-200">
            <div class="bg-rose-50 border border-rose-100 rounded-xl p-2 text-rose-700 text-[11px] flex items-start gap-1.5 group-hover:bg-rose-100/60 transition-colors">
              <i class="bi bi-box-arrow-up-right text-rose-500 text-xs mt-0.5 flex-shrink-0"></i>
              <div class="font-bold tracking-tight text-ellipsis overflow-hidden">
                <div class="text-[9px] uppercase font-black tracking-wider text-rose-500 mb-0.5 whitespace-nowrap">Retiros Registrados</div>
                <span :title="getDetalleExtractions(dia)" class="line-clamp-2 text-[10.5px] leading-tight">
                  {{ getDetalleExtractions(dia) }}
                </span>
              </div>
            </div>
          </div>

          <div v-else class="mt-3 pt-2 border-t border-slate-100 text-center">
            <span class="text-[10px] text-slate-300 uppercase tracking-wider font-bold whitespace-nowrap">Sin retiros del sobre</span>
          </div>
        </div>
      </div>

      <!-- Panel Debug -->
      <div v-if="debug" class="bg-slate-900 text-slate-200 rounded-3xl p-6 shadow-2xl overflow-hidden font-mono text-xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-4">
          <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-indigo-500"></span>
            <span class="font-bold text-white text-sm">Terminal Debug - JSON Crudo</span>
          </div>
          <button @click="debug = false" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-3 py-1.5 rounded-xl transition-colors font-sans font-bold">
            Cerrar Terminal
          </button>
        </div>
        <div class="text-slate-400">
          <span class="text-indigo-400">[URL Endpoint]</span> {{ lastUrl }}
        </div>
        <pre class="bg-slate-950 p-4 rounded-2xl max-h-96 overflow-auto text-emerald-400 leading-relaxed">{{ lastResponseStr }}</pre>
      </div>

    </div>
  </main>
</div>

<script>
  const SERVER_FECHA = '<?= $fecha ?>';
  window.SOBRES_CONFIG = {
      apiEndpoint: <?= json_encode(project_base_url() . 'api/flujo.php') ?>,
      imprimirUrl: <?= json_encode(project_base_url() . 'app/Views/sobres/imprimir_mensual.php') ?>
  };
</script>
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="<?= $_root ?>app/Views/sobres/index.js"></script>

<style>
  [v-cloak] { display: none !important; }
  /* Ocultar barra de desplazamiento en tarjetas si es necesario */
  ::-webkit-scrollbar { width: 6px; height: 6px; }
  ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
  ::-webkit-scrollbar-track { background: transparent; }
</style>

<?php include $_projectRoot . '/app/Views/layouts/footer.php'; ?>