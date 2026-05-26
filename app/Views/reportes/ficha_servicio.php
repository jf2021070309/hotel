<?php
/**
 * app/Views/reportes/ficha_servicio.php
 * Ficha genérica para Limpieza y Desayuno (Imprimible)
 */
$base = '../../../';
$_projectRoot = dirname(__DIR__, 3);
require_once $_projectRoot . '/app/Middleware/session.php';
require_once $_projectRoot . '/app/Middleware/auth.php';
protegerPorRol('cajera', 'limpieza'); 

$fecha = $_GET['fecha'] ?? date('Y-m-d', strtotime('-1 day'));
$page_title = 'Ficha de Servicio — ' . date('d/m/Y', strtotime($fecha));
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
        .hab-col { width: 18% !important; }
        .estado-col { width: 34% !important; }
        .col-comentario { width: 48% !important; }
    }
    .table-ficha th { background-color: #f8f9fa; color: #333; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; vertical-align: middle; }
    .table-ficha td { height: 70px; vertical-align: middle; }
    .hab-col { width: 160px; font-weight: 900; font-size: 24px; text-align: center; color: #1a1a2e; }
    .estado-col { width: 400px; }
</style>

<div class="main-content" id="app-ficha" v-cloak>
    <?php include $_projectRoot . '/app/Views/layouts/sidebar.php'; ?>

    <div class="topbar btn-noPrint">
        <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list fs-4"></i></button>
        <div>
            <h4 class="fw-bold mb-0">Ficha de Limpieza y Desayuno</h4>
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
                    <i class="bi bi-list-check me-2 text-warning"></i>Ocupación al día {{ fmtFecha(fecha) }}
                </h6>
                <span class="badge bg-warning text-dark fw-bold">{{ habitaciones.length }} REGISTRADAS</span>
            </div>
            
            <!-- Encabezado para impresión -->
            <div class="d-none d-print-block text-center mb-4">
                <h2 class="fw-bold text-uppercase border-bottom border-dark pb-2 mb-1">FICHA DE CONTROL DIARIO</h2>
                <p class="fw-bold mb-0">LIMPIEZA Y DESAYUNOS — FECHA REGISTRO: {{ fmtFecha(fecha) }}</p>
            </div>

            <div class="card-body p-0">
                <div v-if="loading" class="text-center py-5">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
                    <p class="mt-3 text-muted fw-bold">Calculando ocupación histórica...</p>
                </div>
                
                <table v-else class="table table-bordered table-ficha mb-0">
                    <thead class="bg-light">
                        <tr class="text-center">
                            <th class="hab-col">NRO DE HABITACIÓN</th>
                            <th class="estado-col">ESTADO</th>
                            <th class="col-comentario">COMENTARIO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="h in habitaciones" :key="h.id">
                            <td class="hab-col">
                                <span class="d-block">#{{ h.hab_numero }}</span>
                                <small class="text-muted d-print-none" style="font-size: 10px; font-weight: normal;">{{ h.hab_tipo }}</small>
                            </td>
                            <td class="p-0">
                                <textarea class="editable-cell" placeholder="Escribir estado..."></textarea>
                            </td>
                            <td class="p-0">
                                <textarea class="editable-cell" placeholder="Añadir comentario..."></textarea>
                            </td>
                        </tr>
                        <tr v-if="habitaciones.length === 0">
                            <td colspan="3" class="text-center py-5 text-muted fst-italic">
                                <i class="bi bi-info-circle me-2"></i>No se encontraron habitaciones ocupadas en la fecha seleccionada.
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
                    <p class="fw-bold small mt-1">PERSONAL DE SERVICIO</p>
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
const { createApp, ref, onMounted } = Vue;
createApp({
    setup() {
        const fecha = ref('<?= $fecha ?>');
        const habitaciones = ref([]);
        const loading = ref(false);

        const cargar = async () => {
            loading.value = true;
            try {
                // Usamos el endpoint de estadísticas o uno que soporte fecha histórica
                // Para simplificar, consultamos al API de limpieza que ya tiene lógica para ocupación por fecha
                const res = await axios.get(`<?= $base ?>api/limpieza.php?action=propuesta&fecha=${fecha.value}`);
                if (res.data.success) {
                    // Filtramos solo las que son 'salida' o 'reposo' (que son las ocupadas)
                    const ocupadas = (res.data.data || []).filter(h => h.tipo === 'salida' || h.tipo === 'reposo');
                    
                    // Mapeamos al formato que espera la tabla
                    habitaciones.value = ocupadas.map(h => ({
                        hab_numero: h.habitacion,
                        hab_tipo: h.tipo.toUpperCase()
                    }));

                    // Ordenar por número de habitación
                    habitaciones.value.sort((a, b) => a.hab_numero.localeCompare(b.hab_numero, undefined, {numeric: true}));
                }
            } catch (e) {
                console.error("Error cargando habitaciones:", e);
            }
            loading.value = false;
        };

        const cambiarFecha = () => {
            cargar();
            // Actualizar URL sin recargar para que el título de la página coincida si se imprime
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

        return { fecha, habitaciones, loading, imprimir, fmtFecha, cambiarFecha };
    }
}).mount('#app-ficha');
</script>

<?php include $_projectRoot . '/app/Views/layouts/footer.php'; ?>
