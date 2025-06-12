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
    public $MaximoParticipantes;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Métodos básicos CRUD------------------
    // Create a new junta
    public function crear() {
        try {
            // Generate unique code
            $this->CodigoJunta = $this->generarCodigoUnico();

            $query = "INSERT INTO {$this->table} 
                    (NombreJunta, Descripcion, CodigoJunta, MontoAporte, FrecuenciaPago, 
                    FechaInicio, CreadaPor, RequiereGarantia, MontoGarantia, 
                    PorcentajeComision, PorcentajePenalidad, DiasGraciaPenalidad, MaximoParticipantes) 
                    VALUES 
                    (:nombre, :descripcion, :codigo, :monto, :frecuencia, 
                    :fechaInicio, :creadaPor, :requiereGarantia, :montoGarantia, 
                    :comision, :penalidad, :diasGracia, :maxParticipantes)";

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
            $stmt->bindParam(':maxParticipantes', $this->MaximoParticipantes, PDO::PARAM_INT);

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
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $query = "UPDATE {$this->table} 
                    SET NombreJunta = :nombre, 
                        Descripcion = :descripcion,
                        RequiereGarantia = :requiereGarantia,
                        MontoGarantia = :montoGarantia,
                        PorcentajeComision = :comision,
                        PorcentajePenalidad = :penalidad,
                        DiasGraciaPenalidad = :diasGracia,
                        MaximoParticipantes = :maxParticipantes,
                        FechaModificacion = NOW()
                    WHERE JuntaID = :id";

            $stmt = $this->conn->prepare($query);

            // Convertir valores booleanos a 1/0 para MySQL
            $requiereGarantia = $this->RequiereGarantia ? 1 : 0;

            // Bind parameters con tipos explícitos
            $stmt->bindParam(':nombre', $this->NombreJunta, PDO::PARAM_STR);
            $stmt->bindParam(':descripcion', $this->Descripcion, PDO::PARAM_STR);
            $stmt->bindParam(':requiereGarantia', $requiereGarantia, PDO::PARAM_INT);
            $stmt->bindParam(':montoGarantia', $this->MontoGarantia, PDO::PARAM_STR);
            $stmt->bindParam(':comision', $this->PorcentajeComision, PDO::PARAM_STR);
            $stmt->bindParam(':penalidad', $this->PorcentajePenalidad, PDO::PARAM_STR);
            $stmt->bindParam(':diasGracia', $this->DiasGraciaPenalidad, PDO::PARAM_INT);
            $stmt->bindParam(':maxParticipantes', $this->MaximoParticipantes, PDO::PARAM_INT);
            $stmt->bindParam(':id', $this->JuntaID, PDO::PARAM_INT);

            $result = $stmt->execute();
            
            if ($result === false) {
                $errorInfo = $stmt->errorInfo();
                error_log("Error al ejecutar la consulta: " . print_r($errorInfo, true));
                throw new Exception("Error de base de datos: " . $errorInfo[2]);
            }
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error PDO al actualizar junta: " . $e->getMessage() . "\nConsulta: " . $query);
            throw $e; // Re-lanzar la excepción para manejo superior
        } catch (Exception $e) {
            error_log("Error general al actualizar junta: " . $e->getMessage());
            throw $e;
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

    /**
     * Cancela una junta (cambia estado a Cancelada)
     * Versión mejorada que unifica las dos funciones anteriores
     */
    public function cancelarJunta($juntaId) {
        try {
            $query = "UPDATE {$this->table} 
                    SET Estado = 'Cancelada', FechaModificacion = NOW() 
                    WHERE JuntaID = :id AND Estado = 'Activa'";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $juntaId);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al cancelar junta: " . $e->getMessage());
            return false;
        }
    }
    /**
     * Inicia una junta (cambia estado a En progreso)
     */
    public function iniciarJunta($juntaId) {
        try {
            $query = "UPDATE {$this->table} 
                    SET Estado = 'En progreso', FechaInicioReal = NOW(), FechaModificacion = NOW() 
                    WHERE JuntaID = :id AND Estado = 'Activa'";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $juntaId);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al iniciar junta: " . $e->getMessage());
            return false;
        }
    }
    /**
     * Completa una junta (cambia estado a Completada)
     */
    public function completarJunta($juntaId) {
        try {
            $query = "UPDATE {$this->table} 
                    SET Estado = 'Completada', FechaFin = NOW(), FechaModificacion = NOW() 
                    WHERE JuntaID = :id AND Estado = 'En progreso'";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $juntaId);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al completar junta: " . $e->getMessage());
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
    

    // Métodos de consulta-------------------------
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
    // Obtener participantes de una junta
    public function obtenerParticipantes($juntaId) {
        try {
            $query = "SELECT u.UsuarioID, CONCAT(u.Nombre, ' ', u.Apellido) AS NombreCompleto, 
                            u.Email AS CorreoElectronico, u.Telefono,
                            pj.OrdenRecepcion AS Posicion, 
                            cb.Banco, cb.NumeroCuenta,
                            CASE WHEN pj.Activo = 1 THEN 'Activo' ELSE 'Inactivo' END AS EstadoParticipacion, 
                            pj.FechaRegistro AS FechaCreacion
                    FROM ParticipantesJuntas pj
                    JOIN Usuarios u ON pj.UsuarioID = u.UsuarioID
                    JOIN CuentasBancarias cb ON pj.CuentaID = cb.CuentaID
                    WHERE pj.JuntaID = :juntaId
                    ORDER BY pj.OrdenRecepcion ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':juntaId', $juntaId);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener participantes: " . $e->getMessage());
            return [];
        }
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
    public function obtenerHistorialPagos($juntaId) {
        try {
            $query = "SELECT p.PagoID, p.FechaPago, u.NombreCompleto AS NombreParticipante, 
                            p.Monto, p.Estado AS EstadoPago, p.Comprobante, p.MetodoPago
                    FROM pagos p
                    JOIN usuarios u ON p.UsuarioID = u.UsuarioID
                    WHERE p.JuntaID = :juntaId
                    ORDER BY p.FechaPago DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':juntaId', $juntaId);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener historial de pagos: " . $e->getMessage());
            return [];
        }
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
   
    // Métodos de paginación y filtrado
    // Obtener todas las juntas (para administradores)
    public function obtenerTodasLasJuntas($busqueda = '', $estados = ['Activa', 'En progreso'], $pagina = 1, $registrosPorPagina = 10) {
        $offset = ($pagina - 1) * $registrosPorPagina;
        
        $sql = "SELECT j.*, 
            CONCAT(u.Nombre, ' ', u.Apellido) as CreadorNombre,
            COUNT(pj.ParticipanteID) as NumeroParticipantes,
            j.MaximoParticipantes
            FROM {$this->table} j
            JOIN Usuarios u ON j.CreadaPor = u.UsuarioID
            LEFT JOIN ParticipantesJuntas pj ON j.JuntaID = pj.JuntaID AND pj.Activo = 1";
            
        $conditions = [];
        $params = [];
        
        if (!empty($busqueda)) {
            $conditions[] = "(j.NombreJunta LIKE :busqueda OR j.CodigoJunta LIKE :busqueda)";
            $params[':busqueda'] = "%$busqueda%";
        }
        
        if (!empty($estados)) {
            if (is_array($estados)) {
                $placeholders = [];
                foreach ($estados as $key => $value) {
                    $placeholders[] = ":estado$key";
                    $params[":estado$key"] = $value;
                }
                $conditions[] = "j.Estado IN (" . implode(", ", $placeholders) . ")";
            } else {
                $conditions[] = "j.Estado = :estado";
                $params[':estado'] = $estados;
            }
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
    public function contarTodasLasJuntas($busqueda = '', $estados = ['Activa', 'En progreso']) {
        $sql = "SELECT COUNT(*) as total 
                FROM {$this->table} j
                JOIN Usuarios u ON j.CreadaPor = u.UsuarioID";
        
        $conditions = [];
        $params = [];
        
        if (!empty($busqueda)) {
            $conditions[] = "(j.NombreJunta LIKE :busqueda OR j.CodigoJunta LIKE :busqueda)";
            $params[':busqueda'] = "%$busqueda%";
        }
        
        if (!empty($estados)) {
            if (is_array($estados)) {
                $placeholders = [];
                foreach ($estados as $key => $value) {
                    $placeholders[] = ":estado$key";
                    $params[":estado$key"] = $value;
                }
                $conditions[] = "j.Estado IN (" . implode(", ", $placeholders) . ")";
            } else {
                $conditions[] = "j.Estado = :estado";
                $params[':estado'] = $estados;
            }
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'];
    }

    public function obtenerJuntasPorUsuario($usuarioID, $busqueda = '', $estados = ['Activa', 'En progreso'], $pagina = 1, $registrosPorPagina = 10) {
        $offset = ($pagina - 1) * $registrosPorPagina;
        
        $sql = "SELECT DISTINCT j.*, 
                (SELECT COUNT(*) FROM ParticipantesJuntas WHERE JuntaID = j.JuntaID AND Activo = 1) as NumeroParticipantes,
                (SELECT COUNT(*) FROM ParticipantesJuntas WHERE JuntaID = j.JuntaID) as MaximoParticipantes,
                EXISTS(SELECT 1 FROM ParticipantesJuntas WHERE JuntaID = j.JuntaID AND UsuarioID = :usuarioID AND Activo = 1) as EsParticipante
                FROM Juntas j
                LEFT JOIN ParticipantesJuntas p ON j.JuntaID = p.JuntaID AND p.Activo = 1
                WHERE p.UsuarioID = :usuarioIDParam";
        
        $params = [
            ':usuarioID' => $usuarioID,
            ':usuarioIDParam' => $usuarioID
        ];
        
        if (!empty($busqueda)) {
            $sql .= " AND (j.NombreJunta LIKE :busqueda OR j.CodigoJunta LIKE :busqueda)";
            $params[':busqueda'] = "%$busqueda%";
        }
        
        if (!empty($estados)) {
            $placeholders = [];
            foreach ($estados as $key => $value) {
                $placeholders[] = ":estado$key";
                $params[":estado$key"] = $value;
            }
            $sql .= " AND j.Estado IN (" . implode(", ", $placeholders) . ")";
        }
    
        $sql .= " ORDER BY j.FechaCreacion DESC
                LIMIT :offset, :limit";
        
        $stmt = $this->conn->prepare($sql);
        
        // Vincular parámetros
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $registrosPorPagina, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }  

    public function contarJuntasPorUsuario($usuarioID, $busqueda = '', $estados = ['Activa', 'En progreso']) {
        $sql = "SELECT COUNT(DISTINCT j.JuntaID) as total 
                FROM Juntas j
                LEFT JOIN ParticipantesJuntas p ON j.JuntaID = p.JuntaID AND p.Activo = 1
                WHERE p.UsuarioID = :usuarioID";
        
        $params = [':usuarioID' => $usuarioID];
        
        if (!empty($busqueda)) {
            $sql .= " AND (j.NombreJunta LIKE :busqueda OR j.CodigoJunta LIKE :busqueda)";
            $params[':busqueda'] = "%$busqueda%";
        }
        
        if (!empty($estados)) {
            $placeholders = [];
            foreach ($estados as $key => $value) {
                $placeholders[] = ":estado$key";
                $params[":estado$key"] = $value;
            }
            $sql .= " AND j.Estado IN (" . implode(", ", $placeholders) . ")";
        }
        
        $stmt = $this->conn->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'];
    }
    /**
     * Verifica si un usuario es participante de una junta (versión mejorada)
     */
    public function esParticipante($juntaId, $usuarioId) {
        try {
            $query = "SELECT COUNT(*) FROM participantes_junta 
                    WHERE JuntaID = :juntaId AND UsuarioID = :usuarioId AND EstadoParticipacion = 'Activo'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':juntaId', $juntaId);
            $stmt->bindParam(':usuarioId', $usuarioId);
            $stmt->execute();
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error al verificar participante: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Agrega un participante a una junta (versión mejorada)
     */
    public function agregarParticipante($juntaId, $usuarioId) {
        try {
            // 1. Verificar si hay cupo disponible
            $queryCupo = "SELECT COUNT(*) as actual, MaximoParticipantes as maximo 
                        FROM participantes_junta pj
                        JOIN Juntas j ON pj.JuntaID = j.JuntaID
                        WHERE pj.JuntaID = :juntaId AND pj.EstadoParticipacion = 'Activo'";
            
            $stmtCupo = $this->conn->prepare($queryCupo);
            $stmtCupo->bindParam(':juntaId', $juntaId);
            $stmtCupo->execute();
            $cupo = $stmtCupo->fetch(PDO::FETCH_ASSOC);
            
            if ($cupo['actual'] >= $cupo['maximo']) {
                return false; // No hay cupo disponible
            }

            // 2. Verificar si ya es participante (aunque inactivo)
            $queryCheck = "SELECT COUNT(*) FROM participantes_junta 
                        WHERE JuntaID = :juntaId AND UsuarioID = :usuarioId";
            
            $stmtCheck = $this->conn->prepare($queryCheck);
            $stmtCheck->bindParam(':juntaId', $juntaId);
            $stmtCheck->bindParam(':usuarioId', $usuarioId);
            $stmtCheck->execute();
            
            if ($stmtCheck->fetchColumn() > 0) {
                // 3a. Actualizar si ya existe pero está inactivo
                $query = "UPDATE participantes_junta 
                        SET EstadoParticipacion = 'Activo', 
                            FechaModificacion = NOW()
                        WHERE JuntaID = :juntaId AND UsuarioID = :usuarioId";
            } else {
                // 3b. Insertar nuevo participante
                $query = "INSERT INTO participantes_junta 
                        (JuntaID, UsuarioID, EstadoParticipacion, FechaCreacion, Posicion)
                        VALUES (:juntaId, :usuarioId, 'Activo', NOW(), 
                        (SELECT IFNULL(MAX(Posicion), 0) + 1 FROM participantes_junta WHERE JuntaID = :juntaId))";
            }
            
            // 4. Ejecutar la operación (INSERT o UPDATE)
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':juntaId', $juntaId);
            $stmt->bindParam(':usuarioId', $usuarioId);
            
            if ($stmt->execute()) {
                // 5. Actualizar contador de participantes
                $this->actualizarContadorParticipantes($juntaId);
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error al agregar participante: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza el contador de participantes en una junta (privada)
     */
    private function actualizarContadorParticipantes($juntaId) {
        $query = "UPDATE {$this->table} j
                SET j.NumeroParticipantes = (
                    SELECT COUNT(*) FROM participantes_junta 
                    WHERE JuntaID = :juntaId AND EstadoParticipacion = 'Activo'
                ),
                FechaModificacion = NOW()
                WHERE j.JuntaID = :juntaId";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':juntaId', $juntaId);
        return $stmt->execute();
    }

    /**
     * Obtiene los participantes activos de una junta (versión mejorada)
     */
        
    public function obtenerJuntaPorID($id) {
    $query = "SELECT * FROM juntas WHERE JuntaID = ?";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(1, $id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
}