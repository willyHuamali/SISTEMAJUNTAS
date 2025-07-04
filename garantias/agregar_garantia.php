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

// Verificar permisos - CORRECCIÓN: Usar $authHelper->tienePermiso()
if (!$authHelper->tienePermiso('garantias.add', $rolId)) {
    header('Location: index_garantia.php?error=permisos');
    exit();
}

$tituloPagina = "Agregar Nueva Garantía";
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
                <input type="hidden" name="accion" value="agregar">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo de Garantía *</label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="">Seleccione un tipo</option>
                                <?php foreach ($tiposGarantia as $tipo): ?>
                                <option value="<?php echo htmlspecialchars($tipo); ?>"><?php echo htmlspecialchars($tipo); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="valor" class="form-label">Valor Estimado *</label>
                            <div class="input-group">
                                <span class="input-group-text">S/</span>
                                <input type="number" class="form-control" id="valor" name="valor" step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="documento" class="form-label">Documento de Garantía</label>
                            <input type="file" class="form-control" id="documento" name="documento" accept=".pdf,.jpg,.jpeg,.png" required>
                            <div class="form-text">Formatos aceptados: PDF, JPG, PNG. Tamaño máximo: 5MB</div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción Detallada *</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required></textarea>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="index_garantia.php" class="btn btn-secondary me-md-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Garantía</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>