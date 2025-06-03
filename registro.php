<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/clases/Usuario.php';

// Generar token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar token CSRF
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception('Token CSRF inválido.');
        }

        // Crear instancia de Database y obtener conexión
        $database = new Database();
        $db = $database->getConnection();
        
        // Pasar la conexión al constructor de Usuario
        $usuario = new Usuario($db);
        
        $datos = [
            'nombre' => trim($_POST['nombre']),
            'apellido' => trim($_POST['apellido']),
            'dni' => trim($_POST['dni']),
            'email' => trim($_POST['email']),
            'nombreUsuario' => trim($_POST['nombreUsuario']),
            'contrasena' => $_POST['contrasena'],
            'confirmar_contrasena' => $_POST['confirmar_contrasena'] ?? '',
            'rolID' => isset($_POST['rolID']) ? (int)$_POST['rolID'] : 2 // Default a Rol de Usuario normal
        ];
        
        $usuarioID = $usuario->registrar($datos);
        
        $success = 'Registro exitoso. Ahora puedes iniciar sesión.';
        // Limpiar el token CSRF después de un registro exitoso
        unset($_SESSION['csrf_token']);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Obtener lista de roles disponibles
$roles = [];
try {
    $database = new Database();
    $db = $database->getConnection();
    $query = "SELECT RolID, NombreRol FROM Roles WHERE Activo = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Error al cargar los roles: " . $e->getMessage();
}

require_once 'includes/header.php';
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
            
            <!-- Tarjeta del formulario -->
            <div class="card shadow-lg border-0">
                <div class="card-header bg-gradient-primary text-white py-3">
                    <h3 class="mb-0 text-center"><i class="fas fa-user-plus mr-2"></i>Registro de Usuario</h3>
                </div>
                <div class="card-body px-4 py-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <div class="mt-2">
                                <a href="login.php" class="btn btn-sm btn-success">
                                    <i class="fas fa-sign-in-alt mr-1"></i>Ir a Inicio de Sesión
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <form id="formRegistro" method="POST" action="registro.php" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="nombre" class="font-weight-bold">Nombre <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <input type="text" class="form-control" id="nombre" name="nombre" required
                                               pattern="[A-Za-záéíóúÁÉÍÓÚñÑ\s]{2,50}"
                                               title="Solo letras (2-50 caracteres)">
                                    </div>
                                    <div class="invalid-feedback">Por favor ingresa un nombre válido (2-50 letras)</div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="apellido" class="font-weight-bold">Apellido <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <input type="text" class="form-control" id="apellido" name="apellido" required
                                               pattern="[A-Za-záéíóúÁÉÍÓÚñÑ\s]{2,50}"
                                               title="Solo letras (2-50 caracteres)">
                                    </div>
                                    <div class="invalid-feedback">Por favor ingresa un apellido válido (2-50 letras)</div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="dni" class="font-weight-bold">DNI <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    </div>
                                    <input type="text" class="form-control" id="dni" name="dni" required
                                           pattern="[0-9]{8}"
                                           title="8 dígitos numéricos">
                                </div>
                                <div class="invalid-feedback">Por favor ingresa un DNI válido (8 dígitos)</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="font-weight-bold">Email <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    </div>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="invalid-feedback">Por favor ingresa un email válido</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="nombreUsuario" class="font-weight-bold">Nombre de Usuario <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-at"></i></span>
                                    </div>
                                    <input type="text" class="form-control" id="nombreUsuario" name="nombreUsuario" required
                                           pattern="[A-Za-z0-9_]{4,20}"
                                           title="4-20 caracteres (letras, números o guión bajo)">
                                </div>
                                <div class="invalid-feedback">4-20 caracteres (letras, números o _)</div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="contrasena" class="font-weight-bold">Contraseña <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        </div>
                                        <input type="password" class="form-control" id="contrasena" name="contrasena" required
                                               minlength="8"
                                               pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$"
                                               title="Mínimo 8 caracteres con mayúscula, minúscula y número">
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="invalid-feedback">Mínimo 8 caracteres con mayúscula, minúscula y número</div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="confirmar_contrasena" class="font-weight-bold">Confirmar Contraseña <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        </div>
                                        <input type="password" class="form-control" id="confirmar_contrasena" name="confirmar_contrasena" required>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="invalid-feedback">Las contraseñas no coinciden</div>
                                </div>
                            </div>
                            
                            <div class="progress mb-3" style="height: 5px;">
                                <div id="password-strength-bar" class="progress-bar" role="progressbar"></div>
                            </div>
                            <small class="form-text text-muted mb-3">
                                <i class="fas fa-info-circle mr-1"></i>La contraseña debe contener al menos 8 caracteres, incluyendo una mayúscula, una minúscula y un número.
                            </small>
                            
                            <div class="form-group">
                                <label for="rolID" class="font-weight-bold">Rol <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                    </div>
                                    <select class="form-control" id="rolID" name="rolID" required>
                                        <?php foreach ($roles as $rol): ?>
                                            <option value="<?php echo htmlspecialchars($rol['RolID']); ?>">
                                                <?php echo htmlspecialchars($rol['NombreRol']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="invalid-feedback">Por favor selecciona un rol</div>
                            </div>
                            
                            <div class="form-group form-check mt-4">
                                <input type="checkbox" class="form-check-input" id="terminos" required>
                                <label class="form-check-label" for="terminos">Acepto los <a href="#" data-toggle="modal" data-target="#terminosModal">términos y condiciones</a></label>
                                <div class="invalid-feedback">Debes aceptar los términos y condiciones</div>
                            </div>
                            
                            <div class="form-group mt-5">
                                <button type="submit" class="btn btn-primary btn-block py-2 rounded-pill font-weight-bold">
                                    <i class="fas fa-user-plus mr-2"></i>Registrarse
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center bg-light">
                    <p class="mb-0">¿Ya tienes una cuenta? <a href="login.php" class="font-weight-bold">Inicia sesión aquí</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Términos y Condiciones -->
<div class="modal fade" id="terminosModal" tabindex="-1" role="dialog" aria-labelledby="terminosModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="terminosModalLabel"><i class="fas fa-file-contract mr-2"></i>Términos y Condiciones</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Contenido de los términos y condiciones -->
                <h5>1. Aceptación de los Términos</h5>
                <p>Al registrarte en nuestro sistema, aceptas cumplir con estos términos y condiciones...</p>
                
                <h5>2. Uso del Sistema</h5>
                <p>El sistema está diseñado para gestionar juntas comunitarias de manera eficiente...</p>
                
                <h5>3. Responsabilidades</h5>
                <p>Eres responsable de mantener la confidencialidad de tu cuenta y contraseña...</p>
                
                <h5>4. Privacidad</h5>
                <p>Respetamos tu privacidad y protegemos tus datos personales...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>

<script>
// Validación del formulario
(function() {
    'use strict';
    
    // Obtener el formulario
    const form = document.getElementById('formRegistro');
    const password = document.getElementById('contrasena');
    const confirmPassword = document.getElementById('confirmar_contrasena');
    const passwordStrengthBar = document.getElementById('password-strength-bar');
    
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
    
    // Validar confirmación de contraseña
    confirmPassword.addEventListener('input', function() {
        if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Las contraseñas no coinciden');
        } else {
            confirmPassword.setCustomValidity('');
        }
    });
    
    // Medidor de fortaleza de contraseña
    password.addEventListener('input', function() {
        const strength = calculatePasswordStrength(password.value);
        updateStrengthBar(strength);
    });
    
    function calculatePasswordStrength(password) {
        let strength = 0;
        
        // Longitud mínima
        if (password.length >= 6) strength += 1;
        if (password.length >= 12) strength += 1;
        
        // Contiene letras mayúsculas y minúsculas
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 1;
        
        // Contiene números
        if (/\d/.test(password)) strength += 1;
        
        // Contiene caracteres especiales
        if (/[^a-zA-Z0-9]/.test(password)) strength += 1;
        
        return strength;
    }
    
    function updateStrengthBar(strength) {
        const percent = (strength / 5) * 100;
        passwordStrengthBar.style.width = percent + '%';
        
        // Cambiar color según fortaleza
        if (percent < 40) {
            passwordStrengthBar.className = 'progress-bar bg-danger';
        } else if (percent < 70) {
            passwordStrengthBar.className = 'progress-bar bg-warning';
        } else {
            passwordStrengthBar.className = 'progress-bar bg-success';
        }
    }
    
    // Validación al enviar el formulario
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        form.classList.add('was-validated');
    }, false);
})();
</script>

<?php require_once 'includes/footer.php'; ?>