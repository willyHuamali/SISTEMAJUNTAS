<?php
class Junta {
    private $conn;
    private $table = 'Juntas';

    // Properties
    public $JuntaID;
    public $NombreJunta;
    public $Descripcion;
    public $CodigoJunta;
    public $MontoAporte;
    public $FrecuenciaPago;
    public $FechaInicio;
    public $FechaCreacion;
    public $CreadaPor;
    public $Estado;
    public $RequiereGarantia;
    public $MontoGarantia;
    public $PorcentajeComision;
    public $PorcentajePenalidad;
    public $DiasGraciaPenalidad;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create a new junta
    public function crear() {
        try {
            // Generate unique code
            $this->CodigoJunta = $this->generarCodigoUnico();

            $query = "INSERT INTO {$this->table} 
                      (NombreJunta, Descripcion, CodigoJunta, MontoAporte, FrecuenciaPago, 
                      FechaInicio, CreadaPor, RequiereGarantia, MontoGarantia, 
                      PorcentajeComision, PorcentajePenalidad, DiasGraciaPenalidad) 
                      VALUES 
                      (:nombre, :descripcion, :codigo, :monto, :frecuencia, 
                      :fechaInicio, :creadaPor, :requiereGarantia, :montoGarantia, 
                      :comision, :penalidad, :diasGracia)";

            $stmt = $this->conn->prepare($query);

            // Bind parameters
            $stmt->bindParam(':nombre', $this->NombreJunta);
            $stmt->bindParam(':descripcion', $this->Descripcion);
            $stmt->bindParam(':codigo', $this->CodigoJunta);
            $stmt->bindParam(':monto', $this->MontoAporte);
            $stmt->bindParam(':frecuencia', $this->FrecuenciaPago);
            $stmt->bindParam(':fechaInicio', $this->FechaInicio);
            $stmt->bindParam(':creadaPor', $this->CreadaPor);
            $stmt->bindParam(':requiereGarantia', $this->RequiereGarantia, PDO::PARAM_BOOL);
            $stmt->bindParam(':montoGarantia', $this->MontoGarantia);
            $stmt->bindParam(':comision', $this->PorcentajeComision);
            $stmt->bindParam(':penalidad', $this->PorcentajePenalidad);
            $stmt->bindParam(':diasGracia', $this->DiasGraciaPenalidad);

            // Execute and return result
            if ($stmt->execute()) {
                $this->JuntaID = $this->conn->lastInsertId();
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error al crear junta: " . $e->getMessage());
            return false;
        }
    }

    // Update a junta
    public function actualizar() {
        try {
            $query = "UPDATE {$this->table} 
                      SET NombreJunta = :nombre, 
                          Descripcion = :descripcion,
                          RequiereGarantia = :requiereGarantia,
                          MontoGarantia = :montoGarantia,
                          PorcentajeComision = :comision,
                          PorcentajePenalidad = :penalidad,
                          DiasGraciaPenalidad = :diasGracia
                      WHERE JuntaID = :id";

            $stmt = $this->conn->prepare($query);

            // Bind parameters
            $stmt->bindParam(':nombre', $this->NombreJunta);
            $stmt->bindParam(':descripcion', $this->Descripcion);
            $stmt->bindParam(':requiereGarantia', $this->RequiereGarantia, PDO::PARAM_BOOL);
            $stmt->bindParam(':montoGarantia', $this->MontoGarantia);
            $stmt->bindParam(':comision', $this->PorcentajeComision);
            $stmt->bindParam(':penalidad', $this->PorcentajePenalidad);
            $stmt->bindParam(':diasGracia', $this->DiasGraciaPenalidad);
            $stmt->bindParam(':id', $this->JuntaID);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al actualizar junta: " . $e->getMessage());
            return false;
        }
    }

    // Delete a junta
    public function eliminar($juntaID) {
        try {
            // First, check if there are any participants or transactions
            $queryCheck = "SELECT COUNT(*) as count FROM ParticipantesJuntas WHERE JuntaID = :id";
            $stmtCheck = $this->conn->prepare($queryCheck);
            $stmtCheck->bindParam(':id', $juntaID);
            $stmtCheck->execute();
            $result = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                return false; // Can't delete if there are participants
            }

            // If no participants, proceed with deletion
            $query = "DELETE FROM {$this->table} WHERE JuntaID = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $juntaID);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al eliminar junta: " . $e->getMessage());
            return false;
        }
    }

    // Cancel a junta
    public function cancelar($juntaID) {
        try {
            $query = "UPDATE {$this->table} 
                      SET Estado = 'Cancelada'
                      WHERE JuntaID = :id AND Estado = 'Activa'";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $juntaID);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al cancelar junta: " . $e->getMessage());
            return false;
        }
    }

    // Generate unique code for junta
    private function generarCodigoUnico() {
        $codigo = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        
        // Verify code is unique
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE CodigoJunta = :codigo";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':codigo', $codigo);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            return $this->generarCodigoUnico(); // Recursive call if code exists
        }
        return $codigo;
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
            return [];
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
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
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
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    // Obtener todas las juntas (para administradores)
    public function obtenerTodasLasJuntas($busqueda = '', $estado = '', $pagina = 1, $registrosPorPagina = 10) {
        $offset = ($pagina - 1) * $registrosPorPagina;
        
        $sql = "SELECT j.*, 
                COUNT(pj.ParticipanteID) as NumeroParticipantes,
                (SELECT COUNT(*) FROM ParticipantesJuntas WHERE JuntaID = j.JuntaID) as MaximoParticipantes
                FROM {$this->table} j
                LEFT JOIN ParticipantesJuntas pj ON j.JuntaID = pj.JuntaID AND pj.Activo = 1";
        
        $conditions = [];
        $params = [];
        
        if (!empty($busqueda)) {
            $conditions[] = "(j.NombreJunta LIKE :busqueda OR j.CodigoJunta LIKE :busqueda)";
            $params[':busqueda'] = "%$busqueda%";
        }
        
        if (!empty($estado)) {
            $conditions[] = "j.Estado = :estado";
            $params[':estado'] = $estado;
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $sql .= " GROUP BY j.JuntaID
                ORDER BY j.FechaCreacion DESC
                LIMIT :offset, :limit";
        
        $stmt = $this->conn->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $registrosPorPagina, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Contar todas las juntas (para administradores)
    public function contarTodasLasJuntas($busqueda = '', $estado = '') {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} j";
        
        $conditions = [];
        $params = [];
        
        if (!empty($busqueda)) {
            $conditions[] = "(j.NombreJunta LIKE :busqueda OR j.CodigoJunta LIKE :busqueda)";
            $params[':busqueda'] = "%$busqueda%";
        }
        
        if (!empty($estado)) {
            $conditions[] = "j.Estado = :estado";
            $params[':estado'] = $estado;
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'];
    }
}