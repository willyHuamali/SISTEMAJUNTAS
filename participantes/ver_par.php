<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../clases/AuthHelper.php';
require_once __DIR__ . '/../clases/ParticipanteJunta.php';

// Verificar autenticación e inactividad
verificarAutenticacion();
verificarInactividad();

$database = new Database();
$db = $database->getConnection();
$authHelper = new \Clases\AuthHelper($db);

$usuarioId = $_SESSION['usuario_id'] ?? null;
$rolId = $_SESSION['rol_id'] ?? null;

// Verificar permiso para ver participantes
if (!$authHelper->tienePermiso('participants.view', $rolId)) {
    $_SESSION['mensaje_error'] = "No tienes permisos para ver los detalles del participante.";
    header('Location: ' . url('dashboard.php'));
    exit;
}

// Validar ID
$participanteId = $_GET['id'] ?? null;
if (!$participanteId || !is_numeric($participanteId)) {
    $_SESSION['mensaje_error'] = "ID de participante no válido.";
    header('Location: ' . url('participantes/participantes.php'));
    exit;
}

// Obtener participante
$participanteModel = new ParticipanteJunta($db);
$participante = $participanteModel->obtenerParticipantePorId($participanteId);

if (!$participante) {
    $_SESSION['mensaje_error'] = "Participante no encontrado.";
    header('Location: ' . url('participantes/participantes.php'));
    exit;
}

$titulo = 'Detalle del Participante';

// Incluir encabezados
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $titulo; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo url('index.php'); ?>">Inicio</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url('participantes/participantes.php'); ?>">Participantes</a></li>
        <li class="breadcrumb-item active">Detalle</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <i class="fas fa-user"></i> Información del Participante
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Nombre</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($participante['Nombre'] . ' ' . $participante['Apellido']); ?></dd>

                <dt class="col-sm-3">DNI</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($participante['DNI']); ?></dd>

                <dt class="col-sm-3">Junta</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($participante['NombreJunta']); ?></dd>

                <dt class="col-sm-3">Orden de Recepción</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($participante['OrdenRecepcion']); ?></dd>

                <dt class="col-sm-3">Estado</dt>
                <dd class="col-sm-9">
                    <span class="badge bg-<?php echo $participante['Activo'] ? 'success' : 'secondary'; ?>">
                        <?php echo $participante['Activo'] ? 'Activo' : 'Inactivo'; ?>
                    </span>
                </dd>

                <dt class="col-sm-3">Cuenta Asociada</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($participante['NumeroCuenta'] ?? 'N/A'); ?></dd>

                <dt class="col-sm-3">Garantía</dt>
                <dd class="col-sm-9">
                    <?php
                    if (!empty($participante['GarantiaID'])) {
                        echo '<a href="' . url('participantes/garantias.php?id=' . $participante['GarantiaID']) . '">Ver garantía</a>';
                    } else {
                        echo 'No registrada';
                    }
                    ?>
                </dd>

                <?php if (!$participante['Activo']): ?>
                    <dt class="col-sm-3">Fecha de Retiro</dt>
                    <dd class="col-sm-9"><?php echo $participante['FechaRetiro'] ? htmlspecialchars($participante['FechaRetiro']) : 'N/A'; ?></dd>

                    <dt class="col-sm-3">Motivo de Retiro</dt>
                    <dd class="col-sm-9"><?php echo $participante['MotivoRetiro'] ? htmlspecialchars($participante['MotivoRetiro']) : 'N/A'; ?></dd>
                <?php endif; ?>
            </dl>

            <a href="<?php echo url('participantes/participantes.php'); ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver al listado
            </a>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/../includes/footer.php';
?>
