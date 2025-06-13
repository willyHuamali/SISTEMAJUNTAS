<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../clases/AuthHelper.php';


// Verificar autenticación e inactividad
verificarAutenticacion();
verificarInactividad();

// Inicializar Database y AuthHelper
$database = new Database();
$db = $database->getConnection();
$authHelper = new \Clases\AuthHelper($db);

// Obtener ID de usuario y rol de la sesión
$usuarioId = $_SESSION['usuario_id'] ?? null;
$rolId = $_SESSION['rol_id'] ?? null;

// Verificar permisos
if (!$authHelper->tienePermiso('garantias.view_own', $rolId) && !$authHelper->tienePermiso('garantias.view_all', $rolId)) {
    header('Location: ../index.php?error=permisos');
    exit();
}

$tituloPagina = "Mis Garantías";
require_once '../includes/header.php';
require_once '../includes/navbar.php';

// Mostrar mensaje de éxito si existe
if (isset($_SESSION['mensaje_exito']) && is_array($_SESSION['mensaje_exito'])) {
    echo '<div class="alert alert-'.htmlspecialchars($_SESSION['mensaje_exito']['tipo']).' alert-dismissible fade show">
        <strong>'.htmlspecialchars($_SESSION['mensaje_exito']['titulo']).'</strong> '.htmlspecialchars($_SESSION['mensaje_exito']['texto']).'
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
    unset($_SESSION['mensaje_exito']);
}

// Obtener listado de garantías - USAMOS LA CONEXIÓN $db QUE YA TENEMOS

if ($authHelper->tienePermiso('garantias.view_all', $rolId)) {
    // Administrador puede ver todas las garantías
    $query = "SELECT g.*, u.Nombre, u.Apellido FROM Garantias g JOIN Usuarios u ON g.UsuarioID = u.UsuarioID";
    $stmt = $db->prepare($query);
} else {
    // Usuario normal solo ve sus propias garantías
    $query = "SELECT * FROM Garantias WHERE UsuarioID = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $_SESSION['usuario_id'], PDO::PARAM_INT);
}

$stmt->execute();
$resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- El resto del código HTML/PHP permanece igual -->
<div class="container mt-4">
    <h2 class="mb-4"><?php echo $tituloPagina; ?></h2>
    
    <?php if ($authHelper->tienePermiso('garantias.add', $rolId)): ?>
    <a href="agregar_garantia.php" class="btn btn-primary mb-3">
        <i class="fas fa-plus"></i> Agregar Nueva Garantía
    </a>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <?php if ($authHelper->tienePermiso('garantias.view_all', $rolId)): ?>
                            <th>Usuario</th>
                            <?php endif; ?>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Valor Estimado</th>
                            <th>Fecha Registro</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultado as $garantia): ?>
                        <tr>
                            <?php if ($authHelper->tienePermiso('garantias.view_all', $rolId)): ?>
                            <td><?php echo htmlspecialchars($garantia['Nombre'] . ' ' . $garantia['Apellido']); ?></td>
                            <?php endif; ?>
                            <td><?php echo htmlspecialchars($garantia['TipoGarantia']); ?></td>
                            <td><?php echo htmlspecialchars($garantia['Descripcion']); ?></td>
                            <td><?php echo formatearMoneda($garantia['ValorEstimado']); ?></td>
                            <td><?php echo formatoFecha($garantia['FechaRegistro']); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $garantia['Estado'] == 'Activa' ? 'success' : 
                                         ($garantia['Estado'] == 'Retenida' ? 'warning' : 'secondary'); 
                                ?>">
                                    <?php echo $garantia['Estado']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="ver_garantia.php?id=<?php echo $garantia['GarantiaID']; ?>" class="btn btn-sm btn-info" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($authHelper->tienePermiso('garantias.edit', $rolId) && ($garantia['UsuarioID'] == $_SESSION['usuario_id'] || $authHelper->tienePermiso('garantias.manage_all', $rolId))): ?>
                                <a href="editar_garantia.php?id=<?php echo $garantia['GarantiaID']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>