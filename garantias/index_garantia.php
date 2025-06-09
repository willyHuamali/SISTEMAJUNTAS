<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Verificar permisos
if (!tienePermiso('garantias.view_own') && !tienePermiso('garantias.view_all')) {
    header('Location: ../index.php?error=permisos');
    exit();
}

$tituloPagina = "Mis Garantías";
require_once '../includes/header.php';
require_once '../includes/navbar.php';

// Obtener listado de garantías
$db = conectarBD();

if (tienePermiso('garantias.view_all')) {
    // Administrador puede ver todas las garantías
    $query = "SELECT g.*, u.Nombre, u.Apellido FROM Garantias g JOIN Usuarios u ON g.UsuarioID = u.UsuarioID";
    $stmt = $db->prepare($query);
} else {
    // Usuario normal solo ve sus propias garantías
    $query = "SELECT * FROM Garantias WHERE UsuarioID = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $_SESSION['usuario_id']);
}

$stmt->execute();
$resultado = $stmt->get_result();
?>

<div class="container mt-4">
    <h2 class="mb-4"><?php echo $tituloPagina; ?></h2>
    
    <?php if (tienePermiso('garantias.add')): ?>
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
                            <?php if (tienePermiso('garantias.view_all')): ?>
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
                        <?php while ($garantia = $resultado->fetch_assoc()): ?>
                        <tr>
                            <?php if (tienePermiso('garantias.view_all')): ?>
                            <td><?php echo htmlspecialchars($garantia['Nombre'] . ' ' . $garantia['Apellido']); ?></td>
                            <?php endif; ?>
                            <td><?php echo htmlspecialchars($garantia['TipoGarantia']); ?></td>
                            <td><?php echo htmlspecialchars($garantia['Descripcion']); ?></td>
                            <td><?php echo formatoMoneda($garantia['ValorEstimado']); ?></td>
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
                                <?php if (tienePermiso('garantias.edit') && ($garantia['UsuarioID'] == $_SESSION['usuario_id'] || tienePermiso('garantias.manage_all'))): ?>
                                <a href="editar_garantia.php?id=<?php echo $garantia['GarantiaID']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>