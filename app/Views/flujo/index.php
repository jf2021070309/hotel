<?php
/**
 * app/Views/flujo/index.php
 */
$base = '../../../';
require_once $base . 'auth/middleware.php';
protegerPorRol('cajera'); 

$page_title = 'Flujo de Caja — Hotel Manager';
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';
?>

<div class="main-content" id="app-flujo-index">
  <div class="topbar border-bottom-0 shadow-sm" style="background: linear-gradient(to right, #ffffff, #f8f9fa);">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list fs-4"></i></button>
    <div>
      <h4 class="fw-bold" style="color: #111; letter-spacing: -0.5px;">
        <i class="bi bi-cash-stack me-2" style="color: #d4af37;"></i>Flujo de Caja
      </h4>
      <p class="mb-0 small text-muted fw-semibold">Control de ingresos, egresos y efectivo por turnos</p>
    </div>
    <div class="ms-auto d-flex align-items-center gap-2">
      <!-- Mismos botones globales de filtro -->
      <select class="form-select form-select-sm" v-model="filtros.mes" @change="listar" style="width:120px;">
        <option v-for="(m, i) in meses" :key="i" :value="i+1">{{ m }}</option>
      </select>
      <input type="number" class="form-control form-control-sm" v-model="filtros.anio" @change="listar" style="width:80px;" min="2020">
    </div>
  </div>

  <div class="page-body">
    <!-- BARRA DE ACCIONES SUPERIOR -->
    <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
      <button class="btn-primary-custom shadow-sm" @click="nuevoTurno" :disabled="loadingCheck" style="border: 1px solid #111;">
        <span v-if="loadingCheck" class="spinner-border spinner-border-sm me-1"></span>
        <i v-else class="bi bi-plus-lg me-1 text-warning"></i>Nuevo Turno (Hoy)
      </button>
      <select class="form-select form-select-sm" v-model="filtros.estado" @change="listar" style="width:160px;">
        <option value="todos">Todos los Estados</option>
        <option value="borrador">Borrador</option>
        <option value="cerrado">Cerrado</option>
        <option value="depositado">Depositado</option>
      </select>
      <a href="dia.php" class="btn btn-sm btn-outline-dark ms-auto">
        <i class="bi bi-calendar2-range me-1"></i>Ver Resumen del Día
      </a>
      <button class="btn btn-sm btn-outline-secondary" @click="listar"><i class="bi bi-arrow-clockwise"></i></button>
    </div>

    <!-- TABLA DE TURNOS -->
    <div class="card border-0 shadow-sm" style="border-radius:10px; overflow:hidden;">
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-success"></div>
        <div class="mt-2 text-muted small">Cargando flujos...</div>
      </div>

      <div class="table-responsive" v-else>
        <table class="table table-hover align-middle mb-0" style="font-size:13px;">
          <thead class="table-light text-secondary">
            <tr style="font-size:11px; text-transform:uppercase; letter-spacing:0.5px;">
              <th>ID</th>
              <th>Fecha</th>
              <th>Turno</th>
              <th>Operador</th>
              <th class="text-end">Ingresos</th>
              <th class="text-end">Egresos</th>
              <th class="text-end text-success">SE ENTREGA A ALEX</th>
              <th>Estado</th>
              <th class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="f in flujos" :key="f.id">
              <td class="text-muted fw-bold">#{{ f.id }}</td>
              <td class="fw-bold">{{ f.fecha }}</td>
              <td><span class="badge bg-secondary rounded-pill"><i class="bi" :class="f.turno==='MAÑANA'?'bi-sun-fill text-warning':'bi-moon-stars-fill'"></i> {{ f.turno }}</span></td>
              <td>{{ f.operador }}</td>
              <td class="text-end">S/ {{ parseFloat(f.total_ingresos).toFixed(2) }}</td>
              <td class="text-end">S/ {{ parseFloat(f.total_egresos).toFixed(2) }}</td>
              <td class="text-end fw-bold text-success">S/ {{ parseFloat(f.efectivo_sobre).toFixed(2) }}</td>
              <td>
                <span class="badge" :class="estadoClass(f.estado)">
                  {{ f.estado.toUpperCase() }}
                </span>
              </td>
              <td class="text-end">
                <a :href="'form.php?id=' + f.id" class="btn btn-sm" :class="f.estado==='borrador'?'btn-primary':'btn-outline-dark'">
                  <i class="bi" :class="f.estado==='borrador'?'bi-pencil-square':'bi-eye'"></i>
                  {{ f.estado==='borrador' ? 'Editar' : 'Ver' }}
                </a>
              </td>
            </tr>
            <tr v-if="flujos.length === 0">
              <td colspan="9" class="text-center py-4 text-muted">No se encontraron turnos con los filtros aplicados.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $base ?>app/Views/flujo/index.js"></script>
