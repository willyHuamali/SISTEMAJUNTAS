<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../clases/Junta.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../clases/AuthHelper.php';

verificarAutenticacion();
verificarInactividad();

$database = new Database();
$db = $database->getConnection();
$authHelper = new \Clases\AuthHelper($db);

// Verificar permisos
if (!$authHelper->tienePermiso('juntas.delete', $_SESSION['rol_id'])) {
    $_SESSION['error'] = 'No tienes permiso para eliminar juntas';
    header('Location: index.php');
    exit;
}

// Obtener ID de la junta
$juntaId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($juntaId <= 0) {
    $_SESSION['error'] = 'ID de junta inválido';
    header('Location: index_junta.php');
    exit;
}

$junta = new Junta($db);
$juntaData = $junta->obtenerPorId($juntaId);

if (!$juntaData) {
    $_SESSION['error'] = 'Junta no encontrada';
    header('Location: index_junta.php');
    exit;
}

// Verificar si el usuario puede eliminar esta junta
if (!$authHelper->tienePermiso('juntas.manage_all', $_SESSION['rol_id']) && 
    $juntaData['CreadaPor'] != $_SESSION['usuario_id']) {
    $_SESSION['error'] = 'No tienes permiso para eliminar esta junta';
    header('Location: index_junta.php');
    exit;
}

// Verificar si la junta puede ser eliminada
if ($juntaData['Estado'] !== 'Activa') {
    $_SESSION['error'] = 'Solo se pueden eliminar juntas en estado "Activa"';
    header('Location: index_junta.php');
    exit;
}

// Procesar eliminación si se confirmó
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
    if ($junta->eliminar($juntaId)) {
        $_SESSION['mensaje_exito'] = 'Junta eliminada exitosamente!';
        header('Location: index_junta.php');
        exit;
    } else {
        $_SESSION['error'] = 'Error al eliminar la junta. Inténtalo nuevamente.';
        header('Location: index_junta.php');
        exit;
    }
}

require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h3><i class="fas fa-exclamation-triangle me-2"></i>Eliminar Junta</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        <h5 class="alert-heading">¿Estás seguro de eliminar esta junta?</h5>
                        <p class="mb-0">Esta acción no se puede deshacer. Todos los datos asociados a esta junta serán eliminados permanentemente.</p>
                    </div>

                    <div class="mb-4">
                        <h5>Detalles de la Junta</h5>
                        <ul class="list-group">
                            <li class="list-group-item">
                                <strong>Nombre:</strong> <?= htmlspecialchars($juntaData['NombreJunta']) ?>
                            </li>
                            <li class="list-group-item">
                                <strong>Código:</strong> <?= htmlspecialchars($juntaData['CodigoJunta']) ?>
                            </li>
                            <li class="list-group-item">
                                <strong>Monto:</strong> S/ <?= number_format($juntaData['MontoAporte'], 2) ?>
                            </li>
                            <li class="list-group-item">
                                <strong>Estado:</strong> <?= htmlspecialchars($juntaData['Estado']) ?>
                            </li>
                        </ul>
                    </div>

                    <form method="post">
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index_junta.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </a>
                            <button type="submit" name="confirmar" class="btn btn-danger">
                                <i class="fas fa-trash-alt me-1"></i> Confirmar Eliminación
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>