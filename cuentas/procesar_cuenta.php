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

// Obtener ID de usuario de la sesión
$usuarioId = $_SESSION['usuario_id'] ?? null;

// Obtener acción a realizar
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        // Verificar permisos
        if (!$authHelper->tienePermiso('cuentas.add', $_SESSION['rol_id'])) {
            header('Location: /sistemajuntas/dashboard.php?error=no_permission');
            exit;
        }

        // Procesar agregar cuenta
        $banco = htmlspecialchars($_POST['banco'] ?? '', ENT_QUOTES, 'UTF-8');
        $numeroCuenta = htmlspecialchars($_POST['numero_cuenta'] ?? '', ENT_QUOTES, 'UTF-8');
        $tipoCuenta = htmlspecialchars($_POST['tipo_cuenta'] ?? '', ENT_QUOTES, 'UTF-8');
        $moneda = htmlspecialchars($_POST['moneda'] ?? '', ENT_QUOTES, 'UTF-8');
        $esPrincipal = isset($_POST['es_principal']) ? 1 : 0;

        // Validar datos
        if (empty($banco) || empty($numeroCuenta) || empty($tipoCuenta) || empty($moneda)) {
            header('Location: agregar_cuenta.php?error=missing_fields');
            exit;
        }

        // Si se marca como principal, desmarcar las demás
        if ($esPrincipal) {
            $stmt = $db->prepare("UPDATE CuentasBancarias SET EsPrincipal = 0 WHERE UsuarioID = ?");
            $stmt->execute([$usuarioId]);
        }

        // Insertar nueva cuenta
        $query = "INSERT INTO CuentasBancarias 
                  (UsuarioID, NumeroCuenta, Banco, TipoCuenta, Moneda, EsPrincipal) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $success = $stmt->execute([$usuarioId, $numeroCuenta, $banco, $tipoCuenta, $moneda, $esPrincipal]);

        if ($success) {
            header('Location: index_cuenta.php?success=Cuenta+agregada+correctamente');
        } else {
            header('Location: agregar_cuenta.php?error=Error+al+agregar+cuenta');
        }
        break;

    case 'edit':
        // Verificar permisos
        if (!$authHelper->tienePermiso('cuentas.edit', $_SESSION['rol_id'])) {
            header('Location: /sistemajuntas/dashboard.php?error=no_permission');
            exit;
        }

        // Procesar edición de cuenta
        $cuentaId = filter_input(INPUT_POST, 'cuenta_id', FILTER_VALIDATE_INT);
        $banco = htmlspecialchars($_POST['banco'] ?? '', ENT_QUOTES, 'UTF-8');
        $numeroCuenta = htmlspecialchars($_POST['numero_cuenta'] ?? '', ENT_QUOTES, 'UTF-8');
        $tipoCuenta = htmlspecialchars($_POST['tipo_cuenta'] ?? '', ENT_QUOTES, 'UTF-8');
        $moneda = htmlspecialchars($_POST['moneda'] ?? '', ENT_QUOTES, 'UTF-8');
        $esPrincipal = isset($_POST['es_principal']) ? 1 : 0;
        $activa = isset($_POST['activa']) ? 1 : 0;

        // Validar datos
        if (!$cuentaId || empty($banco) || empty($numeroCuenta) || empty($tipoCuenta) || empty($moneda)) {
            header('Location: index_cuenta.php?error=missing_fields');
            exit;
        }

        // Verificar que la cuenta pertenece al usuario
        $stmt = $db->prepare("SELECT UsuarioID FROM CuentasBancarias WHERE CuentaID = ?");
        $stmt->execute([$cuentaId]);
        $cuentaUsuarioId = $stmt->fetchColumn();

        if ($cuentaUsuarioId != $usuarioId) {
            header('Location: index_cuenta.php?error=unauthorized');
            exit;
        }

        // Si se marca como principal, desmarcar las demás
        if ($esPrincipal) {
            $stmt = $db->prepare("UPDATE CuentasBancarias SET EsPrincipal = 0 WHERE UsuarioID = ?");
            $stmt->execute([$usuarioId]);
        }

        // Actualizar cuenta
        $query = "UPDATE CuentasBancarias SET 
                  Banco = ?, NumeroCuenta = ?, TipoCuenta = ?, Moneda = ?, 
                  EsPrincipal = ?, Activa = ? 
                  WHERE CuentaID = ?";
        $stmt = $db->prepare($query);
        $success = $stmt->execute([$banco, $numeroCuenta, $tipoCuenta, $moneda, $esPrincipal, $activa, $cuentaId]);

        if ($success) {
            header('Location: index_cuenta.php?success=Cuenta+actualizada+correctamente');
        } else {
            header('Location: editar_cuenta.php?id='.$cuentaId.'&error=Error+al+actualizar+cuenta');
        }
        break;

    // ... (resto de los casos se mantienen igual)
    case 'delete':
        // Verificar permisos
        if (!$authHelper->tienePermiso('cuentas.delete', $_SESSION['rol_id'])) {
            header('Location: /sistemajuntas/dashboard.php?error=no_permission');
            exit;
        }

        // Procesar eliminación de cuenta
        $cuentaId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$cuentaId) {
            header('Location: index_cuenta.php?error=invalid_id');
            exit;
        }

        // Verificar que la cuenta pertenece al usuario
        $stmt = $db->prepare("SELECT UsuarioID FROM CuentasBancarias WHERE CuentaID = ?");
        $stmt->execute([$cuentaId]);
        $cuentaUsuarioId = $stmt->fetchColumn();

        if ($cuentaUsuarioId != $usuarioId) {
            header('Location: index_cuenta.php?error=unauthorized');
            exit;
        }

        // Verificar si la cuenta está en uso
        $stmt = $db->prepare("SELECT COUNT(*) FROM ParticipantesJuntas WHERE CuentaID = ?");
        $stmt->execute([$cuentaId]);
        $enUso = $stmt->fetchColumn();

        if ($enUso > 0) {
            header('Location: index_cuenta.php?error=Cuenta+en+uso,+no+se+puede+eliminar');
            exit;
        }

        // Eliminar cuenta (marcar como inactiva en lugar de borrar físicamente)
        $stmt = $db->prepare("UPDATE CuentasBancarias SET Activa = 0 WHERE CuentaID = ?");
        $success = $stmt->execute([$cuentaId]);

        if ($success) {
            header('Location: index_cuenta.php?success=Cuenta+eliminada+correctamente');
        } else {
            header('Location: index_cuenta.php?error=Error+al+eliminar+cuenta');
        }
        break;

    case 'activate':
    case 'deactivate':
        // Verificar permisos de gestión
        if (!$authHelper->tienePermiso('cuentas.manage', $_SESSION['rol_id'])) {
            header('Location: /sistemajuntas/dashboard.php?error=no_permission');
            exit;
        }

        $cuentaId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$cuentaId) {
            header('Location: gestionar_cuenta.php?error=invalid_id');
            exit;
        }

        $nuevoEstado = ($action == 'activate') ? 1 : 0;
        $stmt = $db->prepare("UPDATE CuentasBancarias SET Activa = ? WHERE CuentaID = ?");
        $success = $stmt->execute([$nuevoEstado, $cuentaId]);

        if ($success) {
            $msg = ($action == 'activate') ? 'Cuenta+activada+correctamente' : 'Cuenta+desactivada+correctamente';
            header('Location: gestionar_cuenta.php?success='.$msg);
        } else {
            header('Location: gestionar_cuenta.php?error=Error+al+cambiar+estado');
        }
        break;

    default:
        header('Location: index_cuenta.php');
        break;
}