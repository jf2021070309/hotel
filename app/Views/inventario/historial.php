<?php
/**
 * app/Views/inventario/historial.php
 * Kardex / Bitácora de movimientos de inventario (Premium Dark)
 */
$base = '../../../';
require_once $base . 'app/Middleware/auth.php';
$page_title = 'Kardex de Inventario — Hotel Manager';
include $base . 'app/Views/layouts/head.php';
?>

<div class="main-content" id="app-kardex" v-cloak>
  
  <?php include $base . 'app/Views/layouts/sidebar.php'; ?>

  <!-- TOPBAR PREMIUM DARK -->
  <div class="topbar" style="background-color: #111827; padding: 0.75rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.05);">
    <div class="d-flex align-items-center justify-content-between w-100">
      <div class="d-flex align-items-center gap-3">
        <button class="btn btn-dark btn-sm rounded-circle d-md-none" onclick="handleMenuClick()" style="width:32px;height:32px;padding:0;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.1);border:none;">
          <i class="bi bi-list text-white"></i>
        </button>
        <div class="d-flex align-items-center gap-3">
          <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#f8fafc,#94a3b8);display:flex;align-items:center;justify-content:center;box-shadow:0 0 15px rgba(148,163,184,0.4);">
            <i class="bi bi-journal-text text-dark fs-5"></i>
          </div>
          <div>
            <h4 class="fw-bold mb-0 text-white" style="font-size:18px;letter-spacing:-0.5px;">Kardex de Inventario</h4>
            <div class="text-white-50" style="font-size:11px;">Bitácora completa de movimientos de stock</div>
          </div>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="index.php" class="btn btn-sm btn-outline-light d-flex align-items-center gap-2" style="font-size:12px;padding:4px 12px;border-color:rgba(255,255,255,0.2);">
          <i class="bi bi-arrow-left"></i>
          <span class="d-none d-md-inline">Volver</span>
        </a>
      </div>
    </div>
  </div>

  <div class="page-body pt-3">
    <!-- FILTROS -->
    <div class="card border-0 shadow-sm mb-3" style="border-radius:12px;">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-end gap-2 w-100">
                <div style="width: 140px;">
                    <label class="form-label text-muted fw-bold mb-1" style="font-size: 10px; text-transform: uppercase;">Producto</label>
                    <select v-model="filtros.producto_id" class="form-select form-select-sm text-dark fw-bold border-0 bg-light shadow-none" style="font-size: 12px;">
                        <option value="">Todos</option>
                        <option v-for="p in productos" :key="p.id" :value="p.id">{{ p.nombre }}</option>
                    </select>
                </div>
                <div style="width: 100px;">
                    <label class="form-label text-muted fw-bold mb-1" style="font-size: 10px; text-transform: uppercase;">Tipo</label>
                    <select v-model="filtros.tipo" class="form-select form-select-sm text-dark fw-bold border-0 bg-light shadow-none" style="font-size: 12px;">
                        <option value="">Todos</option>
                        <option value="VENTA">Venta</option>
                        <option value="CONSUMO_INTERNO">Interno</option>
                        <option value="RECARGA">Recarga</option>
                        <option value="AJUSTE">Ajuste</option>
                    </select>
                </div>
                <div style="width: 120px;">
                    <label class="form-label text-muted fw-bold mb-1" style="font-size: 10px; text-transform: uppercase;">Desde</label>
                    <input type="date" v-model="filtros.fecha_desde" class="form-control form-control-sm text-dark fw-bold border-0 bg-light shadow-none" style="font-size: 12px; padding: 0.25rem 0.4rem;">
                </div>
                <div style="width: 120px;">
                    <label class="form-label text-muted fw-bold mb-1" style="font-size: 10px; text-transform: uppercase;">Hasta</label>
                    <input type="date" v-model="filtros.fecha_hasta" class="form-control form-control-sm text-dark fw-bold border-0 bg-light shadow-none" style="font-size: 12px; padding: 0.25rem 0.4rem;">
                </div>
                <div class="d-flex gap-2 ms-md-auto">
                    <button @click="cargarHistorial" class="btn btn-sm btn-custom-blue fw-bold shadow-sm px-0" style="font-size: 11px; height: 30px; width: 105px;">
                        <i class="bi bi-search me-1"></i> Filtrar
                    </button>
                    <button @click="limpiarFiltros" class="btn btn-sm btn-light border fw-bold px-0" style="font-size: 11px; color: #475569; height: 30px; width: 105px;">
                        <i class="bi bi-eraser me-1"></i> Limpiar
                    </button>
                    <button @click="abrirConsumoInterno" class="btn btn-sm btn-danger fw-bold shadow-sm px-0" style="font-size: 11px; height: 30px; width: 105px;">
                        <i class="bi bi-person-fill me-1"></i> C. Interno
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- RESUMEN -->
    <div class="row g-3 mb-3">
        <!-- VENTAS -->
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3" style="border-radius:12px; background: #fff;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted fw-bold text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Ventas</div>
                        <div class="d-flex align-items-baseline gap-1">
                            <h4 class="mb-0 fw-bold text-dark">{{ resumen.ventas }}</h4>
                            <span class="text-muted" style="font-size: 11px;">unds</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-center rounded" style="width: 42px; height: 42px; background-color: #fee2e2; color: #dc2626;">
                        <i class="bi bi-cart-dash fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- USO INTERNO -->
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3" style="border-radius:12px; background: #fff;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted fw-bold text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Uso Interno</div>
                        <div class="d-flex align-items-baseline gap-1">
                            <h4 class="mb-0 fw-bold text-dark">{{ resumen.internos }}</h4>
                            <span class="text-muted" style="font-size: 11px;">unds</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-center rounded" style="width: 42px; height: 42px; background-color: #fef3c7; color: #d97706;">
                        <i class="bi bi-person-badge fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- RECARGAS -->
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3" style="border-radius:12px; background: #fff;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted fw-bold text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Recargas</div>
                        <div class="d-flex align-items-baseline gap-1">
                            <h4 class="mb-0 fw-bold text-dark">{{ resumen.recargas }}</h4>
                            <span class="text-muted" style="font-size: 11px;">unds</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-center rounded" style="width: 42px; height: 42px; background-color: #dcfce7; color: #16a34a;">
                        <i class="bi bi-box-seam fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- REGISTROS -->
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3" style="border-radius:12px; background: #fff;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted fw-bold text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Registros</div>
                        <div class="d-flex align-items-baseline gap-1">
                            <h4 class="mb-0 fw-bold text-dark">{{ movimientos.length }}</h4>
                            <span class="text-muted" style="font-size: 11px;">movs</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-center rounded" style="width: 42px; height: 42px; background-color: #e0e7ff; color: #4f46e5;">
                        <i class="bi bi-card-list fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- GRID INTERACTIVO -->
    <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 12px;">
      <div class="inv-grid-container">
        <table class="table table-bordered table-hover mb-0 align-middle table-mensual">
          <thead class="table-dark text-white text-uppercase text-center" style="font-size: 10px; letter-spacing: 0.5px;">
            <tr>
              <th style="padding: 12px 16px; width: 140px;">FECHA Y HORA</th>
              <th style="padding: 12px 16px; min-width: 240px;">PRODUCTO</th>
              <th style="padding: 12px 16px; width: 130px;">TIPO DE MOVIMIENTO</th>
              <th style="padding: 12px 16px; width: 110px;">CANTIDAD</th>
              <th style="padding: 12px 16px; width: 110px;">STOCK ANTERIOR</th>
              <th style="padding: 12px 16px; width: 110px;">STOCK RESULTANTE</th>
              <th style="padding: 12px 16px; width: 180px;">REFERENCIA</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="text-center py-5">
                <div class="spinner-border text-primary me-2"></div>
                <span class="text-muted fw-semibold">Cargando movimientos...</span>
              </td>
            </tr>
            <tr v-else-if="movimientos.length === 0">
              <td colspan="7" class="text-center py-5 text-muted">
                <i class="bi bi-journal-text fs-1 d-block opacity-25 mb-2"></i>
                <span>No hay movimientos en el rango seleccionado.</span>
              </td>
            </tr>
            <tr v-for="m in movimientos" :key="m.id" style="font-size: 12.5px;">
              <td class="text-center px-1">
                  <div class="fw-bold text-dark">{{ m.created_at.split(' ')[0] }}</div>
                  <div class="text-muted" style="font-size: 10px;">{{ m.created_at.split(' ')[1] }}</div>
              </td>
              <td class="px-2">
                  <div class="fw-bold text-dark">{{ m.nombre_producto }}</div>
                  <div class="text-muted" style="font-size: 10px;">{{ m.categoria }}</div>
              </td>
              <td class="text-center px-1">
                  <span class="badge rounded-pill" :class="tipoBadge(m.tipo)" style="font-size: 10px; padding: 4px 10px; letter-spacing: 0.5px;">
                      {{ tipoLabel(m.tipo).toUpperCase() }}
                  </span>
              </td>
              <td class="text-center px-1 fw-bold fs-6" :class="m.tipo === 'RECARGA' ? 'text-success' : 'text-danger'">
                  {{ m.tipo === 'RECARGA' ? '+' : '-' }}{{ m.cantidad }}
              </td>
              <td class="text-center px-1 text-muted fw-bold">
                {{ m.stock_antes }}
              </td>
              <td class="text-center px-1 text-dark fw-bold">
                {{ m.stock_despues }}
              </td>
              <td class="px-2">
                  <span class="text-muted fst-italic" style="font-size: 11px;">{{ m.referencia || '—' }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- MODAL CONSUMO INTERNO -->
  <div class="modal fade" id="modalConsumoInterno" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg" style="border-radius:16px; overflow: hidden;">
        <div class="modal-header border-0 p-4 pb-2" style="background: linear-gradient(to right, #111827, #1f2937);">
          <div class="d-flex align-items-center gap-2">
            <div style="background: rgba(255,255,255,0.15); width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
              <i class="bi bi-person-fill text-white" style="font-size: 14px;"></i>
            </div>
            <h5 class="fw-bold mb-0 text-white" style="font-size: 15px;">Consumo Interno</h5>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form @submit.prevent="guardarConsumoInterno">
          <div class="modal-body p-4 pb-2">
            <div class="d-flex p-3 mb-4 rounded-3" style="background-color: #f8f9fa; border: 1px solid #e2e8f0;">
                <i class="bi bi-info-circle-fill text-dark me-3 fs-5 mt-1"></i>
                <div class="small text-dark" style="font-size: 13px;">
                    Registra aquí los productos consumidos por dueños o staff sin cobro a habitación. <strong>Se descontará del inventario.</strong>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label text-muted fw-bold mb-1" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Producto Consumido</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted px-3"><i class="bi bi-box-seam"></i></span>
                    <select v-model="ciForm.producto_id" class="form-select form-select-lg fw-bold text-dark border-start-0 ps-0" required style="box-shadow: none; font-size: 15px;">
                        <option value="">Seleccione el producto...</option>
                        <option v-for="p in productos" :key="p.id" :value="p.id">
                            {{ p.nombre }} (Quedan: {{ p.stock_actual }})
                        </option>
                    </select>
                </div>
            </div>

            <div class="row mb-4 g-3">
                <div class="col-6">
                    <label class="form-label text-muted fw-bold mb-1" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Cantidad</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted px-3"><i class="bi bi-hash"></i></span>
                        <input type="number" v-model="ciForm.cantidad" class="form-control form-control-lg fw-bold text-dark border-start-0 ps-0 text-center" min="1" required style="box-shadow: none; font-size: 16px;">
                    </div>
                </div>
                <div class="col-6">
                    <label class="form-label text-muted fw-bold mb-1" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Stock Resultante</label>
                    <div class="form-control form-control-lg bg-light text-center border-0 fw-bold d-flex align-items-center justify-content-center" style="color: #94a3b8; font-size: 16px; cursor: not-allowed;">
                        {{ ciForm.producto_id ? (productos.find(p => p.id === ciForm.producto_id)?.stock_actual - ciForm.cantidad || 0) : '—' }}
                    </div>
                </div>
            </div>

            <div class="mb-2">
                <label class="form-label text-muted fw-bold mb-1" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Referencia / Staff</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted px-3"><i class="bi bi-person-badge"></i></span>
                    <input type="text" v-model="ciForm.referencia" class="form-control form-control-lg fw-bold text-dark border-start-0 ps-0" placeholder="Ej: Sr. Mendoza, Limpieza..." style="box-shadow: none; font-size: 15px;">
                </div>
            </div>
          </div>
          <div class="modal-footer border-0 p-4 pt-2">
            <div class="d-flex w-100 gap-2">
                <button type="button" class="btn btn-light text-muted fw-bold flex-grow-1 py-2" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn fw-bold text-white flex-grow-1 py-2 shadow-sm" style="background: linear-gradient(135deg, #1f2937, #111827); border: none;">
                    <i class="bi bi-check2-circle me-1"></i> Confirmar Descargo
                </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<style>
  [v-cloak] { display: none !important; }
  body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }

  .inv-grid-container {
    overflow-x: auto;
    background: #fff;
    position: relative;
  }

  .table-mensual {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
  }
  .table-mensual thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    font-size: 12px;
    font-weight: 700;
    padding: 10px 8px;
    vertical-align: middle;
    color: #ffffff !important;
    box-shadow: 0 1px 0 rgba(0,0,0,0.1);
  }
  .table-mensual tbody td {
    padding: 8px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
    background-color: #fff;
    transition: background-color 0.15s;
  }

  /* Bordes del encabezado */
  .table-mensual thead tr:first-child th { border-bottom: none !important; }
  .table-mensual thead tr:last-child th  { border-top: none !important; }
  .table-mensual thead th { border-left: none !important; border-right: none !important; }
  .table-mensual thead th[style*="border-left"] {
    border-left: 1px solid rgba(255,255,255,0.15) !important;
  }

  /* Botones */
  .btn-custom-blue {
    background-color: #1a56db !important;
    color: #fff !important;
    border: 1px solid #1e429f !important;
    transition: all 0.2s;
  }
  .btn-custom-blue:hover:not(:disabled) { background-color: #1e429f !important; }
  .btn-custom-blue:disabled { opacity: 0.65; }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="historial.js?v=<?= time() ?>"></script>
</body></html>
