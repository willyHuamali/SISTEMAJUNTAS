<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

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
$db = conectarBD();

// Procesar según la acción
switch ($accion) {
    case 'agregar':
        // Verificar permisos
        if (!tienePermiso('garantias.add')) {
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
        
        // Insertar en la base de datos
        $query = "INSERT INTO Garantias (UsuarioID, TipoGarantia, Descripcion, ValorEstimado, DocumentoURL, Estado) 
                  VALUES (?, ?, ?, ?, ?, 'Activa')";
        $stmt = $db->prepare($query);
        $stmt->bind_param("issds", $usuarioId, $tipo, $descripcion, $valor, $nombreArchivo);
        
        if ($stmt->execute()) {
            // Registrar en transacciones
            registrarTransaccion('Garantía agregada', 'Garantias', $db->insert_id);
            
            $_SESSION['mensaje_exito'] = "Garantía agregada correctamente";
            header('Location: index_garantia.php');
        } else {
            // Eliminar archivo si hubo error en la BD
            if ($nombreArchivo && file_exists($rutaDestino)) {
                unlink($rutaDestino);
            }
            
            $_SESSION['error_garantia'] = "Error al agregar la garantía";
            header('Location: agregar_garantia.php');
        }
        break;
        
    case 'editar':
        // Verificar permisos y parámetros
        if (!tienePermiso('garantias.edit')) {
            header('Location: index_garantia.php?error=permisos');
            exit();
        }
        
        if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
            header('Location: index_garantia.php?error=id');
            exit();
        }
        
        $garantiaId = (int)$_POST['id'];
        
        // Obtener garantía actual para verificar permisos
        $query = "SELECT UsuarioID, DocumentoURL FROM Garantias WHERE GarantiaID = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $garantiaId);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $garantiaActual = $resultado->fetch_assoc();
        
        if (!$garantiaActual || ($garantiaActual['UsuarioID'] != $_SESSION['usuario_id'] && !tienePermiso('garantias.manage_all'))) {
            header('Location: index_garantia.php?error=permisos');
            exit();
        }
        
        // Validar datos
        $errores = [];
        $tipo = trim($_POST['tipo']);
        $descripcion = trim($_POST['descripcion']);
        $valor = (float)$_POST['valor'];
        $estado = tienePermiso('garantias.manage_all') ? $_POST['estado'] : $garantiaActual['Estado'];
        
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
                  TipoGarantia = ?, 
                  Descripcion = ?, 
                  ValorEstimado = ?, 
                  DocumentoURL = ?, 
                  Estado = ?
                  WHERE GarantiaID = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("ssdssi", $tipo, $descripcion, $valor, $nombreArchivo, $estado, $garantiaId);
        
        if ($stmt->execute()) {
            // Registrar en transacciones
            registrarTransaccion('Garantía actualizada', 'Garantias', $garantiaId);
            
            $_SESSION['mensaje_exito'] = "Garantía actualizada correctamente";
            header('Location: index_garantia.php');
        } else {
            $_SESSION['error_garantia'] = "Error al actualizar la garantía";
            header("Location: editar_garantia.php?id=$garantiaId");
        }
        break;
        
    default:
        header('Location: index_garantia.php?error=accion');
        break;
}

$db->close();
?>