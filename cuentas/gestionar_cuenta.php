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
if (!$authHelper->tienePermiso('cuentas.manage', $_SESSION['rol_id'])) {
    header('Location: /sistemajuntas/dashboard.php?error=no_permission');
    exit;
}

// Obtener todas las cuentas con información de usuario
$query = "SELECT c.*, u.Nombre, u.Apellido, u.DNI 
          FROM CuentasBancarias c
          JOIN Usuarios u ON c.UsuarioID = u.UsuarioID
          ORDER BY u.Nombre, u.Apellido, c.Banco";
$cuentas = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="container mt-4">
    <h2><i class="fas fa-piggy-bank"></i> Gestión de Cuentas Bancarias</h2>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>
    
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>DNI</th>
                    <th>Banco</th>
                    <th>Número de Cuenta</th>
                    <th>Tipo</th>
                    <th>Moneda</th>
                    <th>Principal</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cuentas as $cuenta): ?>
                    <tr>
                        <td><?= htmlspecialchars($cuenta['Nombre'] . ' ' . $cuenta['Apellido']) ?></td>
                        <td><?= htmlspecialchars($cuenta['DNI']) ?></td>
                        <td><?= htmlspecialchars($cuenta['Banco']) ?></td>
                        <td><?= htmlspecialchars($cuenta['NumeroCuenta']) ?></td>
                        <td><?= htmlspecialchars($cuenta['TipoCuenta']) ?></td>
                        <td><?= htmlspecialchars($cuenta['Moneda']) ?></td>
                        <td><?= $cuenta['EsPrincipal'] ? '<span class="badge bg-success">Sí</span>' : '' ?></td>
                        <td><?= $cuenta['Activa'] ? '<span class="badge bg-success">Activa</span>' : '<span class="badge bg-danger">Inactiva</span>' ?></td>
                        <td>
                            <a href="editar_cuenta.php?id=<?= $cuenta['CuentaID'] ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if ($cuenta['Activa']): ?>
                                <a href="procesar_cuenta.php?action=deactivate&id=<?= $cuenta['CuentaID'] ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('¿Estás seguro de desactivar esta cuenta?')">
                                    <i class="fas fa-ban"></i>
                                </a>
                            <?php else: ?>
                                <a href="procesar_cuenta.php?action=activate&id=<?= $cuenta['CuentaID'] ?>" 
                                   class="btn btn-sm btn-success" 
                                   onclick="return confirm('¿Estás seguro de activar esta cuenta?')">
                                    <i class="fas fa-check"></i>
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