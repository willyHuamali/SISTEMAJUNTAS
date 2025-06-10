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

// Verificar permisos para asignar orden
if (!$authHelper->tienePermiso('participantesjuntas.edit', $_SESSION['rol_id'])) {
    $_SESSION['mensaje_error'] = "No tienes permisos para asignar órdenes de recepción.";
    header('Location: ' . url('participantes/participantes.php'));
    exit;
}

// Obtener datos del formulario
$participanteId = $_POST['participante_id'] ?? 0;
$juntaId = $_POST['junta_id'] ?? 0;
$nuevoOrden = $_POST['nuevo_orden'] ?? 0;

// Validar datos
if (empty($participanteId) || empty($juntaId) || empty($nuevoOrden)) {
    $_SESSION['mensaje_error'] = "Datos incompletos para asignar el orden.";
    header('Location: ' . url('participantes/participantes.php'));
    exit;
}

// Crear instancia de ParticipanteJunta
$participanteModel = new ParticipanteJunta($db);

// Verificar si el nuevo orden ya está ocupado
if ($participanteModel->ordenOcupado($juntaId, $nuevoOrden, $participanteId)) {
    $_SESSION['mensaje_error'] = "El orden de recepción $nuevoOrden ya está asignado a otro participante en esta junta.";
    header('Location: ' . url('participantes/participantes.php'));
    exit;
}

// Actualizar el orden
if ($participanteModel->actualizarOrdenRecepcion($participanteId, $juntaId, $nuevoOrden)) {
    $_SESSION['mensaje_exito'] = "Orden de recepción actualizado correctamente.";
} else {
    $_SESSION['mensaje_error'] = "Error al actualizar el orden de recepción.";
}

// Redirigir de vuelta a la lista de participantes
header('Location: ' . url('participantes/participantes.php'));
exit;
?>