<?php
/**
 * app/Views/desayunos/index.php
 */
$base = '../../../';
$_projectRoot = dirname(__DIR__, 3);
require_once $_projectRoot . '/app/Middleware/session.php';
require_once $_projectRoot . '/app/Middleware/auth.php';
protegerPorRol('limpieza', 'desayunos');
$page_title = 'Control de Desayunos — Hotel Manager';
$export_enabled = true;
include $_projectRoot . '/app/Views/layouts/head.php';
include $_projectRoot . '/app/Views/layouts/sidebar.php';
?>

<style>
  /* CSS para replicar el diseño de la Planilla Plana (Rooming V2) */
  :root {
    --vp-informacion: #0f172a; /* Azul oscuro casi negro */
    --vp-datos: #2a49ca;        /* Azul vibrante */
    --vp-acciones: #7c2bd4;     /* Morado */
    --vp-border: #e2e8f0;
  }

  /* Tabla Estilo Excel Premium */
  .desayuno-grid-container {
    max-height: calc(100vh - 320px);
    overflow-y: auto;
    overflow-x: auto;
    border: 1px solid var(--vp-border);
    border-radius: 8px;
    background: #fff;
  }

  .desayuno-table {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
  }

  .desayuno-table thead tr:first-child th {
    font-size: 13px;
    font-weight: 900;
    text-align: center;
    padding: 15px;
    color: #fff !important;
    text-transform: uppercase;
    letter-spacing: 1px;
    border: 1px solid rgba(255,255,255,0.1) !important;
  }

  .desayuno-table thead tr:last-child th {
    background: #0f172a !important;
    color: #fff !important;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    padding: 12px 10px;
    border: 1px solid rgba(255,255,255,0.1) !important;
    text-align: center;
    white-space: nowrap;
  }

  .desayuno-table td {
    padding: 18px 12px; /* Aumentamos altura significativamente */
    vertical-align: middle;
    border: 1px solid var(--vp-border);
    background: #ffffff;
    font-size: 13px;
    color: #1e293b;
    white-space: nowrap;
  }

  .desayuno-table tbody tr:hover td {
    background: #f1f5f9;
  }

  .sticky-hab {
    font-weight: 800;
    text-align: center;
    color: #0f172a;
  }

  /* Estilo select premium de desayuno */
  .desayuno-select {
    font-size: 12px;
    font-weight: 800;
    border-radius: 6px;
    padding: 6px 12px;
    cursor: pointer;
    text-align: center;
    transition: all 0.2s;
    height: 34px;
    border: 1px solid #e2e8f0;
    width: auto;
    min-width: 100px;
    display: inline-block;
  }

  /* Botón "+" estilo imagen */
  .btn-add-dashed {
    border: 1.5px dashed #3b82f6;
    background: #eff6ff;
    color: #3b82f6;
    border-radius: 6px;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    padding: 0;
  }
  .btn-add-dashed:hover {
    background: #dbeafe;
    transform: scale(1.05);
  }

  .row-add-info {
    font-size: 12px;
    color: #64748b;
    margin-left: 12px;
  }
</style>

<div id="app-desayunos" v-cloak style="display:contents">
<div class="main-content">
    <!-- TOPBAR -->
    <div class="topbar" style="background-color: #111827; padding: 0.75rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.05);">
        <div class="d-flex align-items-center justify-content-between w-100">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-dark btn-sm rounded-circle d-md-none" onclick="handleMenuClick()" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.1); border: none;">
                    <i class="bi bi-list text-white"></i>
                </button>
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 40px; height: 40px; border-radius: 10px; background:linear-gradient(135deg,#f8fafc,#94a3b8); display: flex; align-items: center; justify-content: center; box-shadow:0 0 15px rgba(148,163,184,0.4);">
                        <i class="bi bi-egg-fried text-dark fs-5"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0 text-white" style="font-size: 18px; letter-spacing: -0.5px;">Desayunos</h4>
                        <div class="text-white-50" style="font-size: 11px;">Gestión de desayunos y consumos extra estilo Excel</div>
                    </div>
                </div>
            </div>

            <!-- Botón Actualizar a la derecha estilo imagen 2 -->
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-outline-light d-flex align-items-center gap-2" 
                        @click="verDetallePorFecha" 
                        style="font-size: 12px; padding: 4px 12px; border-color: rgba(255,255,255,0.2);">
                    <i class="bi bi-arrow-clockwise"></i> <span class="d-none d-md-inline">Actualizar</span>
                </button>
            </div>
        </div>
    </div>

    <div class="page-body p-4">
        <!-- FILTROS -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; border: 1px solid #f1f5f9 !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-2 px-3">
                <div class="d-flex align-items-center gap-4">
                    <!-- Búsqueda -->
                    <div class="input-group" style="width: 500px;">
                        <span class="input-group-text bg-white border-end-0 text-muted px-3">
                            <i class="bi bi-search" style="font-size: 15px;"></i>
                        </span>
                        <input type="text" v-model="busqueda" class="form-control border-start-0 ps-0 text-dark" 
                               placeholder="Buscar por Nombre, DNI, RUC, Empresa o Celular..." 
                               style="font-size: 14px; height: 38px; border-color: #e2e8f0;">
                    </div>
                    <!-- Contador -->
                    <div class="d-flex align-items-center gap-1 text-muted" style="font-size: 14px; white-space: nowrap;">
                        <i class="bi bi-list-ul fs-5"></i>
                        <span>{{ actual.detalles.length }} registros</span>
                    </div>

                    <!-- Selector de Fecha integra -->
                    <div class="d-flex align-items-center gap-2 ms-2 ps-3 border-start">
                         <input type="date" v-model="actual.fecha" @change="verDetallePorFecha" 
                                class="form-control form-control-sm shadow-sm" 
                                style="width:145px; height: 38px; border-color: #e2e8f0; font-size: 14px;">
                    </div>
                </div>
                
                <div class="d-flex align-items-center gap-2">
                    <!-- Botón Exportar -->
                    <button class="btn btn-success fw-bold px-3 d-flex align-items-center gap-2 shadow-sm" 
                            @click="exportarReporte" :disabled="actual.detalles.length === 0"
                            style="background-color: #059669; border: none; height: 38px; border-radius: 6px; font-size: 13px;">
                        <i class="bi bi-file-earmark-excel-fill"></i>
                        <span>Exportar Excel</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- TABLA -->
        <div class="desayuno-grid-container shadow-sm mb-4">
            <table class="desayuno-table">
                <thead>
                    <tr>
                        <th style="width: 50px;"><i class="bi bi-trash"></i></th>
                        <th style="width: 100px;">N° HAB.</th>
                        <th>HUESPED TITULAR</th>
                        <th style="width: 150px;">CANT. PAX</th>
                        <th style="width: 200px;">INCLUYE DESAYUNO</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(it, idx) in actual.detalles" :key="idx" v-if="matchesFilter(it)">
                        <td class="text-center">
                            <button class="btn btn-link text-danger p-0" @click="eliminarFila(idx)"><i class="bi bi-trash fs-5"></i></button>
                        </td>
                        <td class="sticky-hab text-primary fw-bold">{{ it.habitacion || '---' }}</td>
                        <td class="text-uppercase fw-bold">{{ it.titular || 'HUESPED' }}</td>
                        <td class="text-center fw-bold">{{ it.pax }}</td>
                        <td class="text-center">
                            <select v-model="it.incluye_desayuno" @change="autoGuardar"
                                    class="form-select form-select-sm desayuno-select shadow-sm"
                                    :style="{
                                        backgroundColor: it.incluye_desayuno ? '#d1fae5' : '#fee2e2',
                                        color: it.incluye_desayuno ? '#065f46' : '#991b1b',
                                        borderColor: it.incluye_desayuno ? '#34d399' : '#f87171'
                                    }">
                                <option :value="true">SÍ</option>
                                <option :value="false">NO</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr v-if="actual.detalles.length === 0 && !loading">
                        <td colspan="5" class="text-center py-5 text-muted fst-italic">
                            No hay huéspedes registrados para esta fecha.
                        </td>
                    </tr>
                    <tr v-if="loading">
                        <td colspan="5" class="text-center py-5">
                            <div class="spinner-border text-primary spinner-border-sm"></div> Cargando...
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <!-- ADD ROW FOOTER -->
            <div class="d-flex align-items-center p-2 border-top bg-white">
                <button class="btn-add-dashed shadow-sm" @click="añadirFila">
                    <i class="bi bi-plus-lg"></i>
                </button>
                <span class="row-add-info">Haga clic en el botón + de la izquierda para agregar un nuevo registro al final de la tabla.</span>
            </div>
        </div>

        <!-- NOTAS -->
        <div class="card border-0 shadow-sm mb-3" style="border-radius:8px; border: 1px solid #e2e8f0 !important;">
            <div class="card-body d-flex align-items-center gap-3 py-1 px-3" style="min-height: 46px;">
                 <div class="text-uppercase fw-bold text-muted" style="font-size: 11px; white-space: nowrap; letter-spacing: 0.5px;">
                    NOTAS DEL DÍA:
                 </div>
                 <input type="text" v-model="actual.observacion" @input="triggerAutoGuardarDebounced" 
                        class="form-control form-control-sm border-0 shadow-none ps-2" 
                        style="background-color: #f1f5f9; border-radius: 6px; height: 32px; font-size: 13px; color: #1e293b;"
                        placeholder="Escribir indicaciones adicionales...">
            </div>
        </div>

        <!-- Alerta Solo Lectura -->
        <div v-if="!loading && soloLectura" class="alert d-flex align-items-center py-2 px-3 mb-0" 
             style="border-radius:8px; background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd !important; min-height: 46px;">
            <i class="bi bi-info-circle-fill me-2" style="font-size: 18px; color: #0369a1;"></i>
            <div style="font-size: 13px; line-height: 1.2;">
                <strong class="fw-bold">Registro Finalizado:</strong> Este registro es histórico o ha superado el límite de las 12:00 PM para edición.
            </div>
        </div>
    </div>
</div>
</div>

<!-- SERVER DATA: URLs absolutas para el JS -->
<script>
  window.SERVER_DATA = {
    apiBase: <?= json_encode(project_base_url() . 'ajax/desayunos.php') ?>,
    hoy:     <?= json_encode((int)date('H') >= 12 ? date('Y-m-d', strtotime('+1 day')) : date('Y-m-d')) ?>
  };
</script>

<!-- LIBS específicas de Desayunos -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $_root ?>public/assets/js/desayunos.js?v=<?= time() ?>"></script>
<?php include $_projectRoot . '/app/Views/layouts/footer.php'; ?>
