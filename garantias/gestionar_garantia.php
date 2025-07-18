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

$tituloPagina = "Gestión Completa de Garantías";
require_once '../includes/header.php';
require_once '../includes/navbar.php';

// Mostrar mensaje de éxito si existe
if (isset($_SESSION['mensaje_exito'])) {
    echo '<div class="alert alert-'.$_SESSION['mensaje_exito']['tipo'].' alert-dismissible fade show">
        <strong>'.$_SESSION['mensaje_exito']['titulo'].'</strong> '.$_SESSION['mensaje_exito']['texto'].'
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
    unset($_SESSION['mensaje_exito']);
}

// Obtener listado de todas las garantías con filtros
$filtroEstado = $_GET['estado'] ?? '';
$filtroUsuario = isset($_GET['usuario']) ? (int)$_GET['usuario'] : 0;
$filtroTipo = $_GET['tipo'] ?? '';

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
                            <a href="gestionar_garantia.php" class="btn btn-secondary w-100 mt-2">Limpiar</a>
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

<?php
require_once '../includes/footer.php';
?>