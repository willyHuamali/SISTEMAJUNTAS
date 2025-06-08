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

// Versión corregida de la validación de permisos
$permisosRequeridos = ['participantesjuntas.view', 'participantesjuntas.manage'];
$tienePermiso = $authHelper->tieneAlgunPermiso($permisosRequeridos, $_SESSION['rol_id']);

if (!$tienePermiso) {
    $_SESSION['mensaje_error'] = "No tienes permisos para ver los participantes de esta junta.";
    header('Location: ' . url('dashboard.php')); // Usar la función url() para consistencia
    exit;
}

// Obtener el ID de la junta desde la URL
$juntaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($juntaId <= 0) {
    $_SESSION['mensaje_error'] = "No se ha especificado una junta válida.";
    header('Location: /sistemajuntas/dashboard.php'); // Mejor redirigir al dashboard
    exit;
}

// Instanciar la clase ParticipanteJunta con la conexión a la base de datos
$participanteJunta = new ParticipanteJunta($db);

// Obtener información de la junta y participantes con mejor manejo de errores
try {
    $junta = $participanteJunta->obtenerJuntaPorId($juntaId);
    if (!$junta) {
        throw new Exception("La junta especificada no existe.");
    }
    
    $participantes = $participanteJunta->obtenerParticipantesPorJunta($juntaId);
} catch (Exception $e) {
    $_SESSION['mensaje_error'] = "Error al obtener los datos: " . $e->getMessage();
    header('Location: /sistemajuntas/dashboard.php'); // Redirigir al dashboard
    exit;
}

// Incluir cabecera
$tituloPagina = "Participantes de Junta: " . htmlspecialchars($junta['NombreJunta']);
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Resto del código HTML  -->

<div class="container mt-4">
    <!-- Mensajes de retroalimentación -->
    <?php if (isset($_SESSION['mensaje_exito'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['mensaje_exito']; unset($_SESSION['mensaje_exito']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['mensaje_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['mensaje_error']; unset($_SESSION['mensaje_error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="fas fa-users"></i> <?php echo htmlspecialchars($tituloPagina); ?></h2>
            <p class="text-muted">Gestiona los participantes de esta junta</p>
        </div>
        <div class="col-md-6 text-end">
            <a href="/sistemajuntas/juntas/ver_junta.php?id=<?php echo $juntaId; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver a la junta
            </a>
            <?php if (verificarPermiso($usuarioId, 'AGREGAR_PARTICIPANTE')): ?>
                <a href="/sistemajuntas/participantes/agregar_par.php?junta_id=<?php echo $juntaId; ?>" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Agregar Participante
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Información de la Junta</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <p><strong>Código:</strong> <?php echo htmlspecialchars($junta['CodigoJunta']); ?></p>
                    <p><strong>Monto de Aporte:</strong> S/ <?php echo number_format($junta['MontoAporte'], 2); ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Frecuencia:</strong> <?php echo htmlspecialchars($junta['FrecuenciaPago']); ?></p>
                    <p><strong>Fecha Inicio:</strong> <?php echo date('d/m/Y', strtotime($junta['FechaInicio'])); ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Estado:</strong> <span class="badge bg-<?php echo $junta['Estado'] == 'Activa' ? 'success' : ($junta['Estado'] == 'Cerrada' ? 'info' : 'danger'); ?>">
                        <?php echo htmlspecialchars($junta['Estado']); ?>
                    </span></p>
                    <p><strong>Participantes:</strong> <?php echo count($participantes) . ' / ' . ($junta['MaximoParticipantes'] > 0 ? $junta['MaximoParticipantes'] : 'Sin límite'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-list"></i> Lista de Participantes</h5>
        </div>
        <div class="card-body">
            <?php if (empty($participantes)): ?>
                <div class="alert alert-info">
                    No hay participantes registrados en esta junta.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tablaParticipantes">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Participante</th>
                                <th>DNI</th>
                                <th>Teléfono</th>
                                <th>Orden de Recepción</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($participantes as $index => $participante): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($participante['Nombre'] . ' ' . $participante['Apellido']); ?>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($participante['Email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($participante['DNI']); ?></td>
                                    <td><?php echo htmlspecialchars($participante['Telefono']); ?></td>
                                    <td>
                                        <?php if (verificarPermiso($usuarioId, 'ASIGNAR_ORDEN_PARTICIPANTE') && $junta['Estado'] == 'Activa'): ?>
                                            <form class="form-actualizar-orden" method="post" action="/sistema-juntas/participantes/asignar_order_par.php">
                                                <input type="hidden" name="participante_id" value="<?php echo $participante['ParticipanteID']; ?>">
                                                <input type="hidden" name="junta_id" value="<?php echo $juntaId; ?>">
                                                <div class="input-group input-group-sm">
                                                    <input type="number" name="nuevo_orden" class="form-control" 
                                                        value="<?php echo $participante['OrdenRecepcion']; ?>" min="1" 
                                                        max="<?php echo count($participantes); ?>">
                                                    <button type="submit" class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-save"></i>
                                                    </button>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <?php echo $participante['OrdenRecepcion']; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $participante['Activo'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $participante['Activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="/sistema-juntas/participantes/ver_participante.php?id=<?php echo $participante['ParticipanteID']; ?>" 
                                               class="btn btn-info" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <?php if (verificarPermiso($usuarioId, 'EDITAR_PARTICIPANTE') && $junta['Estado'] == 'Activa'): ?>
                                                <a href="/sistema-juntas/participantes/editar_par.php?id=<?php echo $participante['ParticipanteID']; ?>" 
                                                   class="btn btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (verificarPermiso($usuarioId, 'ELIMINAR_PARTICIPANTE') && $junta['Estado'] == 'Activa'): ?>
                                                <button class="btn btn-danger btn-eliminar" 
                                                        data-id="<?php echo $participante['ParticipanteID']; ?>"
                                                        data-nombre="<?php echo htmlspecialchars($participante['Nombre'] . ' ' . $participante['Apellido']); ?>"
                                                        title="Eliminar">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
<div class="modal fade" id="confirmarEliminarModal" tabindex="-1" aria-labelledby="confirmarEliminarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="confirmarEliminarModalLabel">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar al participante <strong id="nombreParticipante"></strong>?</p>
                <p class="text-danger">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="formEliminar" method="post" action="/sistema-juntas/participantes/eliminar_par.php">
                    <input type="hidden" name="participante_id" id="participanteId">
                    <input type="hidden" name="junta_id" value="<?php echo $juntaId; ?>">
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
