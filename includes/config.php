<?php
// Configuración básica para el sistema
session_start();

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'SistemaJuntas');
define('DB_USER', 'root');
define('DB_PASS', 'WILLY1994');
define('DB_CHARSET', 'utf8mb4');

// Configuración de la aplicación
define('APP_NAME', 'Sistema de Juntas');
define('BASE_URL', 'http://localhost/sistemajuntas/');

// Mostrar errores (solo en desarrollo)
error_reporting(E_ALL);
ini_set('display_errors', 1);