<?php
class CuentaBancaria {
    private $conn;
    private $table = 'CuentasBancarias';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Obtener cuentas por usuario
    public function obtenerCuentasPorUsuario($usuarioID) {
        $query = "SELECT * FROM {$this->table} 
                 WHERE UsuarioID = :usuarioID AND Activa = 1 
                 ORDER BY EsPrincipal DESC, FechaRegistro DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuarioID', $usuarioID);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener cuenta por ID
    public function obtenerPorId($cuentaID) {
        $query = "SELECT * FROM {$this->table} WHERE CuentaID = :cuentaID LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cuentaID', $cuentaID);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Crear nueva cuenta
    public function crear($datos) {
        $query = "INSERT INTO {$this->table} 
                 (UsuarioID, Banco, TipoCuenta, NumeroCuenta, EsPrincipal, Activa) 
                 VALUES 
                 (:usuarioID, :banco, :tipoCuenta, :numeroCuenta, :esPrincipal, 1)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuarioID', $datos['UsuarioID']);
        $stmt->bindParam(':banco', $datos['Banco']);
        $stmt->bindParam(':tipoCuenta', $datos['TipoCuenta']);
        $stmt->bindParam(':numeroCuenta', $datos['NumeroCuenta']);
        $stmt->bindParam(':esPrincipal', $datos['EsPrincipal'], PDO::PARAM_BOOL);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Actualizar cuenta
    public function actualizar($datos) {
        $query = "UPDATE {$this->table} SET 
                 Banco = :banco,
                 TipoCuenta = :tipoCuenta,
                 NumeroCuenta = :numeroCuenta,
                 EsPrincipal = :esPrincipal
                 WHERE CuentaID = :cuentaID";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':banco', $datos['Banco']);
        $stmt->bindParam(':tipoCuenta', $datos['TipoCuenta']);
        $stmt->bindParam(':numeroCuenta', $datos['NumeroCuenta']);
        $stmt->bindParam(':esPrincipal', $datos['EsPrincipal'], PDO::PARAM_BOOL);
        $stmt->bindParam(':cuentaID', $datos['CuentaID']);
        
        return $stmt->execute();
    }

    // Desactivar cuenta
    public function desactivar($cuentaID) {
        $query = "UPDATE {$this->table} SET Activa = 0 WHERE CuentaID = :cuentaID";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cuentaID', $cuentaID);
        return $stmt->execute();
    }

    // Marcar como cuenta principal
    public function marcarComoPrincipal($cuentaID, $usuarioID) {
        // Primero quitar principal de todas las cuentas del usuario
        $query = "UPDATE {$this->table} SET EsPrincipal = 0 
                 WHERE UsuarioID = :usuarioID";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuarioID', $usuarioID);
        $stmt->execute();
        
        // Luego marcar la cuenta específica como principal
        $query = "UPDATE {$this->table} SET EsPrincipal = 1 
                 WHERE CuentaID = :cuentaID";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cuentaID', $cuentaID);
        return $stmt->execute();
    }
}
?>