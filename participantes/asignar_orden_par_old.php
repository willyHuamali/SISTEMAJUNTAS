<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../clases/AuthHelper.php';
require_once __DIR__ . '/../clases/ParticipanteJunta.php';
require_once __DIR__ . '/../clases/Junta.php';

// Verificar autenticación e inactividad
verificarAutenticacion();
verificarInactividad();

// Inicializar Database y AuthHelper
$database = new Database();
$db = $database->getConnection();
$authHelper = new \Clases\AuthHelper($db);

// Verificar permisos
if (!$authHelper->tienePermiso('participantesjuntas.assign_order', $_SESSION['rol_id'])) {
    $_SESSION['mensaje_error'] = "No tienes permisos para asignar órdenes.";
    header('Location: ' . url('participantes/participantes.php'));
    exit;
}

// Obtener ID de la junta
$juntaId = filter_input(INPUT_GET, 'junta_id', FILTER_VALIDATE_INT) ?? 0;

if (empty($juntaId)) {
    $_SESSION['mensaje_error'] = "No se especificó la junta o el ID es inválido.";
    header('Location: ' . url('juntas/index_junta.php'));
    exit;
}

// Obtener información de la junta
$juntaModel = new Junta($db);
$junta = $juntaModel->obtenerJuntaPorId($juntaId);

if (!$junta) {
    $_SESSION['mensaje_error'] = "La junta especificada no existe.";
    header('Location: ' . url('juntas/index_junta.php'));
    exit;
}

// Obtener participantes de la junta
$participanteModel = new ParticipanteJunta($db);
$participantes = $participanteModel->obtenerParticipantesPorJunta($juntaId);

if (empty($participantes)) {
    $_SESSION['mensaje_error'] = "No hay participantes en esta junta.";
    header('Location: ' . url('participantes/participantes.php?junta_id=' . $juntaId));
    exit;
}

// Procesar asignación de orden si es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ordenes = $_POST['orden'] ?? [];
    
    if (empty($ordenes)) {
        $_SESSION['mensaje_error'] = "No se recibieron datos para actualizar.";
    } else {
        $db->beginTransaction();
        $error = false;
        
        try {
            foreach ($ordenes as $participanteId => $orden) {
                $participanteId = filter_var($participanteId, FILTER_VALIDATE_INT);
                $orden = filter_var($orden, FILTER_VALIDATE_INT);
                
                if (!$participanteId || !$orden) {
                    $error = true;
                    continue;
                }
                
                $query = "UPDATE ParticipantesJuntas 
                          SET OrdenRecepcion = :orden 
                          WHERE ParticipanteID = :participanteId AND JuntaID = :juntaId";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':orden', $orden, \PDO::PARAM_INT);
                $stmt->bindParam(':participanteId', $participanteId, \PDO::PARAM_INT);
                $stmt->bindParam(':juntaId', $juntaId, \PDO::PARAM_INT);
                
                if (!$stmt->execute()) {
                    $error = true;
                    break;
                }
            }
            
            if ($error) {
                $db->rollBack();
                $_SESSION['mensaje_error'] = "Error al actualizar los órdenes. Verifique que no haya números duplicados.";
            } else {
                $db->commit();
                $_SESSION['mensaje_exito'] = "Órdenes de recepción actualizados correctamente.";
                $authHelper->registrarAccion($_SESSION['usuario_id'], 'Asignación de órdenes', 'Junta ID: ' . $juntaId);
                header('Location: ' . url('participantes/participantes.php?junta_id=' . $juntaId));
                exit;
            }
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['mensaje_error'] = "Error en la transacción: " . $e->getMessage();
        }
    }
}

// Incluir header
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="container mt-4">
    <h2>Asignar Orden de Recepción</h2>
    <p class="text-muted">Junta: <?= htmlspecialchars($junta['NombreJunta'] ?? '') ?></p>
    
    <?php if (isset($_SESSION['mensaje_error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['mensaje_error']) ?></div>
        <?php unset($_SESSION['mensaje_error']); ?>
    <?php endif; ?>
    
    <div class="card shadow">
        <div class="card-body">
            <form method="post" id="formOrden">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Participante</th>
                                <th>DNI</th>
                                <th>Orden Actual</th>
                                <th>Nuevo Orden</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($participantes as $participante): ?>
                                <tr>
                                    <td><?= htmlspecialchars($participante['Nombre'] ?? '') . ' ' . htmlspecialchars($participante['Apellido'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($participante['DNI'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($participante['OrdenRecepcion'] ?? '') ?></td>
                                    <td>
                                        <input type="number" name="orden[<?= $participante['ParticipanteID'] ?? '' ?>]" 
                                               value="<?= $participante['OrdenRecepcion'] ?? '' ?>" 
                                               min="1" max="<?= count($participantes) ?>" 
                                               class="form-control form-control-sm" style="width: 80px;" required>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between mt-3">
                    <a href="<?= url('participantes/participantes.php?junta_id=' . $juntaId) ?>" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Órdenes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formOrden');
    
    form.addEventListener('submit', function(e) {
        const inputs = document.querySelectorAll('input[name^="orden["]');
        const valores = Array.from(inputs).map(input => parseInt(input.value));
        const valoresUnicos = [...new Set(valores)];
        
        if (valores.length !== valoresUnicos.length) {
            e.preventDefault();
            alert('Error: Hay órdenes duplicados. Cada participante debe tener un orden único.');
            return;
        }
        
        // Validar que los valores estén dentro del rango permitido
        const max = <?= count($participantes) ?>;
        for (const valor of valores) {
            if (valor < 1 || valor > max) {
                e.preventDefault();
                alert(`Error: Los valores deben estar entre 1 y ${max}.`);
                return;
            }
        }
    });
});
</script>

<?php
// Incluir footer
include __DIR__ . '/../includes/footer.php';
?>