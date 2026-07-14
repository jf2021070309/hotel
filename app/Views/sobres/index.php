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
    corePlugins: {
      preflight: false,
    },
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
  <!-- TOPBAR HEADER -->
  <div class="topbar" style="background-color: #111827; padding: 0.75rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.05);">
    <div class="d-flex align-items-center justify-content-between w-100">
      <div class="d-flex align-items-center gap-3">
        <button class="btn btn-dark btn-sm rounded-circle d-md-none" onclick="handleMenuClick()" style="width:32px;height:32px;padding:0;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.1);border:none;">
          <i class="bi bi-list text-white"></i>
        </button>
        <div class="d-flex align-items-center gap-3">
          <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#10b981,#14b8a6);display:flex;align-items:center;justify-content:center;box-shadow:0 0 15px rgba(16,185,129,0.3);">
            <i class="bi bi-envelope-paper-fill text-white fs-5"></i>
          </div>
          <div>
            <h4 class="fw-bold mb-0 text-white" style="font-size:18px;letter-spacing:-0.5px;">Sobre de Alex</h4>
            <div class="text-white-50" style="font-size:11px;">Control Financiero de Efectivo Físico en Caja</div>
          </div>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <!-- Filtro Mes -->
        <select v-model="mesFiltro" @change="consultar" class="form-select form-select-sm border-0" style="background: rgba(255,255,255,0.1); color: white; width: 110px; font-size: 12px; cursor:pointer;">
            <option v-for="(m, i) in meses" :key="i" :value="i+1" style="color: black;">{{ m }}</option>
        </select>
        
        <!-- Filtro Año -->
        <select v-model="anioFiltro" @change="consultar" class="form-select form-select-sm border-0" style="background: rgba(255,255,255,0.1); color: white; width: 80px; font-size: 12px; cursor:pointer;">
            <option v-for="a in anios" :key="a" :value="a" style="color: black;">{{ a }}</option>
        </select>

        <button class="btn btn-sm btn-outline-light d-flex align-items-center gap-2" @click="consultar" style="font-size:12px;padding:4px 12px;border-color:rgba(255,255,255,0.2);">
          <i class="bi bi-arrow-clockwise"></i>
          <span class="d-none d-md-inline">Actualizar</span>
        </button>
      </div>
    </div>
  </div>

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

      <!-- TABLA TIPO EXCEL -->
      <div class="overflow-x-auto bg-white border border-slate-300 shadow-sm mt-6 mb-8">
        <table class="w-full text-sm text-left border-collapse" style="font-family: Arial, sans-serif;">
          <thead class="text-xs text-black uppercase border-b-2 border-slate-700">
            <tr>
              <th class="border border-slate-400 p-2 text-center text-white font-bold" colspan="2" style="background-color: #557934;">{{ meses[mesFiltro - 1] }}-{{ String(anioFiltro).slice(-2) }}</th>
              <th class="border border-slate-400 p-2 text-center font-bold" style="background-color: #E2EFE2;">PESOS EFECTIVO</th>
              <th class="border border-slate-400 p-2 text-center font-bold" style="background-color: #E2EFE2;">DOLARES</th>
              <th class="border border-slate-400 p-2 text-center font-bold" style="background-color: #E2EFE2;">SOLES EFECTIVO</th>
              <th class="border border-slate-400 p-2 text-left font-bold bg-white min-w-[250px]">NOTA</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="dia in dias" :key="dia.fecha">
              <!-- MAÑANA -->
              <tr class="bg-white hover:bg-slate-50 transition-colors">
                <td class="border border-slate-400 px-3 py-1.5 font-bold text-black text-[13px] uppercase w-24">MAÑANA</td>
                <td class="border border-slate-400 px-3 py-1.5 text-center text-black text-[13px] w-28">{{ formatDateNumeric(dia.fecha) }}</td>
                <td class="border border-slate-400 px-3 py-1.5 text-right text-black text-[13px]">{{ dia.MAÑANA.CLP > 0 ? formatMoney(dia.MAÑANA.CLP, 0) : '' }}</td>
                <td class="border border-slate-400 px-3 py-1.5 text-right text-black text-[13px]">{{ dia.MAÑANA.USD > 0 ? formatMoney(dia.MAÑANA.USD) : '' }}</td>
                <td class="border border-slate-400 px-3 py-1.5 text-right text-black text-[13px]">{{ dia.MAÑANA.PEN > 0 ? 'S/ ' + formatMoney(dia.MAÑANA.PEN) : '' }}</td>
                <td class="border border-slate-400 p-0 relative">
                  <input type="text" v-model="dia.MAÑANA.nota_entrega" :disabled="dia.MAÑANA.flujo_id === 0" class="w-full h-full min-h-[30px] bg-transparent border-0 outline-none focus:ring-2 focus:ring-inset focus:ring-[#557934] px-3 text-black text-[11px] uppercase placeholder-slate-300 disabled:opacity-50" placeholder="...">
                </td>
              </tr>
              <!-- TARDE -->
              <tr class="bg-white hover:bg-slate-50 transition-colors">
                <td class="border border-slate-400 px-3 py-1.5 font-bold text-black text-[13px] uppercase">TARDE</td>
                <td class="border border-slate-400 px-3 py-1.5 text-center text-black text-[13px]">{{ formatDateNumeric(dia.fecha) }}</td>
                <td class="border border-slate-400 px-3 py-1.5 text-right text-black text-[13px]">{{ dia.TARDE.CLP > 0 ? formatMoney(dia.TARDE.CLP, 0) : '' }}</td>
                <td class="border border-slate-400 px-3 py-1.5 text-right text-black text-[13px]">{{ dia.TARDE.USD > 0 ? formatMoney(dia.TARDE.USD) : '' }}</td>
                <td class="border border-slate-400 px-3 py-1.5 text-right text-black text-[13px]">{{ dia.TARDE.PEN > 0 ? 'S/ ' + formatMoney(dia.TARDE.PEN) : '' }}</td>
                <td class="border border-slate-400 p-0 relative">
                  <input type="text" v-model="dia.TARDE.nota_entrega" :disabled="dia.TARDE.flujo_id === 0" class="w-full h-full min-h-[30px] bg-transparent border-0 outline-none focus:ring-2 focus:ring-inset focus:ring-[#557934] px-3 text-black text-[11px] uppercase placeholder-slate-300 disabled:opacity-50" placeholder="...">
                </td>
              </tr>
              <!-- TOTAL -->
              <tr class="hover:bg-[#d5e7d5] transition-colors" style="background-color: #C3D8C3;">
                <td class="border border-slate-400 px-3 py-1.5 font-bold text-black text-[13px] uppercase">TOTAL</td>
                <td class="border border-slate-400 px-3 py-1.5 text-center"></td>
                <td class="border border-slate-400 px-3 py-1.5 text-right text-black text-[13px]">{{ dia.TOTAL.CLP > 0 ? formatMoney(dia.TOTAL.CLP, 0) : '' }}</td>
                <td class="border border-slate-400 px-3 py-1.5 text-right text-black text-[13px]">{{ dia.TOTAL.USD > 0 ? formatMoney(dia.TOTAL.USD) : '' }}</td>
                <td class="border border-slate-400 px-3 py-1.5 text-right text-black text-[13px]">{{ dia.TOTAL.PEN > 0 ? 'S/ ' + formatMoney(dia.TOTAL.PEN) : '' }}</td>
                <td class="border border-slate-400 px-3 py-1.5 text-left text-black text-[11px] font-bold">
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- BOTÓN FLOTANTE GUARDAR CAMBIOS -->
      <transition enter-active-class="transition ease-out duration-300" enter-from-class="transform opacity-0 translate-y-10" enter-to-class="transform opacity-100 translate-y-0" leave-active-class="transition ease-in duration-200" leave-from-class="transform opacity-100 translate-y-0" leave-to-class="transform opacity-0 translate-y-10">
        <div v-if="pendingChanges > 0" class="fixed bottom-8 right-8 z-50">
          <button @click="guardarNotas" :disabled="guardandoNotas" class="bg-[#198754] hover:bg-[#157347] text-white px-5 py-2.5 rounded-full shadow-lg font-bold flex items-center gap-2 transition-all transform hover:scale-105 active:scale-95 border border-white/20">
            <i class="bi" :class="guardandoNotas ? 'bi-arrow-repeat animate-spin' : 'bi-download'"></i>
            Guardar Cambios ({{ pendingChanges }} turnos)
          </button>
        </div>
      </transition>

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
  
  /* Tailwind preflight replacement for #app-sobres ONLY */
  #app-sobres *, #app-sobres ::before, #app-sobres ::after {
    border-width: 0;
    border-style: solid;
    border-color: #e5e7eb;
    box-sizing: border-box;
  }
</style>

<?php include $_projectRoot . '/app/Views/layouts/footer.php'; ?>