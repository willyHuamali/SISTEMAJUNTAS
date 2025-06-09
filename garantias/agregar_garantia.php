<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Verificar permisos
if (!tienePermiso('garantias.add')) {
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
                            <label for="documento" class="form-label">Documento de Garantía (PDF, JPG, PNG)</label>
                            <input type="file" class="form-control" id="documento" name="documento" accept=".pdf,.jpg,.jpeg,.png">
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