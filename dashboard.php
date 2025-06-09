<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/clases/Usuario.php';
require_once __DIR__ . '/clases/Junta.php';
require_once __DIR__ . '/clases/AuthHelper.php';
require_once __DIR__ . '/includes/db.php';

verificarAutenticacion();
verificarInactividad();

// Obtener datos del usuario y su rol
$database = new Database();
$db = $database->getConnection();
$authHelper = new Clases\AuthHelper($db);
$usuario = new Usuario($db);
$junta = new Junta($db);

$datosUsuario = $usuario->obtenerPorId($_SESSION['usuario_id']);
$rolUsuario = $_SESSION['rol_id'] ?? null;

// Obtener estadísticas según permisos
$estadisticas = [
    'juntas_activas' => 0,
    'proximo_pago' => 0,
    'proximo_desembolso' => 0,
    'actividad_reciente' => [],
    'proximos_eventos' => []
];

// Solo cargar datos si el usuario tiene permisos para verlos
if ($authHelper->tienePermiso('juntas.view', $rolUsuario)) {
    $estadisticas['juntas_activas'] = $junta->contarJuntasActivas($_SESSION['usuario_id']);
}

if ($authHelper->tienePermiso('payments.view', $rolUsuario)) {
    $estadisticas['proximo_pago'] = $junta->obtenerProximoPago($_SESSION['usuario_id']);
}

if ($authHelper->tienePermiso('disbursements.view', $rolUsuario)) {
    $estadisticas['proximo_desembolso'] = $junta->obtenerProximoDesembolso($_SESSION['usuario_id']);
}

if ($authHelper->tienePermiso('activity.view', $rolUsuario)) {
    $estadisticas['actividad_reciente'] = $junta->obtenerActividadReciente($_SESSION['usuario_id']);
    $estadisticas['proximos_eventos'] = $junta->obtenerProximosEventos($_SESSION['usuario_id']);
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <main class="col-12 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Panel de Control</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php if($authHelper->tienePermiso('reports.generate', $rolUsuario)): ?>
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">Exportar</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Cards de resumen -->
            <div class="row mb-4">
                <?php if($authHelper->tienePermiso('juntas.view', $rolUsuario)): ?>
                <div class="col-md-4">
                    <div class="card text-white bg-primary mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Juntas Activas</h5>
                            <p class="card-text display-4"><?= $estadisticas['juntas_activas'] ?></p>
                            <a href="<?php echo url('juntas/misjuntas.php'); ?>" class="text-white small">Ver mis juntas</a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if($authHelper->tienePermiso('payments.view', $rolUsuario)): ?>
                <div class="col-md-4">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Próximo Pago</h5>
                            <p class="card-text display-4">S/ <?= number_format($estadisticas['proximo_pago'], 2) ?></p>
                            <a href="pagos.php" class="text-white small">Ver pagos</a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if($authHelper->tienePermiso('disbursements.view', $rolUsuario)): ?>
                <div class="col-md-4">
                    <div class="card text-white bg-info mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Próximo Desembolso</h5>
                            <p class="card-text display-4">S/ <?= number_format($estadisticas['proximo_desembolso'], 2) ?></p>
                            <a href="desembolsos.php" class="text-white small">Ver desembolsos</a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Gráficos y tablas -->
            <div class="row">
                <?php if($authHelper->tienePermiso('activity.view', $rolUsuario) && !empty($estadisticas['actividad_reciente'])): ?>
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>Actividad Reciente</h5>
                            <?php if($authHelper->tienePermiso('reports.generate', $rolUsuario)): ?>
                                <a href="reportes/actividad.php" class="btn btn-sm btn-outline-primary">Ver reporte completo</a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Descripción</th>
                                            <th>Monto</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($estadisticas['actividad_reciente'] as $actividad): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($actividad['Fecha']) ?></td>
                                            <td><?= htmlspecialchars($actividad['Descripcion']) ?></td>
                                            <td>S/ <?= number_format($actividad['Monto'], 2) ?></td>
                                            <td>
                                                <span class="badge bg-<?= 
                                                    $actividad['Estado'] == 'Pagado' || $actividad['Estado'] == 'Completado' ? 'success' : 
                                                    ($actividad['Estado'] == 'Pendiente' ? 'warning' : 'danger') 
                                                ?>">
                                                    <?= htmlspecialchars($actividad['Estado']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if($authHelper->tienePermiso('events.view', $rolUsuario) && !empty($estadisticas['proximos_eventos'])): ?>
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Próximos Eventos</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <?php foreach($estadisticas['proximos_eventos'] as $evento): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?= htmlspecialchars($evento['Descripcion']) ?>
                                    <span class="badge bg-primary rounded-pill"><?= htmlspecialchars($evento['Fecha']) ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Sección adicional para administradores -->
            <?php if($authHelper->tienePermiso('admin.dashboard', $rolUsuario)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-warning">
                            <h5 class="mb-0">Panel de Administración</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6>Usuarios Registrados</h6>
                                            <p class="display-5">125</p>
                                            <?php if($authHelper->tienePermiso('users.manage', $rolUsuario)): ?>
                                                <a href="admin/usuarios.php" class="btn btn-sm btn-primary">Gestionar</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6>Juntas Activas</h6>
                                            <p class="display-5">42</p>
                                            <?php if($authHelper->tienePermiso('juntas.manage', $rolUsuario)): ?>
                                                <a href="admin/juntas.php" class="btn btn-sm btn-primary">Gestionar</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6>Pagos Pendientes</h6>
                                            <p class="display-5">18</p>
                                            <?php if($authHelper->tienePermiso('payments.manage', $rolUsuario)): ?>
                                                <a href="admin/pagos.php" class="btn btn-sm btn-primary">Revisar</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6>Desembolsos</h6>
                                            <p class="display-5">7</p>
                                            <?php if($authHelper->tienePermiso('disbursements.manage', $rolUsuario)): ?>
                                                <a href="admin/desembolsos.php" class="btn btn-sm btn-primary">Gestionar</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>