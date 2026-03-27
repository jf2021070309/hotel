<?php
/**
 * app/Views/limpieza/reporte.php
 * Checklist de Housekeeping — Imprimible y digital para camareras
 */
$base = '../../../';
require_once $base . 'auth/session.php';
$fecha = $_GET['fecha'] ?? date('Y-m-d');
$page_title = 'Reporte Housekeeping ' . date('d/m/Y', strtotime($fecha));
include $base . 'includes/head.php';
include $base . 'includes/sidebar.php';
?>

<style>
  @media print {
    .sidebar, .topbar, .btn-noPrint, .sidebar-overlay { display: none !important; }
    .main-content { margin-left: 0 !important; padding: 0 !important; }
    .page-body { padding: 0 !important; }
    .card { break-inside: avoid; }
    .print-section { page-break-before: always; }
  }
  .tipo-salida   { border-left: 5px solid #dc3545 !important; }
  .tipo-estadia  { border-left: 5px solid #ffc107 !important; }
  .tipo-reserva  { border-left: 5px solid #20c997 !important; }
  .check-item    { display: flex; align-items: center; gap: 10px; padding: 6px 0; border-bottom: 1px dashed #dee2e6; font-size: 13px; }
  .check-item:last-child { border-bottom: none; }
  .check-box     { width: 18px; height: 18px; border: 2px solid #adb5bd; border-radius: 3px; flex-shrink: 0; }
  .check-box.done{ background: #198754; border-color: #198754; }
  .hab-badge     { width: 52px; height: 52px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 800; }
  .estado-done   { background: #d1fae5; color: #065f46; }
  .mini { font-size: 10px; letter-spacing: .5px; }
</style>

<div class="main-content" id="app-reporte-limpieza" v-cloak>
  <div class="topbar">
    <button class="btn-burger btn-noPrint" onclick="openSidebar()"><i class="bi bi-list"></i></button>
    <div>
      <h1 class="h4 mb-0"><i class="bi bi-stars me-2 text-info"></i>Reporte Housekeeping</h1>
      <p class="text-muted mb-0 small">{{ formatFecha(fecha) }} — generado a las <?= date('H:i') ?></p>
    </div>
    <div class="ms-auto d-flex gap-2 btn-noPrint">
      <input type="date" v-model="fecha" @change="cargar" class="form-control form-control-sm">
      <button @click="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i> Imprimir</button>
      <a href="historial.php" class="btn btn-outline-secondary"><i class="bi bi-clock-history me-1"></i> Historial</a>
    </div>
  </div>

  <div class="page-body">
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary"></div>
      <p class="mt-2 text-muted">Cargando...</p>
    </div>

    <div v-else>
      <!-- RESUMEN TOP -->
      <div class="row g-3 mb-4 btn-noPrint">
        <div class="col-4">
          <div class="card border-0 shadow-sm tipo-salida p-3 text-center">
            <div class="mini fw-bold text-danger text-uppercase mb-1">🔴 Salida</div>
            <div class="h2 mb-0 fw-bold">{{ grupos.salida.length }}</div>
            <div class="mini text-muted">limpieza profunda</div>
          </div>
        </div>
        <div class="col-4">
          <div class="card border-0 shadow-sm tipo-estadia p-3 text-center">
            <div class="mini fw-bold text-warning text-uppercase mb-1">🟡 Repaso</div>
            <div class="h2 mb-0 fw-bold">{{ grupos.estadia.length }}</div>
            <div class="mini text-muted">estadía activa</div>
          </div>
        </div>
        <div class="col-4">
          <div class="card border-0 shadow-sm tipo-reserva p-3 text-center">
            <div class="mini fw-bold text-success text-uppercase mb-1">🟢 Reserva</div>
            <div class="h2 mb-0 fw-bold">{{ grupos.reserva.length }}</div>
            <div class="mini text-muted">pre check-in</div>
          </div>
        </div>
      </div>

      <!-- SECCIÓN: SALIDAS -->
      <div v-if="grupos.salida.length > 0" class="mb-5">
        <div class="d-flex align-items-center gap-2 mb-3">
          <div class="badge bg-danger px-3 py-2 fs-6">🔴 LIMPIEZA DE SALIDA</div>
          <span class="text-muted small">Limpieza profunda — prioridad ALTA</span>
        </div>
        <div class="row g-3">
          <div class="col-md-6" v-for="h in grupos.salida" :key="h.id">
            <div class="card border-0 shadow-sm tipo-salida" :class="h.estado === 'lista' ? 'estado-done' : ''">
              <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-3">
                  <div class="d-flex gap-3 align-items-center">
                    <div class="hab-badge bg-danger text-white">{{ h.habitacion }}</div>
                    <div>
                      <div class="fw-bold">HAB #{{ h.habitacion }}</div>
                      <div class="small text-muted">{{ h.responsable || 'Sin asignar' }}</div>
                      <div class="mini text-muted" v-if="h.hora_inicio">⏱ Inicio: {{ h.hora_inicio }}</div>
                    </div>
                  </div>
                  <span class="badge" :class="h.estado === 'lista' ? 'bg-success' : (h.estado === 'en_proceso' ? 'bg-warning text-dark' : 'bg-light text-dark border')">
                    {{ h.estado === 'lista' ? '✅ LISTA' : (h.estado === 'en_proceso' ? '🧹 EN PROCESO' : '⏳ PENDIENTE') }}
                  </span>
                </div>
                <div class="bg-light rounded p-2">
                  <div class="mini fw-bold text-uppercase text-muted mb-2">Checklist — Salida</div>
                  <div class="check-item" v-for="task in checklistSalida" :key="task">
                    <div class="check-box" :class="h.estado === 'lista' ? 'done' : ''"></div>
                    <span>{{ task }}</span>
                  </div>
                </div>
                <div v-if="h.observacion" class="mt-2 small text-muted fst-italic">
                  <i class="bi bi-chat-dots me-1"></i> {{ h.observacion }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- SECCIÓN: REPASO (ESTADÍA) -->
      <div v-if="grupos.estadia.length > 0" class="mb-5">
        <div class="d-flex align-items-center gap-2 mb-3">
          <div class="badge bg-warning text-dark px-3 py-2 fs-6">🟡 REPASO DIARIO</div>
          <span class="text-muted small">Huésped sigue hospedado</span>
        </div>
        <div class="row g-3">
          <div class="col-md-6" v-for="h in grupos.estadia" :key="h.id">
            <div class="card border-0 shadow-sm tipo-estadia" :class="h.estado === 'lista' ? 'estado-done' : ''">
              <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-3">
                  <div class="d-flex gap-3 align-items-center">
                    <div class="hab-badge bg-warning text-dark">{{ h.habitacion }}</div>
                    <div>
                      <div class="fw-bold">HAB #{{ h.habitacion }}</div>
                      <div class="small text-muted">{{ h.responsable || 'Sin asignar' }}</div>
                    </div>
                  </div>
                  <span class="badge" :class="h.estado === 'lista' ? 'bg-success' : (h.estado === 'en_proceso' ? 'bg-warning text-dark' : 'bg-light text-dark border')">
                    {{ h.estado === 'lista' ? '✅ LISTA' : (h.estado === 'en_proceso' ? '🧹 EN PROCESO' : '⏳ PENDIENTE') }}
                  </span>
                </div>
                <div class="bg-light rounded p-2">
                  <div class="mini fw-bold text-uppercase text-muted mb-2">Checklist — Repaso</div>
                  <div class="check-item" v-for="task in checklistRepaso" :key="task">
                    <div class="check-box" :class="h.estado === 'lista' ? 'done' : ''"></div>
                    <span>{{ task }}</span>
                  </div>
                </div>
                <div v-if="h.observacion" class="mt-2 small text-muted fst-italic">
                  <i class="bi bi-chat-dots me-1"></i> {{ h.observacion }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- SECCIÓN: RESERVA (PRE CHECK-IN) -->
      <div v-if="grupos.reserva.length > 0" class="mb-5">
        <div class="d-flex align-items-center gap-2 mb-3">
          <div class="badge bg-success px-3 py-2 fs-6">🟢 LIMPIEZA DE RESERVA</div>
          <span class="text-muted small">Verificación pre Check-in</span>
        </div>
        <div class="row g-3">
          <div class="col-md-6" v-for="h in grupos.reserva" :key="h.id">
            <div class="card border-0 shadow-sm tipo-reserva" :class="h.estado === 'lista' ? 'estado-done' : ''">
              <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-3">
                  <div class="d-flex gap-3 align-items-center">
                    <div class="hab-badge bg-success text-white">{{ h.habitacion }}</div>
                    <div>
                      <div class="fw-bold">HAB #{{ h.habitacion }}</div>
                      <div class="small text-muted">{{ h.responsable || 'Sin asignar' }}</div>
                    </div>
                  </div>
                  <span class="badge" :class="h.estado === 'lista' ? 'bg-success' : (h.estado === 'en_proceso' ? 'bg-warning text-dark' : 'bg-light text-dark border')">
                    {{ h.estado === 'lista' ? '✅ LISTA' : (h.estado === 'en_proceso' ? '🧹 EN PROCESO' : '⏳ PENDIENTE') }}
                  </span>
                </div>
                <div class="bg-light rounded p-2">
                  <div class="mini fw-bold text-uppercase text-muted mb-2">Checklist — Reserva</div>
                  <div class="check-item" v-for="task in checklistReserva" :key="task">
                    <div class="check-box" :class="h.estado === 'lista' ? 'done' : ''"></div>
                    <span>{{ task }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="!grupos.salida.length && !grupos.estadia.length && !grupos.reserva.length" class="text-center py-5 text-muted">
        <i class="bi bi-inbox fs-1 d-block mb-3 opacity-25"></i>
        No hay tareas de limpieza para esta fecha. Genera la lista desde el Panel de Limpieza.
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const { createApp, ref, computed, onMounted } = Vue;
createApp({
  setup() {
    const fecha = ref('<?= $fecha ?>');
    const loading = ref(false);
    const lista = ref([]);

    const checklistSalida = [
      'Retirar ropa de cama sucia (sábanas y fundas)',
      'Cambio completo de toallas',
      'Limpiar y desinfectar baño a fondo',
      'Fregar pisos y superficies',
      'Despolvar muebles y equipos',
      'Reponer amenities (shampoo, jabón, papel)',
      'Revisar minibar y bebidas',
      'Verificar TV, control y enchufes',
      'Hacer cama con ropa nueva',
      'Vaciar papelera y ceniceros',
    ];
    const checklistRepaso = [
      'Tender cama (no cambiar sábanas)',
      'Limpiar baño rápido (inodoro, lavatorio)',
      'Cambiar toallas si el huésped lo solicita',
      'Vaciar papelera',
      'Ordenar habitación',
    ];
    const checklistReserva = [
      'Verificar que la cama esté lista',
      'Confirmar amenities completos',
      'Revisar baño esté limpio',
      'Ventilación correcta de la habitación',
      'Dejar llave y control listos',
    ];

    const grupos = computed(() => ({
      salida:  lista.value.filter(h => h.tipo_limpieza === 'salida'),
      estadia: lista.value.filter(h => h.tipo_limpieza === 'estadía'),
      reserva: lista.value.filter(h => h.tipo_limpieza === 'programada'),
    }));

    const cargar = async () => {
      loading.value = true;
      try {
        const res = await axios.get(`../../../api/limpieza.php?action=detalle_fecha&fecha=${fecha.value}`);
        lista.value = res.data.data || [];
      } catch (e) { console.error(e); }
      loading.value = false;
    };

    const formatFecha = (f) => {
      if (!f) return '';
      const [y,m,d] = f.split('-');
      return `${d}/${m}/${y}`;
    };

    onMounted(cargar);

    return { fecha, loading, lista, grupos, checklistSalida, checklistRepaso, checklistReserva, cargar, formatFecha, window };
  }
}).mount('#app-reporte-limpieza');
</script>
</body></html>
