<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../clases/Usuario.php';  // Corregido para apuntar a la carpeta correcta
require_once __DIR__ . '/../includes/db.php';


$database = new Database();
$usuario = new Usuario($database->getConnection());

// Resto del código...

function verificarAutenticacion() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit();
    }
}

function esAdministrador() {
    return isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1;
}

function iniciarSesionUsuario($usuario) {
    $_SESSION['usuario_id'] = $usuario['UsuarioID'];
    $_SESSION['nombre_usuario'] = $usuario['NombreUsuario'];
    $_SESSION['nombre_completo'] = $usuario['Nombre'] . ' ' . $usuario['Apellido'];
    $_SESSION['rol_id'] = $usuario['RolID'];
    $_SESSION['ultimo_actividad'] = time();
}

function cerrarSesion() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

function verificarInactividad() {
    $tiempo_inactividad = 1800; // 30 minutos
    
    if (isset($_SESSION['ultimo_actividad']) && (time() - $_SESSION['ultimo_actividad'] > $tiempo_inactividad)) {
        cerrarSesion();
        header('Location: login.php?timeout=1');
        exit();
    }
    
    $_SESSION['ultimo_actividad'] = time();
}