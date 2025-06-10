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


// Verificar permisos para editar participantes
if (!$authHelper->tienePermiso('participantesjuntas.edit', $_SESSION['rol_id'])) {
    $_SESSION['mensaje_error'] = "No tienes permisos para editar participantes.";
    header('Location: ' . url('participantes/participantes.php'));
    exit;
}

// Obtener ID del participante a editar
$participanteId = $_GET['id'] ?? 0;
if (empty($participanteId)) {
    $_SESSION['mensaje_error'] = "No se especificó el participante.";
    header('Location: ' . url('participantes/participantes.php'));
    exit;
}

// Crear instancia de ParticipanteJunta y obtener datos
$participanteModel = new ParticipanteJunta($db);
$participante = $participanteModel->obtenerParticipantePorId($participanteId);

if (!$participante) {
    $_SESSION['mensaje_error'] = "Participante no encontrado.";
    header('Location: ' . url('participantes/participantes.php'));
    exit;
}

// Obtener listas necesarias
$juntas = $participanteModel->obtenerJuntasDisponibles();
$usuarios = $participanteModel->obtenerUsuariosDisponibles();
$cuentas = $participanteModel->obtenerCuentasDisponibles();
$garantias = $participanteModel->obtenerGarantiasDisponibles();

// Procesar formulario si es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $juntaId = $_POST['junta_id'] ?? '';
    $usuarioId = $_POST['usuario_id'] ?? '';
    $cuentaId = $_POST['cuenta_id'] ?? '';
    $garantiaId = $_POST['garantia_id'] ?? '';
    $ordenRecepcion = $_POST['orden_recepcion'] ?? '';

    // Validar datos
    if (empty($juntaId) || empty($usuarioId) || empty($cuentaId) || empty($ordenRecepcion)) {
        $_SESSION['mensaje_error'] = "Todos los campos obligatorios deben ser completados.";
    } else {
        // Actualizar participante
        $datosActualizados = [
            'JuntaID' => $juntaId,
            'UsuarioID' => $usuarioId,
            'CuentaID' => $cuentaId,
            'GarantiaID' => !empty($garantiaId) ? $garantiaId : null,
            'OrdenRecepcion' => $ordenRecepcion
        ];

        if ($participanteModel->actualizarParticipante($participanteId, $datosActualizados)) {
            $_SESSION['mensaje_exito'] = "Participante actualizado correctamente.";
            header('Location: ' . url('participantes/participantes.php'));
            exit;
        } else {
            $_SESSION['mensaje_error'] = "Error al actualizar participante.";
        }
    }
}

// Incluir header
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h2>Editar Participante</h2>
    
    <?php mostrarMensajes(); ?>
    
    <form method="POST" action="<?= url('participantes/editar_par.php?id=' . $participanteId) ?>">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="junta_id">Junta:</label>
                    <select class="form-control" id="junta_id" name="junta_id" required>
                        <option value="">Seleccione una junta</option>
                        <?php foreach ($juntas as $junta): ?>
                            <option value="<?= $junta['JuntaID'] ?>" <?= $junta['JuntaID'] == $participante['JuntaID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($junta['NombreJunta']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="usuario_id">Usuario:</label>
                    <select class="form-control" id="usuario_id" name="usuario_id" required>
                        <option value="">Seleccione un usuario</option>
                        <?php foreach ($usuarios as $usuario): ?>
                            <option value="<?= $usuario['UsuarioID'] ?>" <?= $usuario['UsuarioID'] == $participante['UsuarioID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($usuario['Nombre'] . ' ' . $usuario['Apellido']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label for="cuenta_id">Cuenta Bancaria:</label>
                    <select class="form-control" id="cuenta_id" name="cuenta_id" required>
                        <option value="">Seleccione una cuenta</option>
                        <?php foreach ($cuentas as $cuenta): ?>
                            <option value="<?= $cuenta['CuentaID'] ?>" <?= $cuenta['CuentaID'] == $participante['CuentaID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cuenta['Banco'] . ' - ' . $cuenta['NumeroCuenta']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="garantia_id">Garantía (Opcional):</label>
                    <select class="form-control" id="garantia_id" name="garantia_id">
                        <option value="">Sin garantía</option>
                        <?php foreach ($garantias as $garantia): ?>
                            <option value="<?= $garantia['GarantiaID'] ?>" <?= $garantia['GarantiaID'] == $participante['GarantiaID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($garantia['TipoGarantia'] . ' - ' . $garantia['Descripcion']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="orden_recepcion">Orden de Recepción:</label>
                    <input type="number" class="form-control" id="orden_recepcion" name="orden_recepcion" 
                           value="<?= htmlspecialchars($participante['OrdenRecepcion']) ?>" required min="1">
                </div>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        <a href="<?= url('participantes/participantes.php') ?>" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>