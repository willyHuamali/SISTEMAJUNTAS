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

// Verificar permisos
if (!$authHelper->tienePermiso('participantesjuntas.manage', $_SESSION['rol_id'])) {
    $_SESSION['mensaje_error'] = "No tienes permisos para agregar participantes.";
    header('Location: ' . url('participantes/participantes.php'));
    exit;
}

// Obtener lista de juntas activas
$participanteModel = new ParticipanteJunta($db);
$juntas = $participanteModel->obtenerJuntasActivas();

// Obtener lista de usuarios disponibles (deberías implementar este método en tu modelo)
// $usuarios = $participanteModel->obtenerUsuariosDisponibles();

// Procesar formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $juntaId = $_POST['junta_id'] ?? '';
    $usuarioId = $_POST['usuario_id'] ?? '';
    $orden = $_POST['orden'] ?? '';
    
    // Validar datos
    // Validar datos
    if (empty($juntaId)) {
        $_SESSION['mensaje_error'] = "Debes seleccionar una junta.";
    } elseif (empty($usuarioId)) {
        $_SESSION['mensaje_error'] = "Debes seleccionar un usuario.";
    } elseif (empty($orden)) {
        $_SESSION['mensaje_error'] = "Debes especificar un orden de recepción.";
    } else {
        // Intentar agregar participante
        if ($participanteModel->agregarParticipante($juntaId, $usuarioId, $orden)) {
            $_SESSION['mensaje_exito'] = "Participante agregado correctamente.";
            header('Location: ' . url('participantes/participantes.php'));
            exit;
        } else {
            $_SESSION['mensaje_error'] = "Error al agregar participante. Verifica que el usuario no esté ya en la junta.";
        }
    }
}

// Título de la página
$titulo = 'Agregar Participante';

// Incluir header
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
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
            <i class="fas fa-user-plus me-1"></i>
            <?php echo $titulo; ?>
        </div>
        <div class="card-body">
            <form method="post" action="<?php echo url('participantes/agregar_par.php'); ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="junta_id" class="form-label">Junta</label>
                        <select class="form-select" id="junta_id" name="junta_id" required>
                            <option value="">Seleccionar Junta</option>
                            <?php foreach ($juntas as $junta): ?>
                                <option value="<?php echo htmlspecialchars($junta['JuntaID']); ?>" 
                                    <?php echo isset($_POST['junta_id']) && $_POST['junta_id'] == $junta['JuntaID'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($junta['NombreJunta']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="usuario_id" class="form-label">Usuario</label>
                        <select class="form-select" id="usuario_id" name="usuario_id" required>
                            <option value="">Seleccionar Usuario</option>
                            <!-- Aquí deberías cargar los usuarios disponibles -->
                            <?php //foreach ($usuarios as $usuario): ?>
                                <option value="<?php //echo htmlspecialchars($usuario['UsuarioID']); ?>" 
                                    <?php //echo isset($_POST['usuario_id']) && $_POST['usuario_id'] == $usuario['UsuarioID'] ? 'selected' : ''; ?>>
                                    <?php //echo htmlspecialchars($usuario['Nombre'] . ' ' . $usuario['Apellido'] . ' (' . $usuario['DNI'] . ')'; ?>
                                </option>
                            <?php //endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="orden" class="form-label">Orden de Recepción</label>
                        <input type="number" class="form-control" id="orden" name="orden" 
                               value="<?php echo htmlspecialchars($_POST['orden'] ?? ''); ?>" 
                               min="1" required>
                        <small class="text-muted">Número que indica el orden en que recibirá el fondo.</small>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Guardar
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
include __DIR__ . '/../includes/footer.php';
?>