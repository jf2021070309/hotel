<?php
/**
 * app/Views/desayunos/index.php
 */
$base = '../../../';
$_projectRoot = defined('BASE_PATH') ? BASE_PATH : (rtrim(realpath(dirname(__DIR__, 3)), '\\/') . DIRECTORY_SEPARATOR);
require_once $_projectRoot . 'app/Middleware/session.php';
require_once $_projectRoot . 'app/Middleware/auth.php';
protegerPorRol('limpieza', 'desayunos');

$page_title = 'Desayunos — Hotel Manager';
include $_projectRoot . 'app/Views/layouts/head.php';

$fechaStr = date('Y-m-d');
?>

<!-- VUE APP -->
<div id="app-desayunos" style="display:contents" v-cloak>
  <?php include $_projectRoot . 'app/Views/layouts/sidebar.php'; ?>
  <div class="main-content">
    <!-- CONTROLES SUPERIORES (Fuera de impresión) -->
    <div class="px-3 pt-3 pb-2 d-flex justify-content-end gap-2 d-print-none">
        <button class="btn btn-primary fw-bold shadow-sm d-flex align-items-center gap-2 px-3" 
                @click="guardarCambios" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm"></span>
            <i v-else class="bi bi-save"></i> 
            Guardar Cambios
        </button>
        <button class="btn btn-danger fw-bold shadow-sm d-flex align-items-center gap-2 px-3" 
                @click="imprimirHoja" :disabled="loading">
            <i class="bi bi-file-pdf"></i> 
            Guardar PDF / Imprimir
        </button>
    </div>

    <!-- HOJA IMPRIMIBLE -->
    <div class="page-body p-4 sheet-container">
        <!-- Contenedor con borde que parece hoja física -->
        <div class="border border-dark bg-white mx-auto sheet-content" style="max-width: 900px;">
            
            <!-- HEADER DE LA HOJA -->
            <table class="table table-bordered border-dark mb-0 table-sm text-uppercase header-table">
                <tbody>
                    <tr>
                        <td rowspan="2" class="text-center align-middle" style="width: 250px;">
                            <h5 class="fw-bold mb-0" style="letter-spacing: 1px; color: #000;">PLATINIUM<br><small style="font-size:10px;">HOTEL ★★★</small></h5>
                        </td>
                        <td rowspan="2" class="text-center align-middle fs-5 fw-bold" style="color: #000; letter-spacing: 1px;">
                            PLATINIUM - DESAYUNOS
                        </td>
                    </tr>
                    <tr></tr>
                    <tr>
                        <td colspan="2" class="p-2 fw-bold" style="color:#000;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-2">
                                    <span style="font-size: 14px;">FECHA:</span>
                                    <input type="date" v-model="fecha" @change="changeFecha" class="form-control form-control-sm border-0 bg-transparent fw-bold p-0 m-0 shadow-none d-print-none" style="width:130px; font-size:14px; color:#000; cursor:pointer;" :disabled="loading">
                                    <span class="d-none d-print-inline text-decoration-underline ms-2 fs-6">{{ fecha }}</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- TABLA DE HABITACIONES -->
            <table class="table table-bordered border-dark mb-0 table-sm text-uppercase body-table">
                <thead class="bg-light">
                    <tr>
                        <th class="text-center py-2" style="width: 100px; color:#000;">HAB</th>
                        <th class="text-center py-2" style="width: 100px; color:#000;">PAX</th>
                        <th class="text-center py-2" style="color:#000;">OBSERVACIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Loading State -->
                    <tr v-if="loading && lista.length === 0">
                        <td colspan="3" class="text-center py-5">
                            <div class="spinner-border text-primary spinner-border-sm"></div> Cargando...
                        </td>
                    </tr>
                    
                    <!-- Filas de datos -->
                    <tr v-else v-for="h in lista" :key="h.id" :class="getRowClass(h)">
                        <!-- HAB -->
                        <td class="text-center fw-bold fs-6 align-middle" style="color:#000; height: 35px;">
                            {{ h.habitacion }}
                        </td>

                        <!-- PAX (Libre) -->
                        <td class="text-center fw-bold align-middle p-0">
                            <input type="text" v-model="h.pax" class="form-control border-0 bg-transparent text-center fw-bold shadow-none w-100 h-100" style="color:#000; outline: none; cursor: text;">
                        </td>

                        <!-- OBSERVACIONES (Libre) -->
                        <td class="text-center fw-bold align-middle p-0">
                            <input type="text" v-model="h.observaciones" class="form-control border-0 bg-transparent text-center fw-bold shadow-none w-100 h-100 px-2" style="font-size: 14px; color: #000; outline: none; cursor: text; text-align: left !important;">
                        </td>
                    </tr>
                    
                    <!-- TOTAL FOOTER -->
                    <tr>
                        <td class="text-end fw-bold py-2 pe-3 align-middle" style="color:#000; font-size: 16px;">
                            TOTAL
                        </td>
                        <td class="text-center fw-bold py-2 align-middle" style="color:#000; font-size: 16px;">
                            {{ totalPax }}
                        </td>
                        <td class="bg-light"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    </div>
</div>

<script>
    window.SERVER_DATA = {
        apiBase: <?= json_encode(project_base_url() . 'ajax/desayunos.php') ?>,
        hoy: <?= json_encode($fechaStr) ?>
    };
</script>
<script src="<?= $base ?>public/assets/js/desayunos.js?v=<?= time() ?>"></script>

<style>
/* ── ESTILOS PARA LA HOJA FÍSICA Y TABLAS ── */
.table-bordered > :not(caption) > * > * {
    border-color: #000 !important;
}

/* Ocultar elementos de UI cuando se imprime */
@media print {
    body * {
        visibility: hidden;
    }
    #app-desayunos, #app-desayunos * {
        visibility: visible;
    }
    .d-print-none {
        display: none !important;
    }
    .sheet-container {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0 !important;
        padding: 0 !important;
    }
    .sheet-content {
        border: none !important;
        width: 100% !important;
        max-width: 100% !important;
    }
    
    input[type="text"] {
        border: none !important;
        background: transparent !important;
        box-shadow: none !important;
        appearance: none;
        -moz-appearance: none;
        -webkit-appearance: none;
    }
    
    /* Ajustes para forzar que todo entre en una sola página */
    .table-bordered td, .table-bordered th {
        padding: 1px 4px !important;
        height: 27px !important;
        font-size: 13px !important;
    }
    .sheet-content {
        zoom: 0.95;
    }
    
    @page { margin: 5mm; size: A4 portrait; }
}

/* Fixes para Inputs */
input[type="text"]:focus {
    background-color: rgba(0,0,0,0.02) !important;
}

[v-cloak] { display: none !important; }
</style>

<?php include $_projectRoot . 'app/Views/layouts/footer.php'; ?>
