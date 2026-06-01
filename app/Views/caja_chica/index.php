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
  <div class="topbar" style="background-color: #111827; padding: 0.75rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.05);">
    <div class="d-flex align-items-center justify-content-between w-100">
      <div class="d-flex align-items-center gap-3">
        <button class="btn btn-dark btn-sm rounded-circle d-md-none" onclick="handleMenuClick()" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.1); border: none;">
          <i class="bi bi-list text-white"></i>
        </button>
        <div class="d-flex align-items-center gap-3">
            <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #f59e0b, #d97706); display: flex; align-items: center; justify-content: center; box-shadow: 0 0 15px rgba(245, 158, 11, 0.4);">
                <i class="bi bi-box2-heart text-white fs-5"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0 text-white" style="font-size: 18px; letter-spacing: -0.5px;">Caja Chica</h4>
                <div class="text-white-50" style="font-size: 11px;">Registro de ciclos terminados y vigentes</div>
            </div>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
          <button @click="listar" class="btn btn-sm btn-outline-light d-flex align-items-center gap-2" style="font-size: 12px; padding: 4px 12px; border-color: rgba(255,255,255,0.2);">
              <i class="bi bi-arrow-clockwise"></i> <span class="d-none d-md-inline">Actualizar</span>
          </button>
      </div>
    </div>
  </div>

  <div class="page-body px-3 py-3" v-cloak>

    <!-- BARRA DE ACCIONES -->
    <div class="card border-0 shadow-sm mb-3" style="border-radius:12px;">
        <div class="card-body p-3 d-flex align-items-center justify-content-between">
            <button @click="abrirNuevoCiclo" class="btn btn-sm btn-success fw-bold shadow-sm px-3" style="font-size: 12px; height: 30px;">
                <i class="bi bi-plus-circle me-1"></i> Abrir Nuevo Ciclo
            </button>
        </div>
    </div>

    <!-- GRID INTERACTIVO -->
    <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 12px;">
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary"></div>
        <div class="mt-2 text-muted small">Cargando ciclos...</div>
      </div>

      <div class="table-responsive" v-else>
        <table class="table table-hover align-middle mb-0" style="font-size:12.5px; white-space: nowrap;">
          <thead>
            <tr style="font-size: 11px; letter-spacing: 0.5px; text-transform: uppercase;">
                <th colspan="1" class="text-center align-middle" style="background-color: #111827 !important; color: white !important; border: 1px solid rgba(255,255,255,0.1) !important; padding: 12px;">INFORMACIÓN</th>
                <th colspan="2" class="text-center align-middle" style="background-color: #293b95 !important; color: white !important; border: 1px solid rgba(255,255,255,0.1) !important; padding: 12px;">TIEMPO Y REGISTRO</th>
                <th colspan="3" class="text-center align-middle" style="background-color: #6a1b9a !important; color: white !important; border: 1px solid rgba(255,255,255,0.1) !important; padding: 12px;">ESTADO FINANCIERO</th>
                <th colspan="2" class="text-center align-middle" style="background-color: #0f766e !important; color: white !important; border: 1px solid rgba(255,255,255,0.1) !important; padding: 12px;">ACCIONES PRINCIPALES</th>
            </tr>
            <tr style="font-size: 11px; letter-spacing: 0.5px; text-transform: uppercase;">
              <th class="text-center align-middle" style="background-color: #111827 !important; color: white !important; border-top: none !important; width: 150px; padding: 12px;">NOMBRE DEL CICLO</th>
              <th class="text-center align-middle" style="background-color: #293b95 !important; color: white !important; border-top: none !important; border-left: 1px solid rgba(255,255,255,0.1) !important; width: 140px; padding: 12px;">APERTURA</th>
              <th class="text-center align-middle" style="background-color: #293b95 !important; color: white !important; border-top: none !important; width: 140px; padding: 12px;">CIERRE</th>
              <th class="text-center align-middle" style="background-color: #6a1b9a !important; color: white !important; border-top: none !important; border-left: 1px solid rgba(255,255,255,0.1) !important; width: 110px; padding: 12px;">INICIAL</th>
              <th class="text-center align-middle" style="background-color: #6a1b9a !important; color: white !important; border-top: none !important; width: 110px; padding: 12px;">GASTADO</th>
              <th class="text-center align-middle" style="background-color: #6a1b9a !important; color: white !important; border-top: none !important; width: 110px; padding: 12px;">SALDO ACTUAL</th>
              <th class="text-center align-middle" style="background-color: #0f766e !important; color: white !important; border-top: none !important; border-left: 1px solid rgba(255,255,255,0.1) !important; width: 100px; padding: 12px;">ESTADO</th>
              <th class="text-center align-middle" style="background-color: #0f766e !important; color: white !important; border-top: none !important; width: 80px; padding: 12px;">ACCIÓN</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="ciclos.length === 0">
              <td colspan="8" class="text-center py-5 text-muted">
                <i class="bi bi-box-seam fs-1 d-block opacity-25 mb-2"></i>
                <span>No se encontraron ciclos de caja chica.</span>
              </td>
            </tr>
            <tr v-for="c in ciclos" :key="c.id">
              <td class="fw-bold text-dark px-3">{{ c.nombre }}</td>
              <td class="px-3">
                <div class="fw-bold text-dark">{{ c.fecha_apertura.split(' ')[0] }}</div>
                <div class="text-muted" style="font-size: 10px;">
                    {{ c.fecha_apertura.split(' ')[1] }} <i class="bi bi-person mx-1"></i>{{ c.usuario_apertura.split(' ')[0] }}
                </div>
              </td>
              <td class="px-3">
                <template v-if="c.fecha_cierre">
                    <div class="fw-bold text-dark">{{ c.fecha_cierre.split(' ')[0] }}</div>
                    <div class="text-muted" style="font-size: 10px;">
                        {{ c.fecha_cierre.split(' ')[1] }} <i class="bi bi-person mx-1"></i>{{ c.usuario_cierre.split(' ')[0] }}
                    </div>
                </template>
                <div v-else class="text-muted fst-italic" style="font-size: 11px;">- En curso -</div>
              </td>
              <td class="text-center text-muted fw-bold px-2">S/ {{ parseFloat(c.saldo_inicial).toFixed(2) }}</td>
              <td class="text-center text-danger fw-bold px-2">- S/ {{ parseFloat(c.total_gastado).toFixed(2) }}</td>
              <td class="text-center fw-bold px-2 fs-6" :class="c.estado === 'abierta' ? 'text-success' : 'text-primary'">
                S/ {{ (parseFloat(c.saldo_inicial) - parseFloat(c.total_gastado)).toFixed(2) }}
              </td>
              <td class="text-center px-2">
                  <span class="badge rounded-pill" :class="c.estado === 'abierta' ? 'bg-success' : 'bg-secondary'" style="font-size: 9.5px; padding: 4px 10px; letter-spacing: 0.5px;">
                      {{ c.estado.toUpperCase() }}
                  </span>
              </td>
              <td class="text-center px-2">
                  <a :href="'detalle.php?id=' + c.id" class="btn btn-sm btn-light border shadow-sm px-2 py-1 text-primary fw-bold" style="font-size: 11px;" title="Ver Detalle">
                      <i class="bi bi-eye"></i> Detalle
                  </a>
              </td>
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
