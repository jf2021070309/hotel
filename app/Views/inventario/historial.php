<?php
/**
 * app/Views/inventario/historial.php
 * Kardex / Bitácora de movimientos de inventario
 */
$base = '../../../';
require_once $base . 'auth/middleware.php';
$page_title = 'Kardex de Inventario — Hotel Manager';
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';
?>

<div class="main-content" id="app-kardex" v-cloak>
    <div class="topbar">
        <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
        <div>
            <h1 class="h4 mb-0">Kardex de Inventario</h1>
            <p class="text-muted mb-0 small">Bitácora completa de movimientos de stock</p>
        </div>
        <button @click="abrirConsumoInterno" class="btn btn-outline-danger d-flex align-items-center gap-2 ms-auto">
            <i class="bi bi-person-fill"></i> Consumo Interno
        </button>
    </div>

    <div class="page-body">
        <!-- FILTROS -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
            <div class="card-body py-3">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold mb-1">Producto</label>
                        <select v-model="filtros.producto_id" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option v-for="p in productos" :key="p.id" :value="p.id">{{ p.nombre }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold mb-1">Tipo</label>
                        <select v-model="filtros.tipo" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="VENTA">Venta (Hab.)</option>
                            <option value="CONSUMO_INTERNO">Consumo Interno</option>
                            <option value="RECARGA">Recarga</option>
                            <option value="AJUSTE">Ajuste</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold mb-1">Desde</label>
                        <input type="date" v-model="filtros.fecha_desde" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold mb-1">Hasta</label>
                        <input type="date" v-model="filtros.fecha_hasta" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button @click="cargarHistorial" class="btn btn-primary btn-sm px-4 w-100">
                            <i class="bi bi-search me-1"></i> Filtrar
                        </button>
                        <button @click="limpiarFiltros" class="btn btn-light btn-sm">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- RESUMEN -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center p-3" style="border-radius:12px; border-left: 4px solid #dc3545 !important;">
                    <div class="small text-muted fw-bold text-uppercase mb-1">Ventas</div>
                    <div class="h4 mb-0 fw-bold text-danger">{{ resumen.ventas }}</div>
                    <div class="mini text-muted">unidades</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center p-3" style="border-radius:12px; border-left: 4px solid #fd7e14 !important;">
                    <div class="small text-muted fw-bold text-uppercase mb-1">Uso Interno</div>
                    <div class="h4 mb-0 fw-bold text-warning">{{ resumen.internos }}</div>
                    <div class="mini text-muted">unidades</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center p-3" style="border-radius:12px; border-left: 4px solid #198754 !important;">
                    <div class="small text-muted fw-bold text-uppercase mb-1">Recargas</div>
                    <div class="h4 mb-0 fw-bold text-success">{{ resumen.recargas }}</div>
                    <div class="mini text-muted">unidades</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center p-3" style="border-radius:12px; border-left: 4px solid #0d6efd !important;">
                    <div class="small text-muted fw-bold text-uppercase mb-1">Registros</div>
                    <div class="h4 mb-0 fw-bold text-primary">{{ movimientos.length }}</div>
                    <div class="mini text-muted">movimientos</div>
                </div>
            </div>
        </div>

        <!-- TABLA -->
        <div class="card border-0 shadow-sm" style="border-radius:12px; overflow:hidden;">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="bg-light text-muted text-uppercase" style="font-size:10px; letter-spacing:.5px;">
                        <tr>
                            <th class="ps-4">FECHA / HORA</th>
                            <th>PRODUCTO</th>
                            <th class="text-center">TIPO</th>
                            <th class="text-center">CANT.</th>
                            <th class="text-center">ANTES</th>
                            <th class="text-center">DESPUÉS</th>
                            <th>REFERENCIA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="movimientos.length === 0">
                            <td colspan="7" class="text-center py-5 text-muted">No hay movimientos en el rango seleccionado.</td>
                        </tr>
                        <tr v-for="m in movimientos" :key="m.id">
                            <td class="ps-4">
                                <div class="fw-bold">{{ m.created_at.split(' ')[0] }}</div>
                                <div class="text-muted mini">{{ m.created_at.split(' ')[1] }}</div>
                            </td>
                            <td>
                                <div class="fw-bold">{{ m.nombre_producto }}</div>
                                <div class="mini text-muted">{{ m.categoria }}</div>
                            </td>
                            <td class="text-center">
                                <span class="badge" :class="tipoBadge(m.tipo)" style="font-size:9px; padding: 4px 8px;">
                                    {{ tipoLabel(m.tipo) }}
                                </span>
                            </td>
                            <td class="text-center fw-bold" :class="m.tipo === 'RECARGA' ? 'text-success' : 'text-danger'">
                                {{ m.tipo === 'RECARGA' ? '+' : '-' }}{{ m.cantidad }}
                            </td>
                            <td class="text-center text-muted">{{ m.stock_antes }}</td>
                            <td class="text-center fw-bold">{{ m.stock_despues }}</td>
                            <td>
                                <span class="small text-muted">{{ m.referencia || '—' }}</span>
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
            <div class="modal-content border-0 shadow" style="border-radius:16px;">
                <div class="modal-header border-0 p-4 pb-0">
                    <h5 class="fw-bold mb-0"><i class="bi bi-person-fill text-danger me-2"></i>Consumo Interno</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form @submit.prevent="guardarConsumoInterno">
                    <div class="modal-body p-4">
                        <div class="alert alert-warning border-0 small mb-3">
                            <i class="bi bi-info-circle me-1"></i> Usa esto cuando el dueño, staff u otro empleado consume una bebida sin cargo a habitación.
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Producto</label>
                            <select v-model="ciForm.producto_id" class="form-select" required>
                                <option value="">Seleccione...</option>
                                <option v-for="p in productos" :key="p.id" :value="p.id">
                                    {{ p.nombre }} (Stock: {{ p.stock_actual }})
                                </option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Cantidad</label>
                            <input type="number" v-model="ciForm.cantidad" class="form-control" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Referencia / Quién consumió</label>
                            <input type="text" v-model="ciForm.referencia" class="form-control" placeholder="Ej: Sr. Mendoza, Personal cocina...">
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger px-5">Registrar Consumo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="historial.js?v=<?= time() ?>"></script>
</body></html>
