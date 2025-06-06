<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/clases/Usuario.php';
require_once __DIR__ . '/includes/db.php';

verificarAutenticacion();
verificarInactividad();

// Mostrar mensaje de éxito si existe
if (isset($_SESSION['mensaje_exito'])) {
    $mensaje_exito = $_SESSION['mensaje_exito'];
    unset($_SESSION['mensaje_exito']); // Limpiar el mensaje después de mostrarlo
}

$database = new Database();
$db = $database->getConnection();
$usuario = new Usuario($db);

// Obtener datos del usuario
$datosUsuario = $usuario->obtenerPorId($_SESSION['usuario_id']);
$rolUsuario = $_SESSION['rol_id'] ?? null;

// Obtener cuentas bancarias del usuario
$cuentasBancarias = [];
if (tienePermiso('account.view', $rolUsuario)) {
    $cuentasBancarias = $usuario->obtenerCuentasBancarias($_SESSION['usuario_id']);
}

// Obtener garantías del usuario
$garantias = [];
if (tienePermiso('guarantees.view', $rolUsuario)) {
    $garantias = $usuario->obtenerGarantias($_SESSION['usuario_id']);
}

// Obtener actividad reciente
$actividadReciente = [];
if (tienePermiso('activity.view', $rolUsuario)) {
    $actividadReciente = $usuario->obtenerActividadReciente($_SESSION['usuario_id'], 5);
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';

?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <!-- Tarjeta de información del usuario -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Información del Perfil</h5>
                </div>
                <div class="card-body text-center">
                    <img src="assets/img/user-default.png" class="rounded-circle mb-3" width="120" alt="Avatar">
                    <h4><?= htmlspecialchars($datosUsuario['Nombre'] . ' ' . htmlspecialchars($datosUsuario['Apellido'])) ?></h4>
                    <p class="text-muted mb-1">@<?= htmlspecialchars($datosUsuario['NombreUsuario']) ?></p>
                    <p class="text-muted"><?= htmlspecialchars($_SESSION['nombre_rol'] ?? 'Rol no definido') ?></p>
                    
                    <div class="d-flex justify-content-center mb-3">
                        <div class="px-3 text-center">
                            <h5 class="mb-0"><?= $datosUsuario['PuntosCredito'] ?? 0 ?></h5>
                            <small class="text-muted">Puntos</small>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-center">
                        <a href="editar_perfil.php" class="btn btn-primary btn-sm me-2">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <a href="cambiar_password.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-key"></i> Cambiar Clave
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Tarjeta de contacto -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Información de Contacto</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-envelope me-2"></i>
                            <?= htmlspecialchars($datosUsuario['Email']) ?>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-phone me-2"></i>
                            <?= $datosUsuario['Telefono'] ? htmlspecialchars($datosUsuario['Telefono']) : 'No registrado' ?>
                        </li>
                        <li>
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <?= $datosUsuario['Direccion'] ? htmlspecialchars($datosUsuario['Direccion']) : 'No registrada' ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <!-- Pestañas -->
            <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab">Actividad</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="accounts-tab" data-bs-toggle="tab" data-bs-target="#accounts" type="button" role="tab">Cuentas Bancarias</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="guarantees-tab" data-bs-toggle="tab" data-bs-target="#guarantees" type="button" role="tab">Garantías</button>
                </li>
            </ul>
            
            <div class="tab-content" id="profileTabsContent">
                <!-- Pestaña de Actividad -->
                <div class="tab-pane fade show active" id="activity" role="tabpanel">
                    <?php if (!empty($actividadReciente)): ?>
                        <div class="list-group">
                            <?php foreach ($actividadReciente as $actividad): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?= htmlspecialchars($actividad['Titulo']) ?></h6>
                                        <small><?= time_elapsed_string($actividad['Fecha']) ?></small>
                                    </div>
                                    <p class="mb-1"><?= htmlspecialchars($actividad['Descripcion']) ?></p>
                                    <?php if (isset($actividad['Monto'])): ?>
                                        <small class="text-muted">Monto: S/ <?= number_format($actividad['Monto'], 2) ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3 text-end">
                            <a href="#" class="btn btn-sm btn-outline-primary">Ver historial completo</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No hay actividad reciente para mostrar.</div>
                    <?php endif; ?>
                </div>
                
                <!-- Pestaña de Cuentas Bancarias -->
                <div class="tab-pane fade" id="accounts" role="tabpanel">
                    <?php if (tienePermiso('account.view', $rolUsuario)): ?>
                        <?php if (!empty($cuentasBancarias)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Banco</th>
                                            <th>Número de Cuenta</th>
                                            <th>Tipo</th>
                                            <th>Moneda</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cuentasBancarias as $cuenta): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($cuenta['Banco']) ?></td>
                                                <td><?= htmlspecialchars($cuenta['NumeroCuenta']) ?></td>
                                                <td><?= htmlspecialchars($cuenta['TipoCuenta']) ?></td>
                                                <td><?= htmlspecialchars($cuenta['Moneda']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $cuenta['Activa'] ? 'success' : 'secondary' ?>">
                                                        <?= $cuenta['Activa'] ? 'Activa' : 'Inactiva' ?>
                                                    </span>
                                                    <?php if ($cuenta['EsPrincipal']): ?>
                                                        <span class="badge bg-primary ms-1">Principal</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (tienePermiso('account.manage', $rolUsuario)): ?>
                                <div class="text-end">
                                    <a href="agregar_cuenta.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> Agregar Cuenta
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                No tienes cuentas bancarias registradas.
                                <?php if (tienePermiso('account.manage', $rolUsuario)): ?>
                                    <a href="agregar_cuenta.php" class="alert-link">Agrega tu primera cuenta</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-warning">No tienes permisos para ver esta información.</div>
                    <?php endif; ?>
                </div>
                
                <!-- Pestaña de Garantías -->
                <div class="tab-pane fade" id="guarantees" role="tabpanel">
                    <?php if (tienePermiso('guarantees.view', $rolUsuario)): ?>
                        <?php if (!empty($garantias)): ?>
                            <div class="row">
                                <?php foreach ($garantias as $garantia): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100">
                                            <div class="card-header d-flex justify-content-between">
                                                <h6 class="mb-0"><?= htmlspecialchars($garantia['TipoGarantia']) ?></h6>
                                                <span class="badge bg-<?= 
                                                    $garantia['Estado'] == 'Activa' ? 'success' : 
                                                    ($garantia['Estado'] == 'Retenida' ? 'danger' : 'secondary')
                                                ?>">
                                                    <?= htmlspecialchars($garantia['Estado']) ?>
                                                </span>
                                            </div>
                                            <div class="card-body">
                                                <p><?= htmlspecialchars($garantia['Descripcion']) ?></p>
                                                <p class="mb-1"><strong>Valor:</strong> S/ <?= number_format($garantia['ValorEstimado'], 2) ?></p>
                                                <p class="mb-1"><strong>Registrada:</strong> <?= date('d/m/Y', strtotime($garantia['FechaRegistro'])) ?></p>
                                            </div>
                                            <div class="card-footer bg-transparent">
                                                <?php if ($garantia['DocumentoURL']): ?>
                                                    <a href="<?= htmlspecialchars($garantia['DocumentoURL']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-file-download"></i> Ver Documento
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                No tienes garantías registradas.
                                <?php if (tienePermiso('guarantees.manage', $rolUsuario)): ?>
                                    <a href="agregar_garantia.php" class="alert-link">Registrar una garantía</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-warning">No tienes permisos para ver esta información.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>