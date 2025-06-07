<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../clases/Junta.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../clases/AuthHelper.php';

verificarAutenticacion();
verificarInactividad();

// Validar token CSRF
if (!isset($_POST['csrf_token'])) {
    $_SESSION['error'] = 'Token de seguridad inválido';
    header('Location: index_junta.php');
    exit;
}

if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['error'] = 'Token de seguridad no coincide';
    header('Location: index_junta.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();
$authHelper = new \Clases\AuthHelper($db);

// Validar y sanitizar datos de entrada
$accion = filter_input(INPUT_POST, 'accion', FILTER_SANITIZE_STRING);
$juntaId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

// Inicializar objeto Junta
$junta = new Junta($db);

try {
    switch ($accion) {
        case 'crear':
            // Verificar permisos
            if (!$authHelper->tienePermiso('juntas.create', $_SESSION['rol_id'])) {
                throw new Exception('No tienes permiso para crear juntas');
            }

            // Validar y sanitizar datos
            $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
            $descripcion = filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_STRING);
            $reglas = filter_input(INPUT_POST, 'reglas', FILTER_SANITIZE_STRING);
            $monto = filter_input(INPUT_POST, 'monto', FILTER_VALIDATE_FLOAT);
            $minParticipantes = filter_input(INPUT_POST, 'min_participantes', FILTER_VALIDATE_INT);
            $maxParticipantes = filter_input(INPUT_POST, 'max_participantes', FILTER_VALIDATE_INT);
            $frecuencia = filter_input(INPUT_POST, 'frecuencia', FILTER_SANITIZE_STRING);
            $fechaInicio = filter_input(INPUT_POST, 'fecha_inicio', FILTER_SANITIZE_STRING);

            // Validaciones
            if (empty($nombre) || $monto <= 0 || $minParticipantes <= 0 || $maxParticipantes <= 0 || 
                $minParticipantes > $maxParticipantes || empty($frecuencia) || empty($fechaInicio)) {
                throw new Exception('Todos los campos son requeridos y deben ser válidos');
            }

            // Crear la junta
            $juntaID = $junta->crearJunta([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'reglas' => $reglas,
                'monto' => $monto,
                'min_participantes' => $minParticipantes,
                'max_participantes' => $maxParticipantes,
                'frecuencia' => $frecuencia,
                'fecha_inicio' => $fechaInicio,
                'creador_id' => $_SESSION['usuario_id']
            ]);

            if (!$juntaID) {
                throw new Exception('Error al crear la junta');
            }

            // Registrar acción en bitácora
            $authHelper->registrarAccion($_SESSION['usuario_id'], 'Creación de junta', "Junta ID: $juntaID");
            
            $_SESSION['mensaje_exito'] = 'Junta creada exitosamente';
            header('Location: ver_junta.php?id=' . $juntaID);
            break;

        case 'editar':
            // Verificar permisos básicos
            if (!$authHelper->tienePermiso('juntas.edit', $_SESSION['rol_id'])) {
                throw new Exception('No tienes permiso para editar juntas');
            }

            // Validar ID
            if ($juntaId <= 0) {
                throw new Exception('ID de junta no válido');
            }

            // Obtener datos actuales de la junta
            $juntaActual = $junta->obtenerJuntaPorID($juntaId);
            if (!$juntaActual) {
                throw new Exception('La junta no existe');
            }

            // Verificar permisos específicos
            $esCreador = ($juntaActual['CreadorID'] == $_SESSION['usuario_id']);
            $puedeGestionarTodo = $authHelper->tienePermiso('juntas.manage_all', $_SESSION['rol_id']);

            if (!$puedeGestionarTodo && !$esCreador) {
                throw new Exception('No tienes permiso para editar esta junta');
            }

            // Verificar estado
            if ($juntaActual['Estado'] != 'Activa') {
                throw new Exception('Solo se pueden editar juntas en estado "Activa"');
            }

            // Validar y sanitizar datos
            $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
            $descripcion = filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_STRING);
            $reglas = filter_input(INPUT_POST, 'reglas', FILTER_SANITIZE_STRING);
            $monto = filter_input(INPUT_POST, 'monto', FILTER_VALIDATE_FLOAT);
            $minParticipantes = filter_input(INPUT_POST, 'min_participantes', FILTER_VALIDATE_INT);
            $maxParticipantes = filter_input(INPUT_POST, 'max_participantes', FILTER_VALIDATE_INT);
            $frecuencia = filter_input(INPUT_POST, 'frecuencia', FILTER_SANITIZE_STRING);
            $fechaInicio = filter_input(INPUT_POST, 'fecha_inicio', FILTER_SANITIZE_STRING);

            // Validaciones
            if (empty($nombre) || $monto <= 0 || $minParticipantes <= 0 || $maxParticipantes <= 0 || 
                $minParticipantes > $maxParticipantes || empty($frecuencia) || empty($fechaInicio)) {
                throw new Exception('Todos los campos son requeridos y deben ser válidos');
            }

            // Verificar mínimo de participantes
            if ($minParticipantes > $juntaActual['NumeroParticipantes']) {
                throw new Exception('El mínimo de participantes no puede ser mayor al número actual de participantes');
            }

            // Actualizar la junta
            $actualizado = $junta->actualizarJunta($juntaId, [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'reglas' => $reglas,
                'monto' => $monto,
                'min_participantes' => $minParticipantes,
                'max_participantes' => $maxParticipantes,
                'frecuencia' => $frecuencia,
                'fecha_inicio' => $fechaInicio
            ]);

            if (!$actualizado) {
                throw new Exception('Error al actualizar la junta');
            }

            // Registrar acción en bitácora
            $authHelper->registrarAccion($_SESSION['usuario_id'], 'Edición de junta', "Junta ID: $juntaId");
            
            $_SESSION['mensaje_exito'] = 'Junta actualizada exitosamente';
            header('Location: ver_junta.php?id=' . $juntaId);
            break;

        case 'unirse':
            // Verificar permisos
            if (!$authHelper->tienePermiso('juntas.join', $_SESSION['rol_id'])) {
                throw new Exception('No tienes permiso para unirte a juntas');
            }

            // Validar ID
            if ($juntaId <= 0) {
                throw new Exception('ID de junta no válido');
            }

            // Obtener datos de la junta
            $juntaActual = $junta->obtenerJuntaPorID($juntaId);
            if (!$juntaActual) {
                throw new Exception('La junta no existe');
            }

            // Verificar estado
            if ($juntaActual['Estado'] != 'Activa') {
                throw new Exception('Solo puedes unirte a juntas en estado "Activa"');
            }

            // Verificar si ya es participante
            if ($junta->esParticipante($juntaId, $_SESSION['usuario_id'])) {
                throw new Exception('Ya eres participante de esta junta');
            }

            // Verificar cupo disponible
            if ($juntaActual['NumeroParticipantes'] >= $juntaActual['MaximoParticipantes']) {
                throw new Exception('No hay cupo disponible en esta junta');
            }

            // Unirse a la junta
            $unido = $junta->agregarParticipante($juntaId, $_SESSION['usuario_id']);

            if (!$unido) {
                throw new Exception('Error al unirse a la junta');
            }

            // Registrar acción en bitácora
            $authHelper->registrarAccion($_SESSION['usuario_id'], 'Unión a junta', "Junta ID: $juntaId");
            
            $_SESSION['mensaje_exito'] = 'Te has unido exitosamente a la junta';
            header('Location: ver_junta.php?id=' . $juntaId);
            break;

        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    
    // Redirigir según la acción
    if ($accion == 'crear') {
        header('Location: crear_junta.php');
    } elseif ($accion == 'editar' && $juntaId > 0) {
        header('Location: editar_junta.php?id=' . $juntaId);
    } elseif ($juntaId > 0) {
        header('Location: ver_junta.php?id=' . $juntaId);
    } else {
        header('Location: index_junta.php');
    }
    exit;
}