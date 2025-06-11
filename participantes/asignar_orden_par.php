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

// Inicializar Database y AuthHelper
$database = new Database();
$db = $database->getConnection();
$authHelper = new \Clases\AuthHelper($db);



// Verificar permisos específicos para asignar orden
if (!$authHelper->tienePermiso('participantesjuntas.assign_order', $_SESSION['rol_id'])) {
    $_SESSION['mensaje_error'] = "No tienes permisos para asignar orden de junta.";
    header('Location: ' . url('participantes/participantes.php'));
    exit;
}

// Obtener ID del participante
$participanteId = $_GET['id'] ?? 0;

if (empty($participanteId)) {
    $_SESSION['mensaje_error'] = "No se especificó el participante.";
    header('Location: ' . url('participantes/participantes.php'));
    exit;
}

// Crear instancia de ParticipanteJunta
$participanteModel = new ParticipanteJunta($db);
$participante = $participanteModel->obtenerParticipantePorId($participanteId);

if (!$participante) {
    $_SESSION['mensaje_error'] = "Participante no encontrado.";
    header('Location: ' . url('participantes/participantes.php'));
    exit;
}

// Obtener datos de la junta
$juntaModel = new Junta($db);
$junta = $juntaModel->obtenerPorId($participante['JuntaID']);

// Obtener todos los participantes de la junta para ver los órdenes ocupados
$participantesJunta = $participanteModel->obtenerParticipantesPorJunta($participante['JuntaID']);

// Procesar formulario si es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevoOrden = $_POST['orden_recepcion'] ?? 0;
    
    // Validar que el orden sea válido
    if ($nuevoOrden < 1 || $nuevoOrden > $junta['MaximoParticipantes']) {
        $_SESSION['mensaje_error'] = "El orden de recepción debe estar entre 1 y " . $junta['MaximoParticipantes'];
    } else {
        // Verificar si el orden ya está ocupado por otro participante
        $ordenOcupado = false;
        foreach ($participantesJunta as $otroParticipante) {
            if ($otroParticipante['ParticipanteID'] != $participanteId && 
                $otroParticipante['OrdenRecepcion'] == $nuevoOrden) {
                $ordenOcupado = true;
                break;
            }
        }
        
        if ($ordenOcupado) {
            $_SESSION['mensaje_error'] = "El orden de recepción $nuevoOrden ya está ocupado por otro participante.";
        } else {
            // Actualizar el orden
            if ($participanteModel->actualizarOrdenRecepcion($participanteId, $nuevoOrden)) {
                $_SESSION['mensaje_exito'] = "Orden de recepción actualizado correctamente.";
                header('Location: ' . url('participantes/ver_par.php?id=' . $participanteId));
                exit;
            } else {
                $_SESSION['mensaje_error'] = "Error al actualizar el orden de recepción.";
            }
        }
    }
}

// Incluir header
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="container mt-4">
    <h2>Asignar Orden de Recepción</h2>
    <p class="text-muted">Junta: <?= htmlspecialchars($junta['NombreJunta']) ?></p>
    <p class="text-muted">Participante: <?= htmlspecialchars($participante['Nombre'] . ' ' . $participante['Apellido']) ?></p>
    
    <div class="card shadow">
        <div class="card-body">
            <form method="post">
                <div class="mb-4">
                    <h5>Órdenes Disponibles</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <?php for ($i = 1; $i <= $junta['MaximoParticipantes']; $i++): ?>
                            <?php 
                            $ocupado = false;
                            $ocupadoPor = '';
                            foreach ($participantesJunta as $otroParticipante) {
                                if ($otroParticipante['OrdenRecepcion'] == $i && $otroParticipante['ParticipanteID'] != $participanteId) {
                                    $ocupado = true;
                                    $ocupadoPor = $otroParticipante['Nombre'] . ' ' . $otroParticipante['Apellido'];
                                    break;
                                }
                            }
                            ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="orden_recepcion" 
                                       id="orden<?= $i ?>" value="<?= $i ?>" 
                                       <?= $participante['OrdenRecepcion'] == $i ? 'checked' : '' ?>
                                       <?= $ocupado ? 'disabled' : '' ?>>
                                <label class="form-check-label" for="orden<?= $i ?>">
                                    <?= $i ?>
                                    <?php if ($ocupado): ?>
                                        <small class="text-muted">(Ocupado por <?= htmlspecialchars($ocupadoPor) ?>)</small>
                                    <?php endif; ?>
                                </label>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="<?= url('participantes/ver_par.php?id=' . $participanteId) ?>" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Incluir footer
include __DIR__ . '/../includes/footer.php';
?>