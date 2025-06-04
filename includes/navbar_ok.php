
<?php
// Verificar si el usuario está logueado
require_once __DIR__ . '/functions.php';
$usuarioLogueado = isset($_SESSION['usuario_id']);
$rolUsuario = $_SESSION['rol_id'] ?? null;
$nombreUsuario = $_SESSION['nombre_usuario'] ?? '';

// El resto del código del navbar...
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
                    <?php if(tienePermiso('notifications.view', $rolUsuario)): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link position-relative" href="#" id="navbarNotificaciones" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-bell"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    3 <span class="visually-hidden">notificaciones no leídas</span>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end dropdown-notifications">
                                <li><h6 class="dropdown-header">Notificaciones</h6></li>
                                <li><a class="dropdown-item" href="#">Pago pendiente para la junta #123</a></li>
                                <li><a class="dropdown-item" href="#">Nuevo mensaje del coordinador</a></li>
                                <li><a class="dropdown-item" href="#">Recordatorio: Desembolso mañana</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="notificaciones.php">Ver todas</a></li>
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