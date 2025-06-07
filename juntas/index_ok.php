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

// Verificar permisos
if (!$authHelper->tienePermiso('juntas.view', $_SESSION['rol_id'])) {
    $_SESSION['error'] = 'No tienes permiso para acceder a esta sección';
    header('Location: ../index.php');
    exit;
}

$junta = new Junta($db);

// Obtener parámetros de búsqueda/filtro
$busqueda = $_GET['busqueda'] ?? '';
$estado = $_GET['estado'] ?? '';
$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$registrosPorPagina = 10;

// Obtener juntas según permisos
if ($authHelper->tienePermiso('juntas.manage_all', $_SESSION['rol_id'])) {
    // Administrador ve todas las juntas
    $juntas = $junta->obtenerTodasLasJuntas($busqueda, $estado, $pagina, $registrosPorPagina);
    $totalJuntas = $junta->contarTodasLasJuntas($busqueda, $estado);
} else {
    // Usuarios normales ven solo juntas en las que participan
    $juntas = $junta->obtenerJuntasPorUsuario($_SESSION['usuario_id'], $busqueda, $estado, $pagina, $registrosPorPagina);
    $totalJuntas = $junta->contarJuntasPorUsuario($_SESSION['usuario_id'], $busqueda, $estado);
}

$totalPaginas = ceil($totalJuntas / $registrosPorPagina);

// Mostrar mensajes de éxito/error
$mensaje_exito = $_SESSION['mensaje_exito'] ?? '';
unset($_SESSION['mensaje_exito']);

$mensaje_error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-users me-2"></i>Listado de Juntas</h2>
        </div>
        <div class="col-md-4 text-end">
            <?php if ($authHelper->tienePermiso('juntas.create', $_SESSION['rol_id'])): ?>
                <a href="crear_junta.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-1"></i> Nueva Junta
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-6">
                    <input type="text" name="busqueda" class="form-control" placeholder="Buscar por nombre o código..." value="<?= htmlspecialchars($busqueda) ?>">
                </div>
                <div class="col-md-3">
                    <select name="estado" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="Activa" <?= $estado === 'Activa' ? 'selected' : '' ?>>Activas</option>
                        <option value="En progreso" <?= $estado === 'En progreso' ? 'selected' : '' ?>>En progreso</option>
                        <option value="Completada" <?= $estado === 'Completada' ? 'selected' : '' ?>>Completadas</option>
                        <option value="Cancelada" <?= $estado === 'Cancelada' ? 'selected' : '' ?>>Canceladas</option>
                    </select>
                </div>
                <div class="col-md-3">
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
    <div class="card">
        <div class="card-body p-0">
            <?php if (!empty($juntas)): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th>Código</th>
                                <th>Monto</th>
                                <th>Participantes</th>
                                <th>Estado</th>
                                <th>Fecha Inicio</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($juntas as $jun): ?>
                                <tr>
                                    <td>
                                        <a href="ver_junta.php?id=<?= $jun['JuntaID'] ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($jun['NombreJunta']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($jun['CodigoJunta']) ?></td>
                                    <td>S/ <?= number_format($jun['MontoAporte'], 2) ?></td>
                                    <td><?= $jun['NumeroParticipantes'] ?> / <?= $jun['MaximoParticipantes'] ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $jun['Estado'] == 'Activa' ? 'success' : 
                                            ($jun['Estado'] == 'Completada' ? 'secondary' : 
                                            ($jun['Estado'] == 'En progreso' ? 'primary' : 'danger'))
                                        ?>">
                                            <?= htmlspecialchars($jun['Estado']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($jun['FechaInicio'])) ?></td>
                                    <td>
                                        <div class="d-flex">
                                            <a href="ver_junta.php?id=<?= $jun['JuntaID'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($authHelper->tienePermiso('juntas.edit', $_SESSION['rol_id']) && $jun['Estado'] != 'Completada' && $jun['Estado'] != 'Cancelada'): ?>
                                                <a href="editar_junta.php?id=<?= $jun['JuntaID'] ?>" class="btn btn-sm btn-outline-secondary me-1" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($authHelper->tienePermiso('juntas.manage_all', $_SESSION['rol_id']) && $jun['Estado'] == 'Activa'): ?>
                                                <form action="procesar_estado.php" method="post" class="d-inline">
                                                    <input type="hidden" name="id" value="<?= $jun['JuntaID'] ?>">
                                                    <input type="hidden" name="accion" value="cancelar">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancelar junta" onclick="return confirm('¿Estás seguro de cancelar esta junta?')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <?php if ($totalPaginas > 1): ?>
                    <nav class="p-3">
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($pagina > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])) ?>">
                                        <i class="fas fa-chevron-left"></i>
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
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])) ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="p-4 text-center">
                    <div class="alert alert-info mb-0">
                        No se encontraron juntas con los criterios de búsqueda seleccionados.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>