<?php
class ParticipanteJunta {
    private $conn;
    private $table = 'ParticipantesJuntas';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Método unificado para verificar participantes
    public function verificarParticipante($juntaId, $usuarioId) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} 
                 WHERE JuntaID = :juntaId AND UsuarioID = :usuarioId AND Activo = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':juntaId', $juntaId);
        $stmt->bindParam(':usuarioId', $usuarioId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    // Método mejorado para agregar participantes
    public function agregarParticipante($juntaId, $usuarioId, $orden) {
        try {
            $this->conn->beginTransaction();

            // 1. Verificar si el usuario ya está en la junta
            if ($this->verificarParticipante($juntaId, $usuarioId)) {
                $usuarioInfo = $this->obtenerInfoUsuario($usuarioId);
                throw new Exception("El usuario {$usuarioInfo['Nombre']} {$usuarioInfo['Apellido']} (DNI: {$usuarioInfo['DNI']}) ya es participante de esta junta");
            }

            // 2. Verificar que el orden no esté ocupado
            $query = "SELECT u.Nombre, u.Apellido, u.DNI FROM {$this->table} pj
                    JOIN Usuarios u ON pj.UsuarioID = u.UsuarioID
                    WHERE pj.JuntaID = :juntaId AND pj.OrdenRecepcion = :orden";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':juntaId', $juntaId);
            $stmt->bindParam(':orden', $orden);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                throw new Exception("El orden $orden ya está ocupado por {$result['Nombre']} {$result['Apellido']} (DNI: {$result['DNI']})");
            }

            // 3. Verificar cuenta bancaria principal
            $query = "SELECT c.CuentaID, c.NumeroCuenta, c.Banco 
                    FROM CuentasBancarias c
                    WHERE c.UsuarioID = :usuarioId AND c.EsPrincipal = 1 AND c.Activa = 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':usuarioId', $usuarioId);
            $stmt->execute();
            $cuentaPrincipal = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cuentaPrincipal) {
                $usuarioInfo = $this->obtenerInfoUsuario($usuarioId);
                throw new Exception("El usuario {$usuarioInfo['Nombre']} {$usuarioInfo['Apellido']} no tiene una cuenta bancaria principal activa registrada");
            }

            // 4. Insertar el nuevo participante
            $insertQuery = "INSERT INTO {$this->table} 
                        (JuntaID, UsuarioID, OrdenRecepcion, CuentaID, FechaRegistro) 
                        VALUES (:juntaId, :usuarioId, :orden, :cuentaId, NOW())";
            
            $insertStmt = $this->conn->prepare($insertQuery);
            $insertStmt->bindParam(':juntaId', $juntaId);
            $insertStmt->bindParam(':usuarioId', $usuarioId);
            $insertStmt->bindParam(':orden', $orden);
            $insertStmt->bindParam(':cuentaId', $cuentaPrincipal['CuentaID']);
            
            $resultado = $insertStmt->execute();
            
            if (!$resultado) {
                throw new Exception("Error al ejecutar la inserción: " . implode(", ", $insertStmt->errorInfo()));
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error en agregarParticipante: " . $e->getMessage());
            throw $e; // Re-lanzamos la excepción para manejo en el controlador
        }
    }

    private function obtenerInfoUsuario($usuarioId) {
        $query = "SELECT Nombre, Apellido, DNI FROM Usuarios WHERE UsuarioID = :usuarioId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuarioId', $usuarioId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Método único para obtener juntas activas
    public function obtenerJuntasActivas() {
        $query = "SELECT JuntaID, NombreJunta FROM Juntas 
                 WHERE Estado = 'Activa' ORDER BY NombreJunta";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Eliminar un participante de la junta (marcar como inactivo)
    public function eliminarParticipante($participanteId) {
        try {
            $query = "UPDATE {$this->table} 
                      SET Activo = 0 
                      WHERE ParticipanteID = :participanteId";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':participanteId', $participanteId);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al eliminar participante: " . $e->getMessage());
            return false;
        }
    }

    // Actualizar el orden de los participantes
    public function actualizarOrdenParticipantes($juntaId, $participantes) {
        try {
            $this->conn->beginTransaction();

            foreach ($participantes as $participante) {
                $participanteId = intval($participante['id']);
                $orden = intval($participante['orden']);

                $query = "UPDATE {$this->table} 
                          SET OrdenRecepcion = :orden 
                          WHERE ParticipanteID = :participanteId AND JuntaID = :juntaId";

                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':orden', $orden);
                $stmt->bindParam(':participanteId', $participanteId);
                $stmt->bindParam(':juntaId', $juntaId);
                $stmt->execute();
            }

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error al actualizar orden de participantes: " . $e->getMessage());
            return false;
        }
    }

    // Obtener participantes por junta
    public function obtenerParticipantesPorJunta($juntaId) {
        $query = "SELECT p.*, u.Nombre, u.Apellido, u.Email, u.DNI, u.Telefono 
                FROM {$this->table} p
                JOIN Usuarios u ON p.UsuarioID = u.UsuarioID
                WHERE p.JuntaID = :juntaId AND p.Activo = 1
                ORDER BY p.OrdenRecepcion";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':juntaId', $juntaId);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener información de una junta específica
    public function obtenerJuntaPorId($juntaId) {
        $query = "SELECT * FROM Juntas WHERE JuntaID = :juntaId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':juntaId', $juntaId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene participantes filtrados con paginación
     */
    public function obtenerParticipantesFiltrados($juntaId = '', $usuario = '', $estado = '', $pagina = 1, $porPagina = 10) {
        $offset = ($pagina - 1) * $porPagina;
        
        $query = "SELECT pj.ParticipanteID, pj.JuntaID, pj.UsuarioID, pj.OrdenRecepcion, pj.Activo,
                        u.Nombre, u.Apellido, u.DNI,
                        j.NombreJunta as NombreJunta
                FROM {$this->table} pj
                JOIN Usuarios u ON pj.UsuarioID = u.UsuarioID
                JOIN Juntas j ON pj.JuntaID = j.JuntaID
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($juntaId)) {
            $query .= " AND pj.JuntaID = :juntaId";
            $params[':juntaId'] = $juntaId;
        }
        
        if (!empty($usuario)) {
            $query .= " AND (u.Nombre LIKE :nombreUsuario OR u.Apellido LIKE :apellidoUsuario OR u.DNI LIKE :dniUsuario)";
            $params[':nombreUsuario'] = "%".$usuario."%";
            $params[':apellidoUsuario'] = "%".$usuario."%";
            $params[':dniUsuario'] = "%".$usuario."%";
        }
        
        if ($estado !== '') {
            $query .= " AND pj.Activo = :estado";
            $params[':estado'] = (int)$estado;
        }
        
        $query .= " ORDER BY pj.ParticipanteID DESC LIMIT $offset, $porPagina";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cuenta el total de participantes con los mismos filtros
     */
    public function contarParticipantesFiltrados($juntaId = '', $usuario = '', $estado = '') {
        $query = "SELECT COUNT(*) as total
                FROM {$this->table} pj
                JOIN Usuarios u ON pj.UsuarioID = u.UsuarioID
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($juntaId)) {
            $query .= " AND pj.JuntaID = :juntaId";
            $params[':juntaId'] = $juntaId;
        }
        
        if (!empty($usuario)) {
            $query .= " AND (u.Nombre LIKE :nombreUsuario OR u.Apellido LIKE :apellidoUsuario OR u.DNI LIKE :dniUsuario)";
            $params[':nombreUsuario'] = "%$usuario%";
            $params[':apellidoUsuario'] = "%$usuario%";
            $params[':dniUsuario'] = "%$usuario%";
        }
        
        if ($estado !== '') {
            $query .= " AND pj.Activo = :estado";
            $params[':estado'] = $estado;
        }
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }


    // Agrega este método a la clase ParticipanteJunta
    public function obtenerParticipantePorId($participanteId) {
        $query = "SELECT pj.*, u.Nombre, u.Apellido, u.DNI, j.NombreJunta 
                FROM {$this->table} pj
                JOIN Usuarios u ON pj.UsuarioID = u.UsuarioID
                JOIN Juntas j ON pj.JuntaID = j.JuntaID
                WHERE pj.ParticipanteID = :participanteId";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':participanteId', $participanteId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // También necesitarás el método actualizarParticipante que se usa en el formulario
    public function actualizarParticipante($datos) {
        $activo = isset($datos['Activo']) ? (int)(bool)$datos['Activo'] : 0;

        $query = "UPDATE {$this->table} 
                SET CuentaID = :cuentaId, 
                    GarantiaID = :garantiaId, 
                    OrdenRecepcion = :ordenRecepcion, 
                    Activo = :activo
                WHERE ParticipanteID = :participanteId";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cuentaId', $datos['CuentaID']);
        $stmt->bindParam(':garantiaId', $datos['GarantiaID']);
        $stmt->bindParam(':ordenRecepcion', $datos['OrdenRecepcion']);
        $stmt->bindParam(':activo', $activo, PDO::PARAM_INT); // Especificamos que es un INT
        $stmt->bindParam(':participanteId', $datos['ParticipanteID']);
        
        return $stmt->execute();
    }

    /**
     * Actualiza el orden de recepción de un participante
     * @param int $participanteId ID del participante
     * @param int $orden Nuevo orden de recepción
     * @return bool True si la actualización fue exitosa
     */
    public function actualizarOrdenRecepcion($participanteId, $orden) {
        $query = "UPDATE ParticipantesJuntas 
                SET OrdenRecepcion = :orden 
                WHERE ParticipanteID = :participanteId";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':orden', $orden);
        $stmt->bindParam(':participanteId', $participanteId);
        
        return $stmt->execute();
    }


    /**
     * Obtiene los números de orden asignados para una junta
     * @param int $juntaId ID de la junta
     * @return array Lista de números asignados
     */
    public function obtenerNumerosAsignados($juntaId) {
        $query = "SELECT OrdenRecepcion FROM {$this->table} 
                WHERE JuntaID = :juntaId AND Activo = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':juntaId', $juntaId);
        $stmt->execute();
        
        $resultados = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $resultados ? array_map('intval', $resultados) : [];
    }

    /**
     * Obtiene los números de orden disponibles para una junta
     * @param int $juntaId ID de la junta
     * @param int $maxParticipantes Máximo de participantes (0 si no hay límite)
     * @return array Lista de números disponibles
     */
    public function obtenerNumerosLibres($juntaId, $maxParticipantes) {
        $asignados = $this->obtenerNumerosAsignados($juntaId);
        
        if ($maxParticipantes <= 0) {
            return []; // No hay límite, no mostramos números libres
        }
        
        $todosNumeros = range(1, $maxParticipantes);
        return array_diff($todosNumeros, $asignados);
    }

    /**
     * Obtiene información completa sobre números asignados y libres
     * @param int $juntaId ID de la junta
     * @return array ['asignados' => [], 'libres' => [], 'maximo' => int]
     */
    public function obtenerInfoNumerosJunta($juntaId) {
        $juntaInfo = $this->obtenerJuntaPorId($juntaId);
        $maxParticipantes = $juntaInfo['MaximoParticipantes'] ?? 0;
        
        $asignados = $this->obtenerNumerosAsignados($juntaId);
        $libres = $this->obtenerNumerosLibres($juntaId, $maxParticipantes);
        
        return [
            'asignados' => $asignados,
            'libres' => $libres,
            'maximo' => $maxParticipantes
        ];
    }




}
