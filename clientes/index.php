<?php
// clientes/index.php — Shell PHP, Vue gestiona lista + creación inline
require_once '../config/conexion.php';
$base = '../'; $page_title = 'Clientes — Hotel Manager';
include '../includes/head.php';
include '../includes/sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
    <div><h4><i class="bi bi-people-fill me-2 text-primary"></i>Clientes</h4><p>Registro de huéspedes</p></div>
    <a href="crear.php" class="btn-primary-custom">
      <i class="bi bi-person-plus-fill"></i> Nuevo Cliente
    </a>

  </div>

  <div class="page-body" id="app-clientes">
    <div class="text-center py-5" v-if="loading"><div class="spinner-border text-primary"></div></div>

    <div v-if="msg.text" class="alert-custom mb-3" :class="msg.ok ? 'alert-success' : 'alert-error'">
      <i :class="msg.ok ? 'bi bi-check-circle-fill' : 'bi bi-exclamation-triangle-fill'"></i> {{ msg.text }}
    </div>

    <!-- Formulario nuevo cliente (colapsable) -->
    <div class="form-card mb-4" v-if="mostrarForm">
      <h6 class="fw-bold mb-3"><i class="bi bi-person-badge-fill me-2 text-primary"></i>Datos del nuevo cliente</h6>
      <div v-if="form.error" class="alert-custom alert-error mb-3"><i class="bi bi-exclamation-triangle-fill"></i> {{ form.error }}</div>
      <div class="row g-3">
        <div class="col-12"><label class="form-label">Nombre Completo</label>
          <input v-model="form.nombre" class="form-control" placeholder="Nombre y apellidos"></div>
        <div class="col-md-6"><label class="form-label">DNI / Documento</label>
          <input v-model="form.dni" class="form-control" placeholder="Número de documento"></div>
        <div class="col-md-6"><label class="form-label">Teléfono</label>
          <input v-model="form.telefono" class="form-control" placeholder="Teléfono"></div>
      </div>
      <div class="mt-3 d-flex gap-2">
        <button class="btn-primary-custom" @click="crearCliente" :disabled="form.guardando">
          <i class="bi bi-person-check-fill"></i> {{ form.guardando ? 'Guardando...' : 'Registrar' }}
        </button>
        <button class="btn-outline-custom" @click="mostrarForm = false">Cancelar</button>
      </div>
    </div>

    <!-- Búsqueda -->
    <div class="report-card mb-4" style="padding:14px 20px" v-if="!loading">
      <div class="d-flex gap-2">
        <input v-model="buscar" @input="filtrar" class="form-control" placeholder="Buscar por nombre o DNI..." style="max-width:380px">
        <button class="btn-outline-custom" style="padding:8px 16px" @click="buscar='';filtrar()">
          <i class="bi bi-x-circle"></i>
        </button>
      </div>
    </div>

    <!-- Tabla -->
    <div class="card-table" v-if="!loading">
      <table class="table">
        <thead><tr><th>#</th><th>Nombre</th><th>DNI</th><th>Teléfono</th><th class="text-end">Acción</th></tr></thead>
        <tbody>
          <tr v-for="(c, i) in clientesFiltrados" :key="c.id">
            <td>{{ i+1 }}</td>
            <td><strong>{{ c.nombre }}</strong></td>
            <td>{{ c.dni }}</td>
            <td>{{ c.telefono || '—' }}</td>
            <td class="text-end">
              <a :href="'../registros/crear.php?cliente_id=' + c.id" class="btn-outline-custom btn-sm">
                <i class="bi bi-person-plus"></i> Registrar Ingreso
              </a>
            </td>
          </tr>
          <tr v-if="clientesFiltrados.length === 0">
            <td colspan="5" class="text-center py-4 text-muted">No se encontraron clientes.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script src="index.js"></script>
</body></html>
