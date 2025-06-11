<?php
// Mostrar todos los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'SistemaJuntas');
define('DB_USER', 'root');
define('DB_PASS', 'WILLY1994');
define('DB_CHARSET', 'utf8mb4');

// Configuración de la aplicación
define('APP_NAME', 'Sistema de Juntas');
define('BASE_URL', 'http://localhost/sistemajuntas/');
//define('BASE_URL', 'http://192.168.18.4/sistemajuntas/');

// Función para assets
function asset($path) {
    return BASE_URL . 'assets/' . ltrim($path, '/');
}