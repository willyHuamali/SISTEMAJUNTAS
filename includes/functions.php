<?php
// functions.php - Funciones auxiliares para el sistema

/**
 * Verifica si un rol tiene un permiso específico
 * 
 * @param string $codigoPermiso Código del permiso a verificar
 * @param int|null $rolID ID del rol del usuario (si es null, retorna false)
 * @return bool True si tiene permiso, False si no
 */
function tienePermiso($codigoPermiso, $rolID) {
    global $db; // Asumimos que $db está disponible globalmente
    
    if (!$rolID) return false;
    
    try {
        $query = "SELECT COUNT(*) as tiene 
                  FROM Roles_Permisos rp
                  JOIN Permisos p ON rp.PermisoID = p.PermisoID
                  WHERE rp.RolID = :rolID AND p.Codigo = :codigo";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':rolID', $rolID, PDO::PARAM_INT);
        $stmt->bindParam(':codigo', $codigoPermiso);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['tiene'] > 0;
    } catch (PDOException $e) {
        error_log("Error al verificar permiso: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene el nombre completo del usuario
 * 
 * @param array $datosUsuario Array con datos del usuario (debe contener 'Nombre' y 'Apellido')
 * @return string Nombre completo formateado
 */
function obtenerNombreCompleto($datosUsuario) {
    return htmlspecialchars(trim($datosUsuario['Nombre'] . ' ' . $datosUsuario['Apellido']));
}

/**
 * Formatea una cantidad monetaria
 * 
 * @param float $monto Cantidad a formatear
 * @param string $moneda Código de moneda (default: 'PEN')
 * @return string Monto formateado con símbolo de moneda
 */
function formatearMoneda($monto, $moneda = 'PEN') {
    $simbolos = [
        'PEN' => 'S/ ',
        'USD' => '$',
        'EUR' => '€'
    ];
    
    $simbolo = $simbolos[$moneda] ?? '';
    return $simbolo . number_format($monto, 2);
}

/**
 * Verifica si una fecha es válida
 * 
 * @param string $date Fecha en formato YYYY-MM-DD
 * @return bool True si es válida, False si no
 */
function esFechaValida($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Redirige a una URL específica
 * 
 * @param string $url URL a redirigir
 * @param int $statusCode Código de estado HTTP (default: 302)
 */
function redirigir($url, $statusCode = 302) {
    header('Location: ' . $url, true, $statusCode);
    exit();
}

/**
 * Genera un token CSRF
 * 
 * @return string Token generado
 */
function generarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica un token CSRF
 * 
 * @param string $token Token a verificar
 * @return bool True si es válido, False si no
 */
function verificarTokenCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Obtiene la URL base de la aplicación
 * 
 * @return string URL base
 */
function obtenerBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    return "$protocol://$host";
}

/**
 * Sanitiza datos de entrada
 * 
 * @param mixed $data Datos a sanitizar
 * @return mixed Datos sanitizados
 */
function sanitizar($data) {
    if (is_array($data)) {
        return array_map('sanitizar', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Muestra un mensaje flash y lo elimina de la sesión
 * 
 * @return string|null Mensaje flash o null si no hay
 */
function mostrarMensajeFlash() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Establece un mensaje flash en la sesión
 * 
 * @param string $message Mensaje a almacenar
 * @param string $type Tipo de mensaje (success, error, warning, info)
 */
function setMensajeFlash($message, $type = 'success') {
    $_SESSION['flash_message'] = [
        'text' => $message,
        'type' => $type
    ];
}

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'año',
        'm' => 'mes',
        'w' => 'semana',
        'd' => 'día',
        'h' => 'hora',
        'i' => 'minuto',
        's' => 'segundo',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' atrás' : 'justo ahora';
}