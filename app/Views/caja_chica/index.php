<?php
/**
 * app/Views/caja_chica/index.php
 */
$base = '../../../';
require_once $base . 'auth/middleware.php';
protegerPorRol('cajera'); 

$page_title = 'Historial Caja Chica — Hotel Manager';
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';
?>

<div class="main-content" id="app-cchica-index">
  <div class="topbar">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
    <div>
      <h4><i class="bi bi-box2-heart me-2 text-primary"></i>Historial de Caja Chica</h4>
      <p class="mb-0 small text-muted">Registro de ciclos terminados y vigentes</p>
    </div>
  </div>

  <div class="page-body">
    <!-- PANEL PRINCIPAL ACTUAl -->
    <div class="d-flex flex-wrap gap-2 mb-4 align-items-center">
      <a href="detalle.php" class="btn btn-primary fw-bold shadow-sm px-4">
        <i class="bi bi-box-arrow-in-right me-2"></i> IR AL CICLO ACTIVO ACTUAL
      </a>
      <!-- Este botón solo debe mostrarse/habilitarse si no hay ciclo activo, lo controlamos con js -->
      <button class="btn btn-success ms-auto" @click="abrirNuevoCiclo" v-if="!hayCicloActivo">
        <i class="bi bi-plus-lg me-1"></i> Abrir Nuevo Ciclo Inicial
      </button>
      <button class="btn btn-outline-secondary" @click="listar">
        <i class="bi bi-arrow-clockwise"></i>
      </button>
    </div>

    <!-- TABLA DE CICLOS -->
    <div class="card border-0 shadow-sm" style="border-radius:10px; overflow:hidden;">
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary"></div>
        <div class="mt-2 text-muted small">Cargando ciclos...</div>
      </div>

      <div class="table-responsive" v-else>
        <table class="table table-hover align-middle mb-0" style="font-size:13px;">
          <thead class="table-light text-secondary">
            <tr style="font-size:11px; text-transform:uppercase; letter-spacing:0.5px;">
              <th>Nombre del Ciclo</th>
              <th>Apertura</th>
              <th>Cierre</th>
              <th class="text-end">Fondo Inicial</th>
              <th class="text-end text-danger">Gastado</th>
              <th class="text-end">Saldo Final / Actual</th>
              <th class="text-center">Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in ciclos" :key="c.id">
              <td class="fw-bold text-dark">{{ c.nombre }}</td>
              <td>
                <div>{{ c.fecha_apertura }}</div>
                <small class="text-muted"><i class="bi bi-person me-1"></i>{{ c.usuario_apertura }}</small>
              </td>
              <td>
                <div v-if="c.fecha_cierre">{{ c.fecha_cierre }}</div>
                <div v-else class="text-muted fst-italic">- En curso -</div>
                <small v-if="c.usuario_cierre" class="text-muted"><i class="bi bi-person me-1"></i>{{ c.usuario_cierre }}</small>
              </td>
              <td class="text-end text-muted">S/ {{ parseFloat(c.saldo_inicial).toFixed(2) }}</td>
              <td class="text-end text-danger fw-bold">- S/ {{ parseFloat(c.total_gastado).toFixed(2) }}</td>
              <td class="text-end fw-bold" :class="c.estado === 'abierta' ? 'text-success' : 'text-primary'">
                S/ {{ (parseFloat(c.saldo_inicial) - parseFloat(c.total_gastado)).toFixed(2) }}
              </td>
              <td class="text-center">
                <span class="badge" :class="c.estado === 'abierta' ? 'bg-success' : 'bg-secondary'">
                  {{ c.estado.toUpperCase() }}
                </span>
              </td>
            </tr>
            <tr v-if="ciclos.length === 0">
              <td colspan="7" class="text-center py-4 text-muted">No se encontraron ciclos de caja chica.</td>
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
<script src="<?= $base ?>app/Views/caja_chica/index.js"></script>
