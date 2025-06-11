<?php
// Verificar si el usuario está logueado
require_once __DIR__ . '/../clases/AuthHelper.php';
require_once __DIR__ . '/../includes/db.php';

$usuarioLogueado = isset($_SESSION['usuario_id']);
$rolUsuario = $_SESSION['rol_id'] ?? null;
$nombreUsuario = $_SESSION['nombre_usuario'] ?? '';

// Crear instancia de AuthHelper
$database = new Database();
$db = $database->getConnection();
$authHelper = new \Clases\AuthHelper($db);

// Inicializar variables de notificaciones
$notificacionesNoLeidas = 0;
$notificacionesRecientes = [];

if ($usuarioLogueado && $authHelper->tienePermiso('notifications.view', $rolUsuario)) {
    try {
        // Contar notificaciones no leídas
        $query = "SELECT COUNT(*) as total FROM Notificaciones WHERE UsuarioID = ? AND Leida = 0";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['usuario_id']]);
        $resultado = $stmt->fetch(\PDO::FETCH_ASSOC);
        $notificacionesNoLeidas = $resultado['total'] ?? 0;
        
        // Obtener notificaciones recientes
        $query = "SELECT * FROM Notificaciones WHERE UsuarioID = ? ORDER BY FechaCreacion DESC LIMIT 5";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['usuario_id']]);
        $notificacionesRecientes = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\Exception $e) {
        error_log("Error al obtener notificaciones: " . $e->getMessage());
    }
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm py-1">
    <div class="container py-1">
        <a class="navbar-brand" href="<?php echo url('index.php'); ?>">
            <i class="fas fa-users me-2"></i>Sistema de Juntas
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="<?php echo url('dashboard.php'); ?>">
                        <i class="fas fa-home"></i> Inicio
                    </a>
                </li>
                
                <?php if($usuarioLogueado): ?>
                    <!-- Menú Juntas -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= in_array(basename($_SERVER['PHP_SELF']), ['index_junta.php', 'misjuntas.php']) ? 'active' : '' ?>" 
                        href="#" id="navbarJuntas" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-users"></i> Juntas
                        </a>
                        <ul class="dropdown-menu">
                            <?php if($authHelper->tienePermiso('juntas.view', $rolUsuario)): ?>
                                <li><a class="dropdown-item" href="<?php echo url('juntas/index_junta.php'); ?>"><i class="fas fa-list me-2"></i>Todas las Juntas</a></li>
                            <?php endif; ?>
                            
                            <?php if($authHelper->tienePermiso('juntas.create', $rolUsuario)): ?>
                                <li><a class="dropdown-item" href="<?php echo url('juntas/crear_junta.php'); ?>"><i class="fas fa-plus-circle me-2"></i>Crear Nueva Junta</a></li>
                            <?php endif; ?>
                            
                            <li><a class="dropdown-item" href="<?php echo url('juntas/misjuntas.php'); ?>"><i class="fas fa-user-friends me-2"></i>Mis Juntas</a></li>
                            
                            <?php if($authHelper->tienePermiso('juntas.edit', $rolUsuario)): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo url('juntas/gestionar_junta.php'); ?>"><i class="fas fa-cog me-2"></i>Gestionar Juntas</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    
                    <!-- Menú Participantes - CORREGIDO -->
                    <?php if($authHelper->tienePermiso('participantesjuntas.view', $rolUsuario) || $authHelper->tienePermiso('participantesjuntas.manage', $rolUsuario)): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?= strpos($_SERVER['PHP_SELF'], 'participantes/') !== false ? 'active' : '' ?>" 
                            href="#" id="navbarParticipantes" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-friends"></i> Participantes
                            </a>
                            <ul class="dropdown-menu">
                                <?php if($authHelper->tienePermiso('participantesjuntas.view', $rolUsuario)): ?>
                                    <li><a class="dropdown-item" href="<?php echo url('participantes/participantes.php'); ?>">
                                        <i class="fas fa-list me-2"></i>Listar Participantes
                                    </a></li>
                                <?php endif; ?>
                                
                                <?php if($authHelper->tienePermiso('participantesjuntas.add', $rolUsuario)): ?>
                                    <li><a class="dropdown-item" href="<?php echo url('participantes/agregar_par.php'); ?>">
                                        <i class="fas fa-user-plus me-2"></i>Agregar Participante
                                    </a></li>
                                <?php endif; ?>
                                
                                <?php if($authHelper->tienePermiso('participantesjuntas.edit', $rolUsuario)): ?>
                                    <li><a class="dropdown-item" href="<?php echo url('participantes/editar_par.php'); ?>">
                                        <i class="fas fa-user-edit me-2"></i>Editar Participante
                                    </a></li>
                                <?php endif; ?>
                                
                                <?php if($authHelper->tienePermiso('participantesjuntas.assign_order', $rolUsuario)): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo url('participantes/asignar_orden_par.php'); ?>">
                                        <i class="fas fa-sort-numeric-down me-2"></i>Asignar Orden
                                    </a></li>
                                <?php endif; ?>
                                
                                <?php if($authHelper->tienePermiso('participantesjuntas.manage_guarantees', $rolUsuario)): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo url('participantes/garantias.php'); ?>">
                                        <i class="fas fa-file-contract me-2"></i>Gestionar Garantías
                                    </a></li>
                                <?php endif; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Resto del navbar permanece igual -->
                    <?php /* Mantener el resto del navbar sin cambios */ ?>
                    
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav ms-auto">
                <?php if($usuarioLogueado): ?>
                    <!-- Notificaciones -->
                    <?php if($usuarioLogueado && $authHelper->tienePermiso('notifications.view', $rolUsuario)): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link position-relative" href="#" id="navbarNotificaciones" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <?php if($notificacionesNoLeidas > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?= $notificacionesNoLeidas ?>
                                        <span class="visually-hidden">notificaciones no leídas</span>
                                    </span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarNotificaciones">
                                <li><h6 class="dropdown-header">Notificaciones recientes</h6></li>
                                <?php if(empty($notificacionesRecientes)): ?>
                                    <li><a class="dropdown-item text-muted">No hay notificaciones</a></li>
                                <?php else: ?>
                                    <?php foreach($notificacionesRecientes as $notif): ?>
                                        <li>
                                            <a class="dropdown-item <?= $notif['Leida'] ? 'text-muted' : 'fw-bold' ?>" href="#">
                                                <div class="d-flex justify-content-between">
                                                    <span><?= htmlspecialchars($notif['Titulo']) ?></span>
                                                    <small class="text-muted"><?= time_elapsed_string($notif['FechaCreacion']) ?></small>
                                                </div>
                                                <small class="d-block text-truncate" style="max-width: 250px;"><?= htmlspecialchars($notif['Mensaje']) ?></small>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="<?php echo url('notificaciones.php'); ?>">Ver todas las notificaciones</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Perfil de usuario -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarUser" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($nombreUsuario) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo url('perfil.php'); ?>"><i class="fas fa-user me-2"></i>Mi Perfil</a></li>
                            
                            <?php if($authHelper->tienePermiso('guarantees.view', $rolUsuario)): ?>
                                <li><a class="dropdown-item" href="<?php echo url('garantias.php'); ?>"><i class="fas fa-file-contract me-2"></i>Mis Garantías</a></li>
                            <?php endif; ?>
                            
                            <?php if($authHelper->tienePermiso('accounts.view', $rolUsuario)): ?>
                                <li><a class="dropdown-item" href="<?php echo url('cuentas/'); ?>"><i class="fas fa-piggy-bank me-2"></i>Mis Cuentas</a></li>
                            <?php endif; ?>
                            
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo url('logout.php'); ?>"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : '' ?>" href="<?php echo url('login.php'); ?>">
                            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'registro.php' ? 'active' : '' ?>" href="<?php echo url('registro.php'); ?>">
                            <i class="fas fa-user-plus"></i> Registrarse
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>