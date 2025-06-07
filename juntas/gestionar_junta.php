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

// Verificar permisos - solo administradores pueden gestionar juntas
if (!$authHelper->tienePermiso('juntas.manage_all', $_SESSION['rol_id'])) {
    $_SESSION['error'] = 'No tienes permiso para acceder a esta sección';
    header('Location: index_junta.php');
    exit;
}

$junta = new Junta($db);

// Obtener parámetros de búsqueda/filtro
$busqueda = $_GET['busqueda'] ?? '';
$estado = $_GET['estado'] ?? 'Todas';

// Definir estados válidos según la base de datos
$estadosValidos = ['Activa', 'En progreso', 'Completada', 'Cancelada'];

// Manejar el caso "Todas" y validar estados
if ($estado === 'Todas') {
    $estadosMostrar = $estadosValidos; // Mostrar todos los estados válidos
} elseif (in_array($estado, $estadosValidos)) {
    $estadosMostrar = [$estado]; // Estado específico seleccionado y validado
} else {
    // Estado no válido, usar valor por defecto
    $estadosMostrar = $estadosValidos;
    $_SESSION['error'] = 'El estado seleccionado no es válido';
}

$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$registrosPorPagina = 15;

// Obtener todas las juntas para administración
$juntas = $junta->obtenerTodasLasJuntas($busqueda, $estadosMostrar, $pagina, $registrosPorPagina);
$totalJuntas = $junta->contarTodasLasJuntas($busqueda, $estadosMostrar);

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
        <div class="col-md-8">
            <h2><i class="fas fa-tasks me-2"></i>Gestión Completa de Juntas</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="index_junta.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver al listado
            </a>
        </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-6">
                    <input type="text" name="busqueda" class="form-control" placeholder="Buscar por nombre, código o creador..." value="<?= htmlspecialchars($busqueda) ?>">
                </div>
                <div class="col-md-3">
                    <select name="estado" class="form-select">
                        <option value="Todas" <?= $estado === 'Todas' ? 'selected' : '' ?>>Todas las juntas</option>
                        <option value="Activa" <?= $estado === 'Activa' ? 'selected' : '' ?>>Solo Activas</option>
                        <option value="En progreso" <?= $estado === 'En progreso' ? 'selected' : '' ?>>Solo En progreso</option>
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

    <!-- Listado de juntas para administración -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Código</th>
                            <th>Creador</th>
                            <th>Participantes</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th>Inicio</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($juntas)): ?>
                            <?php foreach ($juntas as $jun): ?>
                                <tr>
                                    <td><?= $jun['JuntaID'] ?></td>
                                    <td><?= htmlspecialchars($jun['NombreJunta']) ?></td>
                                    <td><?= htmlspecialchars($jun['CodigoJunta']) ?></td>
                                    <td><?= htmlspecialchars($jun['CreadorNombre']) ?></td>
                                    <td><?= $jun['NumeroParticipantes'] ?> / <?= $jun['MaximoParticipantes'] ?></td>
                                    <td>S/ <?= number_format($jun['MontoAporte'], 2) ?></td>
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
                                            <?php if ($jun['Estado'] != 'Completada' && $jun['Estado'] != 'Cancelada'): ?>
                                                <a href="editar_junta.php?id=<?= $jun['JuntaID'] ?>" class="btn btn-sm btn-outline-secondary me-1" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($jun['Estado'] == 'Activa'): ?>
                                                <form action="procesar_estado.php" method="post" class="d-inline me-1">
                                                    <input type="hidden" name="id" value="<?= $jun['JuntaID'] ?>">
                                                    <input type="hidden" name="accion" value="cancelar">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancelar junta" onclick="return confirm('¿Estás seguro de cancelar esta junta?')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($jun['Estado'] == 'Activa' && isset($jun['NumeroParticipantes']) && $jun['NumeroParticipantes'] >= 3): ?>
                                                <form action="procesar_estado.php" method="post" class="d-inline">
                                                    <input type="hidden" name="id" value="<?= $jun['JuntaID'] ?>">
                                                    <input type="hidden" name="accion" value="iniciar">
                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Iniciar junta" onclick="return confirm('¿Estás seguro de iniciar esta junta?')">
                                                        <i class="fas fa-play"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">
                                    <div class="alert alert-info mb-0">
                                        No se encontraron juntas con los criterios de búsqueda seleccionados.
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Paginación -->
    <?php if ($totalPaginas > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
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