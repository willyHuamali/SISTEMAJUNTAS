<?php
require_once __DIR__ . '/includes/config.php';
// Eliminar los requires duplicados
require_once __DIR__ . '/clases/Database.php';
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

// Mostrar vista
require_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h4><i class="fas fa-sign-in-alt"></i> Acceso al Sistema</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <div class="mb-3">
                            <label for="nombreUsuario" class="form-label">Usuario</label>
                            <input type="text" class="form-control" id="nombreUsuario" name="nombreUsuario" 
                                   required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="contrasena" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="contrasena" name="contrasena" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Ingresar
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <small class="text-muted">¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>