
<?php
// Verificar si el usuario está logueado
require_once __DIR__ . '/functions.php';
$usuarioLogueado = isset($_SESSION['usuario_id']);
$rolUsuario = $_SESSION['rol_id'] ?? null;
$nombreUsuario = $_SESSION['nombre_usuario'] ?? '';

// Inicializar variables de notificaciones
$notificacionesNoLeidas = 0;
$notificacionesRecientes = [];

if ($usuarioLogueado && tienePermiso('notifications.view', $rolUsuario)) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Contar notificaciones no leídas
        $query = "SELECT COUNT(*) as total FROM Notificaciones WHERE UsuarioID = ? AND Leida = 0";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['usuario_id']]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        $notificacionesNoLeidas = $resultado['total'] ?? 0;
        
        // Obtener notificaciones recientes
        $query = "SELECT * FROM Notificaciones WHERE UsuarioID = ? ORDER BY FechaCreacion DESC LIMIT 5";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['usuario_id']]);
        $notificacionesRecientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error al obtener notificaciones: " . $e->getMessage());
    }
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm py-1">
    <div class="container py-1">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-users me-2"></i>Sistema de Juntas
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" href="index.php">
                        <i class="fas fa-home"></i> Inicio
                    </a>
                </li>
                
                <?php if($usuarioLogueado): ?>
                    <!-- Menú Juntas -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= in_array(basename($_SERVER['PHP_SELF']), ['juntas.php', 'misjuntas.php']) ? 'active' : '' ?>" 
                           href="#" id="navbarJuntas" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-users"></i> Juntas
                        </a>
                        <ul class="dropdown-menu">
                            <?php if(tienePermiso('juntas.view', $rolUsuario)): ?>
                                <li><a class="dropdown-item" href="juntas.php"><i class="fas fa-list me-2"></i>Todas las Juntas</a></li>
                            <?php endif; ?>
                            
                            <?php if(tienePermiso('juntas.create', $rolUsuario)): ?>
                                <li><a class="dropdown-item" href="crear_junta.php"><i class="fas fa-plus-circle me-2"></i>Crear Nueva Junta</a></li>
                            <?php endif; ?>
                            
                            <li><a class="dropdown-item" href="misjuntas.php"><i class="fas fa-user-friends me-2"></i>Mis Juntas</a></li>
                            
                            <?php if(tienePermiso('juntas.edit', $rolUsuario)): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="gestion_juntas.php"><i class="fas fa-cog me-2"></i>Gestionar Juntas</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <!-- Nuevo Menú Participantes -->
                    <?php if($authHelper->tienePermiso('participants.view', $rolUsuario) || 
                            $authHelper->tienePermiso('participants.manage', $rolUsuario)): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?= strpos($_SERVER['PHP_SELF'], 'juntas/participantes/') !== false ? 'active' : '' ?>" 
                               href="#" id="navbarParticipantes" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-friends"></i> Participantes
                            </a>
                            <ul class="dropdown-menu">
                                <?php if($authHelper->tienePermiso('participants.view', $rolUsuario)): ?>
                                    <li><a class="dropdown-item" href="<?php echo url('juntas/participantes/'); ?>">
                                        <i class="fas fa-list me-2"></i>Listar Participantes
                                    </a></li>
                                <?php endif; ?>
                                
                                <?php if($authHelper->tienePermiso('participants.manage', $rolUsuario)): ?>
                                    <li><a class="dropdown-item" href="<?php echo url('juntas/participantes/agregar_par.php'); ?>">
                                        <i class="fas fa-user-plus me-2"></i>Agregar Participante
                                    </a></li>
                                <?php endif; ?>
                                
                                <?php if($authHelper->tienePermiso('participants.assign_order', $rolUsuario)): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo url('juntas/participantes/asignar_orden.php'); ?>">
                                        <i class="fas fa-sort-numeric-down me-2"></i>Asignar Orden
                                    </a></li>
                                <?php endif; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <!-- Menú Pagos -->
                    <?php if(tienePermiso('payments.view', $rolUsuario) || tienePermiso('payments.register', $rolUsuario)): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?= in_array(basename($_SERVER['PHP_SELF']), ['pagos.php', 'registrar_pago.php']) ? 'active' : '' ?>" 
                               href="#" id="navbarPagos" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-hand-holding-usd"></i> Pagos
                            </a>
                            <ul class="dropdown-menu">
                                <?php if(tienePermiso('payments.view', $rolUsuario)): ?>
                                    <li><a class="dropdown-item" href="pagos.php"><i class="fas fa-list me-2"></i>Historial de Pagos</a></li>
                                <?php endif; ?>
                                
                                <?php if(tienePermiso('payments.register', $rolUsuario)): ?>
                                    <li><a class="dropdown-item" href="registrar_pago.php"><i class="fas fa-plus-circle me-2"></i>Registrar Pago</a></li>
                                <?php endif; ?>
                                
                                <?php if(tienePermiso('payments.reports', $rolUsuario)): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="reportes_pagos.php"><i class="fas fa-chart-bar me-2"></i>Reportes de Pagos</a></li>
                                <?php endif; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Menú Desembolsos -->
                    <?php if(tienePermiso('disbursements.view', $rolUsuario) || tienePermiso('disbursements.create', $rolUsuario)): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?= basename($_SERVER['PHP_SELF']) == 'desembolsos.php' ? 'active' : '' ?>" 
                               href="#" id="navbarDesembolsos" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-money-bill-wave"></i> Desembolsos
                            </a>
                            <ul class="dropdown-menu">
                                <?php if(tienePermiso('disbursements.view', $rolUsuario)): ?>
                                    <li><a class="dropdown-item" href="desembolsos.php"><i class="fas fa-list me-2"></i>Historial</a></li>
                                <?php endif; ?>
                                
                                <?php if(tienePermiso('disbursements.create', $rolUsuario)): ?>
                                    <li><a class="dropdown-item" href="nuevo_desembolso.php"><i class="fas fa-plus-circle me-2"></i>Nuevo Desembolso</a></li>
                                <?php endif; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Menú Administración -->
                    <?php if(tienePermiso('users.manage', $rolUsuario) || tienePermiso('settings.manage', $rolUsuario)): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?= strpos($_SERVER['PHP_SELF'], 'admin/') !== false ? 'active' : '' ?>" 
                               href="#" id="navbarAdmin" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-cog"></i> Administración
                            </a>
                            <ul class="dropdown-menu">
                                <?php if(tienePermiso('users.manage', $rolUsuario)): ?>
                                    <li><a class="dropdown-item" href="admin/usuarios.php"><i class="fas fa-users-cog me-2"></i>Usuarios</a></li>
                                <?php endif; ?>
                                
                                <?php if(tienePermiso('roles.manage', $rolUsuario)): ?>
                                    <li><a class="dropdown-item" href="admin/roles.php"><i class="fas fa-user-tag me-2"></i>Roles</a></li>
                                <?php endif; ?>
                                
                                <?php if(tienePermiso('settings.manage', $rolUsuario)): ?>
                                    <li><a class="dropdown-item" href="admin/configuracion.php"><i class="fas fa-sliders-h me-2"></i>Configuración</a></li>
                                <?php endif; ?>
                                
                                <?php if(tienePermiso('audit.view', $rolUsuario)): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="admin/auditoria.php"><i class="fas fa-clipboard-list me-2"></i>Auditoría</a></li>
                                <?php endif; ?>
                                
                                <?php if(tienePermiso('reports.generate', $rolUsuario)): ?>
                                    <li><a class="dropdown-item" href="admin/reportes.php"><i class="fas fa-chart-pie me-2"></i>Reportes</a></li>
                                <?php endif; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav ms-auto">
                <?php if($usuarioLogueado): ?>
                    <!-- Notificaciones -->
                    <?php if($usuarioLogueado && tienePermiso('notifications.view', $rolUsuario)): ?>
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
                                <li><a class="dropdown-item text-center" href="notificaciones.php">Ver todas las notificaciones</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Perfil de usuario -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarUser" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($nombreUsuario) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user me-2"></i>Mi Perfil</a></li>
                            
                            <?php if(tienePermiso('guarantees.view', $rolUsuario)): ?>
                                <li><a class="dropdown-item" href="garantias.php"><i class="fas fa-file-contract me-2"></i>Mis Garantías</a></li>
                            <?php endif; ?>
                            
                            <?php if(tienePermiso('account.manage', $rolUsuario)): ?>
                                <li><a class="dropdown-item" href="cuentas_bancarias.php"><i class="fas fa-piggy-bank me-2"></i>Mis Cuentas</a></li>
                            <?php endif; ?>
                            
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : '' ?>" href="login.php">
                            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'registro.php' ? 'active' : '' ?>" href="registro.php">
                            <i class="fas fa-user-plus"></i> Registrarse
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>