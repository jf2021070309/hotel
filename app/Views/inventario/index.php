<?php
/**
 * app/Views/inventario/index.php
 */
$base = '../../../';
require_once $base . 'auth/middleware.php';
protegerPorRol('admin');

$page_title = 'Gestión de Inventario — Hotel Manager';
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';
?>

<div class="main-content" id="app-inventario" v-cloak>
    <div class="topbar">
        <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
        <div>
            <h1 class="h4 mb-0">Gestión de Inventario</h1>
            <p class="text-muted mb-0 small">Administración de bebidas, vinos y otros consumibles</p>
        </div>
        <div class="ms-auto d-flex gap-2">
            <a href="historial.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                <i class="bi bi-journal-text"></i> Kardex / Historial
            </a>
            <button @click="abrirNuevo" class="btn btn-primary d-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i> Agregar Producto
            </button>
        </div>
    </div>

    <div class="page-body">
        <div class="card border-0 shadow-sm" style="border-radius:12px; overflow:hidden;">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Producto</th>
                            <th>Categoría</th>
                            <th>Precio Venta</th>
                            <th class="text-center">Stock Actual</th>
                            <th>Refrigeradora</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in productos" :key="p.id">
                            <td class="ps-4">
                                <div class="fw-bold">{{ p.nombre }}</div>
                                <div class="mini text-muted">ID: {{ p.id }}</div>
                            </td>
                            <td><span class="badge bg-light text-dark border">{{ p.categoria }}</span></td>
                            <td class="fw-bold text-success">S/ {{ parseFloat(p.precio_venta).toFixed(2) }}</td>
                            <td class="text-center">
                                <span class="badge fs-6 px-3" :class="p.stock_actual <= 3 ? 'bg-danger' : 'bg-dark'">
                                    {{ p.stock_actual }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-info bg-opacity-10 text-info">REF #{{ p.refrigeradora }}</span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group">
                                    <button @click="abrirRecarga(p)" class="btn btn-sm btn-outline-success" title="Recargar Stock">
                                        <i class="bi bi-plus-circle"></i>
                                    </button>
                                    <button @click="abrirEditar(p)" class="btn btn-sm btn-outline-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button @click="confirmarEliminar(p)" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="productos.length === 0">
                            <td colspan="6" class="text-center py-5 text-muted">No hay productos en el inventario.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL EDITAR / NUEVO -->
    <div class="modal fade" id="modalProducto" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius:16px;">
                <div class="modal-header border-0 p-4 pb-0">
                    <h5 class="fw-bold mb-0">{{ editando ? 'Editar Producto' : 'Nuevo Producto' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form @submit.prevent="guardar">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nombre del Producto</label>
                            <input type="text" v-model="form.nombre" class="form-control" required placeholder="Ej: Coca Cola 500ml">
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Categoría</label>
                                <select v-model="form.categoria" class="form-select" required>
                                    <option value="BEBIDA">BEBIDA</option>
                                    <option value="VINO">VINO</option>
                                    <option value="SNACK">SNACK</option>
                                    <option value="OTROS">OTROS</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Precio Venta (S/)</label>
                                <input type="number" step="0.50" v-model="form.precio_venta" class="form-control" required>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Refrigeradora #</label>
                                <input type="number" v-model="form.refrigeradora" class="form-control" min="1" max="5">
                            </div>
                            <div v-if="!editando" class="col-6">
                                <label class="form-label small fw-bold">Stock Inicial</label>
                                <input type="number" v-model="form.stock_actual" class="form-control" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-5">{{ editando ? 'Guardar Cambios' : 'Crear Producto' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL RECARGAR STOCK -->
    <div class="modal fade" id="modalRecarga" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius:16px;">
                <div class="modal-header border-0 p-4 pb-0">
                    <h5 class="fw-bold mb-0">Recargar Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <p class="small text-muted mb-1">Producto: <b>{{ selectedProd?.nombre }}</b></p>
                    <p class="small text-muted mb-4">Stock actual: <span class="badge bg-dark">{{ selectedProd?.stock_actual }}</span></p>
                    
                    <label class="form-label d-block small fw-bold text-start">Cantidad a añadir</label>
                    <input type="number" v-model="cantidadRecarga" class="form-control form-control-lg text-center fw-bold" min="1" required>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button @click="confirmarRecarga" class="btn btn-success w-100 py-2 fw-bold">Confirmar Recarga</button>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="index.js?v=<?= time() ?>"></script>
</body></html>
