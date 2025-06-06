<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';


verificarAutenticacion();

// Inicializar la conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Verificar permisos con la conexión $db pasada como parámetro
if (!tienePermiso('accounts.view', $_SESSION['rol_id'], $db)) {
    $_SESSION['error'] = 'No tienes permiso para acceder a esta sección';
    header('Location: ../index.php');
    exit;
}

// Verificar si es administrador (con la conexión $db)
$esAdministrador = tienePermiso('accounts.manage_all', $_SESSION['rol_id'], $db);
$usuarioId = $esAdministrador ? ($_GET['usuario_id'] ?? $_SESSION['usuario_id']) : $_SESSION['usuario_id'];

// Obtener cuentas bancarias del usuario
$query = "SELECT cb.*, u.NombreUsuario 
          FROM CuentasBancarias cb
          JOIN Usuarios u ON cb.UsuarioID = u.UsuarioID
          WHERE cb.UsuarioID = ?";
$stmt = $db->prepare($query);
$stmt->execute([$usuarioId]);
$cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si es administrador, obtener nombre del usuario para mostrar
$nombreUsuario = $_SESSION['nombre_usuario'];
if ($esAdministrador && $usuarioId != $_SESSION['usuario_id']) {
    $queryUsuario = "SELECT NombreUsuario FROM Usuarios WHERE UsuarioID = ?";
    $stmtUsuario = $db->prepare($queryUsuario);
    $stmtUsuario->execute([$usuarioId]);
    $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);
    $nombreUsuario = $usuario['NombreUsuario'];
}

$tituloPagina = $esAdministrador ? "Cuentas de $nombreUsuario" : "Mis Cuentas Bancarias";

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><?= htmlspecialchars($tituloPagina) ?></h2>
        
        <?php if (tienePermiso('accounts.manage', $_SESSION['rol_id'], $db) || $esAdministrador): ?>
            <a href="agregar.php<?= $esAdministrador ? '?usuario_id='.$usuarioId : '' ?>" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i> Agregar Cuenta
            </a>
        <?php endif; ?>
    </div>

    <?php 
    // Mostrar mensajes (usa la función correcta)
    $mensaje = mostrarMensajeFlash();
    if ($mensaje): ?>
        <div class="alert alert-<?= $mensaje['type'] ?>">
            <?= $mensaje['text'] ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($cuentas)): ?>
                <div class="alert alert-info">No hay cuentas bancarias registradas.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Banco</th>
                                <th>Número de Cuenta</th>
                                <th>Tipo</th>
                                <th>Moneda</th>
                                <th>Estado</th>
                                <th>Principal</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cuentas as $cuenta): ?>
                                <tr>
                                    <td><?= htmlspecialchars($cuenta['Banco']) ?></td>
                                    <td><?= htmlspecialchars($cuenta['NumeroCuenta']) ?></td>
                                    <td><?= htmlspecialchars($cuenta['TipoCuenta']) ?></td>
                                    <td><?= htmlspecialchars($cuenta['Moneda']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $cuenta['Activa'] ? 'success' : 'secondary' ?>">
                                            <?= $cuenta['Activa'] ? 'Activa' : 'Inactiva' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($cuenta['EsPrincipal']): ?>
                                            <i class="fas fa-check-circle text-success"></i>
                                        <?php else: ?>
                                            <i class="fas fa-times-circle text-secondary"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <?php if (tienePermiso('accounts.manage', $_SESSION['rol_id'], $db) || $esAdministrador): ?>
                                                <a href="editar.php?id=<?= $cuenta['CuentaID'] ?>" 
                                                   class="btn btn-sm btn-outline-primary" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="eliminar.php?id=<?= $cuenta['CuentaID'] ?>" 
                                                   class="btn btn-sm btn-outline-danger" title="Eliminar"
                                                   onclick="return confirm('¿Estás seguro de eliminar esta cuenta?');">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($esAdministrador): ?>
        <div class="mt-3">
            <a href="gestionar.php" class="btn btn-outline-secondary">
                <i class="fas fa-list me-1"></i> Gestionar Todas las Cuentas
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>