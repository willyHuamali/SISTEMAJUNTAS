<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/clases/Usuario.php';

// Redirigir si ya está logueado
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar campos
        if (empty($_POST['nombreUsuario']) || empty($_POST['contrasena'])) {
            throw new Exception('Todos los campos son requeridos');
        }

        $nombreUsuario = trim($_POST['nombreUsuario']);
        $contrasena = $_POST['contrasena'];

        // Instanciar y autenticar usuario
        $db = new Database();
        $usuario = new Usuario($db->getConnection());
        
        $usuarioData = $usuario->login($nombreUsuario, $contrasena);
        
        if ($usuarioData) {
            // Configurar sesión
            $_SESSION['usuario_id'] = $usuarioData['UsuarioID'];
            $_SESSION['nombre_usuario'] = $usuarioData['NombreUsuario'];
            $_SESSION['nombre_completo'] = $usuarioData['Nombre'] . ' ' . $usuarioData['Apellido'];
            $_SESSION['rol_id'] = $usuarioData['RolID'];
            $_SESSION['nombre_rol'] = $usuarioData['NombreRol'];
            $_SESSION['ultimo_actividad'] = time();
            
            // Redirigir al dashboard
            header('Location: dashboard.php');
            exit();
        } else {
            throw new Exception('Credenciales incorrectas');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <!-- Botón para volver al inicio -->
            <div class="mb-4">
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>Volver al Inicio
                </a>
            </div>
            
            <!-- Tarjeta del login -->
            <div class="card shadow-lg border-0">
                <div class="card-header bg-gradient-primary text-white py-3">
                    <h3 class="mb-0 text-center"><i class="fas fa-sign-in-alt mr-2"></i>Iniciar Sesión</h3>
                </div>
                <div class="card-body px-4 py-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <div class="form-group mb-4">
                            <label for="nombreUsuario" class="font-weight-bold">Usuario</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                </div>
                                <input type="text" class="form-control" id="nombreUsuario" name="nombreUsuario" 
                                       required autofocus placeholder="Ingresa tu nombre de usuario">
                            </div>
                        </div>
                        
                        <div class="form-group mb-4">
                            <label for="contrasena" class="font-weight-bold">Contraseña</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                </div>
                                <input type="password" class="form-control" id="contrasena" name="contrasena" 
                                       required placeholder="Ingresa tu contraseña">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary toggle-password" type="button">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group form-check mb-4">
                            <input type="checkbox" class="form-check-input" id="rememberMe">
                            <label class="form-check-label" for="rememberMe">Recordar mi sesión</label>
                        </div>
                        
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary btn-block py-2 rounded-pill font-weight-bold">
                                <i class="fas fa-sign-in-alt mr-2"></i>Ingresar
                            </button>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="recuperar-contrasena.php" class="text-muted">¿Olvidaste tu contraseña?</a>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center bg-light">
                    <p class="mb-0">¿No tienes cuenta? <a href="registro.php" class="font-weight-bold">Regístrate aquí</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Función para mostrar/ocultar contraseña
document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', function() {
        const input = this.closest('.input-group').querySelector('input');
        const icon = this.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
});
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>