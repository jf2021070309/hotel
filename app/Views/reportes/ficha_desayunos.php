<?php
/**
 * app/Views/reportes/ficha_desayunos.php
 * Ficha genérica para Desayuno (Imprimible)
 */
$base = '../../../';
$_projectRoot = dirname(__DIR__, 3);
require_once $_projectRoot . '/app/Middleware/session.php';
require_once $_projectRoot . '/app/Middleware/auth.php';
protegerPorRol('cajera', 'limpieza'); 

$fecha = $_GET['fecha'] ?? date('Y-m-d');
$page_title = 'Ficha de Desayunos — ' . date('d/m/Y', strtotime($fecha));
include $_projectRoot . '/app/Views/layouts/head.php';
?>

<style>
    @media print {
        .btn-noPrint, .sidebar, .topbar, .sidebar-overlay, .btn-burger { display: none !important; }
        .main-content { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
        .page-body { padding: 0 !important; }
        .card { border: none !important; box-shadow: none !important; }
        table { border: 1px solid #000 !important; width: 100% !important; table-layout: fixed; }
        th, td { border: 1px solid #000 !important; color: #000 !important; padding: 10px !important; word-wrap: break-word; vertical-align: middle !important; }
        th { text-align: center !important; font-size: 11px !important; height: 40px !important; }
        .card-header { background: #eee !important; color: #000 !important; border: 1px solid #000 !important; }
        
        /* Ajuste de anchos para impresión */
        .hab-col { width: 15% !important; }
        .titular-col { width: 45% !important; }
        .pax-col { width: 10% !important; }
        .firma-col { width: 30% !important; }
    }
    .table-ficha th { background-color: #f8f9fa; color: #333; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; vertical-align: middle; }
    .table-ficha td { height: 70px; vertical-align: middle; }
    .hab-col { width: 120px; font-weight: 900; font-size: 24px; text-align: center; color: #1a1a2e; }
    .pax-col { font-weight: 900; font-size: 20px; text-align: center; color: #1a1a2e; }
</style>

<div class="main-content" id="app-ficha-desayunos" v-cloak>
    <?php include $_projectRoot . '/app/Views/layouts/sidebar.php'; ?>

    <div class="topbar btn-noPrint">
        <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list fs-4"></i></button>
        <div>
            <h4 class="fw-bold mb-0">Ficha de Desayunos</h4>
            <p class="text-muted small mb-0"><i class="bi bi-calendar-event me-1"></i> {{ fmtFecha(fecha) }} — Habitaciones registradas</p>
        </div>
        <div class="ms-auto d-flex align-items-center gap-2">
            <label class="small fw-bold text-muted d-none d-md-block">CAMBIAR FECHA:</label>
            <input type="date" v-model="fecha" @change="cambiarFecha" class="form-control form-control-sm" style="width: 140px;">
            <button @click="imprimir" class="btn btn-primary fw-bold shadow-sm px-4 border-dark ms-2">
                <i class="bi bi-printer-fill me-2"></i> IMPRIMIR
            </button>
        </div>
    </div>

    <div class="page-body">
        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 16px; border: 1px solid #eee !important;">
            <div class="card-header bg-dark text-white py-3 btn-noPrint d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-uppercase small" style="letter-spacing: 1px;">
                    <i class="bi bi-egg-fried me-2 text-warning"></i>Desayunos al día {{ fmtFecha(fecha) }}
                </h6>
                <span class="badge bg-warning text-dark fw-bold">{{ detallesFiltrados.length }} REGISTRADAS</span>
            </div>
            
            <!-- Encabezado para impresión -->
            <div class="d-none d-print-block text-center mb-4">
                <h2 class="fw-bold text-uppercase border-bottom border-dark pb-2 mb-1">FICHA DE CONTROL DIARIO</h2>
                <p class="fw-bold mb-0">DESAYUNOS — FECHA REGISTRO: {{ fmtFecha(fecha) }}</p>
                <div v-if="datos.observacion" class="mt-2 text-start p-2 border border-dark small">
                    <strong>Notas/Indicaciones:</strong> {{ datos.observacion }}
                </div>
            </div>

            <div class="card-body p-0">
                <div v-if="loading" class="text-center py-5">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
                    <p class="mt-3 text-muted fw-bold">Calculando desayunos...</p>
                </div>
                
                <table v-else class="table table-bordered table-ficha mb-0">
                    <thead class="bg-light">
                        <tr class="text-center">
                            <th class="hab-col">HABITACIÓN</th>
                            <th class="titular-col">HUÉSPED TITULAR</th>
                            <th class="pax-col">DESAYUNOS</th>
                            <th class="firma-col">FIRMA / COMENTARIO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="h in detallesFiltrados" :key="h.habitacion">
                            <td class="hab-col">
                                <span class="d-block">#{{ h.habitacion }}</span>
                            </td>
                            <td class="p-3">
                                <strong>{{ h.titular }}</strong>
                            </td>
                            <td class="pax-col">
                                {{ h.pax }}
                            </td>
                            <td class="p-0">
                                <textarea class="editable-cell" placeholder="Firma o nota..."></textarea>
                            </td>
                        </tr>
                        <tr v-if="detallesFiltrados.length === 0">
                            <td colspan="4" class="text-center py-5 text-muted fst-italic">
                                <i class="bi bi-info-circle me-2"></i>No se encontraron huéspedes con desayuno para la fecha seleccionada.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Footer de firma solo para impresión -->
        <div class="d-none d-print-block mt-5 pt-5">
            <div class="d-flex justify-content-around text-center">
                <div style="width: 250px;">
                    <p class="mb-0">_________________________________</p>
                    <p class="fw-bold small mt-1">ENCARGADO DE COCINA</p>
                </div>
                <div style="width: 250px;">
                    <p class="mb-0">_________________________________</p>
                    <p class="fw-bold small mt-1">V°B° RECEPCIÓN</p>
                </div>
            </div>
            <div class="text-end mt-5">
                <small class="text-muted">Generado por Hotel Manager — <?= date('d/m/Y H:i') ?></small>
            </div>
        </div>
    </div>
</div>

<style>
    .editable-cell {
        width: 100%;
        min-height: 80px;
        border: none;
        padding: 12px;
        resize: none;
        background: transparent;
        font-family: inherit;
        font-size: 13px;
        outline: none;
        display: block;
        overflow: hidden;
        word-wrap: break-word;
        line-height: 1.5;
    }
    .editable-cell:focus {
        background: rgba(0,0,0,0.02);
    }
    @media print {
        .editable-cell::placeholder { color: transparent !important; }
        .editable-cell { 
            height: auto !important; 
            min-height: 80px; 
            border: none !important; 
            padding: 10px !important;
            font-size: 12px !important;
        }
        td { height: auto !important; }
    }
</style>

<script>
const { createApp, ref, computed, onMounted } = Vue;
createApp({
    setup() {
        const fecha = ref('<?= $fecha ?>');
        const datos = ref({ detalles: [], observacion: '' });
        const loading = ref(false);

        const detallesFiltrados = computed(() => {
            return (datos.value.detalles || [])
                .filter(d => d.incluye_desayuno)
                .sort((a, b) => String(a.habitacion).localeCompare(String(b.habitacion), undefined, {numeric: true}));
        });

        const cargar = async () => {
            loading.value = true;
            try {
                const res = await axios.get(`<?= $base ?>api/desayunos.php?action=hoy&fecha=${fecha.value}`);
                if (res.data.ok) {
                    datos.value = res.data.data;
                } else {
                    datos.value = { detalles: [], observacion: '' };
                }
            } catch (e) {
                console.error("Error cargando desayunos:", e);
                datos.value = { detalles: [], observacion: '' };
            }
            loading.value = false;
        };

        const cambiarFecha = () => {
            cargar();
            const newUrl = window.location.pathname + '?fecha=' + fecha.value;
            window.history.pushState({path: newUrl}, '', newUrl);
        };

        const fmtFecha = (f) => {
            if(!f) return '';
            const [y,m,d] = f.split('-');
            return `${d}/${m}/${y}`;
        };

        const imprimir = () => {
            window.print();
        };

        onMounted(cargar);

        return { fecha, datos, detallesFiltrados, loading, imprimir, fmtFecha, cambiarFecha };
    }
}).mount('#app-ficha-desayunos');
</script>

<?php include $_projectRoot . '/app/Views/layouts/footer.php'; ?>
