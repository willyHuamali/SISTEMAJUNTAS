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
    header('Location: index_junta.php');
    exit;
}

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Procesar formulario si se envió
// Procesar formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = 'Token de seguridad inválido';
        header('Location: crear_junta.php');
        exit;
    }

    // Sanitizar y validar entradas (versión actualizada)
    $nombre = trim(htmlspecialchars($_POST['nombre'] ?? '', ENT_QUOTES, 'UTF-8'));
    $descripcion = trim(htmlspecialchars($_POST['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'));
    $monto = filter_input(INPUT_POST, 'monto', FILTER_VALIDATE_FLOAT);
    $frecuencia = htmlspecialchars($_POST['frecuencia'] ?? '', ENT_QUOTES, 'UTF-8');
    $fechaInicio = htmlspecialchars($_POST['fecha_inicio'] ?? '', ENT_QUOTES, 'UTF-8');
    // ... resto del código igual
    $maxParticipantes = filter_input(INPUT_POST, 'max_participantes', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 2, 'max_range' => 50]
    ]);
    $requiereGarantia = isset($_POST['requiere_garantia']);
    $montoGarantia = $requiereGarantia ? filter_input(INPUT_POST, 'monto_garantia', FILTER_VALIDATE_FLOAT) : 0;
    $comision = filter_input(INPUT_POST, 'comision', FILTER_VALIDATE_FLOAT, [
        'options' => ['min_range' => 0, 'max_range' => 100]
    ]);
    $penalidad = filter_input(INPUT_POST, 'penalidad', FILTER_VALIDATE_FLOAT, [
        'options' => ['min_range' => 0, 'max_range' => 100]
    ]);
    $diasGracia = filter_input(INPUT_POST, 'dias_gracia', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 0]
    ]);

    // Validaciones adicionales
    $errores = [];
    if (empty($nombre)) $errores[] = 'El nombre es obligatorio';
    if ($monto === false || $monto <= 0) $errores[] = 'El monto debe ser mayor a cero';
    if ($maxParticipantes === false) $errores[] = 'El número de participantes debe ser entre 2 y 50';
    if (!in_array($frecuencia, ['Semanal', 'Quincenal', 'Mensual'])) $errores[] = 'Frecuencia no válida';
    if (strtotime($fechaInicio) < strtotime(date('Y-m-d'))) $errores[] = 'La fecha no puede ser en el pasado';
    if ($requiereGarantia && ($montoGarantia === false || $montoGarantia < 0)) {
        $errores[] = 'Monto de garantía no válido';
    }

    if (empty($errores)) {
        $junta = new Junta($db);
        $junta->NombreJunta = $nombre;
        $junta->Descripcion = $descripcion;
        $junta->MontoAporte = $monto;
        $junta->FrecuenciaPago = $frecuencia;
        $junta->FechaInicio = $fechaInicio;
        $junta->MaximoParticipantes = $maxParticipantes;
        $junta->CreadaPor = $_SESSION['usuario_id'];
        $junta->RequiereGarantia = $requiereGarantia ? 1 : 0;
        $junta->MontoGarantia = $montoGarantia;
        $junta->PorcentajeComision = $comision ?? 0;
        $junta->PorcentajePenalidad = $penalidad ?? 0;
        $junta->DiasGraciaPenalidad = $diasGracia ?? 0;

        if ($junta->crear()) {
            // Registrar en bitácora
            $authHelper->registrarAccion($_SESSION['usuario_id'], 'Creación de junta', "Junta ID: {$junta->JuntaID}");
            
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
                <div class="card-header bg-primary text-white">
                    <h3><i class="fas fa-users me-2"></i>Crear Nueva Junta</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($errores)): ?>
                        <div class="alert alert-danger">
                            <h5 class="alert-heading">Errores encontrados:</h5>
                            <ul class="mb-0">
                                <?php foreach ($errores as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" id="form-junta">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
                        <!-- Sección Información Básica -->
                        <fieldset class="mb-4">
                            <legend class="fw-bold text-primary">Información Básica</legend>
                            
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre de la Junta *</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required
                                       maxlength="100" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
                                <div class="form-text">Ejemplo: "Junta Familiar 2023"</div>
                            </div>

                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="2"
                                          maxlength="255"><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
                                <div class="form-text">Opcional. Describe el propósito de esta junta.</div>
                            </div>
                        </fieldset>

                        <!-- Sección Configuración Financiera -->
                        <fieldset class="mb-4">
                            <legend class="fw-bold text-primary">Configuración Financiera</legend>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="monto" class="form-label">Monto de Aporte *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">S/</span>
                                        <input type="number" class="form-control" id="monto" name="monto"
                                               step="0.01" min="1" required 
                                               value="<?= htmlspecialchars($_POST['monto'] ?? '') ?>">
                                    </div>
                                    <div class="form-text">Monto que cada participante aportará periódicamente.</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="max_participantes" class="form-label">N° Máximo de Participantes *</label>
                                    <input type="number" class="form-control" id="max_participantes"
                                           name="max_participantes" min="2" max="50" required
                                           value="<?= htmlspecialchars($_POST['max_participantes'] ?? '5') ?>">
                                    <div class="form-text">Mínimo 2, máximo 50 participantes.</div>
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
                        </fieldset>

                        <!-- Sección Garantías y Penalidades -->
                        <fieldset class="mb-4">
                            <legend class="fw-bold text-primary">Garantías y Penalidades</legend>
                            
                            <div class="mb-3 form-check form-switch">
                                <input type="checkbox" class="form-check-input" id="requiere_garantia"
                                       name="requiere_garantia" <?= isset($_POST['requiere_garantia']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="requiere_garantia">Requiere Garantía</label>
                                <div class="form-text">Activa esta opción si los participantes deben dar una garantía inicial.</div>
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
                                    <div class="form-text">Monto que cada participante debe depositar como garantía.</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="comision" class="form-label">Comisión (%)</label>
                                    <input type="number" class="form-control" id="comision" name="comision"
                                           step="0.1" min="0" max="100"
                                           value="<?= htmlspecialchars($_POST['comision'] ?? '0') ?>">
                                    <div class="form-text">Porcentaje que recibe el organizador.</div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="penalidad" class="form-label">Penalidad (%)</label>
                                    <input type="number" class="form-control" id="penalidad" name="penalidad"
                                           step="0.1" min="0" max="100"
                                           value="<?= htmlspecialchars($_POST['penalidad'] ?? '0') ?>">
                                    <div class="form-text">Por retraso en pagos.</div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="dias_gracia" class="form-label">Días de Gracia</label>
                                    <input type="number" class="form-control" id="dias_gracia" name="dias_gracia"
                                           min="0" value="<?= htmlspecialchars($_POST['dias_gracia'] ?? '0') ?>">
                                    <div class="form-text">Días antes de aplicar penalidad.</div>
                                </div>
                            </div>
                        </fieldset>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="index_junta.php" class="btn btn-outline-secondary me-md-2">
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
document.addEventListener('DOMContentLoaded', function() {
    // Toggle campos de garantía
    document.getElementById('requiere_garantia').addEventListener('change', function() {
        const garantiaFields = document.getElementById('garantia_fields');
        garantiaFields.style.display = this.checked ? 'block' : 'none';
        if (this.checked) {
            document.getElementById('monto_garantia').required = true;
        } else {
            document.getElementById('monto_garantia').required = false;
        }
    });

    // Validación de fecha mínima
    const fechaInput = document.getElementById('fecha_inicio');
    fechaInput.min = new Date().toISOString().split('T')[0];
});
</script>

<?php require_once '../includes/footer.php'; ?>