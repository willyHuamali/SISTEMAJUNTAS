<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../clases/AuthHelper.php';
require_once __DIR__ . '/../clases/ParticipanteJunta.php';

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
$permisosRequeridos = ['participantesjuntas.view', 'participantesjuntas.manage'];
$tienePermiso = $authHelper->tieneAlgunPermiso($permisosRequeridos, $_SESSION['rol_id']);

if (!$tienePermiso) {
    $_SESSION['mensaje_error'] = "No tienes permisos para ver los participantes de esta junta.";
    header('Location: ' . url('dashboard.php')); // Usar la función url() para consistencia
    exit;
}

// Configuración de paginación
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$registrosPorPagina = 10;

// Obtener parámetros de búsqueda/filtro
$filtroJunta = isset($_GET['junta']) ? $_GET['junta'] : '';
$filtroUsuario = isset($_GET['usuario']) ? $_GET['usuario'] : '';
$filtroEstado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Crear instancia de ParticipanteJunta
$participanteModel = new ParticipanteJunta($db);

// Obtener participantes con filtros
$participantes = $participanteModel->obtenerParticipantesFiltrados(
    $filtroJunta,
    $filtroUsuario,
    $filtroEstado,
    $paginaActual,
    $registrosPorPagina
);

// Obtener total de registros para paginación
$totalParticipantes = $participanteModel->contarParticipantesFiltrados(
    $filtroJunta,
    $filtroUsuario,
    $filtroEstado
);

// Calcular total de páginas
$totalPaginas = ceil($totalParticipantes / $registrosPorPagina);

// Obtener lista de juntas para el filtro
$juntas = $participanteModel->obtenerJuntasActivas();

// Título de la página
$titulo = 'Listado de Participantes';

// Incluir header
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
//require_once '../includes/header.php';
//require_once '../includes/navbar.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $titulo; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo url('index.php'); ?>">Inicio</a></li>
        <li class="breadcrumb-item active"><?php echo $titulo; ?></li>
    </ol>

    <!-- Filtros de búsqueda -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i>
            Filtros de búsqueda
        </div>
        <div class="card-body">
            <form method="get" action="">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="junta" class="form-label">Junta</label>
                        <select class="form-select" id="junta" name="junta">
                            <option value="">Todas las juntas</option>
                            <?php foreach ($juntas as $junta): ?>
                                <option value="<?php echo htmlspecialchars($junta['JuntaID']); ?>" 
                                    <?php echo $filtroJunta == $junta['JuntaID'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($junta['NombreJunta']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="usuario" class="form-label">Usuario</label>
                        <input type="text" class="form-control" id="usuario" name="usuario" 
                               value="<?php echo htmlspecialchars($filtroUsuario); ?>" 
                               placeholder="Nombre o DNI del usuario">
                    </div>
                    <div class="col-md-4">
                        <label for="estado" class="form-label">Estado</label>
                        <select class="form-select" id="estado" name="estado">
                            <option value="">Todos</option>
                            <option value="1" <?php echo $filtroEstado === '1' ? 'selected' : ''; ?>>Activo</option>
                            <option value="0" <?php echo $filtroEstado === '0' ? 'selected' : ''; ?>>Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i> Buscar
                    </button>
                    <a href="<?php echo url('participantes/participantes.php'); ?>" class="btn btn-secondary">
                        <i class="fas fa-undo me-1"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de participantes -->
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-users me-1"></i>
                    Participantes
                </div>
                <?php if ($authHelper->tienePermiso('participantesjuntas.export', $_SESSION['rol_id'])): ?>
                    <a href="<?php echo url('participantes/exportar.php?'.http_build_query($_GET)); ?>" 
                    class="btn btn-success btn-sm">
                        <i class="fas fa-file-export me-1"></i> Exportar
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($participantes)): ?>
                <div class="alert alert-info">No se encontraron participantes con los filtros seleccionados.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>DNI</th>
                                <th>Junta</th>
                                <th>Orden</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($participantes as $participante): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($participante['ParticipanteID']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($participante['Nombre'] . ' ' . $participante['Apellido']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($participante['DNI']); ?></td>
                                    <td><?php echo htmlspecialchars($participante['NombreJunta']); ?></td>
                                    <td><?php echo htmlspecialchars($participante['OrdenRecepcion']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $participante['Activo'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $participante['Activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="<?php echo url('participantes/detalle.php?id=' . $participante['ParticipanteID']); ?>" 
                                               class="btn btn-sm btn-info" title="Ver detalle">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($authHelper->tienePermiso('participantesjuntas.edit', $_SESSION['rol_id'])): ?>
                                                <a href="<?php echo url('participantes/editar.php?id=' . $participante['ParticipanteID']); ?>" 
                                                class="btn btn-sm btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($authHelper->tienePermiso('participantesjuntas.manage_guarantees', $_SESSION['rol_id']) && $participante['GarantiaID']): ?>
                                                <a href="<?php echo url('participantes/garantias.php?id=' . $participante['GarantiaID']); ?>" 
                                                class="btn btn-sm btn-primary" title="Ver garantía">
                                                    <i class="fas fa-file-contract"></i>
                                                </a>
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
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($paginaActual > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" 
                                       href="<?php echo url('participantes/participantes.php?'.http_build_query(array_merge($_GET, ['pagina' => $paginaActual - 1]))); ?>">
                                        Anterior
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                                <li class="page-item <?php echo $i == $paginaActual ? 'active' : ''; ?>">
                                    <a class="page-link" 
                                       href="<?php echo url('participantes/participantes.php?'.http_build_query(array_merge($_GET, ['pagina' => $i]))); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($paginaActual < $totalPaginas): ?>
                                <li class="page-item">
                                    <a class="page-link" 
                                       href="<?php echo url('participantes/participantes.php?'.http_build_query(array_merge($_GET, ['pagina' => $paginaActual + 1]))); ?>">
                                        Siguiente
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Incluir footer
include __DIR__ . '/../includes/footer.php';
?>