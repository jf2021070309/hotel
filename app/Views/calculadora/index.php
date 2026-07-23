<?php
/**
 * app/Views/calculadora/index.php
 * Módulo Calculadora — Hotel Platinium
 */
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/Middleware/auth.php';
protegerPorRol('cajera', 'calculadora');

require_once __DIR__ . '/../../../app/Controllers/CalculadoraController.php';
$ctrl = new CalculadoraController($pdo);
$data = $ctrl->index();

$tc        = $data['tc'];
$config    = $data['config'];
$historial = $data['historial'];

$page_title = 'Calculadora — Hotel Platinium';
include __DIR__ . '/../layouts/head.php';
?>

<div id="app-calculadora">
<?php include __DIR__ . '/../layouts/sidebar.php'; ?>
<div class="main-content">

  <!-- TOPBAR PREMIUM DARK -->
  <div class="topbar" style="background-color:#111827;padding:0.75rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.05);">
    <div class="d-flex align-items-center justify-content-between w-100">
      <div class="d-flex align-items-center gap-3">
        <button class="btn btn-dark btn-sm rounded-circle d-md-none" onclick="openSidebar()" style="width:32px;height:32px;padding:0;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.1);border:none;">
          <i class="bi bi-list text-white"></i>
        </button>
        <div class="d-flex align-items-center gap-3">
          <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#f8fafc,#94a3b8);display:flex;align-items:center;justify-content:center;box-shadow:0 0 15px rgba(148,163,184,0.4);">
            <i class="bi bi-calculator-fill text-dark fs-5"></i>
          </div>
          <div>
            <h4 class="fw-bold mb-0 text-white" style="font-size:18px;letter-spacing:-0.5px;">Calculadora de Tipos de Cambio</h4>
            <div class="text-white-50" style="font-size:11px;">Conversión en tiempo real &middot; PEN &middot; USD &middot; CLP</div>
          </div>
        </div>
      </div>
      <div class="ms-auto d-flex gap-2">
        <span class="badge d-flex align-items-center gap-1" style="background:rgba(255,255,255,0.1); color:#fff; font-size:11px; padding:6px 12px; border-radius:8px; border:1px solid rgba(255,255,255,0.2);">
          <i class="bi bi-calendar3"></i><?= htmlspecialchars($tc['fecha']) ?>
        </span>
      </div>
    </div>
  </div>

  <div class="page-body">

    <!-- ==================== TAB 1: CALCULADORA ==================== -->
    <div id="tab-calculadora" class="tab-content-pane">

      <!-- Barra de Tipos de Cambio -->
      <div class="tc-bar card border-0 shadow-sm mb-4">
        <div class="card-body py-3 px-4">
          <div class="row g-3 align-items-center">
            <div class="col-auto">
              <label class="tc-bar-label">FECHA TC</label>
              <input type="date" id="barFecha" class="form-control form-control-sm tc-input"
                     value="<?= htmlspecialchars($tc['fecha']) ?>" readonly>
            </div>
            <div class="col-auto">
              <label class="tc-bar-label">TC USD → PEN</label>
              <div class="input-group input-group-sm">
                <span class="input-group-text tc-symbol">$</span>
                <input type="number" id="barTcUsd" class="form-control tc-input" step="any" min="0"
                       value="<?= (float)$tc['tc_usd'] ?>"
                       oninput="recalcularTodo()">
              </div>
            </div>
            <div class="col-auto">
              <label class="tc-bar-label">1 SOL = X PESOS (CLP)</label>
              <div class="input-group input-group-sm">
                <span class="input-group-text tc-symbol">CLP</span>
                <input type="number" id="barTcClp" class="form-control tc-input" step="any" min="0"
                       value="<?= (float)$tc['tc_clp'] ?>"
                       oninput="recalcularTodo()">
              </div>
            </div>
            <div class="col-auto ms-auto d-flex gap-2">
              <button class="btn btn-primary btn-sm px-3 shadow-sm fw-bold" onclick="guardarTCDirecto()" id="btnGuardarTCDirecto">
                <i class="bi bi-floppy-fill me-1"></i>Guardar
              </button>
              <button class="btn btn-outline-secondary btn-sm px-3 shadow-sm" onclick="recargarTC()" id="btnRecargar">
                <i class="bi bi-arrow-clockwise me-1"></i>Recargar
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Paneles de Conversión -->
      <div class="row g-4 panels-row">

        <!-- PANEL 1: SOLES -->
        <div class="col-12 col-md-4">
          <div class="calc-panel panel-soles" id="panelSoles">
            <div class="panel-header">
              <span class="panel-icon">💵</span>
              <div>
                <div class="panel-title">SOLES</div>
                <div class="panel-subtitle">PEN — Nuevos Soles</div>
              </div>
              <div class="ms-auto">
                <div class="toggle-container" title="Aplicar recargo POS 5%">
                  <span class="toggle-label">5% POS</span>
                  <label class="toggle-switch">
                    <input type="checkbox" id="togglePosSoles" onchange="togglePosSolesChange()">
                    <span class="toggle-slider"></span>
                  </label>
                </div>
              </div>
            </div>

            <div class="panel-body">
              <label class="input-label">Monto en Dólares (USD)</label>
              <div class="input-group mb-3">
                <span class="input-group-text monto-symbol">$</span>
                <input type="number" id="montoSoles" class="form-control monto-input"
                       placeholder="0.00" step="0.01" min="0"
                       oninput="calcularSoles()">
              </div>

              <div class="result-rows">
                <div class="result-row">
                  <span class="result-label">Neto Soles</span>
                  <span class="result-value" id="solNeto">—</span>
                </div>
                <div class="result-row comision-row" id="rowComisionSoles" style="display:none;">
                  <span class="result-label text-success">Comisión (5%)</span>
                  <span class="result-value text-success" id="solComision">—</span>
                </div>
              </div>

              <div class="result-total">
                <span class="total-label">TOTAL</span>
                <span class="total-value" id="solTotal">—</span>
              </div>
            </div>
          </div>
        </div>

        <!-- PANEL 2: DÓLARES -->
        <?php if ($config['mostrar_panel_usd']): ?>
        <div class="col-12 col-md-4">
          <div class="calc-panel panel-dolares" id="panelDolares">
            <div class="panel-header">
              <span class="panel-icon">💲</span>
              <div>
                <div class="panel-title">DÓLARES</div>
                <div class="panel-subtitle">USD — Dólares Americanos</div>
              </div>
              <div class="ms-auto">
                <div class="toggle-container" title="Aplicar recargo POS 5%">
                  <span class="toggle-label">5% POS</span>
                  <label class="toggle-switch">
                    <input type="checkbox" id="togglePosDolares" onchange="togglePosDolaresChange()">
                    <span class="toggle-slider"></span>
                  </label>
                </div>
              </div>
            </div>

            <div class="panel-body">
              <label class="input-label">Monto en Dólares (USD)</label>
              <div class="input-group mb-3">
                <span class="input-group-text monto-symbol">$</span>
                <input type="number" id="montoDolares" class="form-control monto-input"
                       placeholder="0.00" step="0.01" min="0"
                       oninput="calcularDolares()">
              </div>

              <div class="result-rows">
                <div class="result-row">
                  <span class="result-label">Neto USD</span>
                  <span class="result-value" id="usdBase">—</span>
                </div>
                <div class="result-row comision-row" id="rowComisionUsd" style="display:none;">
                  <span class="result-label text-success">Comisión (5%)</span>
                  <span class="result-value text-success" id="usdComision">—</span>
                </div>
                <div class="result-row mt-1" style="border-top: 1px dashed #e2e8f0; padding-top: 8px;">
                  <span class="result-label text-muted" style="font-size:11px;">Equiv. en Soles</span>
                  <span class="result-value" style="font-size:13px;" id="usdEnSoles">—</span>
                </div>
              </div>

              <div class="result-total">
                <span class="total-label">TOTAL USD</span>
                <span class="total-value" id="usdTotal">—</span>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- PANEL 3: PESOS CHILENOS -->
        <?php if ($config['mostrar_panel_clp']): ?>
        <div class="col-12 col-md-4">
          <div class="calc-panel panel-pesos" id="panelPesos">
            <div class="panel-header">
              <span class="panel-icon">🇨🇱</span>
              <div>
                <div class="panel-title">PESOS CHILENOS</div>
                <div class="panel-subtitle">CLP — Pesos Chile</div>
              </div>
              <div class="ms-auto">
                <div class="toggle-container" title="Aplicar recargo POS 5%">
                  <span class="toggle-label">5% POS</span>
                  <label class="toggle-switch">
                    <input type="checkbox" id="togglePosPesos" onchange="togglePosPesosChange()">
                    <span class="toggle-slider"></span>
                  </label>
                </div>
              </div>
            </div>

            <div class="panel-body">
              <label class="input-label">Monto en Dólares (USD)</label>
              <div class="input-group mb-3">
                <span class="input-group-text monto-symbol">$</span>
                <input type="number" id="montoPesos" class="form-control monto-input"
                       placeholder="0.00" step="0.01" min="0"
                       oninput="calcularPesos()">
              </div>

              <div class="result-rows">
                <div class="result-row">
                  <span class="result-label">Neto CLP</span>
                  <span class="result-value" id="clpNeto">—</span>
                </div>
                <div class="result-row comision-row" id="rowComisionPesos" style="display:none;">
                  <span class="result-label text-success">Comisión (5%)</span>
                  <span class="result-value text-success" id="clpComision">—</span>
                </div>
                <div class="result-row mt-1" style="border-top: 1px dashed #e2e8f0; padding-top: 8px;">
                  <span class="result-label text-muted" style="font-size:11px;">Equiv. en Soles</span>
                  <span class="result-value" style="font-size:13px;" id="clpEnSoles">—</span>
                </div>
              </div>

              <div class="result-total">
                <span class="total-label">TOTAL CLP</span>
                <span class="total-value" id="clpTotal">—</span>
              </div>

              <div class="result-total" style="font-size:11px; text-align:center; background:#f1f5f9; color:#475569; border-top: 1px solid #e2e8f0;">
                <span id="clpInfoTc">1 USD = <span class="fw-bold">—</span> PEN = <span class="fw-bold">—</span> CLP</span>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

      </div><!-- end panels-row -->
    </div><!-- end tab-calculadora -->




  </div><!-- page-body -->
</div><!-- main-content -->
</div><!-- app-calculadora -->

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// ═══════════════════════════════════════════════════════════
// ESTADO GLOBAL
// ═══════════════════════════════════════════════════════════
let modoDolares = 'efectivo'; // 'efectivo' | 'tarjeta'

// ═══════════════════════════════════════════════════════════
// UTILIDADES
// ═══════════════════════════════════════════════════════════
function formatNum(n) {
    if (isNaN(n) || n === null) return '—';
    return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function getTcUsd() { return parseFloat(document.getElementById('barTcUsd').value) || 0; }
function getTcClp() { return parseFloat(document.getElementById('barTcClp').value) || 0; }

// ═══════════════════════════════════════════════════════════
// TABS
// ═══════════════════════════════════════════════════════════
function switchTab(tab, btn) {
    document.querySelectorAll('.tab-content-pane').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.calc-tab').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).style.display = 'block';
    if (btn) btn.classList.add('active');
}

// ═══════════════════════════════════════════════════════════
// PANEL 1: SOLES
// ═══════════════════════════════════════════════════════════
function calcularSoles() {
    const monto    = parseFloat(document.getElementById('montoSoles').value) || 0;
    const tc_usd   = getTcUsd();
    const posOn    = document.getElementById('togglePosSoles').checked;
    const panel    = document.getElementById('panelSoles');

    if (monto <= 0) {
        document.getElementById('solNeto').textContent    = '—';
        document.getElementById('solComision').textContent = '—';
        document.getElementById('solTotal').textContent   = '—';
        return;
    }

    const neto      = monto * tc_usd;
    const comision  = posOn ? neto * 0.05 : 0;
    const total     = neto + comision;

    document.getElementById('solNeto').textContent    = 'S/ ' + formatNum(neto);
    document.getElementById('solComision').textContent = '+S/ ' + formatNum(comision);
    document.getElementById('solTotal').textContent   = 'S/ ' + formatNum(total);
}

function togglePosSolesChange() {
    const posOn = document.getElementById('togglePosSoles').checked;
    const panel  = document.getElementById('panelSoles');
    const rowCom = document.getElementById('rowComisionSoles');

    rowCom.style.display = posOn ? 'flex' : 'none';
    panel.classList.toggle('panel-pos-activo', posOn);
    calcularSoles();
}

// ═══════════════════════════════════════════════════════════
// PANEL 2: DÓLARES
// ═══════════════════════════════════════════════════════════
function togglePosDolaresChange() {
    const posOn = document.getElementById('togglePosDolares').checked;
    const panel = document.getElementById('panelDolares');
    const rowCom = document.getElementById('rowComisionUsd');

    rowCom.style.display = posOn ? 'flex' : 'none';
    panel.classList.toggle('panel-pos-activo', posOn);
    calcularDolares();
}

function calcularDolares() {
    const el = document.getElementById('montoDolares');
    if (!el) return;
    const monto  = parseFloat(el.value) || 0;
    const tc_usd = getTcUsd();
    const posOn = document.getElementById('togglePosDolares').checked;

    if (monto <= 0) {
        document.getElementById('usdBase').textContent       = '—';
        document.getElementById('usdComision').textContent   = '—';
        document.getElementById('usdEnSoles').textContent    = '—';
        document.getElementById('usdTotal').textContent      = '—';
        return;
    }

    const comision_usd = posOn ? monto * 0.05 : 0;
    const total_usd = monto + comision_usd;
    const en_soles = total_usd * tc_usd;

    document.getElementById('usdBase').textContent      = '$ ' + formatNum(monto);
    document.getElementById('usdComision').textContent  = '+$ ' + formatNum(comision_usd);
    document.getElementById('usdEnSoles').textContent   = 'S/ ' + formatNum(en_soles);
    document.getElementById('usdTotal').textContent     = '$ ' + formatNum(total_usd);
}

// ═══════════════════════════════════════════════════════════
// PANEL 3: PESOS CHILENOS
// ═══════════════════════════════════════════════════════════
function togglePosPesosChange() {
    const posOn = document.getElementById('togglePosPesos').checked;
    const panel = document.getElementById('panelPesos');
    const rowCom = document.getElementById('rowComisionPesos');

    rowCom.style.display = posOn ? 'flex' : 'none';
    panel.classList.toggle('panel-pos-activo', posOn);
    calcularPesos();
}

function calcularPesos() {
    const el = document.getElementById('montoPesos');
    if (!el) return;
    const base_usd  = parseFloat(el.value) || 0;
    const tc_usd    = getTcUsd();
    const tc_clp    = getTcClp(); // 1 SOL = X PESOS
    const posOn     = document.getElementById('togglePosPesos').checked;

    if (base_usd <= 0) {
        document.getElementById('clpNeto').textContent     = '—';
        document.getElementById('clpComision').textContent = '—';
        document.getElementById('clpEnSoles').textContent  = '—';
        document.getElementById('clpTotal').textContent    = '—';
        document.getElementById('clpInfoTc').innerHTML     =
            '1 USD = <span class="fw-bold">—</span> PEN = <span class="fw-bold">—</span> CLP';
        return;
    }

    const clp_por_usd = tc_usd * tc_clp;
    const monto_clp = base_usd * clp_por_usd;
    const comision_clp = posOn ? monto_clp * 0.05 : 0;
    const total_clp = monto_clp + comision_clp;
    
    // Equiv en Soles (ya sea con o sin comisión POS)
    const total_soles = posOn ? (base_usd * tc_usd) * 1.05 : (base_usd * tc_usd);

    document.getElementById('clpNeto').textContent     = 'CLP ' + formatNum(monto_clp);
    document.getElementById('clpComision').textContent = '+CLP ' + formatNum(comision_clp);
    document.getElementById('clpEnSoles').textContent  = 'S/ ' + formatNum(total_soles);
    document.getElementById('clpTotal').textContent    = 'CLP ' + formatNum(total_clp);

    document.getElementById('clpInfoTc').innerHTML =
        '1 USD = <span class="fw-bold">'+formatNum(tc_usd)+'</span> PEN = <span class="fw-bold">'+formatNum(clp_por_usd)+'</span> CLP';
}

// ═══════════════════════════════════════════════════════════
// RECALCULAR TODO (al cambiar TC en la barra)
// ═══════════════════════════════════════════════════════════
function recalcularTodo() {
    calcularSoles();
    calcularDolares();
    calcularPesos();
}

// ═══════════════════════════════════════════════════════════
// RECARGAR TC DESDE BD
// ═══════════════════════════════════════════════════════════
async function recargarTC() {
    const btn = document.getElementById('btnRecargar');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Cargando...';

    try {
        const res = await fetch('<?= project_base_url() ?>api/calculadora.php?action=getTipoCambio');
        const json = await res.json();
        if (json.ok && json.data) {
            document.getElementById('barTcUsd').value  = json.data.tc_usd;
            document.getElementById('barTcClp').value  = json.data.tc_clp;
            document.getElementById('barFecha').value  = json.data.fecha;
            recalcularTodo();
            // Actualizar también el formulario de TC
            document.getElementById('tcUsdInput').value = json.data.tc_usd;
            document.getElementById('tcClpInput').value = json.data.tc_clp;
        }
    } catch(e) {
        console.error('Error al recargar TC:', e);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i>Recargar';
    }
}

// ═══════════════════════════════════════════════════════════
// GUARDAR TIPO DE CAMBIO
// ═══════════════════════════════════════════════════════════
async function guardarTC() {
    const fecha   = document.getElementById('tcFecha').value;
    const tc_usd  = parseFloat(document.getElementById('tcUsdInput').value);
    const tc_clp  = parseFloat(document.getElementById('tcClpInput').value);

    if (!fecha || isNaN(tc_usd) || tc_usd <= 0 || isNaN(tc_clp) || tc_clp <= 0) {
        Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Completa todos los campos correctamente.', confirmButtonColor: '#22c55e' });
        return;
    }

    const btn = document.getElementById('btnGuardarTC');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';

    try {
        const res  = await fetch('<?= project_base_url() ?>api/calculadora.php?action=guardarTC', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fecha, tc_usd, tc_clp })
        });
        const json = await res.json();

        if (json.ok) {
            Swal.fire({ icon: 'success', title: '¡Guardado!', text: json.msg, timer: 2000, showConfirmButton: false });
            // Recargar historial (reload completo o actualizar tabla dinámicamente)
            setTimeout(() => {
                document.getElementById('barTcUsd').value = tc_usd;
                document.getElementById('barTcClp').value = tc_clp;
                document.getElementById('barFecha').value = fecha;
                recalcularTodo();
                recargarHistorial();
            }, 800);
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: json.msg, confirmButtonColor: '#ef4444' });
        }
    } catch(e) {
        Swal.fire({ icon: 'error', title: 'Error de red', text: e.message });
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-floppy-fill me-2"></i>Guardar Tipo de Cambio';
    }
}

async function guardarTCDirecto() {
    const fecha = document.getElementById('barFecha').value || new Date().toISOString().split('T')[0];
    const tc_usd = parseFloat(document.getElementById('barTcUsd').value);
    const tc_clp = parseFloat(document.getElementById('barTcClp').value);

    if (isNaN(tc_usd) || tc_usd <= 0 || isNaN(tc_clp) || tc_clp <= 0) {
        Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Ingresa valores válidos de tipo de cambio.', confirmButtonColor: '#22c55e' });
        return;
    }

    const btn = document.getElementById('btnGuardarTCDirecto');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
    }

    try {
        const res = await fetch('<?= project_base_url() ?>api/calculadora.php?action=guardarTC', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fecha, tc_usd, tc_clp })
        });
        const json = await res.json();

        if (json.ok) {
            Swal.fire({ icon: 'success', title: '¡Guardado!', text: 'Tipos de cambio guardados correctamente', timer: 2000, showConfirmButton: false });
            recalcularTodo();
            const tcUsdInput = document.getElementById('tcUsdInput');
            const tcClpInput = document.getElementById('tcClpInput');
            if (tcUsdInput) tcUsdInput.value = tc_usd;
            if (tcClpInput) tcClpInput.value = tc_clp;
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: json.msg, confirmButtonColor: '#ef4444' });
        }
    } catch(e) {
        Swal.fire({ icon: 'error', title: 'Error de red', text: e.message });
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-floppy-fill me-1"></i>Guardar';
        }
    }
}

async function recargarHistorial() {
    try {
        const res  = await fetch('<?= project_base_url() ?>api/calculadora.php?action=getTipoCambio');
        // Reload the whole page to refresh historial table
        window.location.reload();
    } catch(e) {}
}

// ═══════════════════════════════════════════════════════════
// GUARDAR PARÁMETROS
// ═══════════════════════════════════════════════════════════
async function guardarParams() {
    const recargo_pos    = parseFloat(document.getElementById('pRecargoPOS').value) || 0.05;
    const mostrar_usd    = document.getElementById('pMostrarUSD').checked ? 1 : 0;
    const mostrar_clp    = document.getElementById('pMostrarCLP').checked ? 1 : 0;

    const btn = document.getElementById('btnGuardarParams');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';

    try {
        const res  = await fetch('<?= project_base_url() ?>api/calculadora.php?action=guardarParams', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                recargo_pos: recargo_pos.toFixed(4),
                mostrar_panel_usd: mostrar_usd,
                mostrar_panel_clp: mostrar_clp
            })
        });
        const json = await res.json();

        const msgEl = document.getElementById('paramsMsg');
        if (json.ok) {
            msgEl.style.display = 'block';
            msgEl.innerHTML = `<div class="alert alert-success py-2 small mb-0"><i class="bi bi-check-circle-fill me-1"></i>${json.msg} La página se recargará pronto.</div>`;
            setTimeout(() => window.location.reload(), 1500);
        } else {
            msgEl.style.display = 'block';
            msgEl.innerHTML = `<div class="alert alert-danger py-2 small mb-0"><i class="bi bi-x-circle-fill me-1"></i>${json.msg}</div>`;
        }
    } catch(e) {
        Swal.fire({ icon: 'error', title: 'Error de red', text: e.message });
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-floppy-fill me-2"></i>Guardar Parámetros';
    }
}

// ═══════════════════════════════════════════════════════════
// INIT
// ═══════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
    // Disparar cálculo inicial si hay valores cargados
    recalcularTodo();
});
</script>

<style>
/* ═══════════════════════════════════════════════════
   CALCULADORA — Estilos específicos del módulo
   ═══════════════════════════════════════════════════ */

/* Tabs */
.calc-tabs {
  display: flex;
  gap: 4px;
  list-style: none;
  padding: 0;
  margin: 0;
  border-bottom: 2px solid #e2e8f0;
  padding-bottom: 0px;
}
.calc-tab {
  background: transparent;
  border: none;
  padding: 10px 20px;
  font-size: 13px;
  font-weight: 600;
  color: #64748b;
  border-radius: 8px 8px 0 0;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
  top: 2px;
}
.calc-tab:hover { background: #f8faff; color: #1e40af; }
.calc-tab.active {
  background: white;
  color: #1e40af;
  border: 2px solid #e2e8f0;
  border-bottom-color: white;
}

/* Barra TC */
.tc-bar { border-radius: 12px; }
.tc-bar-label { font-size: 10px; font-weight: 700; color: #94a3b8; letter-spacing: .5px; text-transform: uppercase; display: block; margin-bottom: 4px; }
.tc-input { font-weight: 600; border: 1px solid #e2e8f0; border-radius: 8px; }
.tc-symbol { background: #f8faff; font-weight: 700; font-size: 11px; color: #64748b; }

/* Paneles Calculadora */
.calc-panel {
  background: white;
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(0,0,0,.07);
  overflow: hidden;
  border: 1px solid #f0f4f8;
  transition: box-shadow 0.2s, transform 0.2s;
}
.calc-panel:hover { box-shadow: 0 6px 24px rgba(0,0,0,.10); transform: translateY(-2px); }

.panel-soles  { border-top: 4px solid #22c55e; }
.panel-dolares{ border-top: 4px solid #3b82f6; }
.panel-pesos  { border-top: 4px solid #f97316; }

.panel-pos-activo { background: #fef9c3 !important; }

.panel-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px 12px;
  border-bottom: 1px solid #f1f5f9;
}
.panel-icon { font-size: 24px; }
.panel-title { font-size: 15px; font-weight: 800; color: #1e293b; letter-spacing: .5px; }
.panel-subtitle { font-size: 10px; color: #94a3b8; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; }

.panel-body { padding: 18px 20px 0; }

.input-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; display: block; margin-bottom: 6px; }
.monto-input {
  font-size: 1.4rem;
  font-weight: 700;
  text-align: right;
  border: 2px solid #e2e8f0;
  border-radius: 0 8px 8px 0;
  padding: 8px 14px;
  color: #1e293b;
  transition: border-color 0.2s;
}
.monto-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15); }
.monto-symbol {
  background: #f8faff ;
  border: 2px solid #e2e8f0;
  border-right: none;
  font-weight: 700;
  color: #475569;
  border-radius: 8px 0 0 8px;
}

/* Result Rows */
.result-rows { margin-bottom: 12px; }
.result-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 7px 0;
  border-bottom: 1px dashed #f1f5f9;
}
.result-row:last-child { border-bottom: none; }
.result-label { font-size: 12px; color: #64748b; font-weight: 600; }
.result-value { font-size: 15px; font-weight: 700; color: #1e293b; font-variant-numeric: tabular-nums; }
.comision-row .result-value { color: #16a34a; }

/* Total */
.result-total {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #1e293b;
  color: white;
  padding: 14px 20px;
  margin: 0 -20px;
  border-radius: 0 0 10px 10px;
}
.total-label { font-size: 11px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; opacity: .7; }
.total-value { font-size: 20px; font-weight: 800; font-variant-numeric: tabular-nums; }

/* Toggle switch */
.toggle-container { display: flex; align-items: center; gap: 8px; }
.toggle-label { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; }
.toggle-switch { position: relative; display: inline-block; width: 40px; height: 22px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider {
  position: absolute; cursor: pointer;
  top: 0; left: 0; right: 0; bottom: 0;
  background: #cbd5e1;
  border-radius: 22px;
  transition: .3s;
}
.toggle-slider::before {
  content: "";
  position: absolute;
  height: 16px; width: 16px;
  left: 3px; bottom: 3px;
  background: white;
  border-radius: 50%;
  transition: .3s;
  box-shadow: 0 1px 3px rgba(0,0,0,.2);
}
input:checked + .toggle-slider { background: #22c55e; }
input:checked + .toggle-slider::before { transform: translateX(18px); }

/* Mode switch (Efectivo/Tarjeta) */
.mode-switch {
  display: flex;
  background: #f1f5f9;
  border-radius: 8px;
  padding: 3px;
  gap: 2px;
}
.mode-btn {
  flex: 1;
  border: none;
  background: transparent;
  border-radius: 6px;
  padding: 7px 10px;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  transition: all .2s;
}
.mode-btn.active {
  background: white;
  color: #1e40af;
  box-shadow: 0 1px 4px rgba(0,0,0,.12);
}

/* Responsive */
@media (max-width: 768px) {
  .panels-row { flex-direction: column; }
  .calc-tab { padding: 8px 14px; font-size: 12px; }
  .monto-input { font-size: 1.1rem; }
}

/* Valor "—" gris */
#solNeto:contains('—'), #solTotal:contains('—'),
#usdTotal:contains('—'), #clpEfectivo:contains('—') { color: #94a3b8; }

/* Param Cards (Tab Parámetros) */
.param-card {
  background: white;
  border-radius: 14px;
  border: 1px solid #f0f4f8;
  box-shadow: 0 2px 10px rgba(0,0,0,.06);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transition: box-shadow .2s, transform .2s;
}
.param-card:hover {
  box-shadow: 0 6px 22px rgba(0,0,0,.10);
  transform: translateY(-2px);
}
.param-card-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 22px 0 18px;
  font-size: 28px;
}
.param-card-body {
  padding: 0 22px 22px;
  flex: 1;
}
.param-card-title {
  font-size: 14px;
  font-weight: 800;
  color: #1e293b;
  letter-spacing: .3px;
  margin-bottom: 4px;
}
.param-card-desc {
  font-size: 12px;
  color: #94a3b8;
  line-height: 1.4;
}
.param-hint {
  font-size: 11px;
  color: #94a3b8;
  margin-top: 6px;
}
</style>

</body></html>
