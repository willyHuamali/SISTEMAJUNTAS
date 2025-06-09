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

// Obtener ID de usuario de la sesión
$usuarioId = $_SESSION['usuario_id'] ?? null;

// Verificar permisos
if (!$authHelper->tienePermiso('cuentas.add', $_SESSION['rol_id'])) {
    header('Location: /sistemajuntas/dashboard.php?error=no_permission');
    exit;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="container mt-4">
    <h2><i class="fas fa-plus-circle"></i> Agregar Nueva Cuenta Bancaria</h2>
    
    <form action="procesar_cuenta.php" method="post">
        <input type="hidden" name="action" value="add">
        
        <div class="mb-3">
            <label for="banco" class="form-label">Banco</label>
            <input type="text" class="form-control" id="banco" name="banco" required>
        </div>
        
        <div class="mb-3">
            <label for="numero_cuenta" class="form-label">Número de Cuenta</label>
            <input type="text" class="form-control" id="numero_cuenta" name="numero_cuenta" required>
        </div>
        
        <div class="mb-3">
            <label for="tipo_cuenta" class="form-label">Tipo de Cuenta</label>
            <select class="form-select" id="tipo_cuenta" name="tipo_cuenta" required>
                <option value="">Seleccione...</option>
                <option value="Ahorros">Ahorros</option>
                <option value="Corriente">Corriente</option>
                <option value="Plazo Fijo">Plazo Fijo</option>
            </select>
        </div>
        
        <div class="mb-3">
            <label for="moneda" class="form-label">Moneda</label>
            <select class="form-select" id="moneda" name="moneda" required>
                <option value="PEN">Soles (PEN)</option>
                <option value="USD">Dólares (USD)</option>
            </select>
        </div>
        
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="es_principal" name="es_principal">
            <label class="form-check-label" for="es_principal">Marcar como cuenta principal</label>
        </div>
        
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Guardar Cuenta
        </button>
        <a href="index_cuenta.php" class="btn btn-secondary">
            <i class="fas fa-times"></i> Cancelar
        </a>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>