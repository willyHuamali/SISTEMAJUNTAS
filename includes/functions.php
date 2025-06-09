<?php
/**
 * Genera una URL absoluta
 */
if (!function_exists('url')) {
    function url($path = '') {
        return BASE_URL . ltrim($path, '/');
    }
}

/**
 * Genera una URL para assets
 */
if (!function_exists('asset')) {
    function asset($path) {
        return BASE_URL . 'assets/' . ltrim($path, '/');
    }
}

// Eliminar la función tienePermiso() ya que ahora está en AuthHelper

// Las demás funciones pueden permanecer igual, pero podemos mejorar algunas:

/**
 * Obtiene el nombre completo del usuario con validación
 */
function obtenerNombreCompleto(array $datosUsuario): string {
    $nombre = $datosUsuario['Nombre'] ?? '';
    $apellido = $datosUsuario['Apellido'] ?? '';
    return htmlspecialchars(trim("$nombre $apellido"), ENT_QUOTES, 'UTF-8');
}

/**
 * Formatea una cantidad monetaria con validación
 */
function formatearMoneda(float $monto, string $moneda = 'PEN'): string {
    $simbolos = [
        'PEN' => 'S/ ',
        'USD' => '$',
        'EUR' => '€'
    ];
    
    $simbolo = $simbolos[strtoupper($moneda)] ?? '';
    return $simbolo . number_format($monto, 2, '.', ',');
}

// Las demás funciones pueden permanecer igual...

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

/**
 * Muestra mensajes flash de la sesión (versión mejorada)
 */
function mostrarMensajes() {
    if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-danger">'.$_SESSION['error'].'</div>';
        unset($_SESSION['error']);
    }
    
    if (isset($_SESSION['success'])) {
        echo '<div class="alert alert-success">'.$_SESSION['success'].'</div>';
        unset($_SESSION['success']);
    }
    
    $mensajeFlash = mostrarMensajeFlash();
    if ($mensajeFlash) {
        echo '<div class="alert alert-'.$mensajeFlash['type'].'">'.$mensajeFlash['text'].'</div>';
    }    

}

    /**
     * Formatea un valor numérico como moneda
     * @param float $valor Valor a formatear
     * @param string $moneda Símbolo de moneda (por defecto 'S/')
     * @param int $decimales Número de decimales (por defecto 2)
     * @return string Valor formateado como moneda
     */
    function formatoMoneda($valor, $moneda = 'S/', $decimales = 2) {
        return $moneda . ' ' . number_format((float)$valor, $decimales, '.', ',');
    }

    /**
     * Formatea una fecha para mostrarla de manera legible
     * @param string $fecha Fecha en formato de base de datos (YYYY-MM-DD HH:MM:SS)
     * @param string $formato Formato de salida (por defecto 'd/m/Y H:i')
     * @return string Fecha formateada
     */
    function formatoFecha($fecha, $formato = 'd/m/Y H:i') {
        if (empty($fecha) || $fecha == '0000-00-00 00:00:00') {
            return 'N/A';
        }
        $dateTime = new DateTime($fecha);
        return $dateTime->format($formato);
    }

    /**
    *  PARA procesar_garantia.php
    */
    function registrarTransaccion($db, $descripcion, $tablaReferencia, $referenciaId, $monto = 0, $usuarioId = null, $juntaId = null) {
        try {
            $query = "INSERT INTO Transacciones (
                        TipoTransaccion, 
                        ReferenciaID, 
                        TablaReferencia, 
                        Monto, 
                        Moneda, 
                        FechaTransaccion, 
                        Descripcion, 
                        UsuarioID, 
                        JuntaID, 
                        Estado
                    ) VALUES (
                        :tipoTransaccion,
                        :referenciaId,
                        :tablaReferencia,
                        :monto,
                        'PEN',
                        NOW(),
                        :descripcion,
                        :usuarioId,
                        :juntaId,
                        'Completada'
                    )";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':tipoTransaccion', $descripcion, PDO::PARAM_STR);
            $stmt->bindParam(':referenciaId', $referenciaId, PDO::PARAM_INT);
            $stmt->bindParam(':tablaReferencia', $tablaReferencia, PDO::PARAM_STR);
            $stmt->bindParam(':monto', $monto);
            $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
            $stmt->bindParam(':usuarioId', $usuarioId, PDO::PARAM_INT);
            $stmt->bindParam(':juntaId', $juntaId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al registrar transacción: " . $e->getMessage());
            return false;
        }
    }
