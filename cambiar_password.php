<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/clases/Usuario.php';
require_once __DIR__ . '/includes/db.php';

verificarAutenticacion();
verificarInactividad();

$database = new Database();
$db = $database->getConnection();
$usuario = new Usuario($db);

$mensaje = '';
$error = '';

// Procesar el formulario de cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passwordActual = $_POST['password_actual'] ?? '';
    $nuevoPassword = $_POST['nuevo_password'] ?? '';
    $confirmarPassword = $_POST['confirmar_password'] ?? '';
    
    // Validaciones
    if (empty($passwordActual)) {
        $error = 'Debes ingresar tu contraseña actual.';
    } elseif (empty($nuevoPassword)) {
        $error = 'Debes ingresar una nueva contraseña.';
    } elseif (strlen($nuevoPassword) < 8) {
        $error = 'La nueva contraseña debe tener al menos 8 caracteres.';
    } elseif ($nuevoPassword !== $confirmarPassword) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        // Verificar contraseña actual
        if ($usuario->verificarPassword($_SESSION['usuario_id'], $passwordActual)) {
            // Cambiar la contraseña
            if ($usuario->cambiarPassword($_SESSION['usuario_id'], $nuevoPassword)) {
                $mensaje = 'Contraseña cambiada correctamente.';
                
                // Registrar en historial de seguridad
                registrarActividad($_SESSION['usuario_id'], 'Cambio de contraseña', 'El usuario cambió su contraseña');
                
                // Limpiar variables
                $passwordActual = $nuevoPassword = $confirmarPassword = '';
            } else {
                $error = 'Error al cambiar la contraseña. Por favor, inténtalo de nuevo.';
            }
        } else {
            $error = 'La contraseña actual es incorrecta.';
        }
    }
}
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Cambiar Contraseña</h4>
                </div>
                <div class="card-body">
                    <?php if ($mensaje): ?>
                        <div class="alert alert-success"><?= $mensaje ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="cambiar_password.php">
                        <div class="mb-3">
                            <label for="password_actual" class="form-label">Contraseña Actual</label>
                            <input type="password" class="form-control" id="password_actual" name="password_actual" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="nuevo_password" class="form-label">Nueva Contraseña</label>
                            <input type="password" class="form-control" id="nuevo_password" name="nuevo_password" required>
                            <div class="form-text">Mínimo 8 caracteres.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirmar_password" class="form-label">Confirmar Nueva Contraseña</label>
                            <input type="password" class="form-control" id="confirmar_password" name="confirmar_password" required>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="perfil.php" class="btn btn-secondary me-md-2">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Cambiar Contraseña</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Recomendaciones de Seguridad</h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li>Usa una contraseña única que no hayas usado antes</li>
                        <li>Combina letras mayúsculas, minúsculas, números y símbolos</li>
                        <li>No uses información personal como fechas o nombres</li>
                        <li>Considera usar una frase larga en lugar de una palabra</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>