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

// Obtener ID de usuario de la sesión
$usuarioId = $_SESSION['usuario_id'] ?? null;

// Verificar permisos
if (!$authHelper->tienePermiso('cuentas.view_own', $_SESSION['rol_id'])) {
    header('Location: /sistemajuntas/dashboard.php?error=no_permission');
    exit;
}

// Obtener cuentas del usuario
$query = "SELECT * FROM CuentasBancarias WHERE UsuarioID = ? AND Activa = 1 ORDER BY EsPrincipal DESC";
$stmt = $db->prepare($query);
$stmt->execute([$usuarioId]);
$cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="container mt-4">
    <h2><i class="fas fa-piggy-bank"></i> Mis Cuentas Bancarias</h2>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>
    
    <div class="mb-3">
        <?php if ($authHelper->tienePermiso('cuentas.add', $_SESSION['rol_id'])): ?>
            <a href="agregar_cuenta.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Agregar Cuenta
            </a>
        <?php endif; ?>
    </div>
    
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Banco</th>
                    <th>Número de Cuenta</th>
                    <th>Tipo</th>
                    <th>Moneda</th>
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
                        <td><?= $cuenta['EsPrincipal'] ? '<span class="badge bg-success">Sí</span>' : '' ?></td>
                        <td>
                            <?php if ($authHelper->tienePermiso('cuentas.edit', $_SESSION['rol_id'])): ?>
                                <a href="editar_cuenta.php?id=<?= $cuenta['CuentaID'] ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                            <?php endif; ?>
                            <?php if ($authHelper->tienePermiso('cuentas.delete', $_SESSION['rol_id'])): ?>
                                <a href="procesar_cuenta.php?action=delete&id=<?= $cuenta['CuentaID'] ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('¿Estás seguro de eliminar esta cuenta?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>