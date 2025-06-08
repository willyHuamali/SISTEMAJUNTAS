<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../clases/AuthHelper.php';
require_once __DIR__ . '/../../clases/ParticipanteJunta.php';

// Verificar autenticación e inactividad
verificarAutenticacion();
verificarInactividad();

// Inicializar Database y AuthHelper
$database = new Database();
$db = $database->getConnection();
$authHelper = new \Clases\AuthHelper($db);

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('participantes/participantes.php'));
    exit;
}

// Obtener acción a realizar
$accion = $_POST['accion'] ?? '';
$participanteId = $_POST['id'] ?? 0;

// Validar ID
if (empty($participanteId)) {
    $_SESSION['mensaje_error'] = "No se especificó el participante.";
    header('Location: ' . url('participantes/participantes.php'));
    exit;
}

// Crear instancia de ParticipanteJunta
$participanteModel = new ParticipanteJunta($db);

// Procesar según la acción
switch ($accion) {
    case 'eliminar':
        // Verificar permisos
        if (!$authHelper->tienePermiso('participantesjuntas.manage', $_SESSION['rol_id'])) {
            $_SESSION['mensaje_error'] = "No tienes permisos para eliminar participantes.";
            header('Location: ' . url('participantes/participantes.php'));
            exit;
        }
        
        // Eliminar (marcar como inactivo) el participante
        if ($participanteModel->eliminarParticipante($participanteId)) {
            $_SESSION['mensaje_exito'] = "Participante eliminado correctamente.";
        } else {
            $_SESSION['mensaje_error'] = "Error al eliminar participante.";
        }
        break;
        
    case 'activar':
        // Verificar permisos
        if (!$authHelper->tienePermiso('participantesjuntas.manage', $_SESSION['rol_id'])) {
            $_SESSION['mensaje_error'] = "No tienes permisos para activar participantes.";
            header('Location: ' . url('participantes/participantes.php'));
            exit;
        }
        
        // Activar el participante
        $query = "UPDATE ParticipantesJuntas SET Activo = 1 WHERE ParticipanteID = :participanteId";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':participanteId', $participanteId);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje_exito'] = "Participante activado correctamente.";
        } else {
            $_SESSION['mensaje_error'] = "Error al activar participante.";
        }
        break;
        
    case 'actualizar_orden':
        // Verificar permisos
        if (!$authHelper->tienePermiso('participantesjuntas.edit', $_SESSION['rol_id'])) {
            $_SESSION['mensaje_error'] = "No tienes permisos para actualizar órdenes.";
            header('Location: ' . url('participantes/participantes.php'));
            exit;
        }
        
        $juntaId = $_POST['junta_id'] ?? 0;
        $nuevoOrden = $_POST['nuevo_orden'] ?? 0;
        
        if (empty($juntaId) || empty($nuevoOrden)) {
            $_SESSION['mensaje_error'] = "Datos incompletos para actualizar el orden.";
        } else {
            // Actualizar el orden
            $query = "UPDATE ParticipantesJuntas 
                      SET OrdenRecepcion = :nuevoOrden
                      WHERE ParticipanteID = :participanteId AND JuntaID = :juntaId";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nuevoOrden', $nuevoOrden);
            $stmt->bindParam(':participanteId', $participanteId);
            $stmt->bindParam(':juntaId', $juntaId);
            
            if ($stmt->execute()) {
                $_SESSION['mensaje_exito'] = "Orden de recepción actualizado correctamente.";
            } else {
                $_SESSION['mensaje_error'] = "Error al actualizar el orden de recepción.";
            }
        }
        break;
        
    default:
        $_SESSION['mensaje_error'] = "Acción no válida.";
        break;
}

// Redirigir de vuelta a la lista de participantes
header('Location: ' . url('participantes/participantes.php'));
exit;
?>