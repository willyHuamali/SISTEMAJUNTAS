<?php
class Junta {
    private $conn;
    private $table = 'Juntas';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Obtener juntas recientes
    public function obtenerJuntasRecientes($limite = 6) {
        try {
            $query = "SELECT * FROM Juntas ORDER BY FechaCreacion DESC LIMIT :limite";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error en obtenerJuntasRecientes: " . $e->getMessage());
            return []; // Retorna array vacío en caso de error
        }
    }

    // Obtener detalles de una junta específica
    public function obtenerPorId($juntaID) {
        $query = "SELECT j.*, u.Nombre as CreadorNombre, u.Apellido as CreadorApellido 
                  FROM {$this->table} j
                  JOIN Usuarios u ON j.CreadaPor = u.UsuarioID
                  WHERE j.JuntaID = :juntaID";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':juntaID', $juntaID);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Obtener participantes de una junta
    public function obtenerParticipantes($juntaID) {
        $query = "SELECT p.*, u.Nombre, u.Apellido, u.Email, u.Telefono 
                  FROM ParticipantesJuntas p
                  JOIN Usuarios u ON p.UsuarioID = u.UsuarioID
                  WHERE p.JuntaID = :juntaID AND p.Activo = 1
                  ORDER BY p.OrdenRecepcion";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':juntaID', $juntaID);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
        public function contarJuntasActivas($usuarioID) {
        $query = "SELECT COUNT(*) as total FROM ParticipantesJuntas pj 
                JOIN Juntas j ON pj.JuntaID = j.JuntaID 
                WHERE pj.UsuarioID = :usuarioID AND j.Estado = 'Activa'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuarioID', $usuarioID);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'];
    }

    public function obtenerProximoPago($usuarioID) {
        $query = "SELECT MIN(p.MontoBase) as monto FROM Pagos p
                JOIN ParticipantesJuntas pj ON p.ParticipanteID = pj.ParticipanteID
                WHERE pj.UsuarioID = :usuarioID AND p.Estado = 'Pendiente'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuarioID', $usuarioID);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['monto'] ?? 0;
    }

    public function obtenerProximoDesembolso($usuarioID) {
        $query = "SELECT MAX(d.Monto) as monto FROM Desembolsos d
                JOIN ParticipantesJuntas pj ON d.ParticipanteID = pj.ParticipanteID
                WHERE pj.UsuarioID = :usuarioID AND d.Estado = 'Pendiente'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuarioID', $usuarioID);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['monto'] ?? 0;
    }

    public function obtenerActividadReciente($usuarioID, $limite = 5) {
        $query = "SELECT 'Pago' as Tipo, p.FechaPago as Fecha, 
                CONCAT('Pago para junta ', j.NombreJunta) as Descripcion, 
                p.MontoTotal as Monto, p.Estado
                FROM Pagos p
                JOIN ParticipantesJuntas pj ON p.ParticipanteID = pj.ParticipanteID
                JOIN Juntas j ON pj.JuntaID = j.JuntaID
                WHERE pj.UsuarioID = :usuarioID
                
                UNION ALL
                
                SELECT 'Desembolso' as Tipo, d.FechaDesembolso as Fecha,
                CONCAT('Desembolso de junta ', j.NombreJunta) as Descripcion,
                d.Monto as Monto, d.Estado
                FROM Desembolsos d
                JOIN ParticipantesJuntas pj ON d.ParticipanteID = pj.ParticipanteID
                JOIN Juntas j ON pj.JuntaID = j.JuntaID
                WHERE pj.UsuarioID = :usuarioID
                
                ORDER BY Fecha DESC LIMIT :limite";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuarioID', $usuarioID);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

        public function obtenerProximosEventos($usuarioID, $limite = 3) {
            $query = "SELECT 'Pago' as Tipo, p.FechaVencimiento as Fecha,
                    CONCAT('Pago para junta ', j.NombreJunta) as Descripcion
                    FROM Pagos p
                    JOIN ParticipantesJuntas pj ON p.ParticipanteID = pj.ParticipanteID
                    JOIN Juntas j ON pj.JuntaID = j.JuntaID
                    WHERE pj.UsuarioID = :usuarioID AND p.Estado = 'Pendiente'
                    
                    UNION ALL
                    
                    SELECT 'Reunión' as Tipo, j.FechaInicio as Fecha,
                    CONCAT('Reunión de junta ', j.NombreJunta) as Descripcion
                    FROM Juntas j
                    JOIN ParticipantesJuntas pj ON j.JuntaID = pj.JuntaID
                    WHERE pj.UsuarioID = :usuarioID AND j.Estado = 'Activa'
                    
                    ORDER BY Fecha ASC LIMIT :limite";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':usuarioID', $usuarioID);
            $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

    public function obtenerJuntasPorUsuario($usuarioID, $busqueda = '', $estado = '', $pagina = 1, $registrosPorPagina = 10) {
        $offset = ($pagina - 1) * $registrosPorPagina;
        
        $sql = "SELECT j.* FROM Juntas j
                JOIN ParticipantesJuntas p ON j.JuntaID = p.JuntaID
                WHERE p.UsuarioID = :usuarioID AND p.Activo = 1";
        
        $params = [':usuarioID' => $usuarioID];
        
        if (!empty($busqueda)) {
            $sql .= " AND (j.NombreJunta LIKE :busqueda OR j.CodigoJunta LIKE :busqueda)";
            $params[':busqueda'] = "%$busqueda%";
        }
        
        if (!empty($estado)) {
            $sql .= " AND j.Estado = :estado";
            $params[':estado'] = $estado;
        }
        
        $sql .= " LIMIT :offset, :limit";
        
        $stmt = $this->conn->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $registrosPorPagina, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }    

    public function contarJuntasPorUsuario($usuarioID, $busqueda = '', $estado = '') {
        $sql = "SELECT COUNT(*) as total FROM Juntas j
                JOIN ParticipantesJuntas p ON j.JuntaID = p.JuntaID
                WHERE p.UsuarioID = :usuarioID AND p.Activo = 1";
        
        $params = [':usuarioID' => $usuarioID];
        
        if (!empty($busqueda)) {
            $sql .= " AND (j.NombreJunta LIKE :busqueda OR j.CodigoJunta LIKE :busqueda)";
            $params[':busqueda'] = "%$busqueda%";
        }
        
        if (!empty($estado)) {
            $sql .= " AND j.Estado = :estado";
            $params[':estado'] = $estado;
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'];
    }
            
}