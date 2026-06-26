<?php
/**
 * app/Views/rooming/v2.php
 * Vista de la grilla plana Rooming V2.
 */
$base = '../../../';
$_projectRoot = dirname(__DIR__, 3);
require_once $_projectRoot . '/app/Middleware/auth.php';
protegerPorRol('cajera', 'rooming');

require_once $_projectRoot . '/config/db.php'; // Asegurar PDO

// Obtener lista de habitaciones para el select y auto-completado de tipo
$stmtH = $pdo->query("SELECT numero, tipo, estado FROM habitaciones ORDER BY numero ASC");
$habitacionesList = $stmtH->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Rooming V2 — Hotel Manager';
include $_projectRoot . '/app/Views/layouts/head.php';
?>

<style>
  /* Alertas de Checkout */
  .checkout-atrasado input[type="date"] {
    background-color: #fee2e2 !important; /* rojo suave */
    color: #dc2626 !important; /* rojo fuerte */
    border-radius: 4px;
    font-weight: 900 !important;
    animation: pulse-red 2s infinite;
  }
  .checkout-hoy input[type="date"] {
    background-color: #fef9c3 !important; /* amarillo suave */
    color: #ca8a04 !important; /* amarillo oscuro */
    border-radius: 4px;
    font-weight: 900 !important;
  }
  @keyframes pulse-red {
    0% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.4); }
    70% { box-shadow: 0 0 0 4px rgba(220, 38, 38, 0); }
    100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); }
  }
</style>

<div id="app-rooming-v2" style="display:contents" v-cloak>
  <?php include $_projectRoot . '/app/Views/layouts/sidebar.php'; ?>
  
  <div class="main-content">
    <!-- TOPBAR -->
    <div class="topbar" style="background-color:#111827;padding:0.75rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.05);">
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
              <h4 class="fw-bold mb-0 text-white" style="font-size:18px;letter-spacing:-0.5px;">Rooming V2 &mdash; Planilla Plana</h4>
              <div class="text-white-50" style="font-size:11px;">Edición directa de todos los check-ins estilo Excel</div>
            </div>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <div class="d-flex align-items-center rounded px-2 py-1" style="background-color:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);">
            <select v-model="filtro.mes" class="form-select form-select-sm border-0 fw-bold text-white bg-transparent py-0 px-2" @change="cargarDatos" style="width:auto;font-size:12px;cursor:pointer;outline:none;box-shadow:none;">
              <option value="1" class="text-dark">Enero</option>
              <option value="2" class="text-dark">Febrero</option>
              <option value="3" class="text-dark">Marzo</option>
              <option value="4" class="text-dark">Abril</option>
              <option value="5" class="text-dark">Mayo</option>
              <option value="6" class="text-dark">Junio</option>
              <option value="7" class="text-dark">Julio</option>
              <option value="8" class="text-dark">Agosto</option>
              <option value="9" class="text-dark">Septiembre</option>
              <option value="10" class="text-dark">Octubre</option>
              <option value="11" class="text-dark">Noviembre</option>
              <option value="12" class="text-dark">Diciembre</option>
            </select>
            <div class="vr mx-1" style="height:16px;background:rgba(255,255,255,0.3);"></div>
            <select v-model="filtro.anio" class="form-select form-select-sm border-0 fw-bold text-white bg-transparent py-0 px-1" @change="cargarDatos" style="width:75px;font-size:12px;cursor:pointer;outline:none;box-shadow:none;">
              <option v-for="y in filtro.anios" :key="y" :value="y" class="text-dark">{{ y }}</option>
            </select>
          </div>
          <button class="btn btn-sm btn-outline-light d-flex align-items-center gap-2" @click="cargarDatos" :disabled="loading" style="font-size:12px;padding:4px 12px;border-color:rgba(255,255,255,0.2);">
            <i class="bi bi-arrow-clockwise"></i>
            <span class="d-none d-md-inline">Actualizar</span>
          </button>
          <button class="btn btn-sm d-flex align-items-center gap-2" @click="abrirReportePax"
            style="font-size:12px;padding:4px 12px;background:rgba(212,175,55,0.15);border:1px solid rgba(212,175,55,0.5);color:#f0c040;font-weight:700;">
            <i class="bi bi-file-earmark-person"></i>
            <span class="d-none d-md-inline">REGISTRO PAX</span>
          </button>
        </div>
      </div>
    </div>

    <!-- BODY -->
    <div class="page-body pt-3">
      <!-- CONTROL BAR & ACCIONES -->
      <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
        <div class="card-body p-3">
          <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <!-- Buscador local -->
            <div style="min-width: 280px; max-width: 320px;">
              <div class="input-group input-group-sm rounded shadow-sm">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-start-0 bg-white fw-bold text-secondary" 
                       style="font-size: 12px;" v-model="busqueda" placeholder="Buscar por huésped, hab, obs...">
              </div>
            </div>
            
            <!-- Acciones de edición masiva -->
            <div class="d-flex gap-2">
              <span class="badge bg-primary align-self-center px-3 py-2 fs-7 shadow-sm" v-if="!loading">
                <i class="bi bi-people-fill me-1"></i>{{ filasFiltradas.length }} registros
              </span>
              
              <button class="btn btn-sm btn-outline-primary fw-bold px-3 shadow-sm" @click="agregarFila">
                <i class="bi bi-plus-lg me-1"></i>Añadir Fila
              </button>
              
              <button class="btn btn-sm btn-primary fw-bold px-3 shadow-sm border border-dark" @click="guardarCambios" :disabled="loading || filas.length === 0">
                <i class="bi bi-save me-1"></i>Guardar Cambios
                <span v-if="cambiosCount > 0" class="badge bg-warning text-dark ms-1">{{ cambiosCount }}</span>
              </button>
              
              <button class="btn btn-sm btn-success fw-bold px-3 shadow-sm" @click="exportarExcel" :disabled="loading || filas.length === 0">
                <i class="bi bi-file-earmark-excel me-1"></i>Exportar Excel
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- GRID INTERACTIVE CONTAINER -->
      <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 12px;">
        <div class="mensual-grid-container">
          <table class="table table-bordered table-hover mb-0 align-middle table-mensual">
            <thead>
              <tr class="table-dark text-white text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">
                <th class="sticky-col text-center" style="width: 75px; z-index:12 !important;"><i class="bi bi-gear"></i></th>
                <th class="text-center" style="width: 100px;">OPERADOR</th>
                <th style="width: 110px;">FECHA</th>
                <th style="width: 110px;">HAB</th>
                <th style="width: 130px;">TIPO DE HAB</th>
                <th style="width: 60px;">PAX</th>
                <th style="width: 120px;">MEDIO RESERVA</th>
                <th style="width: 90px;">HORA CHECK IN</th>
                <th style="width: 250px;">NOMBRE Y APELLIDO</th>
                <th style="width: 110px;">DOC. TIPO</th>
                <th style="width: 120px;">DOCUMENTO NÚMERO</th>
                <th style="width: 110px;">NACIONALIDAD</th>
                <th style="width: 100px;">CIUDAD</th>
                <th style="width: 110px; background-color:#065f46 !important;">CHECK IN FECHA</th>
                <th style="width: 110px; background-color:#065f46 !important;">CHECK OUT FECHA</th>
                <th style="width: 110px;">PAGO TOTAL</th>
                <th style="width: 90px;">LATE CHECKOUT</th>
                <th style="width: 130px;">MEDIO DE PAGO</th>
                <th style="width: 140px;">COMPROBANTE PAGO</th>
                <th style="width: 130px;">NUM. COMPROBANTE</th>
                <th style="width: 110px;">QUIEN COBRÓ</th>
                <th style="width: 70px;">CARRO</th>
                <th style="width: 200px;">OBSERVACIONES</th>
              </tr>
            </thead>
            
            <tbody>
              <!-- Spinner de carga -->
              <tr v-if="loading">
                <td colspan="23" class="text-center py-5">
                  <div class="spinner-border text-primary me-2"></div>
                  <span class="text-muted fw-semibold">Cargando registros...</span>
                </td>
              </tr>
              
              <!-- Sin datos -->
              <tr v-else-if="filasFiltradas.length === 0">
                <td colspan="23" class="text-center py-5 text-muted">
                  <i class="bi bi-inbox fs-1 d-block opacity-25 mb-2"></i>
                  <span>No se encontraron registros para este período.</span>
                </td>
              </tr>
              
              <!-- Filas de datos -->
              <tr v-else v-for="(f, idx) in filasFiltradas" :key="f.stay_id || f.temp_id" 
                  :class="{'unsaved-row': f.modificado || !f.stay_id, 'bg-success bg-opacity-10': f.marcado}">
                <!-- Botones de Acción (Sticky 1) -->
                <td class="sticky-col text-center px-1">
                  <div class="d-flex align-items-center justify-content-center gap-2">
                    <span v-if="f.stay_id && f.estado_stay !== 'finalizado'" class="text-primary p-0 d-inline-flex align-items-center" title="Huésped alojado">
                      <i class="bi bi-person-workspace fs-6"></i>
                    </span>
                    <span v-else-if="f.stay_id && f.estado_stay === 'finalizado'" class="text-success p-0 d-inline-flex align-items-center" title="Checkout ya realizado">
                      <i class="bi bi-check-circle-fill fs-6"></i>
                    </span>
                    <button class="btn btn-sm btn-link text-success p-0" @click="f.marcado = !f.marcado" title="Marcar/Desmarcar fila" style="opacity: 0.8;">
                      <i class="bi" :class="f.marcado ? 'bi-check-square-fill' : 'bi-square'"></i>
                    </button>
                    <button class="btn btn-sm btn-link text-secondary p-0" @click="eliminarFila(f, idx)" title="Eliminar registro">
                      <i class="bi bi-trash-fill fs-6"></i>
                    </button>
                  </div>
                </td>
                
                <!-- OPERADOR -->
                <td class="px-1 text-center">
                  <input type="text" v-model="f.operador" class="table-editable-input text-center fw-bold text-primary" @input="marcarModificado(f)" style="width: 100%;">
                </td>
                
                <!-- FECHA -->
                <td class="px-1">
                  <input type="date" v-model="f.fecha" class="table-editable-input text-center" @change="marcarModificado(f)" style="width: 100%;">
                </td>
                
                <!-- HAB -->
                <td class="px-1">
                  <select v-model="f.hab" class="form-select form-select-sm table-editable-select fw-bold text-center" @change="onHabChange(f)" style="width: 100%;">
                    <option value="">-</option>
                    <option v-for="h in habitaciones" :key="h.numero" :value="h.numero">{{ h.numero }}</option>
                  </select>
                </td>
                
                <!-- TIPO DE HAB -->
                <td class="px-1">
                  <input type="text" v-model="f.tipo_hab" class="table-editable-input text-uppercase text-center" readonly style="width: 100%; background-color: #f1f5f9; color: #64748b;" placeholder="-">
                </td>
                
                <!-- PAX -->
                <td class="px-1">
                  <input type="number" v-model.number="f.pax" class="table-editable-input text-center fw-bold" @input="onPaxChange(f)" style="width: 100%;">
                </td>
                
                <!-- MEDIO DE RESERVA -->
                <td class="px-1">
                  <select v-model="f.medio_reserva" class="form-select form-select-sm table-editable-select text-success fw-bold text-center" @change="marcarModificado(f)">
                    <option value="">-</option>
                    <option value="DIRECTO">DIRECTO</option>
                    <option value="WHATSAPP">WHATSAPP</option>
                    <option value="LLAMADA">LLAMADA</option>
                    <option value="BOOKING">BOOKING</option>
                    <option value="CORREO">CORREO</option>
                  </select>
                </td>
                
                <!-- HORA CHECK IN -->
                <td class="px-1">
                  <input type="text" v-model="f.hora_checkin" class="table-editable-input text-center text-secondary fw-semibold" @input="marcarModificado(f)" placeholder="Ej: 14:00" style="width: 100%;">
                </td>
                
                <!-- NOMBRE Y APELLIDO -->
                <td class="p-0 position-relative" style="vertical-align: stretch;">
                  <div class="d-flex flex-column h-100 justify-content-start align-items-stretch">
                    <div v-for="(p, pIdx) in f.pax_list" :key="pIdx" class="pax-input-container position-relative w-100" :style="{ borderBottom: pIdx === f.pax_list.length - 1 ? 'none' : '1px dashed #cbd5e1' }">
                      <input type="text" v-model="p.nombre_apellido" 
                             class="table-editable-input fw-bold text-dark w-100 border-0 bg-transparent px-2" 
                             @input="marcarModificado(f); buscarClientes(f, idx, pIdx)" 
                             @blur="ocultarSugerencias(idx, pIdx)" 
                             placeholder="Huésped..." style="height: 32px; font-size: 11px;">
                      
                      <!-- Dropdown sugerencias clientes -->
                      <div v-if="sugerencias[idx + '_' + pIdx] && sugerencias[idx + '_' + pIdx].length" class="position-absolute bg-white border rounded shadow-lg w-100 z-3 mt-1" style="max-height: 180px; overflow-y: auto; border-radius: 8px; left:0; top: 100%;">
                        <div v-for="s in sugerencias[idx + '_' + pIdx]" :key="s.documento_num" class="px-3 py-1 cursor-pointer border-bottom d-flex align-items-center justify-content-between hover-bg-light" style="font-size: 11px;" @mousedown.prevent="aplicarSugerencia(f, idx, pIdx, s)">
                          <div class="fw-bold">{{ s.nombre_completo }}</div>
                          <small class="text-muted">{{ s.documento_tipo }}: {{ s.documento_num }}</small>
                        </div>
                      </div>
                    </div>
                  </div>
                </td>
                
                <!-- DOC TIPO -->
                <td class="p-0" style="vertical-align: stretch;">
                  <div class="d-flex flex-column h-100 justify-content-start align-items-stretch">
                    <div v-for="(p, pIdx) in f.pax_list" :key="pIdx" class="pax-input-container w-100" :style="{ borderBottom: pIdx === f.pax_list.length - 1 ? 'none' : '1px dashed #cbd5e1' }">
                      <input type="text" v-model="p.documento_tipo" 
                             class="table-editable-input text-center fw-bold text-dark w-100 border-0 bg-transparent px-1" 
                             @input="marcarModificado(f)" 
                             placeholder="DNI" style="height: 32px; font-size: 11px;">
                    </div>
                  </div>
                </td>
                
                <!-- DOCUMENTO NÚMERO -->
                <td class="p-0 position-relative" style="vertical-align: stretch;">
                  <div class="d-flex flex-column h-100 justify-content-start align-items-stretch">
                    <div v-for="(p, pIdx) in f.pax_list" :key="pIdx" class="pax-input-container w-100 d-flex align-items-center" :style="{ borderBottom: pIdx === f.pax_list.length - 1 ? 'none' : '1px dashed #cbd5e1' }">
                      <input type="text" v-model="p.documento_num" 
                             class="table-editable-input text-center fw-bold text-dark w-100 border-0 bg-transparent px-1" 
                             @input="marcarModificado(f); lookupDni(f, idx, pIdx)" 
                             placeholder="Número..." style="height: 32px; font-size: 11px;">
                      <span v-if="lookupLoading[idx + '_' + pIdx]" class="spinner-border spinner-border-sm text-primary ms-1" style="width: 12px; height: 12px; flex-shrink: 0; margin-right: 4px;"></span>
                    </div>
                  </div>
                </td>
                
                <!-- NACIONALIDAD -->
                <td class="p-0" style="vertical-align: stretch;">
                  <div class="d-flex flex-column h-100 justify-content-start align-items-stretch">
                    <div v-for="(p, pIdx) in f.pax_list" :key="pIdx" class="pax-input-container w-100" :style="{ borderBottom: pIdx === f.pax_list.length - 1 ? 'none' : '1px dashed #cbd5e1' }">
                      <input type="text" v-model="p.nacionalidad" 
                             class="table-editable-input text-center text-dark w-100 border-0 bg-transparent px-1" 
                             @input="marcarModificado(f)" 
                             placeholder="Peruana" style="height: 32px; font-size: 11px;">
                    </div>
                  </div>
                </td>
                
                <!-- CIUDAD -->
                <td class="p-0" style="vertical-align: stretch;">
                  <div class="d-flex flex-column h-100 justify-content-start align-items-stretch">
                    <div v-for="(p, pIdx) in f.pax_list" :key="pIdx" class="pax-input-container w-100" :style="{ borderBottom: pIdx === f.pax_list.length - 1 ? 'none' : '1px dashed #cbd5e1' }">
                      <input type="text" v-model="p.ciudad" 
                             class="table-editable-input text-center text-dark w-100 border-0 bg-transparent px-1" 
                             @input="marcarModificado(f)" 
                             placeholder="Ciudad" style="height: 32px; font-size: 11px;">
                    </div>
                  </div>
                </td>
                
                <!-- CHECK IN FECHA -->
                <td class="p-0" style="vertical-align:stretch; background-color:#f0fff4;">
                  <div class="d-flex flex-column h-100">
                    <div v-for="(p, pIdx) in f.periodos_list" :key="'ci'+pIdx" class="pax-input-container w-100"
                         :style="{ borderBottom: pIdx === f.periodos_list.length-1 ? 'none' : '1px dashed #a7f3d0', padding: '2px 4px', backgroundColor: '#f0fff4' }">
                      <input type="date" v-model="p.fecha_checkin"
                             class="table-editable-input text-center text-success fw-bold w-100 border-0 bg-transparent px-1"
                             @change="marcarModificado(f)" style="height:30px;font-size:11px;">
                    </div>
                  </div>
                </td>
                
                <!-- CHECK OUT FECHA -->
                <td class="p-0" style="vertical-align:stretch; background-color:#f0fff4;">
                  <div class="d-flex flex-column h-100">
                    <div v-for="(p, pIdx) in f.periodos_list" :key="'co'+pIdx" class="pax-input-container w-100 d-flex align-items-center"
                         :class="{ 'checkout-atrasado': pIdx===f.periodos_list.length-1 && estadoCheckout(f)==='atrasado', 'checkout-hoy': pIdx===f.periodos_list.length-1 && estadoCheckout(f)==='hoy' }"
                         :style="{ borderBottom: pIdx===f.periodos_list.length-1 ? 'none':'1px dashed #a7f3d0', padding:'2px 4px', backgroundColor:'#f0fff4' }">
                      <input type="date" v-model="p.fecha_checkout"
                             class="table-editable-input text-center text-danger fw-bold w-100 border-0 bg-transparent px-1"
                             @change="marcarModificado(f)" style="height:30px;font-size:11px;">
                      <button v-if="pIdx===f.periodos_list.length-1 && p.fecha_checkout"
                              class="btn btn-sm btn-link text-success p-0 ms-1 flex-shrink-0"
                              @click="agregarExtension(f)" title="Agregar Late Checkout"
                              style="font-size:18px;line-height:1;font-weight:700;">+</button>
                    </div>
                  </div>
                </td>
                
                <!-- PAGO TOTAL -->
                <td class="p-0" style="vertical-align:stretch;">
                  <div class="d-flex flex-column h-100">
                    <div v-for="(p, pIdx) in f.periodos_list" :key="'pt'+pIdx" class="pax-input-container w-100 d-flex align-items-center justify-content-end pe-2"
                         :style="{ borderBottom: pIdx===f.periodos_list.length-1?'none':'1px dashed #cbd5e1', padding:'2px 4px' }">
                      <span class="fw-bold small text-muted me-1">{{ obtenerSimboloMoneda(p.medio_pago) }}</span>
                      <input type="number" step="0.50" v-model.number="p.pago_total"
                             class="table-editable-input text-end fw-bold text-dark border-0 bg-transparent"
                             @input="marcarModificado(f)" style="width:70px;height:28px;font-size:11px;">
                    </div>
                  </div>
                </td>
                
                <!-- LATE CHECK OUT -->
                <td class="p-0" style="vertical-align:stretch;">
                  <div class="d-flex flex-column h-100">
                    <div v-for="(p, pIdx) in f.periodos_list" :key="'lc'+pIdx" class="pax-input-container w-100"
                         :style="{ borderBottom: pIdx===f.periodos_list.length-1?'none':'1px dashed #cbd5e1', padding:'2px 4px' }">
                      <select v-model="p.late_checkout" class="form-select form-select-sm table-editable-select text-center border-0 bg-transparent w-100"
                              @change="marcarModificado(f)" style="height:28px;font-size:11px;">
                        <option value="NO">NO</option>
                        <option value="SI">SI</option>
                      </select>
                    </div>
                  </div>
                </td>
                
                <!-- MEDIO DE PAGO -->
                <td class="p-0" style="vertical-align:stretch;">
                  <div class="d-flex flex-column h-100">
                    <div v-for="(p, pIdx) in f.periodos_list" :key="'mp'+pIdx" class="pax-input-container w-100"
                         :style="{ borderBottom: pIdx===f.periodos_list.length-1?'none':'1px dashed #cbd5e1', padding:'2px 4px' }">
                      <select v-model="p.medio_pago" class="form-select form-select-sm table-editable-select text-center fw-semibold border-0 bg-transparent w-100"
                              @change="marcarModificado(f)" style="height:28px;font-size:11px;">
                        <option value="">-</option>
                        <option value="SOLES EFECTIVO">SOLES EFECTIVO</option>
                        <option value="POS SOLES">POS SOLES</option>
                        <option value="DOLARES EFECTIVO">DOLARES EFECTIVO</option>
                        <option value="POS DOLARES">POS DOLARES</option>
                        <option value="PESOS EFECTIVO">PESOS EFECTIVO</option>
                        <option value="POS PESOS">POS PESOS</option>
                        <option value="YAPE O PLIN">YAPE O PLIN</option>
                        <option value="DEPOSITOS">DEPOSITOS</option>
                      </select>
                    </div>
                  </div>
                </td>
                
                <!-- COMPROBANTE DE PAGO -->
                <td class="p-0" style="vertical-align:stretch;">
                  <div class="d-flex flex-column h-100">
                    <div v-for="(p, pIdx) in f.periodos_list" :key="'cp'+pIdx" class="pax-input-container w-100"
                         :style="{ borderBottom: pIdx===f.periodos_list.length-1?'none':'1px dashed #cbd5e1', padding:'2px 4px' }">
                      <select v-model="p.comprobante_pago" class="form-select form-select-sm table-editable-select text-center fw-semibold border-0 bg-transparent w-100"
                              @change="marcarModificado(f)" style="height:28px;font-size:11px;">
                        <option value="">-</option>
                        <option value="NINGUNO">NINGUNO</option>
                        <option value="BOLETA">BOLETA</option>
                        <option value="FACTURA">FACTURA</option>
                        <option value="F.X.">F.X.</option>
                      </select>
                    </div>
                  </div>
                </td>
                
                <!-- NUMERO DE COMPROBANTE -->
                <td class="p-0" style="vertical-align:stretch;">
                  <div class="d-flex flex-column h-100">
                    <div v-for="(p, pIdx) in f.periodos_list" :key="'nc'+pIdx" class="pax-input-container w-100"
                         :style="{ borderBottom: pIdx===f.periodos_list.length-1?'none':'1px dashed #cbd5e1', padding:'2px 4px' }">
                      <input type="text" v-model="p.numero_comprobante"
                             class="table-editable-input text-center w-100 border-0 bg-transparent px-1"
                             @input="marcarModificado(f)" placeholder="001-452" style="height:28px;font-size:11px;">
                    </div>
                  </div>
                </td>
                
                <!-- QUIEN COBRO -->
                <td class="p-0" style="vertical-align:stretch;">
                  <div class="d-flex flex-column h-100">
                    <div v-for="(p, pIdx) in f.periodos_list" :key="'qc'+pIdx" class="pax-input-container w-100"
                         :style="{ borderBottom: pIdx===f.periodos_list.length-1?'none':'1px dashed #cbd5e1', padding:'2px 4px' }">
                      <input type="text" v-model="p.quien_cobro"
                             class="table-editable-input text-center fw-semibold text-warning-emphasis w-100 border-0 bg-transparent px-1"
                             @input="marcarModificado(f)" style="height:28px;font-size:11px;">
                    </div>
                  </div>
                </td>
                
                <!-- CARRO -->
                <td class="px-1">
                  <select v-model="f.carro" class="form-select form-select-sm table-editable-select text-center" @change="marcarModificado(f)">
                    <option value="NO">NO</option>
                    <option value="SI">SI</option>
                  </select>
                </td>
                
                <!-- OBSERVACIONES -->
                <td class="px-1">
                  <textarea v-model="f.observaciones" class="form-control form-control-sm table-editable-textarea" rows="1" @input="marcarModificado(f)" style="width: 100%; min-height: 24px; font-size: 11px;"></textarea>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ╔══════════════════════════════════════════════════════╗ -->
  <!-- ║          MODAL REGISTRO PAX (Reporte Mensual)       ║ -->
  <!-- ╚══════════════════════════════════════════════════════╝ -->
  <div class="modal fade" id="modalReportePaxV2" tabindex="-1" aria-labelledby="modalReportePaxV2Label" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
      <div class="modal-content border-0" style="background:#f4f6fb;">
        <!-- Header -->
        <div class="modal-header border-0 py-3 px-4"
          style="background:linear-gradient(135deg,#1a1a2e 0%,#16213e 60%,#0f3460 100%);">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center"
              style="width:40px;height:40px;background:rgba(255,255,255,0.12);">
              <i class="bi bi-file-earmark-person text-white fs-5"></i>
            </div>
            <div>
              <h5 class="modal-title fw-bold text-white mb-0" id="modalReportePaxV2Label">Registro PAX — Reporte Mensual</h5>
              <small class="text-white opacity-60">Listado de check-ins por mes para control de huéspedes</small>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <!-- Filtros -->
        <div class="px-4 pt-3 pb-2" style="background:white; border-bottom:1px solid #e9ecef;">
          <div class="d-flex flex-wrap gap-3 align-items-end">
            <div>
              <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size:10px;letter-spacing:.5px;">Mes</label>
              <select v-model="reportePax.mes" class="form-select form-select-sm fw-bold" style="width:140px;" @change="cargarReportePax">
                <option value="1">Enero</option><option value="2">Febrero</option><option value="3">Marzo</option>
                <option value="4">Abril</option><option value="5">Mayo</option><option value="6">Junio</option>
                <option value="7">Julio</option><option value="8">Agosto</option><option value="9">Septiembre</option>
                <option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option>
              </select>
            </div>
            <div>
              <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size:10px;letter-spacing:.5px;">Año</label>
              <select v-model="reportePax.anio" class="form-select form-select-sm fw-bold" style="width:100px;" @change="cargarReportePax">
                <option v-for="y in reportePax.anios" :key="y" :value="y">{{ y }}</option>
              </select>
            </div>
            <div class="ms-auto d-flex gap-2">
              <span class="badge bg-primary align-self-center px-3 py-2" style="font-size:11px;" v-if="!reportePax.cargando">
                <i class="bi bi-people-fill me-1"></i>{{ reportePax.filas.length }} registros
              </span>
              <button class="btn btn-sm btn-success fw-bold px-3 shadow-sm" @click="abrirConfigExportarV2"
                :disabled="reportePax.filas.length === 0">
                <i class="bi bi-file-earmark-excel me-1"></i>Exportar Excel
              </button>
            </div>
          </div>
        </div>

        <!-- Cuerpo -->
        <div class="modal-body p-0">
          <div v-if="reportePax.cargando" class="d-flex justify-content-center align-items-center py-5">
            <div class="spinner-border text-primary me-3"></div>
            <span class="text-muted fw-semibold">Cargando reporte...</span>
          </div>
          <div v-else-if="reportePax.filas.length === 0" class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 opacity-25 d-block mb-2"></i>
            <span class="fw-semibold">No se encontraron check-ins para este período.</span>
          </div>
          <div v-else id="containerReportePaxV2" class="table-responsive" style="overflow-x:auto; cursor: grab;">
            <table id="tablaPaxReporteV2" class="table table-bordered table-hover mb-0 align-middle"
              style="font-size:11px; white-space:nowrap; min-width:2400px;">
              <thead style="background:#1a1a2e; color:white; position:sticky; top:0; z-index:10;">
                <tr>
                  <th class="px-2 py-2 text-center" style="width:40px;"><i class="bi bi-eye-slash"></i></th>
                  <th class="px-3 py-2 text-center" style="min-width:80px;">OPERADOR</th>
                  <th class="px-3 py-2 text-center" style="min-width:90px;">FECHA<br>REGISTRO</th>
                  <th class="px-3 py-2 text-center" style="min-width:60px;">HAB</th>
                  <th class="px-3 py-2 text-center" style="min-width:130px;">TIPO DE HAB</th>
                  <th class="px-3 py-2 text-center" style="min-width:45px;">PAX</th>
                  <th class="px-3 py-2 text-center" style="min-width:110px;">MEDIO DE<br>RESERVA</th>
                  <th class="px-3 py-2 text-center" style="min-width:90px;">HORA DE<br>CHECK IN</th>
                  <th class="px-3 py-2 text-center" style="min-width:200px;">NOMBRE Y APELLIDO</th>
                  <th class="px-3 py-2 text-center" style="min-width:130px;">DOCUMENTO<br>DE IDENTIDAD</th>
                  <th class="px-3 py-2 text-center" style="min-width:110px;">NÚMERO</th>
                  <th class="px-3 py-2 text-center" style="min-width:100px;">NACIONALIDAD</th>
                  <th class="px-3 py-2 text-center" style="min-width:90px;">CIUDAD</th>
                  <th class="px-3 py-2 text-center" style="min-width:100px;">CHECK IN<br>FECHA</th>
                  <th class="px-3 py-2 text-center" style="min-width:100px;">CHECK OUT<br>FECHA</th>
                  <th class="px-3 py-2 text-center" style="min-width:100px;">PAGO TOTAL</th>
                  <th class="px-3 py-2 text-center" style="min-width:90px;">LATE<br>CHECK OUT</th>
                  <th class="px-3 py-2 text-center" style="min-width:120px;">MEDIO DE<br>PAGO</th>
                  <th class="px-3 py-2 text-center" style="min-width:130px;">COMPROBANTE<br>DE PAGO</th>
                  <th class="px-3 py-2 text-center" style="min-width:110px;">NÚMERO DE<br>COMPROBANTE</th>
                  <th class="px-3 py-2 text-center" style="min-width:90px;">QUIEN COBRO</th>
                  <th class="px-3 py-2 text-center" style="min-width:65px;">CARRO</th>
                  <th class="px-3 py-2 text-center" style="min-width:180px;">OBSERVACIONES</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="(fila, idx) in reportePax.filas" :key="idx">
                  <tr :class="[fila.excluir ? 'opacity-50 bg-light-subtle' : '']">
                    <!-- EXCLUIR -->
                    <td class="text-center px-1">
                      <input type="checkbox" v-model="fila.excluir" class="form-check-input" title="Ocultar en Excel"
                        style="cursor:pointer; width:16px; height:16px;">
                    </td>
                    <td class="px-2 text-center"><span class="fw-bold text-primary small">{{ fila.operador || '—' }}</span></td>
                    <td class="px-2 text-center"><span class="fw-bold small">{{ fila.fecha || fila.fecha_registro || '—' }}</span></td>
                    <td class="px-2 text-center fw-bold" style="color:#1a1a2e;">#{{ fila.hab || fila.hab_numero || '—' }}</td>
                    <td class="px-2"><span class="text-uppercase fw-semibold small">{{ fila.tipo_hab || fila.tipo_hab_declarado || '—' }}</span></td>
                    <td class="px-2 text-center fw-bold">{{ fila.pax || fila.pax_total || '—' }}</td>
                    <td class="px-2 text-center"><span class="text-success fw-bold small">{{ fila.medio_reserva || '—' }}</span></td>
                    <td class="px-2 text-center fw-bold">{{ fila.hora_checkin || '—' }}</td>
                    <td class="px-2 fw-bold" style="color:#0f3460;">{{ fila.nombre_apellido || fila.nombre_completo || '—' }}</td>
                    <td class="px-2 text-center"><span class="badge bg-light text-dark border" style="font-size:9px;">{{ fila.documento_tipo || 'DNI' }}</span></td>
                    <td class="px-2 text-center fw-bold">{{ fila.documento_num || '—' }}</td>
                    <td class="px-2 text-center small">{{ fila.nacionalidad || '—' }}</td>
                    <td class="px-2 text-center small">{{ fila.ciudad || '—' }}</td>
                    <td class="px-2 text-center"><span class="text-success fw-bold small">{{ fila.fecha_checkin || fila.fecha_registro || '—' }}</span></td>
                    <td class="px-2 text-center"><span class="text-danger fw-bold small">{{ fila.fecha_checkout || (fila.checkout_list && fila.checkout_list[fila.checkout_list.length-1] ? fila.checkout_list[fila.checkout_list.length-1].fecha : '—') }}</span></td>
                    <td class="px-2 text-end fw-bold">S/ {{ parseFloat(fila.pago_total || fila.total_pago || 0).toFixed(2) }}</td>
                    <td class="px-2 text-center"><span :class="fila.late_checkout === 'SI' || fila.estado === 'late_checkout' ? 'badge bg-warning text-dark' : 'text-muted small'">{{ fila.late_checkout === 'SI' || fila.estado === 'late_checkout' ? 'SI' : 'NO' }}</span></td>
                    <td class="px-2 text-center small fw-bold">{{ fila.medio_pago || fila.metodo_pago || '—' }}</td>
                    <td class="px-2 text-center small">{{ fila.comprobante_pago || fila.tipo_comprobante || '—' }}</td>
                    <td class="px-2 text-center fw-bold">{{ fila.numero_comprobante || fila.num_comprobante || '—' }}</td>
                    <td class="px-2 text-center"><span class="fw-bold text-warning-emphasis small">{{ fila.quien_cobro || fila.cobrador || fila.operador || '—' }}</span></td>
                    <td class="px-2 text-center">{{ fila.carro || '—' }}</td>
                    <td class="px-2" style="max-width:180px; white-space:normal;"><span class="small">{{ fila.observaciones || '—' }}</span></td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer border-0 bg-white py-2 px-4">
          <small class="text-muted me-auto">
            <i class="bi bi-info-circle me-1"></i>
            Usa los checkboxes para excluir filas del Excel. Datos en tiempo real del mes seleccionado.
          </small>
          <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Selector de Columnas para Excel -->
  <div class="modal fade" id="modalExportConfigV2" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg" style="border-radius:16px;">
        <div class="modal-header border-0 bg-success text-white py-3 px-4" style="border-radius: 16px 16px 0 0;">
          <h5 class="modal-title fw-bold"><i class="bi bi-gear-fill me-2"></i>Configurar Exportación</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <p class="text-muted small mb-4">Selecciona las columnas que deseas incluir en tu reporte Excel:</p>
          <div class="row g-2">
            <div v-for="(col, idx) in selColumnas" :key="idx" class="col-6">
              <div class="form-check p-2 border rounded" style="cursor:pointer;" @click="col.checked = !col.checked">
                <input class="form-check-input ms-0 me-2" type="checkbox" v-model="col.checked" @click.stop>
                <label class="form-check-label small fw-bold text-secondary" style="cursor:pointer;">{{ col.label }}</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 p-4 pt-0">
          <div class="w-100 d-flex justify-content-between">
            <button class="btn btn-light btn-sm fw-bold" @click="selColumnas.forEach(c => c.checked = true)">Todos</button>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancelar</button>
              <button type="button" class="btn btn-success px-5 fw-bold shadow" @click="confirmarExportacionV2">
                <i class="bi bi-download me-1"></i> Descargar
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  [v-cloak] { display: none !important; }
  
  .mensual-grid-container {
    max-height: calc(100vh - 145px);
    overflow: auto;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    background-color: #f8fafc;
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
    min-width: 2500px;
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
    background-color: #1e293b;
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
  .table-mensual thead th.sticky-col {
    z-index: 12 !important;
    background-color: #1e293b !important;
    color: #ffffff !important;
    border: 1px solid #334155;
  }

  .pax-input-container {
    padding: 3px 0;
    border-bottom: 1px dashed #cbd5e1;
    background-color: #ffffff;
    transition: background-color 0.15s ease;
  }
  .pax-input-container:last-child {
    border-bottom: none;
  }
  .pax-input-container:nth-child(even) {
    background-color: #f8fafc;
  }
  .pax-input-container:hover {
    background-color: rgba(59, 130, 246, 0.05) !important;
  }
  
  .table-mensual td.p-0 {
    padding: 0 !important;
  }

  .table-mensual td {
    padding: 3px 4px;
    vertical-align: middle;
    border: 1px solid #e2e8f0;
    background-color: #ffffff;
  }

  .table-mensual tbody tr:hover td {
    background-color: #f1f5f9;
  }
  .table-mensual tbody tr:hover td.sticky-col {
    background-color: #e2e8f0;
  }
  
  /* Highlight de filas no guardadas o modificadas */
  .unsaved-row td {
    background-color: #fefbeb !important;
  }
  .unsaved-row td.sticky-col {
    background-color: #fef3c7 !important;
  }
  .unsaved-row {
    border-left: 4px solid #f59e0b !important;
  }

  /* Inputs editables */
  .table-editable-input {
    border: 1px solid transparent;
    background: transparent;
    padding: 4px 6px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: inherit;
    color: inherit;
    text-align: inherit;
    transition: all 0.15s ease;
  }
  .table-editable-input:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
  }
  .table-editable-input:focus {
    border-color: #3b82f6;
    background: #ffffff;
    outline: none;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
  }

  /* Selects editables */
  .table-editable-select {
    border: 1px solid transparent;
    background: transparent;
    padding: 2px 4px;
    font-size: 11px;
    font-weight: inherit;
    color: inherit;
    height: 26px;
    cursor: pointer;
    transition: all 0.15s ease;
  }
  .table-editable-select:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
  }
  .table-editable-select:focus {
    border-color: #3b82f6;
    background: #ffffff;
    outline: none;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
  }

  /* Textareas editables */
  .table-editable-textarea {
    border: 1px solid transparent;
    background: transparent;
    padding: 3px 5px;
    font-size: 11px;
    resize: none;
    transition: all 0.15s ease;
  }
  .table-editable-textarea:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
  }
  .table-editable-textarea:focus {
    border-color: #3b82f6;
    background: #ffffff;
    outline: none;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
  }
  
  .hover-bg-light:hover {
    background-color: #f1f5f9;
  }
</style>

<!-- SERVER VARIABLES -->
<script>
  window.SERVER_DATA = {
    apiEndpoint: <?= json_encode(project_base_url() . 'api/rooming_v2.php') ?>,
    clientSearchEndpoint: <?= json_encode(project_base_url() . 'api/clientes.php') ?>,
    operadorDefault: <?= json_encode($_SESSION['auth_nombre'] ?? 'Kari') ?>,
    habitaciones: <?= json_encode($habitacionesList) ?>
  };
</script>

<!-- LIBRARIES -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- SheetJS para exportar a Excel -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<!-- ExcelJS para Reporte PAX con formato avanzado -->
<script src="https://cdn.jsdelivr.net/npm/exceljs@4.3.0/dist/exceljs.min.js"></script>
<!-- Vue 3 -->
<script src="https://cdn.jsdelivr.net/npm/vue@3.3.4/dist/vue.global.prod.js"></script>

<!-- Vue App Controller -->
<script src="<?= $_root ?>app/Views/rooming/v2.js?v=<?= time() ?>"></script>
<?php include $_projectRoot . '/app/Views/layouts/footer.php'; ?>
