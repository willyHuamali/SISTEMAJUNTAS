<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../clases/Junta.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../clases/AuthHelper.php';

verificarAutenticacion();
verificarInactividad();

// Inicializar Database y AuthHelper
$database = new Database();
$db = $database->getConnection();
$authHelper = new \Clases\AuthHelper($db);

$junta = new Junta($db);

// Obtener parámetros de búsqueda/filtro
$busqueda = $_GET['busqueda'] ?? '';
$estado = $_GET['estado'] ?? 'Todas'; // Cambiado para que "Todas" sea el valor por defecto

// Definir estados válidos según la base de datos
$estadosValidos = ['Activa', 'En progreso', 'Completada', 'Cancelada'];

// Manejar el filtro de estados
if ($estado === 'Todas') {
    $estadosMostrar = $estadosValidos; // Mostrar todos los estados válidos
} elseif (in_array($estado, $estadosValidos)) {
    $estadosMostrar = [$estado]; // Estado específico seleccionado
} else {
    // Estado no válido, mostrar todas
    $estadosMostrar = $estadosValidos;
    $_SESSION['error'] = 'El estado seleccionado no es válido';
}

$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$registrosPorPagina = 12;

// Obtener solo juntas del usuario logueado
$juntas = $junta->obtenerJuntasPorUsuario($_SESSION['usuario_id'], $busqueda, $estadosMostrar, $pagina, $registrosPorPagina);
$totalJuntas = $junta->contarJuntasPorUsuario($_SESSION['usuario_id'], $busqueda, $estadosMostrar);

$totalPaginas = ceil($totalJuntas / $registrosPorPagina);

// Mensajes
$mensaje_exito = $_SESSION['mensaje_exito'] ?? '';
unset($_SESSION['mensaje_exito']);

$mensaje_error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="fas fa-users me-2"></i>Mis Juntas</h2>
        </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-8">
                    <input type="text" name="busqueda" class="form-control" placeholder="Buscar por nombre o código..." value="<?= htmlspecialchars($busqueda) ?>">
                </div>
                <div class="col-md-2">
                    <select name="estado" class="form-select">
                        <option value="Todas" <?= $estado === 'Todas' ? 'selected' : '' ?>>Mostrar todas</option>
                        <option value="Activa" <?= $estado === 'Activa' ? 'selected' : '' ?>>Solo Activas</option>
                        <option value="En progreso" <?= $estado === 'En progreso' ? 'selected' : '' ?>>Solo En progreso</option>
                        <option value="Completada" <?= $estado === 'Completada' ? 'selected' : '' ?>>Completadas</option>
                        <option value="Cancelada" <?= $estado === 'Cancelada' ? 'selected' : '' ?>>Canceladas</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if ($mensaje_exito): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $mensaje_exito ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($mensaje_error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $mensaje_error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Listado de juntas -->
    <div class="row">
        <?php if (!empty($juntas)): ?>
            <?php foreach ($juntas as $jun): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-<?= 
                        $jun['Estado'] == 'Activa' ? 'success' : 
                        ($jun['Estado'] == 'En progreso' ? 'primary' : 
                        ($jun['Estado'] == 'Completada' ? 'secondary' : 'danger'))
                    ?>">
                        <div class="card-header bg-<?= 
                            $jun['Estado'] == 'Activa' ? 'success' : 
                            ($jun['Estado'] == 'En progreso' ? 'primary' : 
                            ($jun['Estado'] == 'Completada' ? 'secondary' : 'danger'))
                        ?> text-white">
                            <h5 class="card-title mb-0"><?= htmlspecialchars($jun['NombreJunta']) ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Código:</span>
                                <strong><?= htmlspecialchars($jun['CodigoJunta']) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Monto:</span>
                                <strong>S/ <?= number_format($jun['MontoAporte'], 2) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Participantes:</span>
                                <strong><?= $jun['NumeroParticipantes'] ?> / <?= $jun['MaximoParticipantes'] ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Estado:</span>
                                <span class="badge bg-<?= 
                                    $jun['Estado'] == 'Activa' ? 'success' : 
                                    ($jun['Estado'] == 'Completada' ? 'secondary' : 
                                    ($jun['Estado'] == 'En progreso' ? 'primary' : 'danger'))
                                ?>">
                                    <?= htmlspecialchars($jun['Estado']) ?>
                                </span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Inicio:</span>
                                <span><?= date('d/m/Y', strtotime($jun['FechaInicio'])) ?></span>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="d-flex justify-content-center">
                                <a href="ver_junta.php?id=<?= $jun['JuntaID'] ?>" class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                    <i class="fas fa-eye"></i> Detalles
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">
                    No se encontraron juntas con los criterios de búsqueda seleccionados.
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Paginación -->
    <?php if ($totalPaginas > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if ($pagina > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])) ?>" aria-label="Anterior">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                    <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($pagina < $totalPaginas): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])) ?>" aria-label="Siguiente">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>