<?php
// Asegúrate de que esta ruta sea correcta según tu estructura de directorios
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../clases/AuthHelper.php';
require_once __DIR__ . '/../clases/ParticipanteJunta.php';
require_once __DIR__ . '/../clases/Junta.php';

// Verificar autenticación e inactividad
verificarAutenticacion();
verificarInactividad();

// Inicializar Database y AuthHelper
$database = new Database();
$db = $database->getConnection();
$authHelper = new \Clases\AuthHelper($db);



// Verificar permisos y autenticación
if (!$authHelper->tienePermiso('participantesjuntas.assign_order', $_SESSION['rol_id'])) {
    $_SESSION['mensaje_error'] = "No tienes permisos para asignar órdenes.";
    header('Location: ' . url('participantes/participantes.php'));
    exit;
}

// Obtener parámetros
$participanteId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$juntaId = isset($_GET['junta_id']) ? (int)$_GET['junta_id'] : 0;

// Validar parámetros
if ($participanteId <= 0 || $juntaId <= 0) {
    $_SESSION['error'] = 'Parámetros inválidos para asignar orden.';
    header('Location: /sistemajuntas/participantes/participantes.php');
    exit;
}

// Obtener datos del participante
$participante = ParticipanteJunta::obtenerPorId($participanteId);
if (!$participante) {
    $_SESSION['error'] = 'Participante no encontrado.';
    header('Location: /sistemajuntas/participantes/participantes.php');
    exit;
}

// Obtener datos de la junta
$junta = Junta::obtenerPorId($juntaId);
if (!$junta) {
    $_SESSION['error'] = 'Junta no encontrada.';
    header('Location: /sistemajuntas/participantes/participantes.php');
    exit;
}

// Obtener participantes de la junta para ver números ocupados
$participantesJunta = ParticipanteJunta::obtenerPorJunta($juntaId);

// Determinar números ocupados y disponibles
$numerosOcupados = [];
$numerosDisponibles = [];

foreach ($participantesJunta as $part) {
    if ($part->id != $participanteId) { // Excluir al participante actual
        $numerosOcupados[] = $part->ordenRecepcion;
    }
}

// Determinar el máximo de participantes
$maxParticipantes = $junta->maximoParticipantes > 0 ? $junta->maximoParticipantes : count($participantesJunta) + 1;

// Generar lista de números disponibles
for ($i = 1; $i <= $maxParticipantes; $i++) {
    if (!in_array($i, $numerosOcupados)) {
        $numerosDisponibles[] = $i;
    }
}

// Procesar asignación de orden si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevoOrden = isset($_POST['orden_recepcion']) ? (int)$_POST['orden_recepcion'] : 0;
    
    // Validar que el número esté disponible
    if (!in_array($nuevoOrden, $numerosDisponibles)) {
        $_SESSION['error'] = 'El número seleccionado no está disponible.';
    } else {
        // Actualizar el orden
        if ($participante->actualizarOrdenRecepcion($nuevoOrden)) {
            $_SESSION['exito'] = 'Orden de recepción actualizado correctamente.';
            header('Location: /sistemajuntas/participantes/ver_par.php?id=' . $participanteId);
            exit;
        } else {
            $_SESSION['error'] = 'Error al actualizar el orden de recepción.';
        }
    }
}

// Incluir cabecera
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="container mt-4">
    <h2>Asignar Orden de Recepción</h2>
    <p class="lead">Junta: <?= htmlspecialchars($junta->nombreJunta) ?></p>
    <p>Participante: <?= htmlspecialchars($participante->usuario->nombreCompleto()) ?></p>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Números Ocupados</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($numerosOcupados)): ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($numerosOcupados as $numero): ?>
                                <span class="badge bg-danger p-2"><?= $numero ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No hay números ocupados.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Números Disponibles</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($numerosDisponibles)): ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($numerosDisponibles as $numero): ?>
                                <span class="badge bg-success p-2"><?= $numero ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No hay números disponibles. La junta está llena.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5>Asignar Nuevo Orden</h5>
        </div>
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label for="orden_recepcion" class="form-label">Seleccionar número de orden:</label>
                    <select class="form-select" id="orden_recepcion" name="orden_recepcion" required>
                        <option value="">-- Seleccione un número --</option>
                        <?php foreach ($numerosDisponibles as $numero): ?>
                            <option value="<?= $numero ?>" <?= ($participante->ordenRecepcion == $numero) ? 'selected' : '' ?>>
                                <?= $numero ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                <a href="/sistemajuntas/participantes/ver_par.php?id=<?= $participanteId ?>" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>

<?php
// Incluir pie de página
include __DIR__ . '/../includes/footer.php';
?>