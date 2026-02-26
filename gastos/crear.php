<?php
// ============================================================
// gastos/crear.php — Registrar un gasto
// ============================================================
require_once '../config/conexion.php';
$base = '../'; $page_title = 'Nuevo Gasto — Hotel Manager';

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descripcion = trim($_POST['descripcion'] ?? '');
    $monto       = (float)($_POST['monto'] ?? 0);
    $fecha       = $_POST['fecha'] ?? hoy();

    if ($descripcion === '') $errores[] = 'La descripción es obligatoria.';
    if ($monto <= 0)         $errores[] = 'El monto debe ser mayor a 0.';

    if (!$errores) {
        $stmt = $conn->prepare("INSERT INTO gastos (descripcion, monto, fecha) VALUES (?,?,?)");
        $stmt->bind_param('sds', $descripcion, $monto, $fecha);
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
      <h4><i class="bi bi-receipt-cutoff me-2 text-primary"></i>Nuevo Gasto</h4>
      <p>Registrar gasto operativo</p>
    </div>
    <a href="index.php" class="btn-outline-custom">
      <i class="bi bi-arrow-left"></i> Volver
    </a>
  </div>

  <div class="page-body">
    <div class="form-card">
      <div class="mb-4 d-flex align-items-center gap-3">
        <div class="stat-icon amber" style="width:44px;height:44px;font-size:18px">
          <i class="bi bi-graph-down-arrow"></i>
        </div>
        <div>
          <h5 class="mb-0 fw-bold">Registrar Gasto</h5>
          <small class="text-muted">Complete los datos del gasto operativo</small>
        </div>
      </div>

      <?php foreach ($errores as $err): ?>
        <div class="alert-custom alert-error"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($err) ?></div>
      <?php endforeach; ?>

      <form method="POST" novalidate>
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Descripción</label>
            <input type="text" name="descripcion" class="form-control"
                   placeholder="Ej: Limpieza, Mantenimiento, Suministros..."
                   value="<?= e($_POST['descripcion'] ?? '') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Monto (S/)</label>
            <input type="number" name="monto" class="form-control"
                   step="0.01" min="0" placeholder="0.00"
                   value="<?= e($_POST['monto'] ?? '') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Fecha</label>
            <input type="date" name="fecha" class="form-control"
                   value="<?= e($_POST['fecha'] ?? hoy()) ?>" required>
          </div>
        </div>
        <div class="mt-4">
          <button type="submit" class="btn-primary-custom w-100 justify-content-center">
            <i class="bi bi-save-fill"></i> Registrar Gasto
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
</body>
</html>
