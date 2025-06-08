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
if (!$authHelper->tienePermiso('juntas.edit', $_SESSION['rol_id'])) {
    $_SESSION['error'] = 'No tienes permiso para editar juntas';
    header('Location: index.php');
    exit;
}

// Obtener ID de la junta
$juntaId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($juntaId <= 0) {
    $_SESSION['error'] = 'ID de junta inválido';
    header('Location: index_junta.php');
    exit;
}

$junta = new Junta($db);
$juntaData = $junta->obtenerPorId($juntaId);

if (!$juntaData) {
    $_SESSION['error'] = 'Junta no encontrada';
    header('Location: index_junta.php');
    exit;
}

// Verificar si el usuario puede editar esta junta
if (!$authHelper->tienePermiso('juntas.manage_all', $_SESSION['rol_id']) && 
    $juntaData['CreadaPor'] != $_SESSION['usuario_id']) {
    $_SESSION['error'] = 'No tienes permiso para editar esta junta';
    header('Location: index_junta.php');
    exit;
}

// Verificar si la junta puede ser editada
if ($juntaData['Estado'] === 'Completada' || $juntaData['Estado'] === 'Cancelada') {
    $_SESSION['error'] = 'No se puede editar una junta ' . strtolower($juntaData['Estado']);
    header('Location: index_junta.php');
    exit;
}

// Procesar formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $requiereGarantia = isset($_POST['requiere_garantia']) ? 1 : 0;
    $montoGarantia = $requiereGarantia ? floatval($_POST['monto_garantia']) : 0;
    $comision = floatval($_POST['comision']);
    $penalidad = floatval($_POST['penalidad']);
    $diasGracia = intval($_POST['dias_gracia']);

    // Validaciones
    $errores = [];
    if (empty($nombre)) $errores[] = 'El nombre es obligatorio';
    if ($comision < 0 || $comision > 100) $errores[] = 'La comisión debe estar entre 0 y 100';
    if ($penalidad < 0 || $penalidad > 100) $errores[] = 'La penalidad debe estar entre 0 y 100';

    if (empty($errores)) {
        $junta->JuntaID = $juntaId;
        $junta->NombreJunta = $nombre;
        $junta->Descripcion = $descripcion;
        $junta->RequiereGarantia = $requiereGarantia;
        $junta->MontoGarantia = $montoGarantia;
        $junta->PorcentajeComision = $comision;
        $junta->PorcentajePenalidad = $penalidad;
        $junta->DiasGraciaPenalidad = $diasGracia;

        if ($junta->actualizar()) {
            $_SESSION['mensaje_exito'] = 'Junta actualizada exitosamente!';
            header('Location: index_junta.php');
            exit;
        } else {
            $errores[] = 'Error al actualizar la junta. Inténtalo nuevamente.';
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
                    <h3><i class="fas fa-edit me-2"></i>Editar Junta</h3>
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
                                   value="<?= htmlspecialchars($juntaData['NombreJunta']) ?>">
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="2"><?= 
                                htmlspecialchars($juntaData['Descripcion']) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Monto de Aporte</label>
                            <div class="input-group">
                                <span class="input-group-text">S/</span>
                                <input type="text" class="form-control" value="<?= 
                                    number_format($juntaData['MontoAporte'], 2) ?>" readonly>
                            </div>
                            <small class="text-muted">No se puede modificar el monto una vez creada la junta.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Frecuencia de Pago</label>
                            <input type="text" class="form-control" value="<?= 
                                htmlspecialchars($juntaData['FrecuenciaPago']) ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="max_participantes" class="form-label">Máximo de Participantes *</label>
                            <input type="number" class="form-control" id="max_participantes" name="max_participantes" 
                                min="<?= count($participantes) ?>" required 
                                value="<?= htmlspecialchars($juntaData['MaximoParticipantes'] ?? 10) ?>">
                            <small class="text-muted">El mínimo es <?= count($participantes) ?> (participantes actuales)</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Fecha de Inicio</label>
                            <input type="text" class="form-control" value="<?= 
                                date('d/m/Y', strtotime($juntaData['FechaInicio'])) ?>" readonly>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="requiere_garantia" 
                                   name="requiere_garantia" <?= $juntaData['RequiereGarantia'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="requiere_garantia">Requiere Garantía</label>
                        </div>

                        <div class="row" id="garantia_fields" style="<?= $juntaData['RequiereGarantia'] ? '' : 'display: none;' ?>">
                            <div class="col-md-6 mb-3">
                                <label for="monto_garantia" class="form-label">Monto de Garantía</label>
                                <div class="input-group">
                                    <span class="input-group-text">S/</span>
                                    <input type="number" class="form-control" id="monto_garantia" 
                                           name="monto_garantia" step="0.01" min="0" 
                                           value="<?= htmlspecialchars($juntaData['MontoGarantia']) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="comision" class="form-label">Comisión (%)</label>
                                <input type="number" class="form-control" id="comision" name="comision" 
                                       step="0.01" min="0" max="100" 
                                       value="<?= htmlspecialchars($juntaData['PorcentajeComision']) ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="penalidad" class="form-label">Penalidad (%)</label>
                                <input type="number" class="form-control" id="penalidad" name="penalidad" 
                                       step="0.01" min="0" max="100" 
                                       value="<?= htmlspecialchars($juntaData['PorcentajePenalidad']) ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="dias_gracia" class="form-label">Días de Gracia</label>
                                <input type="number" class="form-control" id="dias_gracia" name="dias_gracia" 
                                       min="0" value="<?= htmlspecialchars($juntaData['DiasGraciaPenalidad']) ?>">
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index_junta.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Guardar Cambios
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