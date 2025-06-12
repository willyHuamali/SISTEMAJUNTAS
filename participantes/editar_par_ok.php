<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../clases/AuthHelper.php';
require_once __DIR__ . '/../clases/ParticipanteJunta.php';
require_once __DIR__ . '/../clases/Junta.php';
require_once __DIR__ . '/../clases/CuentaBancaria.php'; // Nueva línea añadida
require_once __DIR__ . '/../clases/Garantia.php';      // Probablemente necesaria
require_once __DIR__ . '/../clases/Usuario.php';       // Probablemente necesaria

// Verificar autenticación e inactividad
verificarAutenticacion();
verificarInactividad();

// Inicializar Database y AuthHelper
$database = new Database();
$db = $database->getConnection();
$authHelper = new \Clases\AuthHelper($db);

// Verificar permisos
if (!$authHelper->tienePermiso('participantesjuntas.edit', $_SESSION['rol_id'])) {
    $_SESSION['mensaje_error'] = "No tienes permisos para editar participantes.";
    header('Location: ' . url('participantes/participantes.php'));
    exit;
}

// Obtener ID del participante
$participanteId = $_GET['id'] ?? 0;

if (empty($participanteId)) {
    $_SESSION['mensaje_error'] = "No se especificó el participante.";
    header('Location: ' . url('participantes/participantes.php'));
    exit;
}

// Crear instancia de ParticipanteJunta
$participanteModel = new ParticipanteJunta($db);
$participante = $participanteModel->obtenerParticipantePorId($participanteId);

if (!$participante) {
    $_SESSION['mensaje_error'] = "Participante no encontrado.";
    header('Location: ' . url('participantes/participantes.php'));
    exit;
}

// Obtener datos adicionales necesarios para el formulario
$usuarioModel = new Usuario($db);
$usuario = $usuarioModel->obtenerPorId($participante['UsuarioID']);

$juntaModel = new Junta($db);
$junta = $juntaModel->obtenerPorId($participante['JuntaID']);

$cuentaModel = new CuentaBancaria($db);
$cuentas = $cuentaModel->obtenerCuentasPorUsuario($participante['UsuarioID']);

$garantiaModel = new Garantia($db);
$garantias = $garantiaModel->obtenerGarantiasPorUsuario($participante['UsuarioID']);

// Procesar formulario si es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'ParticipanteID' => $participanteId,
        'CuentaID' => $_POST['cuenta_id'] ?? 0,
        'GarantiaID' => $_POST['garantia_id'] ?? null,
        'OrdenRecepcion' => $_POST['orden_recepcion'] ?? 0,
        'Activo' => isset($_POST['activo']) ? 1 : 0
    ];

    // Validar datos
    if (empty($datos['CuentaID'])) {
        $_SESSION['mensaje_error'] = "Debe seleccionar una cuenta bancaria.";
    } elseif ($participanteModel->actualizarParticipante($datos)) {
        $_SESSION['mensaje_exito'] = "Participante actualizado correctamente.";
        header('Location: ' . url('participantes/ver_par.php?id=' . $participanteId));
        exit;
    } else {
        $_SESSION['mensaje_error'] = "Error al actualizar el participante.";
    }
}

// Incluir header
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="container mt-4">
    <h2>Editar Participante</h2>
    <p class="text-muted">Junta: <?= htmlspecialchars($junta['NombreJunta']) ?></p>
    
    <div class="card shadow">
        <div class="card-body">
            <form method="post">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Usuario</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($usuario['Nombre'] . ' ' . $usuario['Apellido']) ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">DNI</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($usuario['DNI']) ?>" readonly>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="cuenta_id" class="form-label">Cuenta Bancaria *</label>
                    <select class="form-select" id="cuenta_id" name="cuenta_id" required>
                        <option value="">Seleccione una cuenta</option>
                        <?php foreach ($cuentas as $cuenta): ?>
                            <option value="<?= $cuenta['CuentaID'] ?>" <?= $cuenta['CuentaID'] == $participante['CuentaID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cuenta['Banco'] . ' - ' . $cuenta['NumeroCuenta']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if ($junta['RequiereGarantia']): ?>
                <div class="mb-3">
                    <label for="garantia_id" class="form-label">Garantía</label>
                    <select class="form-select" id="garantia_id" name="garantia_id">
                        <option value="">Sin garantía</option>
                        <?php foreach ($garantias as $garantia): ?>
                            <?php if ($garantia['Estado'] == 'Activa'): ?>
                                <option value="<?= $garantia['GarantiaID'] ?>" <?= $garantia['GarantiaID'] == $participante['GarantiaID'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($garantia['TipoGarantia'] . ' - ' . $garantia['Descripcion']) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <label for="orden_recepcion" class="form-label">Orden de Recepción *</label>
                    <input type="number" class="form-control" id="orden_recepcion" name="orden_recepcion" 
                           value="<?= htmlspecialchars($participante['OrdenRecepcion']) ?>" min="1" required>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="activo" name="activo" <?= $participante['Activo'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="activo">Participante Activo</label>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="<?= url('participantes/participantes.php') ?>" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Incluir footer
include __DIR__ . '/../includes/footer.php';
?>