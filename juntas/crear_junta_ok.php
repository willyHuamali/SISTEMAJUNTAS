<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../clases/Junta.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../clases/AuthHelper.php';

verificarAutenticacion();
verificarInactividad();

$database = new Database();
$db = $database->getConnection();
$authHelper = new \Clases\AuthHelper($db);

// Verificar permisos
if (!$authHelper->tienePermiso('juntas.create', $_SESSION['rol_id'])) {
    $_SESSION['error'] = 'No tienes permiso para crear juntas';
    header('Location: index.php');
    exit;
}

// Procesar formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $monto = floatval($_POST['monto']);
    $frecuencia = $_POST['frecuencia'];
    $fechaInicio = $_POST['fecha_inicio'];
    $maxParticipantes = intval($_POST['max_participantes']);
    $requiereGarantia = isset($_POST['requiere_garantia']) ? 1 : 0;
    $montoGarantia = $requiereGarantia ? floatval($_POST['monto_garantia']) : 0;
    $comision = floatval($_POST['comision']);
    $penalidad = floatval($_POST['penalidad']);
    $diasGracia = intval($_POST['dias_gracia']);

    // Validaciones
    $errores = [];
    if (empty($nombre)) $errores[] = 'El nombre es obligatorio';
    if ($monto <= 0) $errores[] = 'El monto debe ser mayor a cero';
    if ($maxParticipantes < 2) $errores[] = 'Debe haber al menos 2 participantes';
    if ($fechaInicio < date('Y-m-d')) $errores[] = 'La fecha no puede ser en el pasado';
    if ($comision < 0 || $comision > 100) $errores[] = 'La comisión debe estar entre 0 y 100';
    if ($penalidad < 0 || $penalidad > 100) $errores[] = 'La penalidad debe estar entre 0 y 100';

    if (empty($errores)) {
        $junta = new Junta($db);
        $junta->NombreJunta = $nombre;
        $junta->Descripcion = $descripcion;
        $junta->MontoAporte = $monto;
        $junta->FrecuenciaPago = $frecuencia;
        $junta->FechaInicio = $fechaInicio;
        $junta->CreadaPor = $_SESSION['usuario_id'];
        $junta->RequiereGarantia = $requiereGarantia;
        $junta->MontoGarantia = $montoGarantia;
        $junta->PorcentajeComision = $comision;
        $junta->PorcentajePenalidad = $penalidad;
        $junta->DiasGraciaPenalidad = $diasGracia;

        if ($junta->crear()) {
            $_SESSION['mensaje_exito'] = 'Junta creada exitosamente!';
            header('Location: index_junta.php');
            exit;
        } else {
            $errores[] = 'Error al crear la junta. Inténtalo nuevamente.';
        }
    }
}

require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-plus-circle me-2"></i>Crear Nueva Junta</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($errores)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errores as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre de la Junta *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required 
                                   value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="2"><?= 
                                htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="monto" class="form-label">Monto de Aporte *</label>
                                <div class="input-group">
                                    <span class="input-group-text">S/</span>
                                    <input type="number" class="form-control" id="monto" name="monto" 
                                           step="0.01" min="1" required 
                                           value="<?= htmlspecialchars($_POST['monto'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="max_participantes" class="form-label">N° de Participantes *</label>
                                <input type="number" class="form-control" id="max_participantes" 
                                       name="max_participantes" min="2" required 
                                       value="<?= htmlspecialchars($_POST['max_participantes'] ?? '5') ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="frecuencia" class="form-label">Frecuencia de Pago *</label>
                                <select class="form-select" id="frecuencia" name="frecuencia" required>
                                    <option value="Semanal" <?= ($_POST['frecuencia'] ?? '') === 'Semanal' ? 'selected' : '' ?>>Semanal</option>
                                    <option value="Quincenal" <?= ($_POST['frecuencia'] ?? '') === 'Quincenal' ? 'selected' : '' ?>>Quincenal</option>
                                    <option value="Mensual" <?= ($_POST['frecuencia'] ?? '') === 'Mensual' ? 'selected' : '' ?>>Mensual</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="fecha_inicio" class="form-label">Fecha de Inicio *</label>
                                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                                       min="<?= date('Y-m-d') ?>" required 
                                       value="<?= htmlspecialchars($_POST['fecha_inicio'] ?? date('Y-m-d')) ?>">
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="requiere_garantia" 
                                   name="requiere_garantia" <?= isset($_POST['requiere_garantia']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="requiere_garantia">Requiere Garantía</label>
                        </div>

                        <div class="row" id="garantia_fields" style="<?= isset($_POST['requiere_garantia']) ? '' : 'display: none;' ?>">
                            <div class="col-md-6 mb-3">
                                <label for="monto_garantia" class="form-label">Monto de Garantía</label>
                                <div class="input-group">
                                    <span class="input-group-text">S/</span>
                                    <input type="number" class="form-control" id="monto_garantia" 
                                           name="monto_garantia" step="0.01" min="0" 
                                           value="<?= htmlspecialchars($_POST['monto_garantia'] ?? '0') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="comision" class="form-label">Comisión (%)</label>
                                <input type="number" class="form-control" id="comision" name="comision" 
                                       step="0.01" min="0" max="100" 
                                       value="<?= htmlspecialchars($_POST['comision'] ?? '0') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="penalidad" class="form-label">Penalidad (%)</label>
                                <input type="number" class="form-control" id="penalidad" name="penalidad" 
                                       step="0.01" min="0" max="100" 
                                       value="<?= htmlspecialchars($_POST['penalidad'] ?? '0') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="dias_gracia" class="form-label">Días de Gracia</label>
                                <input type="number" class="form-control" id="dias_gracia" name="dias_gracia" 
                                       min="0" value="<?= htmlspecialchars($_POST['dias_gracia'] ?? '0') ?>">
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index_junta.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Guardar Junta
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('requiere_garantia').addEventListener('change', function() {
    document.getElementById('garantia_fields').style.display = this.checked ? 'block' : 'none';
});
</script>

<?php require_once '../includes/footer.php'; ?>