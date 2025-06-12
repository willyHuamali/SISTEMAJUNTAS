<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../clases/AuthHelper.php';
require_once __DIR__ . '/../clases/ParticipanteJunta.php';
require_once __DIR__ . '/../clases/Usuario.php';

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

// Inicializar modelos
$participanteModel = new ParticipanteJunta($db);
$usuarioModel = new Usuario($db);

// Obtener lista de juntas activas
$juntas = $participanteModel->obtenerJuntasActivas();

// Obtener lista de usuarios disponibles
$usuariosDisponibles = [];
$juntaSeleccionada = null;
$numerosAsignados = [];
$numerosLibres = [];
$maxParticipantes = 0;

// Procesar selección de junta (sin guardar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['junta_id']) && !isset($_POST['guardar'])) {
    $juntaSeleccionada = $_POST['junta_id'];
    $usuariosDisponibles = $usuarioModel->obtenerUsuariosNoEnJuntaConCuenta($juntaSeleccionada);
    
    // Obtener información de la junta seleccionada
    $juntaInfo = $participanteModel->obtenerJuntaPorId($juntaSeleccionada);
    $maxParticipantes = $juntaInfo['MaximoParticipantes'];
    
    // Obtener números ya asignados
    $participantes = $participanteModel->obtenerParticipantesPorJunta($juntaSeleccionada);
    $numerosAsignados = array_column($participantes, 'OrdenRecepcion');
    
    // Calcular números libres
    if ($maxParticipantes > 0) {
        $todosNumeros = range(1, $maxParticipantes);
        $numerosLibres = array_diff($todosNumeros, $numerosAsignados);
    } else {
        // Si no hay máximo definido, mostramos solo los asignados
        $numerosLibres = [];
    }
}

// Procesar formulario si se envió para guardar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $juntaId = $_POST['junta_id'];
    $usuarioId = $_POST['usuario_id'];
    $orden = $_POST['orden'];
    
    try {
        // Validar datos básicos
        $errores = [];
        if (empty($juntaId)) {
            $errores[] = "Debes seleccionar una junta.";
        }
        if (empty($usuarioId)) {
            $errores[] = "Debes seleccionar un usuario.";
        }
        if (empty($orden) || !is_numeric($orden) || $orden < 1) {
            $errores[] = "Debes especificar un orden de recepción válido (número mayor a 0).";
        }
        
        if (empty($errores)) {
            if ($participanteModel->agregarParticipante($juntaId, $usuarioId, $orden)) {
                $_SESSION['mensaje_exito'] = "Participante agregado correctamente.";
                header('Location: ' . url('participantes/participantes.php'));
                exit;
            }
        } else {
            $_SESSION['mensaje_error'] = implode("<br>", $errores);
        }
    } catch (Exception $e) {
        $_SESSION['mensaje_error'] = $e->getMessage();
    }
    
    // Mantener los datos seleccionados después del error
    $juntaSeleccionada = $juntaId;
    $usuariosDisponibles = $usuarioModel->obtenerUsuariosNoEnJuntaConCuenta($juntaSeleccionada);
    
    // Obtener información actualizada después del posible error
    $juntaInfo = $participanteModel->obtenerJuntaPorId($juntaSeleccionada);
    $maxParticipantes = $juntaInfo['MaximoParticipantes'];
    $participantes = $participanteModel->obtenerParticipantesPorJunta($juntaSeleccionada);
    $numerosAsignados = array_column($participantes, 'OrdenRecepcion');
    
    if ($maxParticipantes > 0) {
        $todosNumeros = range(1, $maxParticipantes);
        $numerosLibres = array_diff($todosNumeros, $numerosAsignados);
    } else {
        $numerosLibres = [];
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

    <!-- Mostrar mensajes de error/éxito -->
    <?php if (isset($_SESSION['mensaje_error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['mensaje_error']; unset($_SESSION['mensaje_error']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['mensaje_exito'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['mensaje_exito']; unset($_SESSION['mensaje_exito']); ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <form method="post" action="<?php echo url('participantes/agregar_par.php'); ?>">
                <input type="hidden" name="guardar" value="1">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="junta_id" class="form-label">Junta</label>
                        <select class="form-select" id="junta_id" name="junta_id" required 
                                onchange="this.form.submit()">
                            <option value="">Seleccionar Junta</option>
                            <?php foreach ($juntas as $junta): ?>
                                <option value="<?php echo htmlspecialchars($junta['JuntaID']); ?>" 
                                    <?php echo isset($juntaSeleccionada) && $juntaSeleccionada == $junta['JuntaID'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($junta['NombreJunta']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="usuario_id" class="form-label">Usuario</label>
                        <select class="form-select" id="usuario_id" name="usuario_id" required>
                            <option value="">Seleccionar Usuario</option>
                            <?php if (!empty($usuariosDisponibles)): ?>
                                <?php foreach ($usuariosDisponibles as $usuario): ?>
                                    <option value="<?php echo htmlspecialchars($usuario['UsuarioID']); ?>" 
                                        <?php echo isset($_POST['usuario_id']) && $_POST['usuario_id'] == $usuario['UsuarioID'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($usuario['Nombre'] . ' ' . $usuario['Apellido'] . ' (' . $usuario['DNI'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>No hay usuarios disponibles para esta junta</option>
                            <?php endif; ?>
                        </select>
                        <?php if (empty($usuariosDisponibles) && isset($juntaSeleccionada)): ?>
                            <small class="text-danger">Todos los usuarios con cuenta bancaria principal ya están en esta junta o no hay usuarios activos con cuenta principal</small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="orden" class="form-label">Orden de Recepción</label>
                        <input type="number" class="form-control" id="orden" name="orden" 
                               value="<?php echo htmlspecialchars($_POST['orden'] ?? ''); ?>" 
                               min="1" required>
                        <small class="text-muted">Número que indica el orden en que recibirá el fondo.</small>
                        
                        <!-- Mostrar números asignados y libres -->
                        <?php if (isset($juntaSeleccionada)): ?>
                            <div class="mt-3">
                                <h6>Información de Números:</h6>
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
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary" name="guardar" value="1">
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
include __DIR__ . '/../includes/footer.php';
?>