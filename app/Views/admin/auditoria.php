<?php
/**
 * app/Views/admin/auditoria.php
 */
$base = '../../../';
require_once $base . 'auth/middleware.php';
protegerPorRol('admin'); // Auditoría es solo para admin real

$page_title = 'Auditoría del Sistema — Hotel Manager';
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';
?>

<div class="main-content">
  <div class="topbar">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
    <div>
      <h4><i class="bi bi-journal-text me-2 text-primary"></i>Auditoría del Sistema</h4>
      <p>Registro histórico de acciones y eventos</p>
    </div>
  </div>

  <div class="page-body" id="app-auditoria">
    <div class="card border-0 shadow-sm overflow-hidden" style="border-radius:12px;">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light">
            <tr>
              <th class="ps-4">Fecha / Hora</th>
              <th>Módulo</th>
              <th>Acción</th>
              <th>Usuario</th>
              <th>Detalle</th>
              <th class="text-end pe-4">IP</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in logs" :key="log.id">
              <td class="ps-4">
                <div class="fw-bold small">{{ fmtFecha(log.fecha_hora) }}</div>
                <div class="text-muted" style="font-size:10px;">ID #{{ log.id }}</div>
              </td>
              <td>
                <span class="badge bg-light text-dark border">{{ log.modulo }}</span>
              </td>
              <td>
                <span :class="getAccionClass(log.accion)">{{ log.accion }}</span>
              </td>
              <td>
                <div class="fw-bold">{{ log.usuario_nombre }}</div>
                <div class="text-muted small">ID: {{ log.usuario_id || 'N/A' }}</div>
              </td>
              <td style="max-width: 300px;">
                <div class="text-truncate" :title="log.detalle">{{ log.detalle }}</div>
              </td>
              <td class="text-end pe-4 text-muted small">
                <code>{{ log.ip }}</code>
              </td>
            </tr>
            <tr v-if="!loading && logs && logs.length === 0">
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="bi bi-info-circle fs-2 d-block mb-2"></i>
                No hay registros de auditoría por el momento.
              </td>
            </tr>
            <tr v-if="loading">
              <td colspan="6" class="text-center py-5">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                <span class="ms-2">Cargando...</span>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="auditoria.js?v=<?= time() ?>"></script>

</body></html>

<style>
  .badge { padding: 6px 10px; font-weight: 600; font-size: 10px; border-radius: 6px; }
  .table thead th { font-size: 11px; letter-spacing: 0.5px; color: #6c757d; border-bottom: none; }
</style>

</body></html>
