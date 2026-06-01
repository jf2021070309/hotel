<?php
/**
 * app/Views/admin/auditoria.php
 */
require_once __DIR__ . '/../../../app/Middleware/auth.php';
protegerPorRol('cajera', 'auditoria');

$page_title = 'Auditoría del Sistema — Hotel Manager';
include __DIR__ . '/../layouts/head.php';
?>

<div id="app-auditoria" style="display:contents" v-cloak>
  <?php include __DIR__ . '/../layouts/sidebar.php'; ?>

  <div class="main-content">
    <!-- TOPBAR PREMIUM DARK -->
    <div class="topbar" style="background-color:#111827;padding:0.75rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.05);">
      <div class="d-flex align-items-center justify-content-between w-100">
        <div class="d-flex align-items-center gap-3">
          <button class="btn btn-dark btn-sm rounded-circle d-md-none" onclick="handleMenuClick()" style="width:32px;height:32px;padding:0;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.1);border:none;">
            <i class="bi bi-list text-white"></i>
          </button>
        <div class="d-flex align-items-center gap-3">
          <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#f59e0b,#d97706);display:flex;align-items:center;justify-content:center;box-shadow:0 0 15px rgba(245,158,11,0.4);">
            <i class="bi bi-journal-text text-white" style="font-size:20px;"></i>
          </div>
          <div>
            <h4 class="fw-bold mb-0 text-white" style="font-size:18px;letter-spacing:-0.5px;">Auditoría del Sistema</h4>
            <div class="text-white-50" style="font-size:11px;">Seguimiento detallado de operaciones &mdash; solo lectura &mdash; <?= date('d/m/Y') ?></div>
          </div>
        </div>
        </div>
      </div>
    </div>

    <!-- BODY -->
    <div class="page-body pt-3">

      <!-- FILTROS -->
      <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
        <div class="card-body p-3">
          <div class="d-flex flex-wrap gap-3 align-items-end justify-content-between">
            <div class="d-flex flex-wrap align-items-end gap-3">

              <!-- Buscar nombre -->
              <div>
                <label class="form-label mb-1 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Buscar por nombre</label>
                <div class="input-group input-group-sm rounded shadow-sm" style="width: 220px;">
                  <span class="input-group-text bg-white border-end-0 text-muted px-2"><i class="bi bi-search"></i></span>
                  <input type="text" class="form-control border-start-0 bg-white text-dark" style="font-size: 13px;" v-model="filters.nombre" placeholder="Nombre del usuario...">
                </div>
              </div>

              <!-- Rol -->
              <div>
                <label class="form-label mb-1 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Rol</label>
                <select class="form-select form-select-sm text-dark" v-model="filters.rol" style="width: 150px; font-size: 13px;">
                  <option value="TODOS">TODOS</option>
                  <option value="admin">Administrador</option>
                  <option value="supervisor">Supervisor</option>
                  <option value="cajera">Cajera</option>
                  <option value="limpieza">Limpieza</option>
                </select>
              </div>

              <!-- Desde -->
              <div>
                <label class="form-label mb-1 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Desde</label>
                <input type="date" class="form-control form-control-sm text-dark" v-model="filters.desde" style="width: 160px; font-size: 13px;">
              </div>

              <!-- Hasta -->
              <div>
                <label class="form-label mb-1 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Hasta</label>
                <input type="date" class="form-control form-control-sm text-dark" v-model="filters.hasta" style="width: 160px; font-size: 13px;">
              </div>
            </div>

            <!-- Acciones -->
            <div class="d-flex gap-2">
              <button class="btn btn-sm btn-custom-blue fw-bold px-3 shadow-sm" @click="fetchLogs" :disabled="loading" style="font-size: 12px;">
                <i class="bi bi-funnel me-1"></i>Filtrar
              </button>
              <button class="btn btn-sm btn-custom-green fw-bold px-3 shadow-sm" @click="exportarExcel" style="font-size: 12px;">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- GRID CONTAINER -->
      <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 12px;">
        <div class="audit-grid-container">
          <table class="table table-bordered table-hover mb-0 align-middle table-mensual">
            <thead>
              <!-- Fila 1: Grupos de color -->
              <tr class="text-center text-white text-uppercase" style="font-size: 10px; letter-spacing: 0.5px; font-weight: 800;">
                <th colspan="2" style="background-color: #111827 !important; border-bottom: none !important; z-index: 13;">FECHA Y USUARIO</th>
                <th colspan="2" style="background-color: #293b95 !important; border-bottom: none !important; border-left: 1px solid rgba(255,255,255,0.1) !important;">ACCIÓN</th>
                <th colspan="1" style="background-color: #6a1b9a !important; border-bottom: none !important; border-left: 1px solid rgba(255,255,255,0.1) !important;">DETALLES / CAMBIOS</th>
                <th colspan="1" style="background-color: #0f766e !important; border-bottom: none !important; border-left: 1px solid rgba(255,255,255,0.1) !important;">DISPOSITIVO</th>
              </tr>
              <!-- Fila 2: Sub-cabeceras -->
              <tr class="text-center text-white text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                <th style="width: 130px; top: 38px; background-color: #111827 !important; border-top: none !important;">FECHA / HORA</th>
                <th style="width: 160px; top: 38px; background-color: #111827 !important; border-top: none !important;">USUARIO / ROL</th>
                <th style="width: 130px; top: 38px; background-color: #293b95 !important; border-top: none !important; border-left: 1px solid rgba(255,255,255,0.1) !important;">MÓDULO</th>
                <th style="width: 160px; top: 38px; background-color: #293b95 !important; border-top: none !important;">ACCIÓN</th>
                <th style="min-width: 380px; top: 38px; background-color: #6a1b9a !important; border-top: none !important; border-left: 1px solid rgba(255,255,255,0.1) !important;">DETALLE</th>
                <th style="width: 150px; top: 38px; background-color: #0f766e !important; border-top: none !important; border-left: 1px solid rgba(255,255,255,0.1) !important;">IP / DISPOSITIVO</th>
              </tr>
            </thead>
            <tbody>
              <!-- Spinner -->
              <tr v-if="loading">
                <td colspan="6" class="text-center py-5">
                  <div class="spinner-border text-primary me-2"></div>
                  <span class="text-muted fw-semibold">Cargando registros de auditoría...</span>
                </td>
              </tr>
              <!-- Sin datos -->
              <tr v-else-if="logs.length === 0">
                <td colspan="6" class="text-center py-5 text-muted">
                  <i class="bi bi-shield-check fs-1 d-block opacity-25 mb-2"></i>
                  <span>No se encontraron registros con los filtros aplicados.</span>
                </td>
              </tr>
              <!-- Filas de datos (solo lectura) -->
              <tr v-else v-for="log in logs" :key="log.id" class="audit-row">
                <!-- FECHA / HORA -->
                <td class="text-center px-2 py-3">
                  <div class="fw-bold text-dark" style="font-size: 13px;">{{ fmtFechaSolo(log.fecha_hora) }}</div>
                  <div class="text-muted" style="font-size: 11px;">{{ fmtHoraSolo(log.fecha_hora) }}</div>
                </td>
                <!-- USUARIO / ROL -->
                <td class="text-center px-2">
                  <div class="fw-bold text-dark" style="font-size: 13px;">{{ log.usuario_nombre }}</div>
                  <span class="badge bg-light text-dark border text-uppercase" style="font-size: 9px;">{{ log.rol_usuario || 'Admin' }}</span>
                </td>
                <!-- MÓDULO -->
                <td class="text-center px-2">
                  <span class="module-capsule text-uppercase">{{ log.modulo }}</span>
                </td>
                <!-- ACCIÓN -->
                <td class="text-center px-2">
                  <span :class="getAccionClass(log.accion)" class="badge px-2 py-1 text-uppercase">
                    {{ (log.accion || '').replace('_', ' ') }}
                  </span>
                </td>
                <!-- DETALLE -->
                <td class="text-start px-3 py-2">
                  <div v-if="!esJson(log.detalle)" class="text-muted" style="font-size: 12.5px;">{{ log.detalle }}</div>
                  <div v-else class="py-1">
                    <div class="fw-bold text-dark mb-2" style="font-size: 13px;">{{ parseDetalle(log.detalle).mensaje }}</div>
                    <div v-if="parseDetalle(log.detalle).cambios" class="p-2" style="background-color: #f8fafc; border-radius: 8px;">
                      <div v-for="(val, field) in parseDetalle(log.detalle).cambios" :key="field" class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-chevron-right text-muted" style="font-size: 9px;"></i>
                        <div class="d-flex align-items-center flex-wrap gap-2" style="font-size: 12px;">
                          <span class="fw-bold text-secondary text-uppercase" style="font-size: 10px; min-width: 80px;">{{ field }}:</span>
                          <span class="text-danger text-decoration-line-through">{{ val.antes || '(vacío)' }}</span>
                          <i class="bi bi-arrow-right text-muted opacity-50"></i>
                          <span class="text-success fw-bold">{{ val.despues || '(vacío)' }}</span>
                        </div>
                      </div>
                    </div>
                    <div v-else-if="log.accion && log.accion.includes('ACTUALIZAR')" class="text-muted" style="font-size: 11px; font-style: italic;">
                      No se detectaron cambios en los valores principales.
                    </div>
                  </div>
                </td>
                <!-- IP / DISPOSITIVO -->
                <td class="text-center px-2">
                  <div class="fw-bold text-dark" style="font-size: 11px;">{{ log.dispositivo || '—' }}</div>
                  <div class="text-muted" style="font-size: 10px;">IP: {{ log.ip }}</div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div><!-- /page-body -->
  </div><!-- /main-content -->
</div>

<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="auditoria.js?v=<?= time() ?>"></script>

<style>
  [v-cloak] { display: none !important; }
  body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }

  /* GRID CONTAINER */
  .audit-grid-container {
    overflow-x: auto;
    background: #fff;
    position: relative;
  }

  /* TABLE */
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
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
    background-color: #fff;
    transition: background-color 0.15s;
  }

  /* Eliminar bordes internos entre las dos filas del encabezado */
  .table-mensual thead tr:first-child th { border-bottom: none !important; }
  .table-mensual thead tr:last-child th  { border-top: none !important; }
  .table-mensual thead th { border-left: none !important; border-right: none !important; }
  .table-mensual thead th[style*="border-left"] {
    border-left: 1px solid rgba(255,255,255,0.15) !important;
  }

  /* Hover de filas de datos */
  .audit-row:hover td { background-color: #f8fafc !important; }

  /* Módulos Cápsula */
  .module-capsule {
    background-color: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.5px;
    display: inline-block;
  }

  /* Badges de acción */
  .badge { padding: 5px 12px; border-radius: 10px; font-weight: 800; font-size: 10px; }
  .badge-yellow { background-color: #fef9c3; color: #854d0e; border: 1px solid #fde047; }
  .badge-red    { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
  .badge-blue   { background: #e0f2fe; color: #075985; border: 1px solid #bae6fd; }
  .badge-green  { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
  .badge-gray   { background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; }

  /* Botones */
  .btn-custom-blue {
    background-color: #1a56db !important;
    color: #fff !important;
    border: 1px solid #1e429f !important;
    transition: all 0.2s;
  }
  .btn-custom-blue:hover:not(:disabled) { background-color: #1e429f !important; }
  .btn-custom-blue:disabled { opacity: 0.65; }

  .btn-custom-green {
    background-color: #059669 !important;
    color: #fff !important;
    border: 1px solid #047857 !important;
    transition: all 0.2s;
  }
  .btn-custom-green:hover { background-color: #047857 !important; }

  .hover-bg-light:hover { background-color: rgba(255,255,255,0.15) !important; }
  .spin-anim { animation: spin 1s linear infinite; }
  @keyframes spin { 100% { transform: rotate(360deg); } }
</style>

</body></html>
