<?php
/**
 * app/Views/admin/auditoria.php
 */
require_once __DIR__ . '/../../../app/Middleware/auth.php';
protegerPorRol('cajera', 'auditoria');

$page_title = 'Auditoría del Sistema — Hotel Manager';
include __DIR__ . '/../layouts/head.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-content">
  <div class="topbar border-bottom-0 shadow-sm" style="background: linear-gradient(to right, #ffffff, #f8f9fa);">
    <div class="d-flex align-items-center justify-content-between w-100">
      <div class="d-flex align-items-center gap-3">
        <button class="btn-burger" onclick="handleMenuClick()"><i class="bi bi-list fs-4"></i></button>
        <div>
          <h4 class="fw-bold m-0" style="color: #111; letter-spacing: -0.5px;">
            <i class="bi bi-shield-check me-2 text-primary"></i>Auditoría del Sistema
          </h4>
          <p class="mb-0 small text-muted fw-semibold">Seguimiento detallado de operaciones</p>
        </div>
      </div>
      <div class="text-end pe-3 pe-sm-4">
        <div class="small text-muted fw-bold text-uppercase" style="letter-spacing: 1px; font-size: 9px;">Hoy es</div>
        <div class="fw-bold text-dark" style="font-size: 13px;"><?= date('d/m/Y') ?></div>
      </div>
    </div>
  </div>

  <div class="page-body px-4 pt-3" id="app-auditoria">
    
    <!-- Filtros -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius:16px;">
      <div class="card-body p-4">
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Buscar por nombre</label>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
              <input type="text" class="form-control border-start-0 ps-0" placeholder="Nombre del usuario..." v-model="filters.nombre">
            </div>
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Rol</label>
            <select class="form-select form-select-sm" v-model="filters.rol">
              <option value="TODOS">TODOS</option>
              <option value="admin">Administrador</option>
              <option value="supervisor">Supervisor</option>
              <option value="cajera">Cajera</option>
              <option value="limpieza">Limpieza</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Desde</label>
            <input type="date" class="form-control form-control-sm" v-model="filters.desde">
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Hasta</label>
            <input type="date" class="form-control form-control-sm" v-model="filters.hasta">
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-primary btn-sm flex-grow-1 fw-bold" @click="fetchLogs">Filtrar</button>
            <button class="btn btn-success btn-sm flex-grow-1 fw-bold" @click="exportarExcel">Excel</button>
          </div>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:16px;">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light">
            <tr>
              <th class="py-3" style="width: 130px;">FECHA / HORA</th>
              <th style="width: 180px;">USUARIO / TRABAJADOR</th>
              <th style="width: 130px;">MÓDULO</th>
              <th style="width: 180px;">ACCIÓN</th>
              <th class="text-center">DETALLES / CAMBIOS</th>
              <th class="pe-4 text-center" style="width: 160px;">DISPOSITIVO</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in logs" :key="log.id">
              <td class="ps-4">
                <div class="fw-bold">{{ fmtFechaSolo(log.fecha_hora) }}</div>
                <div class="small text-muted">{{ fmtHoraSolo(log.fecha_hora) }}</div>
              </td>
              <td>
                <div class="fw-bold">{{ log.usuario_nombre }}</div>
                <span class="badge bg-light text-muted border text-uppercase" style="font-size: 9px;">{{ log.rol_usuario || 'Admin' }}</span>
              </td>
              <td>
                <span class="module-capsule text-uppercase px-3 py-1">
                   {{ log.modulo }}
                </span>
              </td>
              <td>
                <span :class="getAccionClass(log.accion)" class="badge px-2 py-1 text-uppercase">
                  {{ (log.accion || '').replace('_', ' ') }}
                </span>
              </td>
              <td class="text-start px-4" style="min-width: 400px; max-width: 600px;">
                <!-- Si NO es JSON -->
                <div v-if="!esJson(log.detalle)" class="text-muted small">
                  {{ log.detalle }}
                </div>
                <!-- SI ES JSON -->
                <div v-else class="py-2">
                    <div class="fw-bold text-dark mb-2" style="font-size: 13px;">{{ parseDetalle(log.detalle).mensaje }}</div>
                    
                    <div v-if="parseDetalle(log.detalle).cambios" class="p-3 border-0" style="background-color: #f8fafc; border-radius: 12px;">
                        <div v-for="(val, field) in parseDetalle(log.detalle).cambios" :key="field" class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-chevron-right text-muted" style="font-size: 9px;"></i>
                            <div class="d-flex align-items-center flex-wrap gap-2" style="font-size: 12.5px;">
                                <span class="fw-bold text-secondary text-uppercase" style="font-size: 10px; min-width: 80px;">{{ field }}:</span>
                                <span class="text-danger text-decoration-line-through">{{ val.antes || '(vacio)' }}</span>
                                <i class="bi bi-arrow-right text-muted opacity-50"></i>
                                <span class="text-success fw-bold">{{ val.despues || '(vacio)' }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="log.accion.includes('ACTUALIZAR')" class="small text-muted italic">
                      No se detectaron cambios en los valores principales.
                    </div>
                </div>
              </td>
              <td class="pe-4 text-end">
                <div class="fw-bold small">{{ log.dispositivo }}</div>
                <div class="text-muted" style="font-size: 10px;">IP: {{ log.ip }}</div>
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
<script src="auditoria.js?v=<?= time() ?>"></script>

<style>
  /* Cabeceras Premium */
  .table thead th {
    background-color: #f1f5f9; /* Fondo azulado suave */
    color: #475569;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 16px 10px;
    border: none;
    text-align: center;
    vertical-align: middle;
  }

  /* Redondear la primera y última celda de la cabecera */
  .table thead tr th:first-child { border-radius: 12px 0 0 0; }
  .table thead tr th:last-child { border-radius: 0 12px 0 0; }

  /* Estilos para celdas de datos */
  .table tbody td {
    padding: 18px 10px;
    text-align: center;
    border-bottom: 1px solid #f1f5f9;
  }

  /* Forzar alineación a la izquierda SOLO para detalles */
  .text-start {
    text-align: left !important;
  }

  /* Labels de Acción - Colores de Imagen Referencia */
  .badge-yellow { background-color: #fef9c3; color: #854d0e; border: 1px solid #fde047; }
  .badge-red { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
  .badge-blue { background: #e0f2fe; color: #075985; border: 1px solid #bae6fd; }
  .badge-green { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
  .badge-gray { background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; }

  /* Módulos Cápsula */
  .module-capsule {
    background-color: #f8fafc;
    color: #475569;
    border: 1px solid #e2e8f0;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.5px;
  }

  .badge { padding: 6px 14px; border-radius: 12px; font-weight: 800; font-size: 10px; }
  .table tr:hover { background-color: #fbfcfe !important; }
</style>
</body></html>
