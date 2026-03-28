<?php
/**
 * app/Views/cuadro_reservas/index.php
 */
$base = '../../../';
require_once $base . 'auth/middleware.php';
protegerPorRol('cajera');

$page_title = 'Cuadro de Reservas — Hotel Manager';
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';
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
    background: rgba(0,0,0,.14);
    border-radius: 8px;
    padding: 1px 4px;
    display: inline-block;
    font-weight: 700;
    margin-top: 1px;
  }

  /* ── View-mode row heights ─────────────────────────────── */
  .vm-compact tbody tr  { height: 26px; }
  .vm-normal  tbody tr  { height: 36px; }
  .vm-ampliado tbody tr { height: 50px; }

  /* ── Colors ────────────────────────────────────────────── */
  .est-pendiente  { background: #FFCCCC; color: #7A0000; }
  .est-adelanto   { background: #FFF176; color: #5D4000; }
  .est-parcial    { background: #FFB74D; color: #4E2200; }
  .est-pagado     { background: #A5D6A7; color: #1B5E20; }
  .est-limpieza   { background: #9E9E9E; color: #fff; }
  .est-bloqueado  { background: #BDBDBD; color: #333; }
  .est-mantenimiento {
    background: repeating-linear-gradient(
      45deg, #ccc, #ccc 4px, #e9e9e9 4px, #e9e9e9 9px
    );
    color: #333;
  }
  .est-late_checkout { background: #CE93D8; color: #4A148C; }
  
  /* Colores por Canal */
  .canal-booking { background: #FF9800 !important; color: #fff !important; }
  .canal-llamada { background: #4CAF50 !important; color: #fff !important; }
  .canal-booking .titular, .canal-llamada .titular { color: #fff !important; }
  .canal-booking .badge-pax, .canal-llamada .badge-pax { background: rgba(255,255,255,0.3); color: #fff; }

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
  }
  .mobile-list { display: none; }

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
</style>

<div class="main-content" id="app-reservas">
  <div class="topbar border-bottom-0 shadow-sm" style="background: linear-gradient(to right, #ffffff, #f8f9fa);">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list fs-4"></i></button>
    <div>
      <h4 class="fw-bold" style="color: #111; letter-spacing: -0.5px;">
        <i class="bi bi-calendar3 me-2" style="color: #d4af37;"></i>Cuadro de Reservas
      </h4>
      <p class="mb-0 small text-muted fw-semibold" style="letter-spacing: 0.5px;">Vista mensual — Tiempo real</p>
    </div>
    <div class="d-flex align-items-center gap-2 ms-auto">
      <span class="badge" style="background: #111; color: #d4af37; font-size:10px; border: 1px solid #d4af37;">
        <i class="bi bi-arrow-repeat me-1"></i> Actualizado hace {{ segsActualizado }}s
      </span>
      <button class="btn btn-sm btn-outline-dark" @click="cargarDatos" title="Recargar"><i class="bi bi-arrow-clockwise"></i></button>
    </div>
  </div>

  <div class="page-body">

    <!-- RESUMEN -->
    <div class="summary-pills">
      <div class="s-pill"><span class="cnt" style="color: #111;">{{ resumen.ocupadas }}<small style="font-size:12px; color:#aaa;">/{{ resumen.total }}</small></span><span class="lbl">🏠 Ocupadas</span></div>
      <div class="s-pill"><span class="cnt">{{ resumen.pax_total }}</span><span class="lbl">👥 PAX Hoy</span></div>
      <div class="s-pill"><span class="cnt text-success">S/{{ ingresos }}</span><span class="lbl">💰 Ingresos</span></div>
      <div class="s-pill"><span class="cnt text-danger">{{ resumen.pendientes }}</span><span class="lbl">⏳ Pendientes</span></div>
      <div class="s-pill"><span class="cnt" style="color:#7A0000;">{{ resumen.cnt_pendiente }}</span><span class="lbl">🔴 Pendiente</span></div>
      <div class="s-pill"><span class="cnt" style="color:#5D4000;">{{ resumen.cnt_adelanto }}</span><span class="lbl">🟡 Adelanto</span></div>
      <div class="s-pill"><span class="cnt" style="color:#4E2200;">{{ resumen.cnt_parcial }}</span><span class="lbl">🟠 Parcial</span></div>
      <div class="s-pill"><span class="cnt" style="color:#1B5E20;">{{ resumen.cnt_pagado }}</span><span class="lbl">🟢 Pagado</span></div>
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

      <div class="divider"></div>
      
      <span class="small ms-2 fw-bold">CANALES:</span>
      <span class="small"><span class="legend-dot canal-booking"></span>Booking (Naranja)</span>
      <span class="small"><span class="legend-dot canal-llamada"></span>Llamada (Verde)</span>

      <div class="divider"></div>

      <!-- Leyenda inline -->
      <span class="small"><span class="legend-dot est-pendiente"></span>Pendiente</span>
      <span class="small"><span class="legend-dot est-adelanto"></span>Adelanto</span>
      <span class="small"><span class="legend-dot est-parcial"></span>Parcial</span>
      <span class="small"><span class="legend-dot est-pagado"></span>Pagado</span>
      <span class="small"><span class="legend-dot est-late_checkout"></span>Late CO</span>

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
              <td class="col-hab">
                <span style="font-size:12px; font-weight:700;">#{{ hab.numero }}</span>
                <span class="text-muted ms-1" style="font-size:9px;">{{ hab.tipo }}</span>
              </td>
              <td v-for="d in diasEnMes" :key="d"
                  class="col-day"
                  :class="{ 'today-col': d === hoyDia && mesActual === mesHoy && anioActual === anioHoy }"
                  :style="{ width: colWidth + 'px', height: rowHeight + 'px' }"
                  @click="onCeldaClick(hab, d)">

                <!-- Stay block: only render on first day of stay -->
                <div v-if="esInicioStay(hab, d)"
                     class="stay-block"
                     :class="['est-' + getCeldaStay(hab, d).estado_pago, 'canal-' + (getCeldaStay(hab, d).canal || '').toLowerCase()]"
                     :style="{ width: (getCeldaStay(hab, d).cols * colWidth - 3) + 'px' }"
                     @click.stop="openContextMenu($event, getCeldaStay(hab, d))">
                  <span class="titular">{{ getCeldaStay(hab, d).titular }}</span>
                  <span v-if="viewMode !== 'compacto'" class="badge-pax">{{ getCeldaStay(hab, d).pax }} PAX</span>
                </div>

                <!-- Estado especial sin huésped -->
                <div v-else-if="!getCeldaStay(hab,d) && esDiaEstadoEspecial(hab)"
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

    <!-- VISTA MÓVIL -->
    <div class="mobile-list">
      <div class="card border-0 shadow-sm p-3 mb-2" v-for="stay in staysHoyMovil" :key="stay.id">
        <div class="fw-bold">#{{ stay.hab_numero }} — {{ stay.titular }}</div>
        <div class="text-muted small">{{ stay.pax }} PAX · {{ stay.estado_pago }}</div>
      </div>
      <div v-if="!staysHoyMovil.length" class="text-center text-muted py-4">Sin ocupación hoy</div>
    </div>

  </div><!-- /.page-body -->

  <!-- ─── MODAL DETALLE ───────────────────────────────────── -->
  <div class="modal fade" id="modalDetalleReservas" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content" v-if="staySeleccionado">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-info-circle me-2"></i>Estadía #{{ staySeleccionado.id }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label text-muted small">HUÉSPED TITULAR</label>
              <div class="fw-bold">{{ staySeleccionado.titular }}</div>
            </div>
            <div class="col-md-3">
              <label class="form-label text-muted small">PAX</label>
              <div class="fw-bold">{{ staySeleccionado.pax }}</div>
            </div>
            <div class="col-md-3">
              <label class="form-label text-muted small">CANAL</label>
              <div>{{ staySeleccionado.canal }}</div>
            </div>
            <div class="col-md-4">
              <label class="form-label text-muted small">INGRESO</label>
              <div>{{ staySeleccionado.fecha_inicio }}</div>
            </div>
            <div class="col-md-4">
              <label class="form-label text-muted small">SALIDA</label>
              <div>{{ staySeleccionado.fecha_fin }}</div>
            </div>
            <div class="col-md-4">
              <label class="form-label text-muted small">NOCHES</label>
              <div>{{ staySeleccionado.noches }}</div>
            </div>
            <div class="col-12">
              <label class="form-label text-muted small">ESTADO DE PAGO</label>
              <div class="d-flex justify-content-between small mb-1">
                <span>Cobrado: <strong>{{ staySeleccionado.moneda_pago }} {{ staySeleccionado.total_cobrado.toFixed(2) }}</strong></span>
                <span>Total: <strong>{{ staySeleccionado.moneda_pago }} {{ staySeleccionado.total_pago.toFixed(2) }}</strong></span>
                <span class="badge" :class="badgeClass(staySeleccionado.estado_pago)">{{ staySeleccionado.estado_pago }}</span>
              </div>
              <div class="progress" style="height:8px;">
                <div class="progress-bar" :class="barClass(staySeleccionado.estado_pago)"
                     :style="{ width: porcentajePago(staySeleccionado) + '%' }"></div>
              </div>
            </div>
            <!-- Pago rápido -->
            <div class="col-12 border-top pt-3">
              <div class="fw-bold small mb-2"><i class="bi bi-cash-coin me-1"></i>Pago Rápido</div>
              <div class="row g-2">
                <div class="col-md-3">
                  <input type="number" class="form-control form-control-sm" v-model.number="pagoRapido.monto" placeholder="Monto" min="1">
                </div>
                <div class="col-md-3">
                  <select class="form-select form-select-sm" v-model="pagoRapido.moneda">
                    <option value="PEN">PEN</option>
                    <option value="USD">USD</option>
                    <option value="CLP">CLP</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <select class="form-select form-select-sm" v-model="pagoRapido.metodo">
                    <option value="efectivo">Efectivo</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="yape">Yape</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <button class="btn btn-sm btn-success w-100" @click="guardarPagoRapido" :disabled="loadingPago">
                    <span v-if="loadingPago" class="spinner-border spinner-border-sm me-1"></span>
                    Guardar
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-warning btn-sm" @click="lateCheckout(staySeleccionado)">
            <i class="bi bi-moon-stars me-1"></i>Late Checkout
          </button>
          <button class="btn btn-outline-danger btn-sm" @click="checkout(staySeleccionado)">
            <i class="bi bi-door-open me-1"></i>Checkout
          </button>
          <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $base ?>app/Views/reservas/reservas.js"></script>
