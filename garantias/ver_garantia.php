<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../clases/AuthHelper.php';

// Verificar autenticación e inactividad
verificarAutenticacion();
verificarInactividad();

// Inicializar Database y AuthHelper
$database = new Database();
$db = $database->getConnection();
$authHelper = new \Clases\AuthHelper($db);

// Obtener ID de usuario y rol de la sesión
$usuarioId = $_SESSION['usuario_id'] ?? null;
$rolId = $_SESSION['rol_id'] ?? null;

// Verificar permisos
if (!$authHelper->tienePermiso('garantias.view_own', $rolId) && !$authHelper->tienePermiso('garantias.view_all', $rolId)) {
    header('Location: ../index.php?error=permisos');
    exit();
}

// Verificar que se haya proporcionado un ID de garantía
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index_garantia.php?error=id_invalido');
    exit();
}

$garantiaId = $_GET['id'];

// Obtener los datos de la garantía
if ($authHelper->tienePermiso('garantias.view_all', $rolId)) {
    // Administrador puede ver cualquier garantía
    $query = "SELECT g.*, u.Nombre, u.Apellido, u.Email, u.Telefono 
              FROM Garantias g 
              JOIN Usuarios u ON g.UsuarioID = u.UsuarioID 
              WHERE g.GarantiaID = ?";
} else {
    // Usuario normal solo puede ver sus propias garantías
    $query = "SELECT g.*, u.Nombre, u.Apellido, u.Email, u.Telefono 
              FROM Garantias g 
              JOIN Usuarios u ON g.UsuarioID = u.UsuarioID 
              WHERE g.GarantiaID = ? AND g.UsuarioID = ?";
}

$stmt = $db->prepare($query);

if ($authHelper->tienePermiso('garantias.view_all', $rolId)) {
    $stmt->bindParam(1, $garantiaId, PDO::PARAM_INT);
} else {
    $stmt->bindParam(1, $garantiaId, PDO::PARAM_INT);
    $stmt->bindParam(2, $usuarioId, PDO::PARAM_INT);
}

$stmt->execute();
$garantia = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar que la garantía existe y el usuario tiene permiso para verla
if (!$garantia) {
    header('Location: index_garantia.php?error=garantia_no_encontrada');
    exit();
}

$tituloPagina = "Detalles de Garantía";
require_once '../includes/header.php';
require_once '../includes/navbar.php';

// Mostrar mensaje de éxito si existe
if (isset($_SESSION['mensaje_exito'])) {
    echo '<div class="alert alert-'.$_SESSION['mensaje_exito']['tipo'].' alert-dismissible fade show">
        <strong>'.$_SESSION['mensaje_exito']['titulo'].'</strong> '.$_SESSION['mensaje_exito']['texto'].'
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
    unset($_SESSION['mensaje_exito']);
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><?php echo $tituloPagina; ?></h2>
        <a href="index_garantia.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al listado
        </a>
    </div>
    
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Información General</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <h5 class="text-muted">Tipo de Garantía</h5>
                        <p><?php echo htmlspecialchars($garantia['TipoGarantia']); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <h5 class="text-muted">Descripción</h5>
                        <p><?php echo htmlspecialchars($garantia['Descripcion']); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <h5 class="text-muted">Valor Estimado</h5>
                        <p><?php echo formatearMoneda($garantia['ValorEstimado']); ?></p>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <h5 class="text-muted">Estado</h5>
                        <span class="badge bg-<?php 
                            if ($garantia['Estado'] == 'Activa') {
                                echo 'success';
                            } elseif ($garantia['Estado'] == 'Retenida') {
                                echo 'warning';
                            } else {
                                echo 'secondary';
                            }
                        ?>">
                            <?php echo $garantia['Estado']; ?>
                        </span>
                    </div>
                    
                    <div class="mb-3">
                        <h5 class="text-muted">Fecha de Registro</h5>
                        <p><?php echo formatoFecha($garantia['FechaRegistro']); ?></p>
                    </div>
                    
                    <?php if (!empty($garantia['FechaVencimiento'])): ?>
                    <div class="mb-3">
                        <h5 class="text-muted">Fecha de Vencimiento</h5>
                        <p><?php echo formatoFecha($garantia['FechaVencimiento']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($authHelper->tienePermiso('garantias.view_all', $rolId)): ?>
    <div class="card mt-4">
        <div class="card-header bg-info text-white">
            <h4 class="mb-0">Información del Usuario</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <h5 class="text-muted">Nombre Completo</h5>
                        <p><?php echo htmlspecialchars($garantia['Nombre'] . ' ' . $garantia['Apellido']); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <h5 class="text-muted">Email</h5>
                        <p><?php echo htmlspecialchars($garantia['Email']); ?></p>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <h5 class="text-muted">Teléfono</h5>
                        <p><?php echo htmlspecialchars($garantia['Telefono']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Sección de documentos adjuntos -->
    <div class="card mt-4">
        <div class="card-header bg-secondary text-white">
            <h4 class="mb-0">Documentos Adjuntos</h4>
        </div>
        <div class="card-body">
            <?php 
            // Obtener documentos adjuntos
            $queryDocs = "SELECT * FROM DocumentosGarantias WHERE GarantiaID = ?";
            $stmtDocs = $db->prepare($queryDocs);
            $stmtDocs->bindParam(1, $garantiaId, PDO::PARAM_INT);
            $stmtDocs->execute();
            $documentos = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($documentos) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nombre del Documento</th>
                                <th>Tipo</th>
                                <th>Fecha de Subida</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documentos as $doc): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($doc['NombreDocumento']); ?></td>
                                <td><?php echo htmlspecialchars($doc['TipoDocumento']); ?></td>
                                <td><?php echo formatoFecha($doc['FechaSubida']); ?></td>
                                <td>
                                    <a href="../uploads/garantias/<?php echo $doc['RutaDocumento']; ?>" 
                                       target="_blank" 
                                       class="btn btn-sm btn-primary" 
                                       title="Ver Documento">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="../uploads/garantias/<?php echo $doc['RutaDocumento']; ?>" 
                                       download 
                                       class="btn btn-sm btn-success" 
                                       title="Descargar">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No hay documentos adjuntos para esta garantía.
                </div>
            <?php endif; ?>
            
            <?php if ($authHelper->tienePermiso('garantias.upload_docs', $rolId) && 
                     ($garantia['UsuarioID'] == $_SESSION['usuario_id'] || $authHelper->tienePermiso('garantias.manage_all', $rolId))): ?>
                <div class="mt-3">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalSubirDocumento">
                        <i class="fas fa-upload"></i> Subir Documento
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Historial de cambios -->
    <div class="card mt-4">
        <div class="card-header bg-dark text-white">
            <h4 class="mb-0">Historial de Cambios</h4>
        </div>
        <div class="card-body">
            <?php 
            // Obtener historial de cambios
            $queryHistorial = "SELECT h.*, u.Nombre, u.Apellido 
                              FROM HistorialGarantias h 
                              JOIN Usuarios u ON h.UsuarioID = u.UsuarioID 
                              WHERE h.GarantiaID = ? 
                              ORDER BY h.FechaCambio DESC";
            $stmtHistorial = $db->prepare($queryHistorial);
            $stmtHistorial->bindParam(1, $garantiaId, PDO::PARAM_INT);
            $stmtHistorial->execute();
            $historial = $stmtHistorial->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($historial) > 0): ?>
                <div class="timeline">
                    <?php foreach ($historial as $cambio): ?>
                    <div class="timeline-item">
                        <div class="timeline-point"></div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <span class="text-primary fw-bold"><?php echo htmlspecialchars($cambio['Nombre'] . ' ' . $cambio['Apellido']); ?></span>
                                <span class="text-muted ms-2"><?php echo formatoFechaHora($cambio['FechaCambio']); ?></span>
                            </div>
                            <div class="timeline-body">
                                <p><?php echo htmlspecialchars($cambio['DescripcionCambio']); ?></p>
                                <?php if (!empty($cambio['DetallesAdicionales'])): ?>
                                <div class="alert alert-light p-2 mt-2">
                                    <small><?php echo htmlspecialchars($cambio['DetallesAdicionales']); ?></small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No hay registro de cambios para esta garantía.
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Botones de acción -->
    <div class="mt-4 d-flex justify-content-between">
        <div>
            <?php if ($authHelper->tienePermiso('garantias.edit', $rolId) && 
                     ($garantia['UsuarioID'] == $_SESSION['usuario_id'] || $authHelper->tienePermiso('garantias.manage_all', $rolId))): ?>
                <a href="editar_garantia.php?id=<?php echo $garantiaId; ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Editar Garantía
                </a>
            <?php endif; ?>
        </div>
        
        <div>
            <?php if (
                $authHelper->tienePermiso('garantias.change_status', $rolId) &&
                ($garantia['UsuarioID'] == $_SESSION['usuario_id'] || $authHelper->tienePermiso('garantias.manage_all', $rolId))
            ): ?>
                <?php if ($garantia['Estado'] == 'Activa'): ?>
                    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalCambiarEstado">
                        <i class="fas fa-times-circle"></i> Cancelar Garantía
                    </button>
                <?php elseif ($garantia['Estado'] == 'Cancelada'): ?>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCambiarEstado">
                        <i class="fas fa-check-circle"></i> Reactivar Garantía
                    </button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para subir documento -->
<div class="modal fade" id="modalSubirDocumento" tabindex="-1" aria-labelledby="modalSubirDocumentoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="procesar_documento.php" method="post" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalSubirDocumentoLabel">Subir Documento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="garantia_id" value="<?php echo $garantiaId; ?>">
                    
                    <div class="mb-3">
                        <label for="tipoDocumento" class="form-label">Tipo de Documento</label>
                        <select class="form-select" id="tipoDocumento" name="tipo_documento" required>
                            <option value="">Seleccione un tipo</option>
                            <option value="Identificación">Identificación</option>
                            <option value="Recibo">Recibo</option>
                            <option value="Contrato">Contrato</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nombreDocumento" class="form-label">Nombre del Documento</label>
                        <input type="text" class="form-control" id="nombreDocumento" name="nombre_documento" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="archivoDocumento" class="form-label">Archivo</label>
                        <input type="file" class="form-control" id="archivoDocumento" name="archivo_documento" required>
                        <div class="form-text">Formatos permitidos: PDF, JPG, PNG. Tamaño máximo: 5MB</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Subir Documento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para cambiar estado -->
<div class="modal fade" id="modalCambiarEstado" tabindex="-1" aria-labelledby="modalCambiarEstadoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="procesar_estado.php" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCambiarEstadoLabel">
                        <?php echo $garantia['Estado'] == 'Activa' ? 'Cancelar Garantía' : 'Reactivar Garantía'; ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="garantia_id" value="<?php echo $garantiaId; ?>">
                    <input type="hidden" name="nuevo_estado" value="<?php echo $garantia['Estado'] == 'Activa' ? 'Cancelada' : 'Activa'; ?>">
                    
                    <div class="mb-3">
                        <label for="razonCambio" class="form-label">
                            <?php echo $garantia['Estado'] == 'Activa' ? 'Razón de cancelación' : 'Razón de reactivación'; ?>
                        </label>
                        <textarea class="form-control" id="razonCambio" name="razon_cambio" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-<?php echo $garantia['Estado'] == 'Activa' ? 'danger' : 'success'; ?>">
                        <?php echo $garantia['Estado'] == 'Activa' ? 'Confirmar Cancelación' : 'Confirmar Reactivación'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .timeline {
        position: relative;
        padding-left: 20px;
    }
    
    .timeline-item {
        position: relative;
        padding-bottom: 20px;
    }
    
    .timeline-point {
        position: absolute;
        left: -20px;
        top: 0;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: #0d6efd;
    }
    
    .timeline-content {
        padding-left: 15px;
    }
    
    .timeline-header {
        margin-bottom: 5px;
    }
    
    .timeline-body {
        font-size: 0.9rem;
    }
</style>

<?php
require_once '../includes/footer.php';
?>