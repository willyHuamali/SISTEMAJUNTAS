<?php
class Garantia {
    private $conn;
    private $table = 'Garantias';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Obtener garantías por usuario
    public function obtenerGarantiasPorUsuario($usuarioID) {
        $query = "SELECT * FROM {$this->table} 
                 WHERE UsuarioID = :usuarioID 
                 ORDER BY Estado, FechaRegistro DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuarioID', $usuarioID);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener garantía por ID
    public function obtenerPorId($garantiaID) {
        $query = "SELECT g.*, u.Nombre, u.Apellido 
                 FROM {$this->table} g
                 JOIN Usuarios u ON g.UsuarioID = u.UsuarioID
                 WHERE g.GarantiaID = :garantiaID LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':garantiaID', $garantiaID);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Crear nueva garantía
    public function crear($datos) {
        $query = "INSERT INTO {$this->table} 
                 (UsuarioID, TipoGarantia, Descripcion, ValorAproximado, 
                 DocumentoPath, Estado, FechaRegistro) 
                 VALUES 
                 (:usuarioID, :tipoGarantia, :descripcion, :valorAproximado, 
                 :documentoPath, 'Pendiente', NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuarioID', $datos['UsuarioID']);
        $stmt->bindParam(':tipoGarantia', $datos['TipoGarantia']);
        $stmt->bindParam(':descripcion', $datos['Descripcion']);
        $stmt->bindParam(':valorAproximado', $datos['ValorAproximado']);
        $stmt->bindParam(':documentoPath', $datos['DocumentoPath']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Actualizar garantía
    public function actualizar($datos) {
        $query = "UPDATE {$this->table} SET 
                 TipoGarantia = :tipoGarantia,
                 Descripcion = :descripcion,
                 ValorAproximado = :valorAproximado
                 WHERE GarantiaID = :garantiaID";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':tipoGarantia', $datos['TipoGarantia']);
        $stmt->bindParam(':descripcion', $datos['Descripcion']);
        $stmt->bindParam(':valorAproximado', $datos['ValorAproximado']);
        $stmt->bindParam(':garantiaID', $datos['GarantiaID']);
        
        return $stmt->execute();
    }

    // Cambiar estado de garantía
    public function cambiarEstado($garantiaID, $estado) {
        $query = "UPDATE {$this->table} SET Estado = :estado 
                 WHERE GarantiaID = :garantiaID";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':garantiaID', $garantiaID);
        return $stmt->execute();
    }

    // Obtener garantías activas
    public function obtenerGarantiasActivas($usuarioID) {
        $query = "SELECT * FROM {$this->table} 
                 WHERE UsuarioID = :usuarioID AND Estado = 'Activa'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuarioID', $usuarioID);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Verificar si una garantía pertenece a un usuario
    public function perteneceAUsuario($garantiaID, $usuarioID) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} 
                 WHERE GarantiaID = :garantiaID AND UsuarioID = :usuarioID";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':garantiaID', $garantiaID);
        $stmt->bindParam(':usuarioID', $usuarioID);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['count'] > 0;
    }
}
?>