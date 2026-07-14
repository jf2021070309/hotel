<?php
/**
 * app/Views/flujo/v2.php
 * Módulo de Flujo de Caja Mensual V2 — Grilla completa tipo Excel
 */
$base = '../../../';
$_projectRoot = dirname(__DIR__, 3);
require_once $_projectRoot . '/app/Middleware/auth.php';
require_once $_projectRoot . '/app/Helpers/url.php';
protegerPorRol('cajera', 'flujo');
require_once $_projectRoot . '/config/db.php'; // Asegurar PDO

$page_title = 'Flujo de Caja  — Hotel Manager';
include $_projectRoot . '/app/Views/layouts/head.php';
include $_projectRoot . '/app/Views/layouts/sidebar.php';
?>

<div class="main-content" id="app-flujo-v2" v-cloak>
  <!-- TOPBAR -->
  <div class="topbar" style="background-color: #111827; padding: 0.75rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.05);">
    <div class="d-flex align-items-center justify-content-between w-100">
      <div class="d-flex align-items-center gap-3">
        <button class="btn btn-dark btn-sm rounded-circle d-md-none" onclick="handleMenuClick()" style="width:32px;height:32px;padding:0;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.1);border:none;">
          <i class="bi bi-list text-white"></i>
        </button>
        <div class="d-flex align-items-center gap-3">
          <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#f8fafc,#94a3b8);display:flex;align-items:center;justify-content:center;box-shadow:0 0 15px rgba(148,163,184,0.4);">
            <i class="bi bi-table text-dark fs-5"></i>
          </div>
          <div>
            <h4 class="fw-bold mb-0 text-white" style="font-size:18px;letter-spacing:-0.5px;">Flujo de Caja </h4>
          </div>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <div class="d-flex align-items-center rounded px-2 py-1" style="background-color:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);">
          <select class="form-select form-select-sm border-0 fw-bold text-white bg-transparent py-0 px-2" v-model="filtros.mes" @change="generarCalendario" style="width:auto;font-size:12px;cursor:pointer;outline:none;box-shadow:none;">
            <option v-for="(m, i) in meses" :key="i" :value="i+1" class="text-dark">{{ m }}</option>
          </select>
          <div class="vr mx-1" style="height:16px;background:rgba(255,255,255,0.3);"></div>
          <input type="number" class="form-control form-control-sm border-0 fw-bold text-white bg-transparent py-0 px-1 text-center" v-model="filtros.anio" @change="generarCalendario" style="width:65px;font-size:12px;outline:none;box-shadow:none;" min="2020">
        </div>
        <button class="btn btn-sm btn-outline-light d-flex align-items-center gap-2" @click="cargarDatos" :disabled="loading" style="font-size:12px;padding:4px 12px;border-color:rgba(255,255,255,0.2);">
          <span v-if="loading" class="spinner-border spinner-border-sm"></span>
          <i v-else class="bi bi-arrow-clockwise"></i>
          <span class="d-none d-md-inline">Actualizar</span>
        </button>
      </div>
    </div>
  </div>

  <div class="page-body p-4" style="background-color: #f8fafc;">
    <!-- TABLA DE GRILLA MENSUAL -->
    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden; background: #fff;">
      
      <!-- Cargando Spinner -->
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
        <div class="mt-3 fw-bold text-secondary">Generando y consultando grilla mensual ...</div>
        <p class="text-muted small">Por favor espere mientras procesamos las transacciones del mes.</p>
      </div>

      <!-- Contenedor con scroll vertical y horizontal -->
      <div v-else class="mensual-grid-container table-responsive">
        <table class="table table-bordered table-mensual mb-0">
          <thead>
            <!-- Fila de Cabecera Nivel 1 -->
            <tr>
              <th colspan="2" class="sticky-header" style="background-color: #0f172a; width: 220px; min-width: 220px;">INFORMACIÓN</th>
              <th colspan="7" style="background-color: #1e3a8a; border-left: 2px solid #334155;">INGRESOS</th>
              <th colspan="12" style="background-color: #581c87; border-left: 4px solid #ef4444;" class="separator-header">GASTOS / EGRESOS</th>
            </tr>
            <!-- Fila de Cabecera Nivel 2 -->
            <tr>
              <th class="sticky-col text-center" style="left: 0; width: 100px; min-width: 100px;">TURNO</th>
              <th class="sticky-col-2 text-center" style="left: 100px; width: 120px; min-width: 120px;">FECHA</th>
              
              <!-- Ingresos sub-columns -->
              <th style="background-color: #1e3a8a; color: #93c5fd; min-width: 100px;">DEPOS / TRAN</th>
              <th style="background-color: #1e3a8a; color: #93c5fd; min-width: 100px;">YAPE O PLIN</th>
              <th style="background-color: #1e3a8a; color: #93c5fd; min-width: 100px;">POS DOLARES</th>
              <th style="background-color: #1e3a8a; color: #93c5fd; min-width: 100px;">POS SOLES</th>
              <th style="background-color: #1e3a8a; color: #93c5fd; min-width: 110px;">PESOS EFECT.</th>
              <th style="background-color: #1e3a8a; color: #93c5fd; min-width: 90px;">DOLARES EF.</th>
              <th style="background-color: #1e3a8a; color: #93c5fd; min-width: 110px;">SOLES EFECT.</th>
              
              <!-- Egresos sub-columns -->
              <th style="background-color: #581c87; color: #e9d5ff; min-width: 100px; border-left: 4px solid #ef4444;" class="separator-col-head">MERCADO</th>
              <th style="background-color: #581c87; color: #e9d5ff; min-width: 90px;">MOVILIDAD</th>
              <th style="background-color: #581c87; color: #e9d5ff; min-width: 120px;">CAFETERÍA</th>
              <th style="background-color: #581c87; color: #e9d5ff; min-width: 100px;">LAVANDERÍA</th>
              <th style="background-color: #581c87; color: #e9d5ff; min-width: 110px;">ÚTILES ESCR.</th>
              <th style="background-color: #581c87; color: #e9d5ff; min-width: 110px;">RECEPCIÓN CC</th>
              <th style="background-color: #581c87; color: #e9d5ff; min-width: 110px;">REPUESTOS</th>
              <th style="background-color: #581c87; color: #e9d5ff; min-width: 110px;">PERSONAL</th>
              <th style="background-color: #581c87; color: #e9d5ff; min-width: 100px;">OTROS</th>
              
              <!-- Totales -->
              <th style="background-color: #8b1e3f; color: #fbcfe8; min-width: 115px;">TOTAL EGRESO</th>
              <th style="background-color: #78350f; color: #fde68a; min-width: 115px;">TOTAL A ENTRE</th>
              <th style="background-color: #0f172a; color: #cbd5e1; min-width: 140px;">SE ENTREGA A</th>
            </tr>
          </thead>
          <tbody>
            <!-- Loop de días del mes -->
            <template v-for="d in diasGrid" :key="d.fecha">
              
              <!-- Fila Turno MAÑANA -->
              <tr :id="'row-manana-' + d.fecha" :class="{'table-active-row': d.manana.flujo_id !== null, 'today-row-highlight': d.fecha === obtenerFechaHoy()}">
                <td class="sticky-col fw-semibold text-center" style="left: 0;">
                  <span class="badge bg-light text-dark border"><i class="bi bi-sun-fill text-warning me-1"></i>MAÑAN</span>
                </td>
                <td class="sticky-col-2 text-center" style="left: 100px;">{{ d.fecha_formateada }}</td>
                
                <!-- Ingresos MAÑANA -->
                <td v-for="(campo, idx) in ['depo', 'yape', 'pos_usd', 'pos_pen', 'pesos', 'usd_ef', 'pen_ef']"
                    :key="'in_m_'+campo"
                    class="num-cell position-relative" 
                    :class="{'zero-val': d.manana[campo] === 0, 'has-details': d.manana.detalles[campo].length > 0, 'fw-bold text-success': campo === 'pen_ef'}" 
                    @click="abrirMenuHabitaciones(d, d.manana, 'MAÑANA', campo)">
                  {{ d.manana[campo] ? (campo === 'pos_usd' || campo === 'usd_ef' ? '$ ' : (campo === 'pesos' ? 'CLP ' : 'S/ ')) + formatearNumero(d.manana[campo]) : '-' }}
                  <div v-if="d.manana.detalles[campo].length > 0" class="flujo-tooltip text-dark" v-html="getTooltipHtml(d.manana.detalles[campo])"></div>
                </td>
                
                <!-- Egresos MAÑANA -->
                <td v-for="campo in ['mercado', 'movilidad', 'cafeteria', 'lavanderia', 'utiles', 'recepcion', 'repuestos', 'personal', 'otros_eg']" 
                    :key="'m_'+campo" 
                    class="num-cell text-danger p-0 align-middle" 
                    :class="{'zero-val': d.manana[campo] === 0, 'separator-col': campo === 'mercado'}">
                  
                  <div v-if="edicionActiva !== d.manana.flujo_id + '_' + campo" 
                       class="w-100 h-100 px-2 py-2" 
                       style="min-height: 28px;"
                       @click="iniciarEdicion(d.manana, campo)">
                    {{ d.manana[campo] ? 'S/ ' + formatearNumero(d.manana[campo]) : '-' }}
                  </div>
                  
                  <input v-else type="number" step="0.01"
                         class="form-control border-0 text-end text-danger fw-bold shadow-none p-1 m-0 h-100 w-100" 
                         v-model.number="d.manana[campo]" 
                         @blur="finalizarEdicion(d, 'manana')" 
                         @keyup.enter="finalizarEdicion(d, 'manana')" 
                         v-focus>
                </td>
                
                <!-- Totales MAÑANA -->
                <td class="num-cell fw-bold text-danger">{{ d.manana.total_egreso ? 'S/ ' + formatearNumero(d.manana.total_egreso) : '-' }}</td>
                <td class="num-cell fw-bold text-dark bg-light">{{ d.manana.total_entregar ? 'S/ ' + formatearNumero(d.manana.total_entregar) : '-' }}</td>
                <td class="text-truncate text-muted text-center" style="max-width: 140px; font-size: 11px;">
                  <span v-if="d.manana.flujo_id" class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                    {{ d.manana.nota_entrega || d.manana.operador || 'Cerrado' }}
                  </span>
                  <span v-else>-</span>
                </td>
                
              </tr>
              
              <!-- Fila Turno TARDE -->
              <tr :id="'row-tarde-' + d.fecha" :class="{'table-active-row': d.tarde.flujo_id !== null, 'today-row-highlight': d.fecha === obtenerFechaHoy()}">
                <td class="sticky-col fw-semibold text-center" style="left: 0;">
                  <span class="badge bg-light text-dark border"><i class="bi bi-moon-stars-fill text-primary me-1"></i>TARDE</span>
                </td>
                <td class="sticky-col-2 text-center" style="left: 100px;">{{ d.fecha_formateada }}</td>
                
                <!-- Ingresos TARDE -->
                <td v-for="(campo, idx) in ['depo', 'yape', 'pos_usd', 'pos_pen', 'pesos', 'usd_ef', 'pen_ef']"
                    :key="'in_t_'+campo"
                    class="num-cell position-relative" 
                    :class="{'zero-val': d.tarde[campo] === 0, 'has-details': d.tarde.detalles[campo].length > 0, 'fw-bold text-success': campo === 'pen_ef'}" 
                    @click="abrirMenuHabitaciones(d, d.tarde, 'TARDE', campo)">
                  {{ d.tarde[campo] ? (campo === 'pos_usd' || campo === 'usd_ef' ? '$ ' : (campo === 'pesos' ? 'CLP ' : 'S/ ')) + formatearNumero(d.tarde[campo]) : '-' }}
                  <div v-if="d.tarde.detalles[campo].length > 0" class="flujo-tooltip text-dark" v-html="getTooltipHtml(d.tarde.detalles[campo])"></div>
                </td>
                
                <!-- Egresos TARDE -->
                <td v-for="campo in ['mercado', 'movilidad', 'cafeteria', 'lavanderia', 'utiles', 'recepcion', 'repuestos', 'personal', 'otros_eg']" 
                    :key="'t_'+campo" 
                    class="num-cell text-danger p-0 align-middle" 
                    :class="{'zero-val': d.tarde[campo] === 0, 'separator-col': campo === 'mercado'}">
                  
                  <div v-if="edicionActiva !== d.tarde.flujo_id + '_' + campo" 
                       class="w-100 h-100 px-2 py-2" 
                       style="min-height: 28px;"
                       @click="iniciarEdicion(d.tarde, campo)">
                    {{ d.tarde[campo] ? 'S/ ' + formatearNumero(d.tarde[campo]) : '-' }}
                  </div>
                  
                  <input v-else type="number" step="0.01"
                         class="form-control border-0 text-end text-danger fw-bold shadow-none p-1 m-0 h-100 w-100" 
                         v-model.number="d.tarde[campo]" 
                         @blur="finalizarEdicion(d, 'tarde')" 
                         @keyup.enter="finalizarEdicion(d, 'tarde')" 
                         v-focus>
                </td>
                
                <!-- Totales TARDE -->
                <td class="num-cell fw-bold text-danger">{{ d.tarde.total_egreso ? 'S/ ' + formatearNumero(d.tarde.total_egreso) : '-' }}</td>
                <td class="num-cell fw-bold text-dark bg-light">{{ d.tarde.total_entregar ? 'S/ ' + formatearNumero(d.tarde.total_entregar) : '-' }}</td>
                <td class="text-truncate text-muted text-center" style="max-width: 140px; font-size: 11px;">
                  <span v-if="d.tarde.flujo_id" class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                    {{ d.tarde.nota_entrega || d.tarde.operador || 'Cerrado' }}
                  </span>
                  <span v-else>-</span>
                </td>
                
              </tr>
              
              <!-- Fila de TOTAL DEL DÍA -->
              <tr class="day-total">
                <td class="sticky-col text-center" style="left: 0;">TOTAL</td>
                <td class="sticky-col-2 text-center" style="left: 100px;">{{ d.fecha_formateada }}</td>
                
                <!-- Ingresos TOTAL DÍA -->
                <td class="num-cell">{{ d.total.depo ? 'S/ ' + formatearNumero(d.total.depo) : '-' }}</td>
                <td class="num-cell">{{ d.total.yape ? 'S/ ' + formatearNumero(d.total.yape) : '-' }}</td>
                <td class="num-cell">{{ d.total.pos_usd ? '$ ' + formatearNumero(d.total.pos_usd) : '-' }}</td>
                <td class="num-cell">{{ d.total.pos_pen ? 'S/ ' + formatearNumero(d.total.pos_pen) : '-' }}</td>
                <td class="num-cell">{{ d.total.pesos ? 'CLP ' + formatearNumero(d.total.pesos) : '-' }}</td>
                <td class="num-cell">{{ d.total.usd_ef ? '$ ' + formatearNumero(d.total.usd_ef) : '-' }}</td>
                <td class="num-cell fw-bold text-success">{{ d.total.pen_ef ? 'S/ ' + formatearNumero(d.total.pen_ef) : '-' }}</td>
                
                <!-- Egresos TOTAL DÍA -->
                <td class="num-cell separator-col">{{ d.total.mercado ? 'S/ ' + formatearNumero(d.total.mercado) : '-' }}</td>
                <td class="num-cell">{{ d.total.movilidad ? 'S/ ' + formatearNumero(d.total.movilidad) : '-' }}</td>
                <td class="num-cell">{{ d.total.cafeteria ? 'S/ ' + formatearNumero(d.total.cafeteria) : '-' }}</td>
                <td class="num-cell">{{ d.total.lavanderia ? 'S/ ' + formatearNumero(d.total.lavanderia) : '-' }}</td>
                <td class="num-cell">{{ d.total.utiles ? 'S/ ' + formatearNumero(d.total.utiles) : '-' }}</td>
                <td class="num-cell">{{ d.total.recepcion ? 'S/ ' + formatearNumero(d.total.recepcion) : '-' }}</td>
                <td class="num-cell">{{ d.total.repuestos ? 'S/ ' + formatearNumero(d.total.repuestos) : '-' }}</td>
                <td class="num-cell">{{ d.total.personal ? 'S/ ' + formatearNumero(d.total.personal) : '-' }}</td>
                <td class="num-cell">{{ d.total.otros_eg ? 'S/ ' + formatearNumero(d.total.otros_eg) : '-' }}</td>
                
                <!-- Totales TOTAL DÍA -->
                <td class="num-cell fw-bold text-danger">{{ d.total.total_egreso ? 'S/ ' + formatearNumero(d.total.total_egreso) : '-' }}</td>
                <td class="num-cell fw-bold text-dark" style="background-color: #fde68a !important;">{{ d.total.total_entregar ? 'S/ ' + formatearNumero(d.total.total_entregar) : '-' }}</td>
                <td class="text-center">-</td>
              </tr>
            </template>
            
            <!-- Fila de TOTAL GENERAL DEL MES (FOOTER) -->
            <tr class="total-general">
              <td class="sticky-col text-center" style="left: 0;">TOTAL GENER</td>
              <td class="sticky-col-2 text-center" style="left: 100px;">MES COMPLETO</td>
              
              <!-- Ingresos TOTAL GENERAL -->
              <td class="num-cell">{{ totalesGenerales.depo ? 'S/ ' + formatearNumero(totalesGenerales.depo) : '-' }}</td>
              <td class="num-cell">{{ totalesGenerales.yape ? 'S/ ' + formatearNumero(totalesGenerales.yape) : '-' }}</td>
              <td class="num-cell">{{ totalesGenerales.pos_usd ? '$ ' + formatearNumero(totalesGenerales.pos_usd) : '-' }}</td>
              <td class="num-cell">{{ totalesGenerales.pos_pen ? 'S/ ' + formatearNumero(totalesGenerales.pos_pen) : '-' }}</td>
              <td class="num-cell">{{ totalesGenerales.pesos ? 'CLP ' + formatearNumero(totalesGenerales.pesos) : '-' }}</td>
              <td class="num-cell">{{ totalesGenerales.usd_ef ? '$ ' + formatearNumero(totalesGenerales.usd_ef) : '-' }}</td>
              <td class="num-cell fw-bold text-white">{{ totalesGenerales.pen_ef ? 'S/ ' + formatearNumero(totalesGenerales.pen_ef) : '-' }}</td>
              
              <!-- Egresos TOTAL GENERAL -->
              <td class="num-cell separator-col">{{ totalesGenerales.mercado ? 'S/ ' + formatearNumero(totalesGenerales.mercado) : '-' }}</td>
              <td class="num-cell">{{ totalesGenerales.movilidad ? 'S/ ' + formatearNumero(totalesGenerales.movilidad) : '-' }}</td>
              <td class="num-cell">{{ totalesGenerales.cafeteria ? 'S/ ' + formatearNumero(totalesGenerales.cafeteria) : '-' }}</td>
              <td class="num-cell">{{ totalesGenerales.lavanderia ? 'S/ ' + formatearNumero(totalesGenerales.lavanderia) : '-' }}</td>
              <td class="num-cell">{{ totalesGenerales.utiles ? 'S/ ' + formatearNumero(totalesGenerales.utiles) : '-' }}</td>
              <td class="num-cell">{{ totalesGenerales.recepcion ? 'S/ ' + formatearNumero(totalesGenerales.recepcion) : '-' }}</td>
              <td class="num-cell">{{ totalesGenerales.repuestos ? 'S/ ' + formatearNumero(totalesGenerales.repuestos) : '-' }}</td>
              <td class="num-cell">{{ totalesGenerales.personal ? 'S/ ' + formatearNumero(totalesGenerales.personal) : '-' }}</td>
              <td class="num-cell">{{ totalesGenerales.otros_eg ? 'S/ ' + formatearNumero(totalesGenerales.otros_eg) : '-' }}</td>
              
              <!-- Totales TOTAL GENERAL -->
              <td class="num-cell fw-bold text-white" style="background-color: #991b1b !important;">{{ totalesGenerales.total_egreso ? 'S/ ' + formatearNumero(totalesGenerales.total_egreso) : '-' }}</td>
              <td class="num-cell fw-bold text-white" style="background-color: #92400e !important;">{{ totalesGenerales.total_entregar ? 'S/ ' + formatearNumero(totalesGenerales.total_entregar) : '-' }}</td>
              <td class="text-center">-</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- MODAL AÑADIR CONSUMO RAPIDO -->
  <!-- ... (omitido para brevedad visual si no hay cambios aquí, pero no toco el modal) ... -->
  <div class="modal fade" id="modalAddConsumoFlujo" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header bg-dark text-white border-0 py-2">
          <h6 class="modal-title fw-bold">Añadir Consumo - {{ formConsumo.turnoName }}</h6>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-3">
              <div class="mb-3">
                <label class="form-label text-muted small fw-bold mb-1">Seleccionar Habitación</label>
                <select class="form-select form-select-sm fw-bold" v-model="formConsumo.stay_id">
                  <option value="">- Seleccione -</option>
                  <optgroup v-if="staysEnCelda.length > 0" label="En este pago">
                    <option v-for="h in staysEnCelda" :key="'celda_'+h.id" :value="h.id">HAB {{ h.hab_numero }} - {{ h.huesped_principal }}</option>
                  </optgroup>
                </select>
              </div>
           <div class="mb-3">
             <label class="form-label fw-bold text-secondary" style="font-size:12px;">Tipo de Consumo</label>
             <select class="form-select form-select-sm fw-bold" v-model="formConsumo.tipo">
               <option value="BEBIDA">Bebida (Frigobar)</option>
               <option value="DESAYUNO">Desayuno Buffet</option>
             </select>
           </div>
           
           <div v-if="formConsumo.tipo === 'BEBIDA'" class="mb-3">
             <label class="form-label fw-bold text-secondary" style="font-size:12px;">Producto</label>
             <select class="form-select form-select-sm fw-bold" v-model="formConsumo.producto_id" @change="onProductoChange">
               <option value="">- Seleccione Bebida -</option>
               <option v-for="p in productosRefri" :key="p.id" :value="p.id">{{ p.nombre }} - S/ {{ p.precio_venta }}</option>
             </select>
           </div>
           
           <div class="mb-3">
             <label class="form-label fw-bold text-secondary" style="font-size:12px;">Medio de Pago / Destino</label>
             <select class="form-select form-select-sm fw-bold" v-model="formConsumo.columna">
               <option value="pen_ef">Efectivo Soles</option>
               <option value="usd_ef">Efectivo Dólares</option>
               <option value="pesos">Efectivo Pesos</option>
               <option value="pos_pen">POS Soles</option>
               <option value="pos_usd">POS Dólares</option>
               <option value="yape">Yape / Plin</option>
               <option value="depo">Transferencia</option>
             </select>
           </div>
           
           <div class="mb-3">
             <label class="form-label fw-bold text-secondary" style="font-size:12px;">Precio Cargado</label>
             <div class="input-group input-group-sm">
               <span class="input-group-text bg-light fw-bold">
                 {{ ['pos_usd', 'usd_ef'].includes(formConsumo.columna) ? '$' : (formConsumo.columna === 'pesos' ? 'CLP' : 'S/') }}
               </span>
               <input type="number" step="0.01" class="form-control fw-bold" v-model="formConsumo.precio">
             </div>
           </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button class="btn btn-sm btn-primary w-100 fw-bold" @click="guardarConsumoFlujo">
            Sumar a {{ 
              formConsumo.columna === 'pen_ef' ? 'Efectivo Soles' :
              formConsumo.columna === 'usd_ef' ? 'Efectivo Dólares' :
              formConsumo.columna === 'pesos' ? 'Efectivo Pesos' :
              formConsumo.columna === 'pos_pen' ? 'POS Soles' :
              formConsumo.columna === 'pos_usd' ? 'POS Dólares' :
              formConsumo.columna === 'yape' ? 'Yape / Plin' :
              'Transferencia'
            }}
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- BOTÓN FLOTANTE PARA GUARDAR EGRESOS -->
  <div v-if="turnosModificados.size > 0" class="position-fixed bottom-0 end-0 p-4" style="z-index: 1050; animation: fadeUp 0.3s ease-out;">
    <button class="btn btn-success shadow-lg fw-bold rounded-pill px-4 py-3" @click="guardarCambiosEgresos">
      <i class="bi bi-save me-2"></i> Guardar Cambios ({{ turnosModificados.size }} turnos)
    </button>
  </div>
</div>

<style>
  [v-cloak] { display: none !important; }
  
  .mensual-grid-container {
    max-height: calc(100vh - 145px);
    overflow: auto;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
  }
  
  /* Scrollbar elegante */
  .mensual-grid-container::-webkit-scrollbar {
    width: 10px;
    height: 10px;
  }
  .mensual-grid-container::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
  }
  .mensual-grid-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
    border: 2px solid #f1f5f9;
  }
  .mensual-grid-container::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
  }

  .table-mensual {
    min-width: 2200px;
    font-size: 11.5px;
    border-collapse: separate;
    border-spacing: 0;
  }
  
  /* Sticky de headers */
  .table-mensual thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    color: #ffffff;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 10px;
    letter-spacing: 0.5px;
    text-align: center;
    border: 1px solid #334155;
    vertical-align: middle;
    padding: 8px 6px;
  }
  .table-mensual thead tr:nth-child(2) th {
    top: 33px;
  }

  /* Sticky columnas de izquierda */
  .table-mensual th.sticky-col,
  .table-mensual td.sticky-col {
    position: sticky;
    left: 0;
    z-index: 6;
    background-color: #f8fafc;
    border-right: 1px solid #cbd5e1;
  }
  .table-mensual th.sticky-col-2,
  .table-mensual td.sticky-col-2 {
    position: sticky;
    left: 100px;
    z-index: 6;
    background-color: #f8fafc;
    border-right: 2px solid #94a3b8;
  }
  
  .table-mensual thead th.sticky-col {
    z-index: 12 !important;
    background-color: #0f172a !important;
    color: #ffffff !important;
    border: 1px solid #334155;
  }
  .table-mensual thead th.sticky-col-2 {
    z-index: 12 !important;
    background-color: #0f172a !important;
    color: #ffffff !important;
    border: 1px solid #334155;
    border-right: 2px solid #94a3b8;
  }

  .table-mensual td {
    padding: 5px 8px;
    vertical-align: middle;
    border: 1px solid #e2e8f0;
  }
  
  .table-active-row td {
    background-color: #f1f5f9;
  }
  
  /* Highlight fila del día de hoy */
  .table-mensual tr.today-row-highlight td {
    background-color: #fef08a !important; /* Amarillo premium suave para destacar el día de hoy */
  }
  .table-mensual tr.today-row-highlight td.sticky-col,
  .table-mensual tr.today-row-highlight td.sticky-col-2 {
    background-color: #fef08a !important;
  }
  
  /* Separación visual roja */
  .separator-col, .separator-col-head {
    border-left: 4px solid #ef4444 !important;
  }
  
  /* Fila total de día */
  .table-mensual tr.day-total td {
    background-color: #fffbeb !important;
    font-weight: 700;
    color: #b45309;
    border-top: 1px solid #fef3c7;
    border-bottom: 2px solid #f59e0b;
  }
  .table-mensual tr.day-total td.sticky-col,
  .table-mensual tr.day-total td.sticky-col-2 {
    background-color: #fef3c7 !important;
  }

  /* Fila total general */
  .table-mensual tr.total-general td {
    background: linear-gradient(135deg, #1e293b, #0f172a) !important;
    color: #ffffff !important;
    font-weight: 800;
    font-size: 12px;
    border-top: 2px solid #d97706;
    border-bottom: 3px double #d97706;
    position: sticky;
    bottom: 0;
    z-index: 9;
  }
  .table-mensual tr.total-general td.sticky-col,
  .table-mensual tr.total-general td.sticky-col-2 {
    background: linear-gradient(135deg, #1e293b, #0f172a) !important;
    color: #ffffff !important;
  }

  .table-mensual td.num-cell {
    text-align: right;
    font-family: 'Courier New', Courier, monospace;
    font-weight: 600;
    color: #1e293b;
    cursor: pointer;
    transition: background-color 0.1s ease;
  }
  .table-mensual td.num-cell:hover {
    background-color: rgba(0, 0, 0, 0.05) !important;
  }
  .table-mensual td.num-cell.zero-val {
    color: #94a3b8;
    font-weight: 400;
  }
  
  /* Triángulo rojo indicando que hay desglose */
  .has-details {
    position: relative;
  }
  .has-details::after {
    content: "";
    position: absolute;
    top: 0;
    right: 0;
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-top: 8px solid #ef4444; /* Rojo */
    pointer-events: none;
  }
  
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  /* Tooltip Flotante para Detalles de Ingresos */
  .flujo-tooltip {
    visibility: hidden;
    opacity: 0;
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%) translateY(5px);
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
  .flujo-tooltip::after {
    content: "";
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border-width: 6px;
    border-style: solid;
    border-color: #fff transparent transparent transparent;
  }
  .flujo-tooltip::before {
    content: "";
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border-width: 7px;
    border-style: solid;
    border-color: #ddd transparent transparent transparent;
    z-index: -1;
  }
  .num-cell:hover .flujo-tooltip {
    visibility: visible;
    opacity: 1;
    transform: translateX(-50%) translateY(-5px);
  }
</style>

<script>
  window.SERVER_ROUTES = {
    apiMensual: <?= json_encode(project_base_url() . 'api/flujo.php') ?> + '?action=mensual_grid',
    form: <?= json_encode(route('flujo/form.php')) ?>
  };
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $_root ?>app/Views/flujo/v2.js?v=<?= time() ?>"></script>
