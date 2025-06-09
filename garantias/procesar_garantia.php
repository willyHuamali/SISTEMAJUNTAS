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

// Verificar autenticación y permisos
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit();
}

// Verificar acción
if (!isset($_POST['accion'])) {
    header('Location: index_garantia.php?error=accion');
    exit();
}

$accion = $_POST['accion'];

// Procesar según la acción
switch ($accion) {
    case 'agregar':
        // Verificar permisos usando AuthHelper
        if (!$authHelper->tienePermiso('garantias.add', $rolId)) {
            header('Location: index_garantia.php?error=permisos');
            exit();
        }
        
        // Validar datos
        $errores = [];
        $tipo = trim($_POST['tipo']);
        $descripcion = trim($_POST['descripcion']);
        $valor = (float)$_POST['valor'];
        $usuarioId = $_SESSION['usuario_id'];
        
        if (empty($tipo)) {
            $errores[] = "El tipo de garantía es requerido";
        }
        
        if (empty($descripcion)) {
            $errores[] = "La descripción es requerida";
        }
        
        if ($valor <= 0) {
            $errores[] = "El valor estimado debe ser mayor a cero";
        }
        
        // Procesar archivo
        $nombreArchivo = null;
        if (isset($_FILES['documento']) && $_FILES['documento']['error'] === UPLOAD_ERR_OK) {
            $archivo = $_FILES['documento'];
            $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
            $extensionesPermitidas = ['pdf', 'jpg', 'jpeg', 'png'];
            
            if (!in_array(strtolower($extension), $extensionesPermitidas)) {
                $errores[] = "Solo se permiten archivos PDF, JPG, JPEG o PNG";
            } elseif ($archivo['size'] > 5 * 1024 * 1024) { // 5MB máximo
                $errores[] = "El archivo no puede ser mayor a 5MB";
            } else {
                $nombreArchivo = uniqid() . '.' . $extension;
                $rutaDestino = '../uploads/garantias/' . $nombreArchivo;
                
                if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
                    $errores[] = "Error al subir el archivo";
                    $nombreArchivo = null;
                }
            }
        }
        
        // Si hay errores, redirigir
        if (!empty($errores)) {
            $_SESSION['errores_garantia'] = $errores;
            header('Location: agregar_garantia.php');
            exit();
        }
        
        // Insertar en la base de datos usando PDO
        try {
            $query = "INSERT INTO Garantias (UsuarioID, TipoGarantia, Descripcion, ValorEstimado, DocumentoURL, Estado) 
                      VALUES (:usuarioId, :tipo, :descripcion, :valor, :documento, 'Activa')";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':usuarioId', $usuarioId, PDO::PARAM_INT);
            $stmt->bindParam(':tipo', $tipo, PDO::PARAM_STR);
            $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
            $stmt->bindParam(':valor', $valor);
            $stmt->bindParam(':documento', $nombreArchivo, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                $garantiaId = $db->lastInsertId();
                
                // Registrar en transacciones
                registrarTransaccion($db, 'Garantía agregada', 'Garantias', $garantiaId, $valor, $usuarioId);
                
                $_SESSION['mensaje_exito'] = "Garantía agregada correctamente";
                header('Location: index_garantia.php');
                exit();
            } else {
                throw new Exception("Error al ejecutar la consulta");
            }
        } catch (Exception $e) {
            // Eliminar archivo si hubo error en la BD
            if ($nombreArchivo && file_exists($rutaDestino)) {
                unlink($rutaDestino);
            }
            
            $_SESSION['error_garantia'] = "Error al agregar la garantía: " . $e->getMessage();
            header('Location: agregar_garantia.php');
            exit();
        }
        break;
        
    case 'editar':
        // Verificar permisos usando AuthHelper
        if (!$authHelper->tienePermiso('garantias.edit', $rolId)) {
            header('Location: index_garantia.php?error=permisos');
            exit();
        }
        
        if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
            header('Location: index_garantia.php?error=id');
            exit();
        }
        
        $garantiaId = (int)$_POST['id'];
        
        // Obtener garantía actual para verificar permisos
        try {
            $query = "SELECT UsuarioID, DocumentoURL, Estado FROM Garantias WHERE GarantiaID = :garantiaId";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':garantiaId', $garantiaId, PDO::PARAM_INT);
            $stmt->execute();
            $garantiaActual = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$garantiaActual || ($garantiaActual['UsuarioID'] != $_SESSION['usuario_id'] && !$authHelper->tienePermiso('garantias.manage_all', $rolId))) {
                header('Location: index_garantia.php?error=permisos');
                exit();
            }
            
            // Validar datos
            $errores = [];
            $tipo = trim($_POST['tipo']);
            $descripcion = trim($_POST['descripcion']);
            $valor = (float)$_POST['valor'];
            $estado = $authHelper->tienePermiso('garantias.manage_all', $rolId) ? $_POST['estado'] : $garantiaActual['Estado'];
            
            if (empty($tipo)) {
                $errores[] = "El tipo de garantía es requerido";
            }
            
            if (empty($descripcion)) {
                $errores[] = "La descripción es requerida";
            }
            
            if ($valor <= 0) {
                $errores[] = "El valor estimado debe ser mayor a cero";
            }
            
            // Procesar archivo
            $nombreArchivo = $garantiaActual['DocumentoURL'];
            if (isset($_FILES['documento']) && $_FILES['documento']['error'] === UPLOAD_ERR_OK) {
                $archivo = $_FILES['documento'];
                $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
                $extensionesPermitidas = ['pdf', 'jpg', 'jpeg', 'png'];
                
                if (!in_array(strtolower($extension), $extensionesPermitidas)) {
                    $errores[] = "Solo se permiten archivos PDF, JPG, JPEG o PNG";
                } elseif ($archivo['size'] > 5 * 1024 * 1024) { // 5MB máximo
                    $errores[] = "El archivo no puede ser mayor a 5MB";
                } else {
                    // Eliminar archivo anterior si existe
                    if ($nombreArchivo && file_exists('../uploads/garantias/' . $nombreArchivo)) {
                        unlink('../uploads/garantias/' . $nombreArchivo);
                    }
                    
                    $nombreArchivo = uniqid() . '.' . $extension;
                    $rutaDestino = '../uploads/garantias/' . $nombreArchivo;
                    
                    if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
                        $errores[] = "Error al subir el archivo";
                        $nombreArchivo = $garantiaActual['DocumentoURL']; // Mantener el anterior
                    }
                }
            }
            
            // Si hay errores, redirigir
            if (!empty($errores)) {
                $_SESSION['errores_garantia'] = $errores;
                header("Location: editar_garantia.php?id=$garantiaId");
                exit();
            }
            
            // Actualizar en la base de datos
            $query = "UPDATE Garantias SET 
                      TipoGarantia = :tipo, 
                      Descripcion = :descripcion, 
                      ValorEstimado = :valor, 
                      DocumentoURL = :documento, 
                      Estado = :estado
                      WHERE GarantiaID = :garantiaId";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':tipo', $tipo, PDO::PARAM_STR);
            $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
            $stmt->bindParam(':valor', $valor);
            $stmt->bindParam(':documento', $nombreArchivo, PDO::PARAM_STR);
            $stmt->bindParam(':estado', $estado, PDO::PARAM_STR);
            $stmt->bindParam(':garantiaId', $garantiaId, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                // Registrar en transacciones
                registrarTransaccion($db, 'Garantía actualizada', 'Garantias', $garantiaId, $valor, $usuarioId);
                
                $_SESSION['mensaje_exito'] = "Garantía actualizada correctamente";
                header('Location: index_garantia.php');
                exit();
            } else {
                throw new Exception("Error al ejecutar la consulta");
            }
        } catch (Exception $e) {
            $_SESSION['error_garantia'] = "Error al actualizar la garantía: " . $e->getMessage();
            header("Location: editar_garantia.php?id=$garantiaId");
            exit();
        }
        break;
        
    default:
        header('Location: index_garantia.php?error=accion');
        exit();
}

// No es necesario cerrar la conexión explícitamente con PDO
?>