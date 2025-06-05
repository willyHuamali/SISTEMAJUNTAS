<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/clases/Usuario.php';
require_once __DIR__ . '/clases/Database.php';

verificarAutenticacion();
verificarInactividad();

$database = new Database();
$db = $database->getConnection();
$usuario = new Usuario($db);

$datosUsuario = $usuario->obtenerPorId($_SESSION['usuario_id']);
$mensaje = '';
$error = '';

// Procesar el formulario de edición
// Procesar el formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    
    // Validaciones básicas
    if (empty($nombre) || empty($apellido)) {
        $error = 'Nombre y apellido son campos obligatorios.';
    } else {
        $datosActualizados = [
            'Nombre' => $nombre,
            'Apellido' => $apellido,
            'Telefono' => $telefono,
            'Direccion' => $direccion
        ];
        
        if ($usuario->actualizarPerfil($_SESSION['usuario_id'], $datosActualizados)) {
            // Redireccionar a perfil.php con mensaje de éxito
            $_SESSION['mensaje_exito'] = 'Perfil actualizado correctamente.';
            header('Location: perfil.php');
            exit;
        } else {
            $error = 'Error al actualizar el perfil. Por favor, inténtalo de nuevo.';
        }
    }
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Editar Perfil</h4>
                </div>
                <div class="card-body">
                    <?php if ($mensaje): ?>
                        <div class="alert alert-success"><?= $mensaje ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="editar_perfil.php">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nombre" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                       value="<?= htmlspecialchars($datosUsuario['Nombre'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="apellido" class="form-label">Apellido</label>
                                <input type="text" class="form-control" id="apellido" name="apellido" 
                                       value="<?= htmlspecialchars($datosUsuario['Apellido'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" 
                                   value="<?= htmlspecialchars($datosUsuario['Email'] ?? '') ?>">
                            <small class="text-muted">Para cambiar tu correo electrónico, contacta al administrador.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono" 
                                   value="<?= htmlspecialchars($datosUsuario['Telefono'] ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="direccion" class="form-label">Dirección</label>
                            <textarea class="form-control" id="direccion" name="direccion" rows="2"><?= htmlspecialchars($datosUsuario['Direccion'] ?? '') ?> </textarea>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="perfil.php" class="btn btn-secondary me-md-2">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>