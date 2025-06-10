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

// Verificar permisos y parámetros
if (!$authHelper->tienePermiso('garantias.edit', $rolId)) {
    header('Location: index_garantia.php?error=permisos');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index_garantia.php?error=id');
    exit();
}

$garantiaId = (int)$_GET['id'];

// Obtener datos de la garantía
$query = "SELECT * FROM Garantias WHERE GarantiaID = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $garantiaId, PDO::PARAM_INT);
$stmt->execute();
$garantia = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar existencia y permisos
if (!$garantia || ($garantia['UsuarioID'] != $_SESSION['usuario_id'] && !$authHelper->tienePermiso('garantias.manage_all', $rolId))) {
    header('Location: index_garantia.php?error=noexiste');
    exit();
}

$tituloPagina = "Editar Garantía";
require_once '../includes/header.php';
require_once '../includes/navbar.php';

// Tipos de garantía permitidos
$tiposGarantia = [
    'Depósito en efectivo',
    'Propiedad inmueble',
    'Vehículo',
    'Electrodomésticos',
    'Joyas',
    'Otros bienes'
];
?>

<div class="container mt-4">
    <h2 class="mb-4"><?php echo $tituloPagina; ?></h2>
    
    <div class="card">
        <div class="card-body">
            <form action="procesar_garantia.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id" value="<?php echo $garantiaId; ?>">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo de Garantía *</label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="">Seleccione un tipo</option>
                                <?php foreach ($tiposGarantia as $tipo): ?>
                                <option value="<?php echo htmlspecialchars($tipo); ?>" <?php echo $garantia['TipoGarantia'] == $tipo ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tipo); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="valor" class="form-label">Valor Estimado *</label>
                            <div class="input-group">
                                <span class="input-group-text">S/</span>
                                <input type="number" class="form-control" id="valor" name="valor" 
                                       value="<?php echo htmlspecialchars($garantia['ValorEstimado']); ?>" step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Documento Actual</label>
                            <?php if ($garantia['DocumentoURL']): ?>
                            <div>
                                <a href="../uploads/garantias/<?php echo htmlspecialchars($garantia['DocumentoURL']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    Ver Documento
                                </a>
                            </div>
                            <?php else: ?>
                            <p class="text-muted">No hay documento cargado</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="documento" class="form-label">Nuevo Documento (PDF, JPG, PNG)</label>
                            <input type="file" class="form-control" id="documento" name="documento" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción Detallada *</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required><?php 
                        echo htmlspecialchars($garantia['Descripcion']); 
                    ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select" id="estado" name="estado" <?php echo !$authHelper->tienePermiso('garantias.manage_all', $rolId) ? 'disabled' : ''; ?>>
                        <option value="Activa" <?php echo $garantia['Estado'] == 'Activa' ? 'selected' : ''; ?>>Activa</option>
                        <option value="Retenida" <?php echo $garantia['Estado'] == 'Retenida' ? 'selected' : ''; ?>>Retenida</option>
                        <option value="Liberada" <?php echo $garantia['Estado'] == 'Liberada' ? 'selected' : ''; ?>>Liberada</option>
                    </select>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="index_garantia.php" class="btn btn-secondary me-md-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Actualizar Garantía</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>