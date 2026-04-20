<?php
/**
 * app/Views/cuadro_reservas/index.php
 */
$base = '../../../';
$_projectRoot = dirname(__DIR__, 3);
require_once $_projectRoot . '/auth/middleware.php';
protegerPorRol('cajera', 'reservas');

$page_title = 'Cuadro de Reservas — Hotel Manager';
include $_projectRoot . '/includes/head.php';
include $_projectRoot . '/includes/sidebar.php';
?>

<style>
  /* ── Layout ────────────────────────────────────────────── */
  #app-reservas .page-body {
    padding: 12px 16px;
    display: flex;
    flex-direction: column;
    height: calc(100vh - 60px);  /* fill remaining viewport */
  }
  .cuadro-wrapper {
    overflow-x: auto;
    overflow-y: auto;
    flex: 1 1 auto;     /* grow to fill remaining space */
    border-radius: 0 0 10px 10px;
  }

  /* ── Table ─────────────────────────────────────────────── */
  .cuadro-table {
    border-collapse: separate;
    border-spacing: 0;
    font-size: 11px;
    table-layout: fixed;
  }
  .cuadro-table th,
  .cuadro-table td {
    border: 1px solid #d8d8d8;
    padding: 0;
    white-space: nowrap;
    vertical-align: top;
    box-sizing: border-box;
  }

  /* ── Header row ────────────────────────────────────────── */
  .cuadro-table thead th {
    background: #111111;
    color: #fff;
    text-align: center;
    font-weight: 700;
    font-size: 10px;
    position: sticky;
    top: 0;
    z-index: 20;
  }
  .cuadro-table thead th.col-hab {
    text-align: left;
    padding: 6px 10px;
    position: sticky;
    left: 0;
    z-index: 30;
    background: #111111;
    min-width: 160px;
    width: 160px;
  }
  .cuadro-table thead th.col-day {
    padding: 4px 2px;
  }
  .cuadro-table thead th.col-day.today-hdr {
    background: #A68966;
  }

  /* ── Body rows ─────────────────────────────────────────── */
  .cuadro-table tbody tr:nth-child(even) td.col-hab { background: #f0f0f0; }
  .cuadro-table tbody tr:nth-child(odd)  td.col-hab { background: #f8f8f8; }

  .cuadro-table td.col-hab {
    position: sticky;
    left: 0;
    z-index: 10;
    min-width: 160px;
    width: 160px;
    padding: 4px 8px;
    font-weight: 600;
    border-right: 2px solid #c0c0c0;
    font-size: 11px;
  }
  .cuadro-table td.col-day {
    padding: 1px;
    vertical-align: top;
    position: relative;
    overflow: visible;
  }
  .cuadro-table td.col-day.today-col {
    background: #FFFDE7 !important;
  }

  /* ── Hover Highlighting ─────────────────────────────────── */
  .cuadro-table tbody tr:hover td.col-hab {
    background-color: #f0f0f0 !important;
  }
  .cuadro-table tbody tr:hover td.col-day {
    background-color: rgba(212, 175, 55, 0.04);
  }
  .cuadro-table td.col-day:hover {
    background-color: rgba(212, 175, 55, 0.2) !important;
    outline: 1.5px solid #d4af37;
    outline-offset: -1.5px;
    z-index: 20;
    cursor: cell;
  }
  .cuadro-table td.col-day.today-col:hover {
    background-color: #FFF9C4 !important;
  }

  /* ── Stay block ────────────────────────────────────────── */
  .stay-block {
    border-radius: 3px;
    padding: 2px 5px;
    cursor: pointer;
    overflow: hidden;
    position: absolute;
    top: 1px;
    left: 1px;
    height: calc(100% - 2px);
    display: flex;
    flex-direction: column;
    justify-content: center;
    transition: filter .15s, box-shadow .15s;
    border: 1px solid rgba(0,0,0,.08);
    z-index: 5;
  }
  .stay-progress-container {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: rgba(0,0,0,0.15);
    z-index: 10;
  }
  .stay-progress-bar {
    height: 100%;
    transition: width 0.3s ease;
  }
  .stay-block:hover {
    filter: brightness(.88);
    box-shadow: 0 2px 8px rgba(0,0,0,.18);
    z-index: 15;
  }
  .stay-block .titular {
    font-weight: 700;
    font-size: 10px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
  }
  .stay-block .badge-pax {
    font-size: 8px;
    padding: 1px 0;
    display: inline-block;
    font-weight: 700;
    margin-top: 1px;
  }

  /* ── View-mode row heights ─────────────────────────────── */
  .vm-compact tbody tr  { height: 26px; }
  .vm-normal  tbody tr  { height: 36px; }
  .vm-ampliado tbody tr { height: 50px; }

  /* ── Room Categories ─────────────────────────────────── */
  .cat-triple      { border-left: 5px solid #3F51B5 !important; background: #E8EAF6 !important; }
  .cat-ejecutiva   { border-left: 5px solid #FFA000 !important; background: #FFF8E1 !important; }
  .cat-doble       { border-left: 5px solid #43A047 !important; background: #E8F5E9 !important; }
  .cat-matrimonial { border-left: 5px solid #E91E63 !important; background: #FCE4EC !important; }
  .cat-platinium   { border-left: 5px solid #455A64 !important; background: #ECEFF1 !important; }
  .cat-generic     { border-left: 5px solid #9E9E9E !important; background: #F5F5F5 !important; }

  /* ── Booking Channels Borders & Colors ───────────────── */
  .canal-directo  { background-color: #2E7D32 !important; color: #fff !important; padding: 4px 8px; }
  .canal-booking  { background-color: #003580 !important; color: #fff !important; padding: 4px 8px; }
  .canal-whatsapp { background-color: #25D366 !important; color: #fff !important; padding: 4px 8px; }
  .canal-llamada  { background-color: #0288D1 !important; color: #fff !important; padding: 4px 8px; }

  /* ── States ─────────────────────────────────────────── */
  /* ── New Traffic Light Color System ───────────────────── */
  .res-booking { background: #F57C00 !important; color: #fff !important; border: 1px solid #E65100 !important; }
  .res-directo { background: #2E7D32 !important; color: #fff !important; border: 1px solid #1B5E20 !important; }
  .res-inhouse { background: #0288D1 !important; color: #fff !important; border: 1px solid #01579B !important; }
  
  .res-booking .titular, .res-directo .titular, .res-inhouse .titular { color: #fff !important; }
  .res-booking .badge-pax, .res-directo .badge-pax, .res-inhouse .badge-pax { background: transparent; color: #fff; }
  
  /* Mantener el resto para otros elementos */
  .est-limpieza   { background: #9E9E9E; color: #fff; box-shadow: inset 0 0 10px rgba(0,0,0,0.1); }

  /* ── Summary pills ─────────────────────────────────────── */
  .summary-pills { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px; }
  .s-pill {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 5px 14px;
    display: flex; flex-direction: column; align-items: center;
    box-shadow: 0 1px 3px rgba(0,0,0,.05);
    min-width: 90px;
  }
  .s-pill .cnt  { font-size: 20px; font-weight: 800; line-height: 1; }
  .s-pill .lbl  { font-size: 9px;  color: #999; text-transform: uppercase; letter-spacing: .8px; margin-top: 1px; }

  /* ── Legend ────────────────────────────────────────────── */
  .legend-dot { width: 12px; height: 12px; border-radius: 2px; display: inline-block; vertical-align: middle; margin-right: 3px; }

  /* ── View-mode toggle ──────────────────────────────────── */
  .vm-btn { padding: 3px 10px; font-size: 11px; border-radius: 4px; cursor: pointer; border: 1px solid #ccc; background: #f5f5f5; transition: background .15s; }
  .vm-btn.active { background: #111111; color: #d4af37; border-color: #111111; }

  /* ── Controls bar ──────────────────────────────────────── */
  .controls-bar { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-bottom: 8px; }
  .controls-bar .divider { width: 1px; background: #ddd; height: 24px; }

  /* ── Mobile ────────────────────────────────────────────── */
  @media (max-width: 768px) {
    .cuadro-wrapper { display: none; }
    .mobile-list { display: block !important; }
    #app-reservas .page-body { height: auto; }
  }
  .mobile-list { display: none; padding-bottom: 30px; }
  .mobile-stay-card {
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 12px;
    color: #fff;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    border: none;
    position: relative;
    overflow: hidden;
  }
  .mobile-stay-card:active { transform: scale(0.98); opacity: 0.9; }
  .mobile-stay-card .hab-badge {
    background: rgba(0,0,0,0.2);
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 800;
  }
  .mobile-stay-card .titular {
    font-size: 16px;
    font-weight: 700;
    margin-top: 4px;
    display: block;
    text-transform: uppercase;
  }
  .mobile-stay-card .info-line {
    font-size: 12px;
    opacity: 0.9;
    margin-top: 4px;
    display: flex;
    align-items: center;
    gap: 12px;
  }

  /* Colores Mobile por Canal (Sincronizados con desktop pero sólidos) */
  .m-res-directo { background: linear-gradient(135deg, #2E7D32, #1B5E20) !important; box-shadow: 0 4px 12px rgba(46,125,50,0.3); }
  .m-res-booking  { background: linear-gradient(135deg, #003580, #00224f) !important; box-shadow: 0 4px 12px rgba(0,53,128,0.3); }
  .m-res-whatsapp { background: linear-gradient(135deg, #25D366, #128C7E) !important; box-shadow: 0 4px 12px rgba(37,211,102,0.3); }
  .m-res-llamada  { background: linear-gradient(135deg, #0288D1, #01579B) !important; box-shadow: 0 4px 12px rgba(2,136,209,0.3); }
  .m-res-generic  { background: linear-gradient(135deg, #455A64, #263238) !important; box-shadow: 0 4px 12px rgba(69,90,100,0.3); }

  /* ── Print ─────────────────────────────────────────────── */
  @media print {
    .sidebar, .topbar, .controls-bar, .summary-pills, .page-body > div:not(.card) { display: none !important; }
    .cuadro-wrapper { overflow: visible !important; max-height: none !important; }
    .cuadro-table { font-size: 8px; }
  }

  /* ── Context Menu ──────────────────────────────────────── */
  .context-menu {
    position: fixed;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    z-index: 9999;
    min-width: 160px;
    padding: 6px 0;
    overflow: hidden;
    font-size: 13px;
  }
  .context-menu .cm-item {
    display: block;
    padding: 8px 16px;
    color: #333;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.15s;
  }
  .context-menu .cm-item:hover { background: #f0f0f0; }
  .context-menu .cm-item i { margin-right: 8px; opacity: 0.7; }

  /* ── Custom Modal Design ──────────────────────────────── */
  .modal-info-card {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 12px;
    border: 1px solid #edf2f7;
    height: 100%;
    transition: transform 0.2s;
  }
  .modal-info-card:hover { transform: translateY(-2px); }
  .modal-info-label { font-size: 10px; font-weight: 800; color: #a0aec0; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
  .modal-info-value { font-size: 14px; font-weight: 700; color: #2d3748; }
  
  .payment-section { background: #fff; border-radius: 16px; border: 1.5px solid #e2e8f0; padding: 20px; margin-top: 15px; }
  .quick-pay-card { background: #1a202c; border-radius: 12px; padding: 15px; color: #fff; margin-top: 15px; }
  .quick-pay-card input, .quick-pay-card select { background: rgba(255,255,255,0.1) !important; border: 1px solid rgba(255,255,255,0.2) !important; color: #fff !important; }
  .quick-pay-card input::placeholder { color: rgba(255,255,255,0.5); }
</style>

<div class="main-content" id="app-reservas">
  <div class="topbar border-bottom-0 shadow-sm" style="background: linear-gradient(to right, #ffffff, #f8f9fa);">
    <div class="d-flex align-items-center gap-3">
      <button class="btn-burger" onclick="handleMenuClick()"><i class="bi bi-list fs-4"></i></button>
      <div>
        <h4 class="fw-bold" style="color: #111; letter-spacing: -0.5px;">
          <i class="bi bi-calendar3 me-2" style="color: #d4af37;"></i>Cuadro de Reservas
        </h4>
        <p class="mb-0 small text-muted fw-semibold" style="letter-spacing: 0.5px;">Vista mensual — Tiempo real</p>
      </div>
    </div>
  </div>

  <div class="page-body">

    <!-- RESUMEN Y LEYENDA -->
    <div class="summary-pills align-items-center">

    </div>

    <!-- CONTROLS -->
    <div class="controls-bar">
      <!-- Navegación mes -->
      <button class="btn btn-sm btn-outline-secondary" @click="cambiarMes(-1)"><i class="bi bi-chevron-left"></i></button>
      <select class="form-select form-select-sm" v-model="mesActual" @change="cargarDatos" style="width:120px;">
        <option v-for="(m,i) in meses" :key="i" :value="i+1">{{ m }}</option>
      </select>
      <input type="number" class="form-control form-control-sm" v-model.number="anioActual" @change="cargarDatos" style="width:80px;" min="2020" max="2100">
      <button class="btn btn-sm btn-outline-secondary" @click="cambiarMes(1)"><i class="bi bi-chevron-right"></i></button>
      <button class="btn btn-sm btn-warning fw-bold" @click="irHoy">Hoy</button>

      <div class="divider"></div>

      <!-- Filtros -->
      <select class="form-select form-select-sm" v-model="filtroPiso" style="width:120px;">
        <option value="">Todos pisos</option>
        <option v-for="p in pisos" :key="p" :value="p">Piso {{ p }}</option>
      </select>
      <select class="form-select form-select-sm" v-model="filtroPago" style="width:140px;">
        <option value="">Todos pagos</option>
        <option value="pendiente">🔴 Pendiente</option>
        <option value="adelanto">🟡 Adelanto</option>
        <option value="parcial">🟠 Parcial</option>
        <option value="pagado">🟢 Pagado</option>
      </select>

      <div class="divider"></div>

      <!-- Modos de vista -->
      <span style="font-size:10px; color:#888; font-weight:600;">VISTA:</span>
      <button class="vm-btn" :class="{active: viewMode==='compacto'}" @click="viewMode='compacto'">
        <i class="bi bi-grid-3x3"></i> Compacto
      </button>
      <button class="vm-btn" :class="{active: viewMode==='normal'}" @click="viewMode='normal'">
        <i class="bi bi-grid"></i> Normal
      </button>
      <button class="vm-btn" :class="{active: viewMode==='ampliado'}" @click="viewMode='ampliado'">
        <i class="bi bi-layout-split"></i> Ampliado
      </button>



      <div class="ms-auto">
        <button class="btn btn-sm btn-outline-dark" onclick="window.print()"><i class="bi bi-printer"></i></button>
      </div>
    </div>

    <!-- GRILLA -->
    <div class="card border-0 shadow-sm flex-grow-1 d-flex flex-column" style="border-radius:10px; overflow:hidden; min-height:0;">
      <div class="cuadro-wrapper">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-warning"></div>
          <div class="mt-2 text-muted small">Cargando cuadro...</div>
        </div>

        <table v-else class="cuadro-table w-100" :class="'vm-' + viewMode">
          <colgroup>
            <col style="width:160px; min-width:160px;">
            <col v-for="d in diasEnMes" :key="d" :style="{ width: colWidth + 'px', minWidth: colWidth + 'px' }">
          </colgroup>
          <thead>
            <tr>
              <th class="col-hab" style="padding:6px 10px;">
                {{ meses[mesActual-1] }} {{ anioActual }}
              </th>
              <th v-for="d in diasEnMes" :key="d"
                  class="col-day"
                  :class="{ 'today-hdr': d === hoyDia && mesActual === mesHoy && anioActual === anioHoy }">
                <div style="font-size:11px; font-weight:800;">{{ d }}</div>
                <div style="font-size:8px; opacity:.7;">{{ getDiaSemana(d) }}</div>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="hab in habitacionesFiltradas" :key="hab.id" :style="{ height: rowHeight + 'px' }">
              <td class="col-hab fw-bold" :class="getTipoClass(hab.tipo)">
                <div class="hab-num">#{{ hab.numero }}</div>
                <div class="hab-tipo text-muted mini">{{ hab.tipo }}</div>
              </td>
              <td v-for="d in diasEnMes" :key="d"
                  class="col-day"
                  :class="{ 'today-col': d === hoyDia && mesActual === mesHoy && anioActual === anioHoy }"
                  :style="{ width: colWidth + 'px', height: rowHeight + 'px' }"
                  @click="onCeldaClick(hab, d)">

                <!-- Stay block: only render on first day of stay -->
                <div v-if="esInicioStay(hab, d)"
                     class="stay-block animate__animated animate__fadeIn shadow-sm"
                     :class="getStayColorClass(getCeldaStay(hab, d))"
                     :style="{ width: (calcCols(getCeldaStay(hab, d)) * colWidth - 5) + 'px' }"
                     @click="abrirDetalle(getCeldaStay(hab, d))">
                  <span class="titular">{{ getCeldaStay(hab, d).titular }}</span>
                  <span v-if="viewMode !== 'compacto'" class="badge-pax">{{ getCeldaStay(hab, d).pax }} PAX</span>

                  <!-- Micro-barra de pago -->
                  <div class="stay-progress-container">
                    <div class="stay-progress-bar" 
                         :style="{ width: porcentajePago(getCeldaStay(hab, d)) + '%', backgroundColor: getColorPago(getCeldaStay(hab, d)) }"></div>
                  </div>
                </div>

                <!-- Estado especial sin huésped (Solo hoy) -->
                <div v-else-if="!getCeldaStay(hab,d) && esDiaEstadoEspecial(hab, d)"
                     class="stay-block"
                     :class="'est-' + hab.estado"
                     :style="{ width: (colWidth - 3) + 'px' }">
                  <span class="titular" style="font-size:8px; text-transform:uppercase;">{{ hab.estado }}</span>
                </div>

              </td>
            </tr>
            <tr v-if="habitacionesFiltradas.length === 0">
              <td :colspan="diasEnMes + 1" class="text-center py-4 text-muted">
                Sin habitaciones con los filtros aplicados.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- VISTA MÓVIL (Lista de tarjetas) -->
    <div class="mobile-list px-2 mt-2">
      <div v-for="stay in staysHoyMovil" 
           :key="stay.id" 
           class="mobile-stay-card shadow-sm animate__animated animate__fadeInUp" 
           :class="'m-res-' + (stay.canal || 'directo').toLowerCase()"
           @click="abrirDetalle(stay)">
        
        <div class="d-flex justify-content-between align-items-start">
          <span class="hab-badge">HAB #{{ stay.hab_numero }}</span>
          <i class="bi bi-chevron-right opacity-50"></i>
        </div>
        
        <span class="titular">{{ stay.titular }}</span>
        
        <div class="info-line">
          <span><i class="bi bi-people-fill me-1"></i> {{ stay.pax }} PAX</span>
          <span><i class="bi bi-tag-fill me-1"></i> {{ stay.canal }}</span>
        </div>
      </div>

      <!-- Estado vacío -->
      <div v-if="!loading && !staysHoyMovil.length" class="text-center py-5">
        <div class="mb-3"><i class="bi bi-calendar-x fs-1 text-muted opacity-25"></i></div>
        <h6 class="text-muted fw-bold">Sin ocupación para esta fecha</h6>
        <p class="small text-muted">No se encontraron reservas activas con los filtros actuales.</p>
      </div>
    </div>

  </div><!-- /.page-body -->

  <!-- ─── MODAL RESERVA RÁPIDA ─────────────────────────── -->
  <div class="modal fade" id="modalQuickReserva" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg" style="border-radius:16px;">
        <div class="modal-header bg-dark text-white border-0 py-3" style="border-radius:16px 16px 0 0;">
          <h5 class="modal-title d-flex align-items-center gap-2">
            <i class="bi bi-pencil-square text-warning"></i> Reserva Rápida
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div v-if="formQuick.hab" class="alert alert-secondary py-2 small mb-3">
            <i class="bi bi-door-open me-1"></i> Habitación <strong>#{{ formQuick.hab.numero }}</strong> — {{ formQuick.hab.tipo }}
            <br>
            <i class="bi bi-calendar-event me-1"></i> Inicio: <strong>{{ formQuick.fecha }}</strong>
          </div>
          
          <form @submit.prevent="guardarQuickReserva">
            <div class="mb-3">
              <label class="form-label small fw-bold text-muted">NOMBRE DEL HUÉSPED / EMPRESA</label>
              <input type="text" class="form-control" v-model="formQuick.titular" placeholder="Ej: Juan Pérez" required>
            </div>
            
            <div class="row g-3 mb-3">
              <div class="col-6">
                <label class="form-label small fw-bold text-muted">NOCHES</label>
                <input type="number" class="form-control" v-model.number="formQuick.noches" min="1" max="60" required>
              </div>
              <div class="col-6">
                <label class="form-label small fw-bold text-muted">CANAL</label>
                <select class="form-select" v-model="formQuick.canal" required>
                  <option value="DIRECTO">DIRECTO</option>
                  <option value="LLAMADA">LLAMADA</option>
                  <option value="WHATSAPP">WHATSAPP</option>
                  <option value="BOOKING">BOOKING</option>
                  <option value="CORREO">CORREO</option>
                </select>
              </div>
            </div>
            
            <div class="mb-3">
              <label class="form-label small fw-bold text-muted">OBSERVACIONES / TELÉFONO</label>
              <textarea class="form-control" v-model="formQuick.observaciones" rows="2" placeholder="Notas breves..."></textarea>
            </div>
            
            <div class="mt-4 d-grid">
              <button type="submit" class="btn btn-dark py-2 fw-bold" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                Confirmar Reserva
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- ─── MODAL DETALLE PREMIUM ────────────────────────────── -->
  <div class="modal fade" id="modalDetalleReservas" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;" v-if="staySeleccionado">
        <div class="modal-header border-0 p-4 pb-2" :class="staySeleccionado.estado_pago === 'pagado' ? 'bg-success text-white' : 'bg-dark text-white'">
          <div class="d-flex align-items-center gap-3">
            <div class="bg-white bg-opacity-25 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
              <i class="bi bi-info-circle fs-4"></i>
            </div>
            <div>
              <h5 class="modal-title fw-bold mb-0">Estadía #{{ staySeleccionado.id }}</h5>
              <span class="small opacity-75">Resumen de cuenta y alojamiento</span>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body p-4 pt-4">
          <!-- Bloque Info Principal -->
          <div class="row g-3">
            <div class="col-md-6">
              <div class="modal-info-card">
                <div class="modal-info-label"><i class="bi bi-person-fill me-1"></i> Huésped Titular</div>
                <div class="modal-info-value fs-5">{{ staySeleccionado.titular }}</div>
              </div>
            </div>
            <div class="col-md-3 col-6">
              <div class="modal-info-card">
                <div class="modal-info-label"><i class="bi bi-people-fill me-1"></i> PAX</div>
                <div class="modal-info-value">{{ staySeleccionado.pax }} Personas</div>
              </div>
            </div>
            <div class="col-md-3 col-6">
              <div class="modal-info-card">
                <div class="modal-info-label"><i class="bi bi-tag-fill me-1"></i> Canal</div>
                <div class="modal-info-value">
                  <span class="badge" :class="'canal-' + (staySeleccionado.canal || '').toLowerCase()">{{ staySeleccionado.canal }}</span>
                </div>
              </div>
            </div>

            <!-- Bloque Fechas -->
            <div class="col-md-4 col-6">
              <div class="modal-info-card border-start border-4 border-primary">
                <div class="modal-info-label text-primary">Ingreso</div>
                <div class="modal-info-value"><i class="bi bi-calendar-check me-1"></i> {{ staySeleccionado.fecha_inicio }}</div>
              </div>
            </div>
            <div class="col-md-4 col-6">
              <div class="modal-info-card border-start border-4 border-danger">
                <div class="modal-info-label text-danger">Salida</div>
                <div class="modal-info-value"><i class="bi bi-calendar-x me-1"></i> {{ staySeleccionado.fecha_fin }}</div>
              </div>
            </div>
            <div class="col-md-4 col-12">
              <div class="modal-info-card text-center bg-light">
                <div class="modal-info-label">Duración</div>
                <div class="modal-info-value"><i class="bi bi-moon-stars me-1 text-warning"></i> {{ staySeleccionado.noches }} Noches</div>
              </div>
            </div>

            <!-- SECCIÓN DE PAGO (Regla de Oro) -->
            <div class="col-12 mt-4">
              <div class="payment-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                   <h6 class="fw-bold mb-0 text-uppercase small" style="letter-spacing: 1px;">Estado Financiero</h6>
                   <span class="badge rounded-pill px-3 py-2 fs-6 shadow-sm" :class="badgeClass(staySeleccionado.estado_pago)">
                     {{ staySeleccionado.estado_pago.toUpperCase() }}
                   </span>
                </div>
                
                <div class="row align-items-center g-3">
                  <div class="col-md-4 text-center">
                    <div class="small text-muted mb-1">Pagado</div>
                    <div class="h5 fw-bold text-success mb-0">{{ staySeleccionado.moneda_pago }} {{ formatNumber(staySeleccionado.total_cobrado) }}</div>
                  </div>
                  <div class="col-md-4 text-center border-start border-end">
                     <div class="small text-muted mb-1">Por cobrar</div>
                     <div class="h5 fw-bold text-danger mb-0">{{ staySeleccionado.moneda_pago }} {{ formatNumber(staySeleccionado.total_pago - staySeleccionado.total_cobrado) }}</div>
                  </div>
                  <div class="col-md-4 text-center">
                    <div class="small text-muted mb-1">Total Reserva</div>
                    <div class="h5 fw-bold text-dark mb-0">{{ staySeleccionado.moneda_pago }} {{ formatNumber(staySeleccionado.total_pago) }}</div>
                  </div>
                </div>

                <div class="mt-4">
                  <div class="progress rounded-pill shadow-sm" style="height: 12px; background: #edf2f7;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         :class="barClass(staySeleccionado.estado_pago)"
                         :style="{ width: porcentajePago(staySeleccionado) + '%' }"></div>
                  </div>
                  <div class="text-center mt-2 small text-muted fw-bold">{{ porcentajePago(staySeleccionado) }}% cubierto</div>
                </div>
              </div>
            </div>

            <!-- ACCIÓN: CHECK-IN RÁPIDO (SOLO SI ESTÁ RESERVADO) -->
            <div class="col-12 mt-3" v-if="staySeleccionado.estado === 'reservado'">
              <button class="btn btn-primary w-100 py-3 rounded-4 shadow-lg d-flex align-items-center justify-content-center gap-3 border-0" 
                      @click="realizarCheckin(staySeleccionado)" 
                      style="background: linear-gradient(135deg, #0288D1 0%, #01579B 100%);">
                <div class="bg-white text-primary rounded-circle p-2 d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px;">
                   <i class="bi bi-person-check-fill h4 mb-0"></i>
                </div>
                <div class="text-start text-white">
                   <div class="fw-bold fs-5">REGISTRAR INGRESO</div>
                   <div class="small opacity-75">Marcar entrada del huésped (Check-in)</div>
                </div>
              </button>
            </div>

            <!-- ACCIÓN: GESTIONAR EN ROOMING -->
            <div class="col-12 mt-3" v-if="staySeleccionado.estado_pago !== 'pagado'">
              <button class="btn btn-dark w-100 py-3 rounded-4 shadow-lg d-flex align-items-center justify-content-center gap-3 border-0" 
                      @click="irARooming(staySeleccionado)" 
                      style="background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);">
                <div class="bg-success rounded-circle p-2 d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px;">
                  <i class="bi bi-cash-stack fs-5 text-white"></i>
                </div>
                <div class="text-start">
                  <div class="fw-bold lh-1 text-warning" style="letter-spacing: 0.5px;">GESTIONAR CUENTA Y PAGOS</div>
                  <div class="small opacity-75 lh-1 mt-1 text-white">Ir al módulo oficial de Rooming</div>
                </div>
                <i class="bi bi-arrow-right ms-auto fs-5 text-warning opacity-75"></i>
              </button>
            </div>
          </div>
        </div>

        <div class="modal-footer border-0 p-4 pt-0 gap-2">
          <button class="btn btn-danger rounded-pill px-4 shadow-sm" @click="checkout(staySeleccionado)">
            <i class="bi bi-door-open-fill me-2"></i>Check Out
          </button>
          <button class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ─── CONTEXT MENU ────────────────────────────────────── -->
  <div v-if="ctxMenu.visible" class="context-menu" :style="{ top: ctxMenu.y + 'px', left: ctxMenu.x + 'px' }">
    <div class="cm-item" @click="handleCtxAction('detalle')"><i class="bi bi-info-circle text-primary"></i>Ver Detalles</div>
    <div class="cm-item" @click="handleCtxAction('cobrar')"><i class="bi bi-cash-coin text-success"></i>Cobrar / Pagos</div>
    <div class="cm-item" @click="handleCtxAction('checkout')"><i class="bi bi-door-open text-danger"></i>Hacer Check Out</div>
  </div>

</div><!-- /#app-reservas -->

<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $base ?>app/Views/reservas/reservas.js"></script>
