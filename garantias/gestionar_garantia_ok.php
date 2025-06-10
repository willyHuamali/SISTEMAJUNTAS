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

// Verificar permisos (solo administradores pueden gestionar todas las garantías)
if (!$authHelper->tienePermiso('garantias.manage_all', $rolId)) {
    header('Location: index_garantia.php?error=permisos');
    exit();
}

$tituloPagina = "Gestión de Garantías";
require_once '../includes/header.php';
require_once '../includes/navbar.php';

// Obtener listado de todas las garantías con filtros
$db = conectarBD();

// Filtros
$filtroEstado = isset($_GET['estado']) ? $_GET['estado'] : '';
$filtroUsuario = isset($_GET['usuario']) ? (int)$_GET['usuario'] : 0;
$filtroTipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';

// Construir consulta con filtros
$query = "SELECT g.*, u.Nombre, u.Apellido FROM Garantias g JOIN Usuarios u ON g.UsuarioID = u.UsuarioID WHERE 1=1";
$params = [];
$types = '';

if (!empty($filtroEstado)) {
    $query .= " AND g.Estado = ?";
    $params[] = $filtroEstado;
    $types .= 's';
}

if ($filtroUsuario > 0) {
    $query .= " AND g.UsuarioID = ?";
    $params[] = $filtroUsuario;
    $types .= 'i';
}

if (!empty($filtroTipo)) {
    $query .= " AND g.TipoGarantia = ?";
    $params[] = $filtroTipo;
    $types .= 's';
}

$query .= " ORDER BY g.FechaRegistro DESC";

$stmt = $db->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$resultado = $stmt->get_result();

// Obtener usuarios para filtro
$usuarios = [];
$queryUsuarios = "SELECT UsuarioID, Nombre, Apellido FROM Usuarios ORDER BY Nombre";
$resultUsuarios = $db->query($queryUsuarios);
while ($usuario = $resultUsuarios->fetch_assoc()) {
    $usuarios[] = $usuario;
}

// Tipos de garantía para filtro
$tiposGarantia = [];
$queryTipos = "SELECT DISTINCT TipoGarantia FROM Garantias ORDER BY TipoGarantia";
$resultTipos = $db->query($queryTipos);
while ($tipo = $resultTipos->fetch_assoc()) {
    $tiposGarantia[] = $tipo['TipoGarantia'];
}
?>

<div class="container mt-4">
    <h2 class="mb-4"><?php echo $tituloPagina; ?></h2>
    
    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filtros</h5>
        </div>
        <div class="card-body">
            <form method="get" action="gestionar_garantia.php">
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="">Todos</option>
                                <option value="Activa" <?php echo $filtroEstado == 'Activa' ? 'selected' : ''; ?>>Activa</option>
                                <option value="Retenida" <?php echo $filtroEstado == 'Retenida' ? 'selected' : ''; ?>>Retenida</option>
                                <option value="Liberada" <?php echo $filtroEstado == 'Liberada' ? 'selected' : ''; ?>>Liberada</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="usuario" class="form-label">Usuario</label>
                            <select class="form-select" id="usuario" name="usuario">
                                <option value="0">Todos</option>
                                <?php foreach ($usuarios as $usuario): ?>
                                <option value="<?php echo $usuario['UsuarioID']; ?>" <?php echo $filtroUsuario == $usuario['UsuarioID'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($usuario['Nombre'] . ' ' . $usuario['Apellido']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo de Garantía</label>
                            <select class="form-select" id="tipo" name="tipo">
                                <option value="">Todos</option>
                                <?php foreach ($tiposGarantia as $tipo): ?>
                                <option value="<?php echo htmlspecialchars($tipo); ?>" <?php echo $filtroTipo == $tipo ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tipo); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="mb-3 w-100">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Listado de garantías -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Valor Estimado</th>
                            <th>Fecha Registro</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($garantia = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($garantia['Nombre'] . ' ' . $garantia['Apellido']); ?></td>
                            <td><?php echo htmlspecialchars($garantia['TipoGarantia']); ?></td>
                            <td><?php echo htmlspecialchars($garantia['Descripcion']); ?></td>
                            <td><?php echo formatoMoneda($garantia['ValorEstimado']); ?></td>
                            <td><?php echo formatoFecha($garantia['FechaRegistro']); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $garantia['Estado'] == 'Activa' ? 'success' : 
                                         ($garantia['Estado'] == 'Retenida' ? 'warning' : 'secondary'); 
                                ?>">
                                    <?php echo $garantia['Estado']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="ver_garantia.php?id=<?php echo $garantia['GarantiaID']; ?>" class="btn btn-sm btn-info" title="Ver">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="editar_garantia.php?id=<?php echo $garantia['GarantiaID']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger btn-cambiar-estado" 
                                            data-id="<?php echo $garantia['GarantiaID']; ?>"
                                            data-estado="<?php echo $garantia['Estado']; ?>"
                                            title="Cambiar Estado">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para cambiar estado -->
<div class="modal fade" id="modalCambiarEstado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cambiar Estado de Garantía</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formCambiarEstado" method="post" action="procesar_estado_garantia.php">
                <input type="hidden" name="id" id="garantiaId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nuevoEstado" class="form-label">Nuevo Estado</label>
                        <select class="form-select" id="nuevoEstado" name="estado" required>
                            <option value="Activa">Activa</option>
                            <option value="Retenida">Retenida</option>
                            <option value="Liberada">Liberada</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="motivo" class="form-label">Motivo (Opcional)</label>
                        <textarea class="form-control" id="motivo" name="motivo" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Script para manejar el modal de cambio de estado
document.addEventListener('DOMContentLoaded', function() {
    const botonesCambiarEstado = document.querySelectorAll('.btn-cambiar-estado');
    const modalCambiarEstado = new bootstrap.Modal(document.getElementById('modalCambiarEstado'));
    const formCambiarEstado = document.getElementById('formCambiarEstado');
    const garantiaIdInput = document.getElementById('garantiaId');
    const nuevoEstadoSelect = document.getElementById('nuevoEstado');
    
    botonesCambiarEstado.forEach(boton => {
        boton.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const estadoActual = this.getAttribute('data-estado');
            
            garantiaIdInput.value = id;
            nuevoEstadoSelect.value = estadoActual === 'Activa' ? 'Retenida' : 
                                    estadoActual === 'Retenida' ? 'Liberada' : 'Activa';
            
            modalCambiarEstado.show();
        });
    });
    
    // Manejar envío del formulario
    formCambiarEstado.addEventListener('submit', function(e) {
        e.preventDefault();
        
        fetch(this.action, {
            method: 'POST',
            body: new FormData(this)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error al cambiar el estado');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar la solicitud');
        });
    });
});
</script>

<?php
require_once '../includes/footer.php';
?>