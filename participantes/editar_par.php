<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../clases/AuthHelper.php';
require_once __DIR__ . '/../../clases/ParticipanteJunta.php';

// Verificar autenticación e inactividad
verificarAutenticacion();
verificarInactividad();

// Inicializar Database y AuthHelper
$database = new Database();
$db = $database->getConnection();
$authHelper = new \Clases\AuthHelper($db);

// Verificar permisos
if (!$authHelper->tienePermiso('participantesjuntas.edit', $_SESSION['rol_id'])) {
    $_SESSION['mensaje_error'] = "No tienes permisos para editar participantes.";
    header('Location: ' . url('participantes/participantes.php'));
    exit;
}

// Obtener ID del participante a editar
$participanteId = $_GET['id'] ?? 0;
if (empty($participanteId)) {
    $_SESSION['mensaje_error'] = "No se especificó el participante a editar.";
    header('Location: ' . url('participantes/participantes.php'));
    exit;
}

// Crear instancia de ParticipanteJunta
$participanteModel = new ParticipanteJunta($db);

// Obtener datos del participante
$participante = $participanteModel->obtenerParticipantePorId($participanteId);
if (!$participante) {
    $_SESSION['mensaje_error'] = "Participante no encontrado.";
    header('Location: ' . url('participantes/participantes.php'));
    exit;
}

// Obtener lista de juntas activas
$juntas = $participanteModel->obtenerJuntasActivas();

// Procesar formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $juntaId = $_POST['junta_id'] ?? '';
    $orden = $_POST['orden'] ?? '';
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validar datos
    if (empty($juntaId)) {
        $_SESSION['mensaje_error'] = "Debes seleccionar una junta.";
    } elseif (empty($orden)) {
        $_SESSION['mensaje_error'] = "Debes especificar un orden de recepción.";
    } else {
        // Actualizar participante
        $query = "UPDATE ParticipantesJuntas 
                  SET JuntaID = :juntaId, OrdenRecepcion = :orden, Activo = :activo
                  WHERE ParticipanteID = :participanteId";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':juntaId', $juntaId);
        $stmt->bindParam(':orden', $orden);
        $stmt->bindParam(':activo', $activo);
        $stmt->bindParam(':participanteId', $participanteId);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje_exito'] = "Participante actualizado correctamente.";
            header('Location: ' . url('participantes/participantes.php'));
            exit;
        } else {
            $_SESSION['mensaje_error'] = "Error al actualizar participante.";
        }
    }
}

// Título de la página
$titulo = 'Editar Participante';

// Incluir header
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $titulo; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo url('index.php'); ?>">Inicio</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url('participantes/participantes.php'); ?>">Participantes</a></li>
        <li class="breadcrumb-item active"><?php echo $titulo; ?></li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-user-edit me-1"></i>
            <?php echo $titulo; ?>
        </div>
        <div class="card-body">
            <form method="post" action="<?php echo url('participantes/editar_par.php?id=' . $participanteId); ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="junta_id" class="form-label">Junta</label>
                        <select class="form-select" id="junta_id" name="junta_id" required>
                            <option value="">Seleccionar Junta</option>
                            <?php foreach ($juntas as $junta): ?>
                                <option value="<?php echo htmlspecialchars($junta['JuntaID']); ?>" 
                                    <?php echo ($junta['JuntaID'] == $participante['JuntaID'] || (isset($_POST['junta_id']) && $_POST['junta_id'] == $junta['JuntaID'])) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($junta['NombreJunta']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Usuario</label>
                        <input type="text" class="form-control" 
                               value="<?php echo htmlspecialchars($participante['Nombre'] . ' ' . $participante['Apellido'] . ' (' . $participante['DNI'] . ')'); ?>" 
                               readonly>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="orden" class="form-label">Orden de Recepción</label>
                        <input type="number" class="form-control" id="orden" name="orden" 
                               value="<?php echo htmlspecialchars($_POST['orden'] ?? $participante['OrdenRecepcion']); ?>" 
                               min="1" required>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" id="activo" name="activo" 
                                   <?php echo (($_POST['activo'] ?? $participante['Activo']) ? 'checked' : ''); ?>>
                            <label class="form-check-label" for="activo">Activo</label>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Guardar Cambios
                    </button>
                    <a href="<?php echo url('participantes/participantes.php'); ?>" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Incluir footer
include __DIR__ . '/../../includes/footer.php';
?>