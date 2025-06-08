<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../clases/Junta.php';
require_once __DIR__ . '/../clases/ParticipanteJunta.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../clases/AuthHelper.php';

verificarAutenticacion();
verificarInactividad();

// Inicializar Database y AuthHelper
$database = new Database();
$db = $database->getConnection();
$authHelper = new \Clases\AuthHelper($db);

// Verificar permisos
if (!$authHelper->tienePermiso('participantes.manage', $_SESSION['rol_id'])) {
    $_SESSION['error'] = 'No tienes permiso para acceder a esta sección';
    header('Location: index_junta.php');
    exit;
}

$participanteId = $_GET['id'] ?? null;
$juntaId = $_GET['junta_id'] ?? null;

if (!$participanteId || !$juntaId) {
    $_SESSION['error'] = 'Parámetros inválidos';
    header('Location: index_junta.php');
    exit;
}

$participanteJunta = new ParticipanteJunta($db);
$participante = $participanteJunta->obtenerParticipantePorId($participanteId);

if (!$participante) {
    $_SESSION['error'] = 'Participante no encontrado';
    header('Location: participantes.php?junta_id=' . $juntaId);
    exit;
}

$junta = new Junta($db);
$juntaInfo = $junta->obtenerJuntaPorId($juntaId);

if (!$juntaInfo) {
    $_SESSION['error'] = 'Junta no encontrada';
    header('Location: index_junta.php');
    exit;
}

// Verificar que la junta esté activa
if ($juntaInfo['Estado'] != 'Activa') {
    $_SESSION['error'] = 'No se pueden retirar participantes de una junta que no está activa';
    header('Location: participantes.php?junta_id=' . $juntaId);
    exit;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motivoRetiro = $_POST['motivo_retiro'] ?? '';
    
    // Validaciones
    if (empty($motivoRetiro)) {
        $_SESSION['error'] = 'Debe especificar un motivo para el retiro';
    } else {
        // Retirar participante
        $resultado = $participanteJunta->retirarParticipante($participanteId, $motivoRetiro, $_SESSION['usuario_id']);
        
        if ($resultado) {
            $_SESSION['mensaje_exito'] = 'Participante retirado correctamente';
            header('Location: participantes.php?junta_id=' . $juntaId);
            exit;
        } else {
            $_SESSION['error'] = 'Error al retirar el participante';
        }
    }
}

// Mensajes
$mensaje_error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-user-minus me-2"></i>Retirar Participante</h2>
            <p class="text-muted">Junta: <?= htmlspecialchars($juntaInfo['NombreJunta']) ?> (<?= htmlspecialchars($juntaInfo['CodigoJunta']) ?>)</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="participantes.php?junta_id=<?= $juntaId ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>

    <?php if ($mensaje_error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $mensaje_error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Advertencia:</strong> Esta acción retirará al participante de la junta. 
                Asegúrese de que esta es la acción que desea realizar.
            </div>
            
            <div class="mb-4">
                <h5>Información del Participante</h5>
                <p><strong>ID:</strong> <?= $participante['ParticipanteID'] ?></p>
                <p><strong>Orden de Recepción:</strong> <?= $participante['OrdenRecepcion'] ?></p>
                <p><strong>Fecha de Registro:</strong> <?= date('d/m/Y', strtotime($participante['FechaRegistro'])) ?></p>
            </div>
            
            <form method="post">
                <div class="mb-3">
                    <label for="motivo_retiro" class="form-label">Motivo del Retiro</label>
                    <textarea class="form-control" id="motivo_retiro" name="motivo_retiro" rows="3" required></textarea>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="participantes.php?junta_id=<?= $juntaId ?>" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-user-minus me-1"></i> Confirmar Retiro
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>