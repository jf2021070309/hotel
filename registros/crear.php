<?php
// registros/crear.php — Shell PHP para registrar ingreso
require_once '../config/conexion.php';
$base = '../'; $page_title = 'Registrar Ingreso — Hotel Manager';
// Pre-selección desde dashboard o clientes
$pre_hab    = (int)($_GET['hab']        ?? 0);
$pre_cliente= (int)($_GET['cliente_id'] ?? 0);
include '../includes/head.php';
include '../includes/sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <div><h4><i class="bi bi-person-plus-fill me-2 text-primary"></i>Registrar Ingreso</h4><p>Registro de nuevos huéspedes</p></div>
    <a href="index.php" class="btn-outline-custom"><i class="bi bi-arrow-left"></i> Volver</a>
  </div>

  <div class="page-body" id="app-checkin">
    <div class="text-center py-5" v-if="loading"><div class="spinner-border text-primary"></div></div>

    <div class="form-card" v-if="!loading">
      <div class="mb-4 d-flex align-items-center gap-3">
        <div class="stat-icon blue" style="width:44px;height:44px;font-size:18px"><i class="bi bi-person-plus-fill"></i></div>
        <div><h5 class="mb-0 fw-bold">Nuevo Ingreso</h5><small class="text-muted">Complete los datos del huésped</small></div>
      </div>

      <div v-if="error" class="alert-custom alert-error mb-3"><i class="bi bi-exclamation-triangle-fill"></i> {{ error }}</div>

      <div class="row g-3">
        <!-- Habitación -->
        <div class="col-12">
          <label class="form-label">Habitación Libre</label>
          <select v-model="form.habitacion_id" class="form-select" @change="autoFillPrecio">
            <option value="">Seleccionar habitación libre...</option>
            <option v-for="h in habitaciones" :key="h.id" :value="h.id" :data-precio="h.precio_base">
              {{ h.numero }} — {{ h.tipo }} (S/ {{ parseFloat(h.precio_base).toFixed(2) }}/noche)
            </option>
          </select>
        </div>

        <!-- Tipo de cliente -->
        <div class="col-12">
          <label class="form-label">Cliente</label>
          <div class="d-flex gap-3 mb-2">
            <label class="d-flex align-items-center gap-2" style="cursor:pointer">
              <input type="radio" v-model="clienteTipo" value="existente"> Cliente existente
            </label>
            <label class="d-flex align-items-center gap-2" style="cursor:pointer">
              <input type="radio" v-model="clienteTipo" value="nuevo"> Nuevo cliente
            </label>
          </div>

          <div v-if="clienteTipo === 'existente'">
            <select v-model="form.cliente_id" class="form-select">
              <option value="">Seleccionar cliente...</option>
              <option v-for="c in clientes" :key="c.id" :value="c.id">{{ c.nombre }} — DNI: {{ c.dni }}</option>
            </select>
          </div>
          <div v-else class="row g-2">
            <div class="col-12"><input v-model="form.nombre" class="form-control" placeholder="Nombre completo"></div>
            <div class="col-md-6"><input v-model="form.dni" class="form-control" placeholder="DNI / Documento"></div>
            <div class="col-md-6"><input v-model="form.telefono" class="form-control" placeholder="Teléfono"></div>
          </div>
        </div>

        <div class="col-md-6">
          <label class="form-label">Fecha de Ingreso</label>
          <input v-model="form.fecha_ingreso" type="datetime-local" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Precio por Noche (S/)</label>
          <input v-model="form.precio" type="number" step="0.01" class="form-control" placeholder="0.00">
        </div>
      </div>

      <div class="mt-4">
        <button class="btn-primary-custom w-100 justify-content-center" @click="registrar" :disabled="guardando">
          <i class="bi bi-person-check-fill"></i> {{ guardando ? 'Registrando...' : 'Registrar Ingreso' }}
        </button>
      </div>
    </div><!-- /form-card -->
  </div>
</div>

<script>
  const PRE_HAB     = <?= $pre_hab ?>;
  const PRE_CLIENTE = <?= $pre_cliente ?>;
</script>
<script src="crear.js"></script>
</body></html>
