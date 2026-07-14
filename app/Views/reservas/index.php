<?php
/**
 * app/Views/cuadro_reservas/index.php
 */
$base = '../../../';
$_projectRoot = dirname(__DIR__, 3);
require_once $_projectRoot . '/app/Middleware/auth.php';
protegerPorRol('cajera', 'reservas');

$page_title = 'Cuadro de Reservas — Hotel Manager';
include $_projectRoot . '/app/Views/layouts/head.php';
include $_projectRoot . '/app/Views/layouts/sidebar.php';
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
    flex: 1 1 auto;
    border-radius: 0 0 10px 10px;
    cursor: grab;
    user-select: none;
  }
  .cuadro-wrapper.grabbing {
    cursor: grabbing !important;
  }
  .cuadro-wrapper.grabbing * {
    cursor: grabbing !important;
    user-select: none;
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

  /* ── Footer Totales PAX ────────────────────────────────── */
  .cuadro-table tfoot th,
  .cuadro-table tfoot td {
    background: #111111;
    color: #fff;
    font-weight: 700;
    position: sticky;
    bottom: 0;
    z-index: 25;
    border-top: 2px solid #d4af37;
    height: 34px;
  }
  .cuadro-table tfoot td.col-hab {
    left: 0;
    z-index: 35;
    background: #111111;
    color: #d4af37;
    border-right: 2px solid #c0c0c0;
  }
  .cuadro-table tfoot td.today-col-tot {
    background: #A68966 !important;
  }

  /* ── Hover Highlighting ─────────────────────────────────── */
  .cuadro-table tbody tr:hover {
    position: relative;
    z-index: 9999;
  }
  .cuadro-table tbody tr:hover td.col-hab {
    background-color: #f0f0f0 !important;
  }
  .cuadro-table tbody tr:hover td.col-day {
    background-color: rgba(212, 175, 55, 0.04);
  }
  
  /* When a stay block is hovered anywhere, lower all sticky columns below the tooltip */
  .cuadro-table:has(.stay-block:hover) thead th.col-hab,
  .cuadro-table:has(.stay-block:hover) td.col-hab {
    z-index: 1 !important;
  }
  
  .cuadro-table td.col-day:hover {
    background-color: rgba(212, 175, 55, 0.2) !important;
    outline: 1.5px solid #d4af37;
    outline-offset: -1.5px;
    z-index: 999 !important;
    cursor: cell;
    position: sticky;
    left: auto;
  }
  .cuadro-table td.col-day.today-col:hover {
    background-color: #FFF9C4 !important;
  }

  /* ── Stay block ────────────────────────────────────────── */
  .stay-block {
    border-radius: 4px;
    padding: 4px 6px;
    cursor: pointer;
    position: absolute;
    top: 1px;
    left: 1px;
    height: calc(100% - 2px);
    box-sizing: border-box;
    display: flex;
    flex-direction: row;
    justify-content: flex-start;
    align-items: center;
    gap: 3px;
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
    border-radius: 0 0 4px 4px;
  }
  .stay-progress-bar {
    height: 100%;
    transition: width 0.3s ease;
  }
  .stay-block:hover {
    filter: brightness(.88);
    box-shadow: 0 2px 8px rgba(0,0,0,.18);
    z-index: 50;
    transform: translateZ(10px);
  }
  .stay-block .titular {
    font-weight: 700;
    font-size: 11px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
    display: block;
    width: 100%;
    text-align: left;
    margin: 0;
  }
  .stay-block .badge-pax {
    font-size: 9px;
    padding: 2px 6px;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-weight: 700;
    margin: 0;
    width: fit-content;
    line-height: 1;
  }
  .note-indicator {
    position: absolute;
    top: 0;
    right: 0;
    width: 0;
    height: 0;
    border-top: 10px solid #dc3545;
    border-left: 10px solid transparent;
    z-index: 15;
  }

  .stay-tooltip {
    visibility: hidden;
    opacity: 0;
    position: absolute;
    bottom: 100%;
    left: 10px;
    transform: translateY(5px);
    background: #fff;
    color: #333;
    text-align: left;
    padding: 8px 12px;
    border-radius: 6px;
    z-index: 9999;
    font-size: 11.5px;
    font-weight: 500;
    white-space: pre-wrap;
    width: max-content;
    max-width: 250px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    border: 1px solid #ddd;
    transition: opacity 0.15s, transform 0.15s;
    pointer-events: none;
    line-height: 1.4;
  }
  .stay-tooltip::after {
    content: "";
    position: absolute;
    top: 100%;
    left: 10px;
    border-width: 6px;
    border-style: solid;
    border-color: #fff transparent transparent transparent;
  }
  .stay-tooltip::before {
    content: "";
    position: absolute;
    top: 100%;
    left: 9px;
    border-width: 7px;
    border-style: solid;
    border-color: #ddd transparent transparent transparent;
    z-index: -1;
  }
  .stay-block:hover .stay-tooltip {
    visibility: visible;
    opacity: 1;
    transform: translateZ(10px) translateY(-5px);
  }

  /* ── View-mode row heights ─────────────────────────────── */
  .vm-compact tbody tr  { height: 28px; }
  .vm-normal  tbody tr  { height: 42px; }
  .vm-ampliado tbody tr { height: 56px; }

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
  .res-finalizado { background: #64748b !important; color: #fff !important; border: 1px solid #475569 !important; opacity: 0.85; }
  
  .res-booking .titular, .res-directo .titular, .res-inhouse .titular, .res-finalizado .titular { color: #fff !important; }
  .res-booking .badge-pax, .res-directo .badge-pax, .res-inhouse .badge-pax, .res-finalizado .badge-pax { background: rgba(0,0,0,0.25) !important; color: #fff !important; }
  
  /* Mantener el resto para otros elementos */
  /* Estados: limpieza = plomo, sucio = café, mantenimiento = rojo */
  .est-limpieza { background: #9ca3af; color: #fff; box-shadow: inset 0 0 6px rgba(0,0,0,0.08); }
  .est-sucio { background: #795548; color: #fff; box-shadow: inset 0 0 10px rgba(0,0,0,0.15); }
  .est-mantenimiento { background: #E53935; color: #fff; box-shadow: inset 0 0 10px rgba(0,0,0,0.2); }

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
    border-radius: 8px;
    padding: 7px 8px;
    border: 1px solid #edf2f7;
    height: 100%;
    transition: transform 0.2s;
  }
  .modal-info-card:hover { transform: translateY(-2px); }
  .modal-info-label { font-size: 8px; font-weight: 800; color: #a0aec0; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 2px; }
  .modal-info-value { font-size: 12px; font-weight: 700; color: #2d3748; }
  
  .payment-section { background: #fff; border-radius: 12px; border: 1.5px solid #e2e8f0; padding: 10px; margin-top: 8px; }
  #modalDetalleReservas .btn { font-size: 12px; }
  #modalDetalleReservas .btn .fs-5,
  #modalDetalleReservas .btn .h4,
  #modalDetalleReservas .btn .h5 { font-size: 1rem !important; }
  .reserva-actions-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
  }
  .reserva-action-btn {
    min-height: 44px;
    border-radius: 10px;
    border: none;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-weight: 700;
    font-size: 13px;
    line-height: 1;
    transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
  }
  .reserva-action-btn:hover {
    transform: translateY(-1px);
    filter: brightness(1.1);
  }
  .reserva-action-btn.action-edit {
    background: #fff;
    color: #1f2937;
  }
  .reserva-action-btn.action-edit:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #111827;
  }
  .reserva-action-btn.action-confirm {
    background: linear-gradient(135deg, #0288D1 0%, #01579B 100%);
    color: #fff;
    border-color: transparent;
  }
  .reserva-action-btn.action-reject {
    background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%);
    color: #fff;
    border-color: transparent;
  }
  .reserva-stay-actions {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
  }
  .reserva-stay-actions > .btn {
    width: 100% !important;
    margin: 0 !important;
    min-height: 70px;
    border-radius: 14px;
    padding: 8px 6px !important;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 6px !important;
  }
  .reserva-stay-actions > .btn .text-start {
    text-align: center !important;
  }
  .reserva-stay-actions > .btn .small {
    display: none;
  }
  .reserva-stay-actions > .btn .fw-bold {
    font-size: 10px;
    letter-spacing: .2px;
  }
  .reserva-stay-actions > .btn > div:first-child {
    width: 30px !important;
    height: 30px !important;
  }
  .reserva-stay-actions > .btn.btn-outline-dark:hover {
    background: #f8fafc;
    color: #111827;
    border-color: #cbd5e1;
  }
  .quick-pay-card { background: #1a202c; border-radius: 12px; padding: 15px; color: #fff; margin-top: 15px; }
  .quick-pay-card input, .quick-pay-card select { background: rgba(255,255,255,0.1) !important; border: 1px solid rgba(255,255,255,0.2) !important; color: #fff !important; }
  .quick-pay-card input::placeholder { color: rgba(255,255,255,0.5); }
</style>

<div class="main-content" id="app-reservas">
  <!-- TOPBAR PREMIUM DARK -->
  <div class="topbar" style="background-color:#111827;padding:0.75rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.05);">
    <div class="d-flex align-items-center justify-content-between w-100">
      <div class="d-flex align-items-center gap-3">
        <button class="btn btn-dark btn-sm rounded-circle d-md-none" onclick="handleMenuClick()" style="width:32px;height:32px;padding:0;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.1);border:none;">
          <i class="bi bi-list text-white"></i>
        </button>
        <div class="d-flex align-items-center gap-3">
          <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#f8fafc,#94a3b8);display:flex;align-items:center;justify-content:center;box-shadow:0 0 15px rgba(148,163,184,0.4);">
            <i class="bi bi-calendar3 text-dark fs-5"></i>
          </div>
          <div>
            <h4 class="fw-bold mb-0 text-white" style="font-size:18px;letter-spacing:-0.5px;">Cuadro de Reservas</h4>
            <div class="text-white-50" style="font-size:11px;">Vista anual &mdash; Tiempo real</div>
          </div>
        </div>
      </div>
      <div class="ms-auto d-flex align-items-center gap-2">
        <button class="btn btn-sm btn-outline-light d-flex align-items-center gap-2" @click="cargarDatos" style="font-size:12px;padding:4px 12px;border-color:rgba(255,255,255,0.2);">
          <i class="bi bi-arrow-clockwise"></i>
          <span class="d-none d-md-inline">Actualizar</span>
        </button>
      </div>
    </div>
  </div>

  <div class="page-body">

    <div v-if="activeQuickGuest" class="mb-2 animate__animated animate__headShake">
      <div class="alert alert-dark d-flex align-items-center justify-content-between py-2 px-3 shadow-sm border-0" 
           style="background: #111; border-left: 4px solid #ef4444 !important; border-radius: 12px;">
        <div class="d-flex align-items-center gap-3">
          <div class="bg-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
            <i class="bi bi-person-fill text-white"></i>
          </div>
          <div>
            <div class="text-white fw-bold small" style="letter-spacing: 0.5px;">MODO RESERVA ACTIVO</div>
            <div class="text-white-50" style="font-size: 11px;">Haciendo reserva para: <strong class="text-danger">{{ activeQuickGuest.nombre.toUpperCase() }}</strong></div>
          </div>
        </div>
        <button class="btn btn-sm btn-outline-light border-0 opacity-75" @click="activeQuickGuest = null">
          Cancelar <i class="bi bi-x-circle ms-1"></i>
        </button>
      </div>
    </div>

    <!-- CONTROLS -->
    <div class="controls-bar">
      <!-- Navegación año -->
      <button class="btn btn-sm btn-outline-secondary" @click="cambiarAnio(-1)"><i class="bi bi-chevron-left"></i></button>
      <input type="number" class="form-control form-control-sm text-center fw-bold" v-model.number="anioActual" @change="cargarDatos" style="width:80px;" min="2020" max="2100">
      <button class="btn btn-sm btn-outline-secondary" @click="cambiarAnio(1)"><i class="bi bi-chevron-right"></i></button>
      <button class="btn btn-sm btn-warning fw-bold" @click="irHoy">Hoy</button>

      <div class="divider"></div>

      <input type="date" class="form-control form-control-sm text-center" v-model="fechaBuscador" @change="irAFecha" style="width:125px; cursor: pointer;" title="Buscar fecha en el calendario">

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
            <col v-for="d in diasEnAnio" :key="d" :style="{ width: colWidth + 'px', minWidth: colWidth + 'px' }">
          </colgroup>
          <thead>
            <tr>
              <th class="col-hab" style="padding:6px 10px;">
                Año {{ anioActual }}
              </th>
              <th v-for="d in diasEnAnio" :key="d"
                  :id="'day-hdr-' + d"
                  class="col-day"
                  :class="{ 'today-hdr': d === hoyDia && anioActual === anioHoy }">
                <div style="font-size:11px; font-weight:800;">{{ formatDiaHdr(d) }}</div>
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
              <td v-for="d in diasEnAnio" :key="d"
                  class="col-day"
                  :class="{ 'today-col': d === hoyDia && anioActual === anioHoy }"
                  :style="{ width: colWidth + 'px', height: rowHeight + 'px' }"
                  @click="onCeldaClick(hab, d)">

                <div class="w-100 h-100 position-relative">
                  <!-- Stay block: render on every day of stay independently -->
                  <div v-for="stay in getTodosCeldaStays(hab, d)" :key="stay.id"
                       class="stay-block animate__animated animate__fadeIn shadow-sm"
                       :class="getStayColorClass(stay)"
                       :style="getStayStyle(stay, d, colWidth)"
                       @click.stop="abrirDetalle(stay, hab.numero)">
                       
                    <div v-if="stay.observaciones && stay.observaciones.trim() !== ''" class="note-indicator"></div>
                    
                    <div v-if="stay.observaciones && stay.observaciones.trim() !== ''" class="stay-tooltip text-dark">
                      <strong style="color: #0288D1; display: block; margin-bottom: 3px; font-size: 12px;">Observaciones</strong>
                      {{ stay.observaciones }}
                    </div>
                    
                    <span v-if="viewMode !== 'compacto'" class="badge-pax" style="flex-shrink: 0;">
                      <i class="bi bi-people-fill"></i> {{ stay.pax }} PAX
                    </span>
                    <span class="titular" :style="esBloqueoEspecial(stay) ? 'font-size: 9.5px; letter-spacing: 0.3px; line-height: 1.2;' : ''">
                      {{ esBloqueoEspecial(stay) ? stay.titular.replace(/\[|\]/g, '') : stay.titular }}
                    </span>

                    <!-- Micro-barra de pago -->
                    <div v-if="!esBloqueoEspecial(stay)" class="stay-progress-container">
                      <div class="stay-progress-bar" 
                           :style="{ width: porcentajePago(stay) + '%', backgroundColor: getColorPago(stay) }"></div>
                    </div>
                  </div>

                  <!-- (El estado especial flotante fue removido a pedido del usuario) -->
                </div>

              </td>
            </tr>
            <tr v-if="habitacionesFiltradas.length === 0">
              <td :colspan="diasEnAnio + 1" class="text-center py-4 text-muted">
                Sin habitaciones con los filtros aplicados.
              </td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="row-totales-pax" style="height: 34px;">
              <td class="col-hab fw-bold bg-dark text-warning" style="position: sticky; left: 0; z-index: 35; padding: 6px 10px; font-size: 11px;">
                <i class="bi bi-people-fill me-2" style="color: #d4af37;"></i>TOTAL PAX
              </td>
              <td v-for="d in diasEnAnio" :key="d" 
                  class="col-day text-center fw-bold bg-dark text-white" 
                  :class="{ 'today-col-tot': d === hoyDia && anioActual === anioHoy }"
                  style="vertical-align: middle; padding: 0; font-size: 11px;">
                <span v-if="getPaxTotalDia(d) > 0" class="badge bg-warning text-dark px-2 py-1" style="font-size: 10px;">
                  {{ getPaxTotalDia(d) }}
                </span>
                <span v-else class="text-muted" style="opacity: 0.3;">-</span>
              </td>
            </tr>
          </tfoot>
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
                {{ formQuick.editando ? 'Guardar Cambios' : 'Confirmar Reserva' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- ─── MODAL DETALLE PREMIUM ────────────────────────────── -->
  <div class="modal fade" id="modalDetalleReservas" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
      <div class="modal-content border-0 shadow" style="border-radius: 12px; overflow: hidden;" v-if="staySeleccionado">
        
        <div class="modal-header border-bottom px-4 py-3 bg-white">
          <div class="d-flex flex-column">
            <h5 class="modal-title fw-bold text-dark mb-1" style="font-size: 1.15rem;">
              Estadía #{{ staySeleccionado.id }}
            </h5>
            <span class="text-muted" style="font-size: 0.85rem;">{{ staySeleccionado.titular }}</span>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body px-4 py-3">
          
          <!-- Metadatos (PAX, Canal) -->
          <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-people text-muted"></i>
              <span class="fw-medium text-dark" style="font-size: 0.9rem;">{{ staySeleccionado.pax }} Personas</span>
            </div>
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-tag text-muted"></i>
              <span class="fw-medium text-dark" style="font-size: 0.9rem;">{{ staySeleccionado.canal || 'DIRECTO' }}</span>
            </div>
            <div class="ms-auto">
               <span class="badge rounded-pill bg-light text-dark border fw-medium" style="font-size: 0.75rem;">
                 {{ staySeleccionado.estado.toUpperCase() }}
               </span>
            </div>
          </div>

          <!-- Fechas -->
          <div class="row g-3 mb-4">
            <div class="col-5">
              <div class="text-muted small mb-1" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Check-in</div>
              <div class="fw-bold text-dark">{{ staySeleccionado.fecha_inicio }}</div>
            </div>
            <div class="col-2 d-flex align-items-center justify-content-center">
              <i class="bi bi-arrow-right text-muted opacity-50"></i>
            </div>
            <div class="col-5 text-end">
              <div class="text-muted small mb-1" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Check-out</div>
              <div class="fw-bold text-dark">{{ staySeleccionado.fecha_fin }}</div>
            </div>
          </div>

          <div class="d-flex justify-content-center mb-4">
             <span class="badge bg-light text-muted border px-3 py-2 fw-medium rounded-pill">
               <i class="bi bi-moon-stars me-1"></i> {{ staySeleccionado.noches }} Noches
             </span>
          </div>

          <!-- Finanzas -->
          <div class="bg-light rounded-3 p-3 mb-3 border">
            <div class="d-flex justify-content-between align-items-center mb-3">
               <span class="fw-bold text-dark" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Estado de Cuenta</span>
               <span class="fw-bold" :class="staySeleccionado.estado_pago === 'pagado' ? 'text-success' : 'text-warning'" style="font-size: 0.85rem; text-transform: uppercase;">
                 {{ staySeleccionado.estado_pago }}
               </span>
            </div>
            
            <div class="row text-center mb-3 g-0">
              <div class="col-4">
                <div class="text-muted mb-1" style="font-size: 0.75rem;">Total</div>
                <div class="fw-bold text-dark">{{ staySeleccionado.moneda_pago }} {{ formatNumber(staySeleccionado.total_pago) }}</div>
              </div>
              <div class="col-4 border-start border-end">
                <div class="text-muted mb-1" style="font-size: 0.75rem;">Pagado</div>
                <div class="fw-bold text-dark">{{ staySeleccionado.moneda_pago }} {{ formatNumber(staySeleccionado.total_cobrado) }}</div>
              </div>
              <div class="col-4">
                <div class="text-muted mb-1" style="font-size: 0.75rem;">Saldo</div>
                <div class="fw-bold" :class="(staySeleccionado.total_pago - staySeleccionado.total_cobrado) > 0 ? 'text-danger' : 'text-dark'">
                  {{ staySeleccionado.moneda_pago }} {{ formatNumber(staySeleccionado.total_pago - staySeleccionado.total_cobrado) }}
                </div>
              </div>
            </div>

            <div class="progress rounded-pill" style="height: 4px; background: #e2e8f0;">
              <div class="progress-bar" 
                   :class="staySeleccionado.estado_pago === 'pagado' ? 'bg-success' : 'bg-dark'"
                   :style="{ width: porcentajePago(staySeleccionado) + '%' }"></div>
            </div>
          </div>

          <!-- Acciones -->
          <div class="reserva-actions-grid mt-4" v-if="staySeleccionado.estado === 'reservado' && !esBloqueoEspecial(staySeleccionado)">
             <button class="btn reserva-action-btn text-white shadow-sm" style="background: #64748b;" @click="editarQuickReserva(staySeleccionado)">
               <i class="bi bi-pen-fill"></i>
               <span style="letter-spacing: 0.3px;">Editar</span>
             </button>
             <button class="btn reserva-action-btn text-white shadow-sm" style="background: #111827;" @click="confirmarReserva(staySeleccionado)">
               <i class="bi bi-box-arrow-in-right fs-5"></i>
               <span style="letter-spacing: 0.3px;">Check-in</span>
             </button>
             <button class="btn reserva-action-btn text-white shadow-sm" style="background: #ef4444;" @click="rechazarReserva(staySeleccionado)">
               <i class="bi bi-trash-fill"></i>
               <span style="letter-spacing: 0.3px;">Cancelar</span>
             </button>
          </div>
          
          <div class="reserva-actions-grid mt-4" v-if="esBloqueoEspecial(staySeleccionado)">
             <button class="btn reserva-action-btn text-white shadow-sm w-100" style="background: #10b981;" @click="rechazarReserva(staySeleccionado)">
               <i class="bi bi-check2-circle fs-5"></i>
               <span style="letter-spacing: 0.3px;">Marcar como Libre</span>
             </button>
          </div>

          <div class="mt-4" v-if="staySeleccionado.estado_pago !== 'pagado' && staySeleccionado.estado !== 'reservado' && !esBloqueoEspecial(staySeleccionado)">
             <button class="btn btn-dark w-100 py-2 fw-medium" @click="irARooming(staySeleccionado)">
               <i class="bi bi-receipt me-2"></i> Gestionar Cuenta en Rooming
             </button>
          </div>

        </div>

        <div class="modal-footer bg-light border-top px-4 py-3 d-flex justify-content-between">
           <div>
             <button v-if="!esBloqueoEspecial(staySeleccionado) && (staySeleccionado.estado === 'activo' || staySeleccionado.estado === 'late_checkout')" 
                     class="btn btn-outline-danger fw-medium" 
                     @click="checkout(staySeleccionado)">
               <i class="bi bi-door-open me-2"></i> Registrar Salida
             </button>
           </div>
           <button class="btn btn-secondary fw-medium px-4" data-bs-dismiss="modal">Cerrar</button>
        </div>

      </div>
    </div>
  </div>

  <!-- ─── CONTEXT MENU ────────────────────────────────────── -->
  <div v-if="ctxMenu.visible" class="context-menu" :style="{ top: ctxMenu.y + 'px', left: ctxMenu.x + 'px' }">
    <div class="cm-item" @click="handleCtxAction('detalle')"><i class="bi bi-info-circle text-primary"></i>Ver Detalles</div>
    <div class="cm-item" v-if="!esBloqueoEspecial(ctxMenu.stay)" @click="handleCtxAction('cobrar')"><i class="bi bi-cash-coin text-success"></i>Cobrar / Pagos</div>
    <div class="cm-item" v-if="!esBloqueoEspecial(ctxMenu.stay) && (ctxMenu.stay.estado === 'activo' || ctxMenu.stay.estado === 'late_checkout')" @click="handleCtxAction('checkout')"><i class="bi bi-door-open text-danger"></i>Hacer Check Out</div>
    <div class="cm-item" v-if="esBloqueoEspecial(ctxMenu.stay)" @click="rechazarReserva(ctxMenu.stay)"><i class="bi bi-check2-circle text-success"></i>Marcar Libre</div>
  </div>

</div><!-- /#app-reservas -->

<script>
  const PROJECT_BASE_URL = '<?= project_base_url() ?>';
</script>
<script src="<?= project_base_url() ?>app/Views/reservas/reservas.js?v=<?= filemtime(__DIR__ . '/reservas.js') ?>"></script>
<?php include $_projectRoot . '/app/Views/layouts/footer.php'; ?>
