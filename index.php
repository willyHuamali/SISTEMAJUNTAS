<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/clases/Junta.php';

// Verificar si el usuario está logueado
$usuarioLogueado = isset($_SESSION['usuario_id']);

// Obtener las juntas más recientes
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $junta = new Junta($db);
    $juntasRecientes = $junta->obtenerJuntasRecientes(6);
} catch(Exception $e) {
    $error = "Error al cargar las juntas: " . $e->getMessage();
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container py-5 fade-in">
    <!-- Hero Section -->
    <div class="row align-items-center mb-5">
        <div class="col-lg-6">
            <h1 class="display-4 fw-bold mb-3">Sistema de Juntas Comunitarias</h1>
            <p class="lead mb-4">Organiza, participa y administra juntas de ahorro de manera sencilla y segura.</p>
            <div class="d-flex gap-3">
                <?php if($usuarioLogueado): ?>
                    <a href="crear-junta.php" class="btn btn-primary btn-lg px-4">
                        <i class="fas fa-plus me-2"></i>Crear Nueva Junta
                    </a>
                <?php else: ?>
                    <a href="registro.php" class="btn btn-primary btn-lg px-4">
                        <i class="fas fa-user-plus me-2"></i>Regístrate Gratis
                    </a>
                    <a href="login.php" class="btn btn-outline-primary btn-lg px-4">
                        <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-6 d-none d-lg-block">
            <img src="assets/img/juntas-hero.png" alt="Sistema de Juntas" class="img-fluid rounded-3 shadow">
        </div>
    </div>

    <!-- Juntas Recientes -->
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4"><i class="fas fa-users me-2"></i>Juntas Recientes</h2>
            <a href="juntas.php" class="btn btn-sm btn-outline-primary">Ver todas</a>
        </div>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php elseif(empty($juntasRecientes)): ?>
            <div class="alert alert-info">No hay juntas disponibles actualmente.</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach($juntasRecientes as $junta): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header bg-<?= $junta['Estado'] == 'Activa' ? 'success' : 'secondary' ?> text-white">
                                <div class="d-flex justify-content-between">
                                    <span><?= htmlspecialchars($junta['NombreJunta']) ?></span>
                                    <span class="badge bg-light text-dark"><?= htmlspecialchars($junta['Estado']) ?></span>
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="card-text"><?= htmlspecialchars($junta['Descripcion']) ?></p>
                                <ul class="list-group list-group-flush mb-3">
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Aporte:</span>
                                        <strong>S/ <?= number_format($junta['MontoAporte'], 2) ?></strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Frecuencia:</span>
                                        <span><?= htmlspecialchars($junta['FrecuenciaPago']) ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Inicio:</span>
                                        <span><?= date('d/m/Y', strtotime($junta['FechaInicio'])) ?></span>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">Código: <?= htmlspecialchars($junta['CodigoJunta']) ?></small>
                                    <a href="junta.php?id=<?= $junta['JuntaID'] ?>" class="btn btn-sm btn-outline-primary">
                                        Ver detalles
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Beneficios -->
    <section class="mb-5">
        <h2 class="h4 mb-4"><i class="fas fa-star me-2"></i>Beneficios de Nuestro Sistema</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 mb-3 mx-auto" style="width: 60px; height: 60px;">
                            <i class="fas fa-shield-alt fa-2x"></i>
                        </div>
                        <h5 class="card-title">Seguridad Garantizada</h5>
                        <p class="card-text">Todas las transacciones están protegidas con encriptación de última generación.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 mb-3 mx-auto" style="width: 60px; height: 60px;">
                            <i class="fas fa-chart-line fa-2x"></i>
                        </div>
                        <h5 class="card-title">Control Total</h5>
                        <p class="card-text">Administra y monitorea todas tus juntas desde un solo lugar.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 mb-3 mx-auto" style="width: 60px; height: 60px;">
                            <i class="fas fa-bell fa-2x"></i>
                        </div>
                        <h5 class="card-title">Notificaciones</h5>
                        <p class="card-text">Recibe alertas de pagos, desembolsos y eventos importantes.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php
require_once 'includes/footer.php';
?>