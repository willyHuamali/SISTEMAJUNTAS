<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../clases/Junta.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../clases/AuthHelper.php';
require_once __DIR__ . '/../clases/ParticipanteJunta.php';

verificarAutenticacion();
verificarInactividad();

// Inicializar Database y AuthHelper
$database = new Database();
$db = $database->getConnection();
$authHelper = new \Clases\AuthHelper($db);

// Verificar permisos
if (!$authHelper->tienePermiso('juntas.view', $_SESSION['rol_id'])) {
    $_SESSION['error'] = 'No tienes permiso para acceder a esta sección';
    header('Location: ../index.php');
    exit;
}

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'ID de junta no válido';
    header('Location: index_junta.php');
    exit;
}

$junta = new Junta($db);
$participanteModel = new ParticipanteJunta($db);
$juntaDetalle = $junta->obtenerJuntaPorID($_GET['id']);

// Verificar si la junta existe
if (!$juntaDetalle) {
    $_SESSION['error'] = 'La junta solicitada no existe';
    header('Location: index_junta.php');
    exit;
}

// Verificar permisos para ver esta junta específica
// Verificar permisos para ver esta junta específica
if (!$authHelper->tienePermiso('juntas.manage_all', $_SESSION['rol_id'])) {
    // Si no es administrador, verificar si es participante o creador
    $esParticipante = $junta->esParticipante($_GET['id'], $_SESSION['usuario_id']);
    $esCreador = (isset($juntaDetalle['CreadorID']) && ($juntaDetalle['CreadorID'] == $_SESSION['usuario_id']));
    
    if (!$esParticipante && !$esCreador) {
        $_SESSION['error'] = 'No tienes permiso para ver esta junta';
        header('Location: index_junta.php');
        exit;
    }
    
    // Verificar si el participante tiene permiso para ver detalles
    if ($esParticipante && !$authHelper->tienePermiso('juntas.view_own_participation', $_SESSION['rol_id'])) {
        $_SESSION['error'] = 'No tienes permiso para ver los detalles de esta junta';
        header('Location: index_junta.php');
        exit;
    }
}

// Obtener participantes de la junta
$participantes = $junta->obtenerParticipantes($_GET['id']);

// Obtener información de números asignados/libres
$infoNumeros = $participanteModel->obtenerInfoNumerosJunta($_GET['id']);
$numerosAsignados = $infoNumeros['asignados'];
$numerosLibres = $infoNumeros['libres'];
$maxParticipantes = $infoNumeros['maximo'];

// Obtener historial de pagos si la junta está en progreso o completada
$historialPagos = [];
if ($juntaDetalle['Estado'] == 'En progreso' || $juntaDetalle['Estado'] == 'Completada') {
    $historialPagos = $junta->obtenerHistorialPagos($_GET['id']);
}

// Mensajes
$mensaje_exito = $_SESSION['mensaje_exito'] ?? '';
unset($_SESSION['mensaje_exito']);

$mensaje_error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-users me-2"></i>Detalles de Junta</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="index_junta.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if ($mensaje_exito): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $mensaje_exito ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($mensaje_error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $mensaje_error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Detalles de la junta -->
    <div class="card mb-4 border-<?= 
        $juntaDetalle['Estado'] == 'Activa' ? 'success' : 
        ($juntaDetalle['Estado'] == 'En progreso' ? 'primary' : 
        ($juntaDetalle['Estado'] == 'Completada' ? 'secondary' : 'danger'))
    ?>">
        <div class="card-header bg-<?= 
            $juntaDetalle['Estado'] == 'Activa' ? 'success' : 
            ($juntaDetalle['Estado'] == 'En progreso' ? 'primary' : 
            ($juntaDetalle['Estado'] == 'Completada' ? 'secondary' : 'danger'))
        ?> text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0"><?= htmlspecialchars($juntaDetalle['NombreJunta']) ?></h3>
                <span class="badge bg-light text-dark fs-6"><?= htmlspecialchars($juntaDetalle['Estado']) ?></span>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <h5 class="text-muted">Información General</h5>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Código:</span>
                            <strong><?= htmlspecialchars($juntaDetalle['CodigoJunta']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Monto de aporte:</span>
                            <strong>S/ <?= number_format($juntaDetalle['MontoAporte'], 2) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Frecuencia:</span>
                            <strong><?= htmlspecialchars($juntaDetalle['FrecuenciaPago']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Participantes:</span>
                            <strong><?= count($participantes) ?> / <?= htmlspecialchars($juntaDetalle['MaximoParticipantes']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Fecha de inicio:</span>
                            <strong><?= date('d/m/Y', strtotime($juntaDetalle['FechaInicio'])) ?></strong>
                        </div>
                        <?php if ($juntaDetalle['Estado'] == 'Completada'): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Fecha de finalización:</span>
                                <strong><?= date('d/m/Y', strtotime($juntaDetalle['FechaFin'])) ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <h5 class="text-muted">Descripción</h5>
                        <hr>
                        <p><?= nl2br(htmlspecialchars($juntaDetalle['Descripcion'] ?? 'Sin descripción')) ?></p>
                    </div>
                    <div class="mb-3">
                        <h5 class="text-muted">Reglas</h5>
                        <hr>
    
                        <!-- Add new fields -->
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Requiere garantía:</span>
                                    <strong><?= isset($juntaDetalle['RequiereGarantia']) ? ($juntaDetalle['RequiereGarantia'] ? 'Sí' : 'No') : 'No especificado' ?></strong>
                                </div>
                                <?php if (isset($juntaDetalle['RequiereGarantia']) && $juntaDetalle['RequiereGarantia']): ?>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Monto de garantía:</span>
                                        <strong>S/ <?= isset($juntaDetalle['MontoGarantia']) ? number_format($juntaDetalle['MontoGarantia'], 2) : '0.00' ?></strong>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <?php if (isset($juntaDetalle['RequiereGarantia']) && $juntaDetalle['RequiereGarantia']): ?>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Penalidad %:</span>
                                        <strong><?= isset($juntaDetalle['PenalidadPorcentaje']) ? $juntaDetalle['PenalidadPorcentaje'] : '0' ?>%</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Días de gracia:</span>
                                        <strong><?= isset($juntaDetalle['DiasGracia']) ? $juntaDetalle['DiasGracia'] : '0' ?></strong>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-transparent">
            <div class="d-flex justify-content-between">
                <?php if ($authHelper->tienePermiso('juntas.edit', $_SESSION['rol_id']) && $juntaDetalle['Estado'] != 'Completada' && $juntaDetalle['Estado'] != 'Cancelada'): ?>
                    <a href="editar_junta.php?id=<?= $juntaDetalle['JuntaID'] ?>" class="btn btn-outline-primary">
                        <i class="fas fa-edit me-1"></i> Editar Junta
                    </a>
                <?php endif; ?>
                
                <?php if ($authHelper->tienePermiso('juntas.manage_all', $_SESSION['rol_id']) && $juntaDetalle['Estado'] == 'Activa'): ?>
                    <form action="procesar_estado.php" method="post" class="d-inline">
                        <input type="hidden" name="id" value="<?= $juntaDetalle['JuntaID'] ?>">
                        <input type="hidden" name="accion" value="cancelar">
                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('¿Estás seguro de cancelar esta junta?')">
                            <i class="fas fa-times me-1"></i> Cancelar Junta
                        </button>
                    </form>
                <?php endif; ?>
                
                <?php if ($juntaDetalle['Estado'] == 'Activa' && $authHelper->tienePermiso('juntas.join', $_SESSION['rol_id']) && !$junta->esParticipante($juntaDetalle['JuntaID'], $_SESSION['usuario_id'])): ?>
                    <form action="procesar_junta.php" method="post" class="d-inline">
                        <input type="hidden" name="id" value="<?= $juntaDetalle['JuntaID'] ?>">
                        <input type="hidden" name="accion" value="unirse">
                        <button type="submit" class="btn btn-success" onclick="return confirm('¿Deseas unirte a esta junta?')">
                            <i class="fas fa-user-plus me-1"></i> Unirse a la Junta
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Números de orden de recepción -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fas fa-list-ol me-2"></i>Números de Orden de Recepción</h4>
                <?php if ($authHelper->tienePermiso('participantes.add', $_SESSION['rol_id']) && $juntaDetalle['Estado'] == 'Activa'): ?>
                    <a href="agregar_par.php?junta_id=<?= $juntaDetalle['JuntaID'] ?>" class="btn btn-primary">
                        <i class="fas fa-user-plus me-1"></i> Agregar Participante
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-header bg-info text-white">
                            Números Asignados
                        </div>
                        <div class="card-body">
                            <?php if (!empty($numerosAsignados)): ?>
                                <?php sort($numerosAsignados); ?>
                                <?php echo implode(', ', $numerosAsignados); ?>
                            <?php else: ?>
                                No hay números asignados aún
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-header bg-success text-white">
                            Números Libres
                        </div>
                        <div class="card-body">
                            <?php if (!empty($numerosLibres)): ?>
                                <?php sort($numerosLibres); ?>
                                <?php echo implode(', ', $numerosLibres); ?>
                            <?php else: ?>
                                <?php if ($maxParticipantes > 0): ?>
                                    No hay números libres (todos asignados)
                                <?php else: ?>
                                    Esta junta no tiene límite de participantes
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($maxParticipantes > 0): ?>
                <small class="text-muted">Total participantes: <?php echo count($numerosAsignados); ?> de <?php echo $maxParticipantes; ?></small>
            <?php endif; ?>
        </div>
    </div>

    <!-- Participantes -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h4 class="mb-0"><i class="fas fa-users me-2"></i>Participantes</h4>
        </div>
        <div class="card-body">
            <?php if (!empty($participantes)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Teléfono</th>
                                <th>Posición</th>
                                <th>Banco</th>
                                <th>Número de Cuenta</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // First, display the creator if exists
                            if (isset($juntaDetalle['CreadorID']) && $juntaDetalle['CreadorID']):
                                $creatorFound = false;
                                foreach ($participantes as $index => $participante) {
                                    if ($participante['UsuarioID'] == $juntaDetalle['CreadorID']) {
                                        $creatorFound = true;
                                        ?>
                                        <tr class="table-primary">
                                            <td>1</td>
                                            <td><?= htmlspecialchars($participante['NombreCompleto']) ?> <span class="badge bg-info">Creador</span></td>
                                            <td><?= htmlspecialchars($participante['CorreoElectronico']) ?></td>
                                            <td><?= htmlspecialchars($participante['Telefono'] ?? 'N/A') ?></td>
                                            <td><?= $participante['Posicion'] ?? 'N/A' ?></td>
                                            <td><?= htmlspecialchars($participante['Banco'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($participante['NumeroCuenta'] ?? 'N/A') ?></td>
                                            <td>
                                                <span class="badge bg-<?= $participante['EstadoParticipacion'] == 'Activo' ? 'success' : 'secondary' ?>">
                                                    <?= $participante['EstadoParticipacion'] ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php
                                        break;
                                    }
                                }
                                
                                // Now display other participants
                                $counter = 1;
                                foreach ($participantes as $index => $participante):
                                    // Skip the creator as we already displayed it
                                    if (isset($juntaDetalle['CreadorID']) && $participante['UsuarioID'] == $juntaDetalle['CreadorID']) {
                                        continue;
                                    }
                                    $counter++;
                                    ?>
                                    <tr>
                                        <td><?= $counter ?></td>
                                        <td><?= htmlspecialchars($participante['NombreCompleto']) ?></td>
                                        <td><?= htmlspecialchars($participante['CorreoElectronico']) ?></td>
                                        <td><?= htmlspecialchars($participante['Telefono'] ?? 'N/A') ?></td>
                                        <td><?= $participante['Posicion'] ?? 'N/A' ?></td>
                                        <td><?= htmlspecialchars($participante['Banco'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($participante['NumeroCuenta'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $participante['EstadoParticipacion'] == 'Activo' ? 'success' : 'secondary' ?>">
                                                <?= $participante['EstadoParticipacion'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach;
                                
                                // If creator not found in participants list, add them at position 1
                                if (!$creatorFound && isset($juntaDetalle['CreadorNombre'])): ?>
                                    <tr class="table-primary">
                                        <td>1</td>
                                        <td><?= htmlspecialchars($juntaDetalle['CreadorNombre']) ?> <span class="badge bg-info">Creador</span></td>
                                        <td><?= htmlspecialchars($juntaDetalle['CreadorCorreo'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($juntaDetalle['CreadorTelefono'] ?? 'N/A') ?></td>
                                        <td>1</td>
                                        <td><?= htmlspecialchars($juntaDetalle['CreadorBanco'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($juntaDetalle['CreadorCuenta'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="badge bg-success">Activo</span>
                                        </td>
                                    </tr>
                                <?php endif;
                            else:
                                // If no creator info, display participants normally
                                foreach ($participantes as $index => $participante): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($participante['NombreCompleto']) ?></td>
                                        <td><?= htmlspecialchars($participante['CorreoElectronico']) ?></td>
                                        <td><?= htmlspecialchars($participante['Telefono'] ?? 'N/A') ?></td>
                                        <td><?= $participante['Posicion'] ?? 'N/A' ?></td>
                                        <td><?= htmlspecialchars($participante['Banco'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($participante['NumeroCuenta'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $participante['EstadoParticipacion'] == 'Activo' ? 'success' : 'secondary' ?>">
                                                <?= $participante['EstadoParticipacion'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach;
                            endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info mb-0">
                    No hay participantes registrados en esta junta.
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Historial de pagos (si aplica) -->
    <?php if (!empty($historialPagos)): ?>
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h4 class="mb-0"><i class="fas fa-history me-2"></i>Historial de Pagos</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Participante</th>
                                <th>Monto</th>
                                <th>Estado</th>
                                <th>Comprobante</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historialPagos as $index => $pago): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= date('d/m/Y', strtotime($pago['FechaPago'])) ?></td>
                                    <td><?= htmlspecialchars($pago['NombreParticipante']) ?></td>
                                    <td>S/ <?= number_format($pago['Monto'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $pago['EstadoPago'] == 'Completado' ? 'success' : ($pago['EstadoPago'] == 'Pendiente' ? 'warning' : 'danger') ?>">
                                            <?= $pago['EstadoPago'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($pago['Comprobante'])): ?>
                                            <a href="../uploads/comprobantes/<?= $pago['Comprobante'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-file-invoice"></i> Ver
                                            </a>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>