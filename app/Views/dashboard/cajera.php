<?php
/**
 * app/Views/dashboard/cajera.php
 * Note: $base is strictly '' because this is required from the root index.php
 */
$base = '';
$page_title = 'Panel Operativo — Hotel Manager';
include 'includes/head.php';
include 'includes/sidebar.php';
?>

<div class="main-content" id="app-dash-cajera" v-cloak>
  <div class="topbar">
    <button class="btn-burger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
    <div>
      <h4>
        ¡Hola, <?= explode(' ', $_SESSION['auth_nombre'] ?? 'Operador')[0] ?>! 👋 
        <span class="fs-6 text-muted fw-normal ms-2">Turno <?= (date('H') >= 6 && date('H') < 14) ? 'MAÑANA' : 'TARDE' ?></span>
      </h4>
      <p class="mb-0 small text-muted">Panel de control operativo — <?= date('d/m/Y') ?></p>
    </div>
    <div class="ms-auto d-flex align-items-center gap-2">
       <span class="badge bg-light text-dark border px-3 py-2">
           <i class="bi bi-clock-history me-1"></i> Actualizado hace {{ segundosDesdeUpdate }}s
       </span>
       <span class="badge bg-dark px-3 py-2 fs-6" id="reloj"></span>
    </div>
  </div>

  <div class="page-body">
    
    <div v-if="loadingInicial" class="text-center py-5 mt-5">
      <div class="spinner-border text-primary" role="status"></div>
      <h5 class="mt-3 text-muted">Cargando tus tareas de hoy...</h5>
    </div>

    <div v-else>
      <div class="row g-4">
        
        <!-- COLUMNA IZQUIERDA: ALERTAS Y ACCIONES -->
        <div class="col-lg-8">
          
          <!-- BLOQUE 1: URGENTE AHORA (Cobros) -->
          <div class="card shadow-sm border-0 mb-4 border-start border-danger border-5">
            <div class="card-header bg-white py-3">
              <h6 class="m-0 fw-bold text-danger"><i class="bi bi-exclamation-octagon-fill me-2"></i>⚠️ URGENTE AHORA: Cobros pendientes</h6>
            </div>
            <div class="card-body p-0">
               <div class="table-responsive">
                 <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small">
                      <tr>
                        <th class="ps-3">HAB.</th>
                        <th>HUÉSPED</th>
                        <th class="text-center">DEUDA</th>
                        <th class="text-end pe-3">ACCIÓN</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="urgentes.length === 0">
                        <td colspan="4" class="text-center py-4 text-success fw-bold">
                          <i class="bi bi-check-circle-fill me-1"></i> No hay cobros urgentes pendientes en este momento.
                        </td>
                      </tr>
                      <tr v-for="u in urgentes">
                        <td class="ps-3 fw-bold fs-5">{{ u.hab }}</td>
                        <td>
                          <div class="fw-bold">{{ u.huesped }}</div>
                          <small class="text-muted">Gasto acumulado</small>
                        </td>
                        <td class="text-center">
                          <span class="badge bg-danger fs-6 px-3">S/ {{ parseFloat(u.debe).toFixed(2) }}</span>
                        </td>
                        <td class="text-end pe-3">
                          <a :href="'app/Views/rooming/index.php?buscar=' + u.hab" class="btn btn-primary fw-bold shadow-sm">
                            COBRAR <i class="bi bi-arrow-right ms-1"></i>
                          </a>
                        </td>
                      </tr>
                    </tbody>
                 </table>
               </div>
            </div>
          </div>

          <!-- BLOQUE 2: CHECKOUTS DE HOY -->
          <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
              <h6 class="m-0 fw-bold text-dark"><i class="bi bi-calendar-x me-2"></i>📋 CHECKOUTS DE HOY (Salidas)</h6>
            </div>
            <div class="card-body p-0">
               <div class="table-responsive">
                 <table class="table align-middle mb-0">
                    <thead class="table-light text-secondary small">
                      <tr>
                        <th class="ps-3">HAB.</th>
                        <th>HUÉSPED</th>
                        <th class="text-center">SALDO</th>
                        <th class="text-center">ESTADO PAGO</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="checkouts_hoy.length === 0">
                        <td colspan="4" class="text-center py-4 text-muted">No se registran salidas programadas para hoy.</td>
                      </tr>
                      <tr v-for="c in checkouts_hoy" :class="parseFloat(c.saldo) > 0 ? 'table-danger' : 'table-success'">
                        <td class="ps-3 fw-bold">{{ c.hab }}</td>
                        <td>{{ c.huesped }}</td>
                        <td class="text-center fw-bold">S/ {{ parseFloat(c.saldo).toFixed(2) }}</td>
                        <td class="text-center">
                          <span v-if="parseFloat(c.saldo) > 0" class="badge bg-danger">DEBE SALDO</span>
                          <span v-else class="badge bg-success">✅ PAGADO</span>
                        </td>
                      </tr>
                    </tbody>
                 </table>
               </div>
            </div>
          </div>

          <!-- BLOQUE 3: CHECK-INS ESPERADOS -->
          <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
              <h6 class="m-0 fw-bold text-primary"><i class="bi bi-calendar-check me-2"></i>📅 CHECK-INS ESPERADOS (Entradas)</h6>
            </div>
            <div class="card-body p-0">
               <div class="table-responsive">
                 <table class="table align-middle mb-0">
                    <thead class="table-light text-secondary small">
                      <tr>
                        <th class="ps-3">HAB.</th>
                        <th>CANAL</th>
                        <th class="text-center">PAX</th>
                        <th class="text-center">HORA EST.</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="checkins_esperados.length === 0">
                        <td colspan="4" class="text-center py-4 text-muted">No hay reservas pendientes de check-in para hoy.</td>
                      </tr>
                      <tr v-for="i in checkins_esperados">
                        <td class="ps-3 fw-bold">{{ i.hab }}</td>
                        <td><span class="badge bg-light text-dark border">{{ i.canal }}</span></td>
                        <td class="text-center">{{ i.pax }}</td>
                        <td class="text-center text-primary fw-bold">{{ i.hora_estimada }}</td>
                      </tr>
                    </tbody>
                 </table>
               </div>
            </div>
          </div>

        </div>

        <div class="col-lg-4">
          
          <!-- ALERTAS DE INVENTARIO -->
          <div v-if="alertasInventario.length > 0" class="card shadow-sm border-0 mb-4 animate__animated animate__shakeX">
             <div class="card-header bg-warning text-dark py-2 fw-bold small">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> ALERTAS DE INVENTARIO
             </div>
             <div class="card-body p-2">
                <div v-for="a in alertasInventario" :key="a.id" class="d-flex justify-content-between align-items-center mb-1 p-2 bg-light rounded">
                   <span class="small fw-bold">{{ a.nombre }}</span>
                   <span class="badge bg-danger">Quedan {{ a.stock_actual }}</span>
                </div>
             </div>
          </div>

          <!-- MI TURNO -->
          <div class="card shadow-sm border-0 mb-4 bg-white">
            <div class="card-header bg-primary text-white py-3">
              <h6 class="m-0 fw-bold"><i class="bi bi-wallet2 me-2"></i>💰 MI TURNO (En curso)</h6>
            </div>
            <div class="card-body">
               <div class="mb-3 d-flex justify-content-between">
                 <span class="text-muted">Ingresos:</span>
                 <span class="fw-bold text-success">S/ {{ mi_turno.ingresos.toFixed(2) }}</span>
               </div>
               <div class="mb-3 d-flex justify-content-between">
                 <span class="text-muted">Egresos:</span>
                 <span class="fw-bold text-danger">S/ {{ mi_turno.egresos.toFixed(2) }}</span>
               </div>
               <hr>
               <div class="mb-4 d-flex justify-content-between align-items-center">
                 <span class="fw-bold h6 mb-0 text-dark">EFECTIVO EN SOBRE:</span>
                 <span class="h4 mb-0 fw-bold text-primary">S/ {{ mi_turno.efectivo_sobre.toFixed(2) }}</span>
               </div>
               
               <div v-if="mi_turno.estado === 'inexistente'" class="alert alert-warning border-0 small mb-0">
                 <i class="bi bi-exclamation-triangle-fill"></i> No has iniciado tu flujo de caja de hoy.
               </div>
               <div v-else class="d-flex align-items-center gap-2">
                 <span class="text-muted small">Estado:</span>
                 <span class="badge" :class="mi_turno.estado === 'borrador' ? 'bg-warning text-dark' : 'bg-success'">
                    {{ mi_turno.estado.toUpperCase() }}
                 </span>
               </div>
            </div>
          </div>

          <!-- ACCESOS RÁPIDOS -->
          <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
              <h6 class="m-0 fw-bold text-dark"><i class="bi bi-lightning-fill me-1 text-warning"></i> ACCESOS RÁPIDOS</h6>
            </div>
            <div class="card-body pt-0">
               <div class="d-grid gap-2 mt-3">
                  <a href="app/Views/rooming/index.php" class="btn btn-lg btn-outline-primary text-start">
                    <i class="bi bi-plus-square-fill me-2"></i> Nuevo Check-in
                  </a>
                  <a href="app/Views/rooming/index.php" class="btn btn-lg btn-outline-secondary text-start">
                    <i class="bi bi-card-checklist me-2"></i> Ver Rooming
                  </a>
                  <a href="app/Views/flujo/index.php" class="btn btn-lg btn-outline-secondary text-start">
                    <i class="bi bi-cash-stack me-2 text-success"></i> Flujo de Caja
                  </a>
                  <a href="app/Views/reservas/index.php" class="btn btn-lg btn-outline-secondary text-start">
                    <i class="bi bi-grid-3x3-gap-fill me-2 text-warning"></i> Cuadro Reservas
                  </a>
                  <a href="app/Views/caja_chica/index.php" class="btn btn-lg btn-outline-secondary text-start">
                    <i class="bi bi-box2-heart me-2 text-danger"></i> Caja Chica
                  </a>
                  <a href="app/Views/yape/index.php" class="btn btn-lg btn-outline-secondary text-start">
                    <i class="bi bi-wallet2 me-2" style="color:#7b2cbf"></i> Gastos Yape
                  </a>
               </div>
            </div>
          </div>

        </div>

      </div>
    </div>

  </div>
</div>

<script src="app/Views/dashboard/cajera.js"></script>

<script>
  function tick() { document.getElementById('reloj').textContent = new Date().toLocaleTimeString('es-PE',{hour:'2-digit',minute:'2-digit'}); }
  tick(); setInterval(tick, 1000);
</script>
