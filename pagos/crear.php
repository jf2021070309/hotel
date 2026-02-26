<?php
// ============================================================
// pagos/crear.php — Registrar un pago
// ============================================================
require_once '../config/conexion.php';
$base = '../'; $page_title = 'Registrar Pago — Hotel Manager';

$errores = [];
$pre_registro = (int)($_GET['registro_id'] ?? 0);

// Cargar registros activos para el formulario
$sql = "SELECT r.id, r.precio,
               h.numero hab_num,
               c.nombre cliente
        FROM registros r
        JOIN habitaciones h ON h.id = r.habitacion_id
        JOIN clientes c ON c.id = r.cliente_id
        WHERE r.estado = 'activo'
        ORDER BY h.numero";
$registros_activos = $conn->query($sql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registro_id = (int)($_POST['registro_id'] ?? 0);
    $monto       = (float)($_POST['monto'] ?? 0);
    $metodo      = $_POST['metodo'] ?? 'efectivo';
    $fecha       = $_POST['fecha'] ?? hoy();

    if ($registro_id <= 0) $errores[] = 'Seleccione un registro (habitación/huésped).';
    if ($monto <= 0)       $errores[] = 'El monto debe ser mayor a 0.';
    if (!in_array($metodo, ['efectivo','tarjeta'])) $errores[] = 'Método de pago inválido.';

    if (!$errores) {
        $stmt = $conn->prepare("INSERT INTO pagos (registro_id, monto, metodo, fecha) VALUES (?,?,?,?)");
        $stmt->bind_param('idss', $registro_id, $monto, $metodo, $fecha);
        if ($stmt->execute()) {
            $stmt->close();
            redirigir('index.php?msg=registrado');
        } else {
            $errores[] = 'Error al registrar: ' . $conn->error;
            $stmt->close();
        }
    }
}
?>
<?php include '../includes/head.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div>
      <h4><i class="bi bi-cash-coin me-2 text-primary"></i>Registrar Pago</h4>
      <p>Ingrese el pago del huésped</p>
    </div>
    <a href="index.php" class="btn-outline-custom">
      <i class="bi bi-arrow-left"></i> Volver
    </a>
  </div>

  <div class="page-body">
    <div class="form-card">
      <div class="mb-4 d-flex align-items-center gap-3">
        <div class="stat-icon cyan" style="width:44px;height:44px;font-size:18px">
          <i class="bi bi-cash-stack"></i>
        </div>
        <div>
          <h5 class="mb-0 fw-bold">Nuevo Pago</h5>
          <small class="text-muted">Asociar pago a un registro activo</small>
        </div>
      </div>

      <?php foreach ($errores as $err): ?>
        <div class="alert-custom alert-error"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($err) ?></div>
      <?php endforeach; ?>

      <form method="POST" novalidate>
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Habitación / Huésped</label>
            <select name="registro_id" id="sel_registro" class="form-select"
                    onchange="autoFillMonto(this)" required>
              <option value="">Seleccionar huésped activo...</option>
              <?php while ($ra = $registros_activos->fetch_assoc()): ?>
                <option value="<?= (int)$ra['id'] ?>"
                        data-precio="<?= (float)$ra['precio'] ?>"
                        <?= ($pre_registro === (int)$ra['id'] || (isset($_POST['registro_id']) && (int)$_POST['registro_id'] === (int)$ra['id'])) ? 'selected' : '' ?>>
                  Hab. <?= e($ra['hab_num']) ?> — <?= e($ra['cliente']) ?> (<?= moneda((float)$ra['precio']) ?>/noche)
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Monto (S/)</label>
            <input type="number" name="monto" id="inp_monto" class="form-control"
                   step="0.01" min="0" placeholder="0.00"
                   value="<?= e($_POST['monto'] ?? '') ?>" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Método de Pago</label>
            <select name="metodo" class="form-select">
              <option value="efectivo" <?= (($_POST['metodo'] ?? 'efectivo') === 'efectivo') ? 'selected':'' ?>>
                💵 Efectivo
              </option>
              <option value="tarjeta" <?= (($_POST['metodo'] ?? '') === 'tarjeta') ? 'selected':'' ?>>
                💳 Tarjeta
              </option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Fecha del Pago</label>
            <input type="date" name="fecha" class="form-control"
                   value="<?= e($_POST['fecha'] ?? hoy()) ?>" required>
          </div>
        </div>

        <div class="mt-4">
          <button type="submit" class="btn-primary-custom w-100 justify-content-center">
            <i class="bi bi-save-fill"></i> Registrar Pago
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function autoFillMonto(sel) {
  const opt = sel.options[sel.selectedIndex];
  const precio = opt.getAttribute('data-precio');
  if (precio && document.getElementById('inp_monto').value === '') {
    document.getElementById('inp_monto').value = parseFloat(precio).toFixed(2);
  }
}
// Pre-fill si hay selección
const selReg = document.getElementById('sel_registro');
if (selReg && selReg.value) autoFillMonto(selReg);
</script>
</body>
</html>
