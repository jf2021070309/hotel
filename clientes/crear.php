<?php
// clientes/crear.php — Formulario para registrar nuevo cliente
require_once '../config/conexion.php';
$base = '../'; $page_title = 'Nuevo Cliente — Hotel Manager';
include '../includes/head.php';
include '../includes/sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <div><h4><i class="bi bi-person-plus-fill me-2 text-primary"></i>Nuevo Cliente</h4><p>Registro de cliente</p></div>
    <a href="index.php" class="btn-outline-custom"><i class="bi bi-arrow-left"></i> Volver</a>
  </div>

  <div class="page-body" id="app-cli-crear">
    <div class="form-card" style="max-width:560px;margin:auto">
      <div class="mb-4 d-flex align-items-center gap-3">
        <div class="stat-icon blue" style="width:44px;height:44px;font-size:18px"><i class="bi bi-person-badge-fill"></i></div>
        <div><h5 class="mb-0 fw-bold">Nuevo Cliente</h5><small class="text-muted">Registre los datos del huésped</small></div>
      </div>

      <div v-if="error" class="alert-custom alert-error mb-3"><i class="bi bi-exclamation-triangle-fill"></i> {{ error }}</div>

      <div class="row g-3">
        <div class="col-12">
          <label class="form-label">Nombre Completo</label>
          <input v-model="form.nombre" class="form-control" placeholder="Nombre y apellidos">
        </div>
        <div class="col-md-6">
          <label class="form-label">DNI / Nro. Documento</label>
          <input v-model="form.dni" class="form-control" placeholder="Número de documento">
        </div>
        <div class="col-md-6">
          <label class="form-label">Teléfono</label>
          <input v-model="form.telefono" class="form-control" placeholder="Opcional">
        </div>
      </div>

      <div class="mt-4">
        <button class="btn-primary-custom w-100 justify-content-center" @click="guardar" :disabled="guardando">
          <i class="bi bi-person-check-fill"></i> {{ guardando ? 'Guardando...' : 'Registrar Cliente' }}
        </button>
      </div>
    </div>
  </div>
</div>
<script src="crear.js"></script>
</body></html>
