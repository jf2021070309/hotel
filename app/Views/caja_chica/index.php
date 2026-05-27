<?php
/**
 * app/Views/caja_chica/index.php
 */
$base = '../../../';
$_projectRoot = dirname(__DIR__, 3);
require_once $_projectRoot . '/app/Middleware/auth.php';
protegerPorRol('cajera', 'caja_chica');

$page_title = 'Historial Caja Chica — Hotel Manager';
include $_projectRoot . '/app/Views/layouts/head.php';
include $_projectRoot . '/app/Views/layouts/sidebar.php';
?>

<div class="main-content" id="app-cchica-index">
  <div class="topbar border-bottom-0 shadow-sm" style="background: linear-gradient(to right, #ffffff, #f8f9fa);">
    <div class="d-flex align-items-center justify-content-between w-100">
      <div class="d-flex align-items-center gap-3">
        <button class="btn-burger" onclick="handleMenuClick()"><i class="bi bi-list fs-4"></i></button>
        <div>
          <h4 class="fw-bold mb-0" style="color: #111; letter-spacing: -0.5px;">
            <i class="bi bi-box2-heart me-2" style="color: #d4af37;"></i>Historial de Caja Chica
          </h4>
          <p class="mb-0 small text-muted fw-semibold">Registro de ciclos terminados y vigentes</p>
        </div>
      </div>
      <div class="text-end pe-3 pe-sm-4">
        <div class="small text-muted fw-bold text-uppercase" style="letter-spacing: 1px; font-size: 9px;">Hoy es</div>
        <div class="fw-bold text-dark" style="font-size: 13px;"><?= date('d/m/Y') ?></div>
      </div>
    </div>
  </div>

  <div class="page-body">
    <!-- PANEL PRINCIPAL ACTUAl -->
    <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
      <!-- Ahora el botón de abrir es el principal y está a la izquierda -->
      <button class="btn btn-success fw-bold shadow-sm px-4" @click="abrirNuevoCiclo">
        <i class="bi bi-plus-lg me-1"></i> ABRIR NUEVO CICLO
      </button>

      <button class="btn btn-outline-secondary ms-auto" @click="listar">
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
<script src="<?= $_root ?>app/Views/caja_chica/index.js?v=<?= filemtime(__DIR__ . '/index.js') ?>"></script>
