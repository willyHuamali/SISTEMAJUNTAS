<?php
class ParticipanteJunta {
    private $conn;
    private $table = 'ParticipantesJuntas';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Verificar si un usuario es participante de una junta
    public function esParticipante($juntaId, $usuarioId) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} 
                  WHERE JuntaID = :juntaId AND UsuarioID = :usuarioId AND Activo = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':juntaId', $juntaId);
        $stmt->bindParam(':usuarioId', $usuarioId);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['count'] > 0;
    }

    // Agregar un nuevo participante a la junta
    public function agregarParticipante($juntaId, $usuarioId, $orden) {
        try {
            // Verificar que el usuario no esté ya en la junta
            if ($this->esParticipante($juntaId, $usuarioId)) {
                return false;
            }

            $query = "INSERT INTO {$this->table} 
                      (JuntaID, UsuarioID, OrdenRecepcion, FechaUnion) 
                      VALUES 
                      (:juntaId, :usuarioId, :orden, NOW())";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':juntaId', $juntaId);
            $stmt->bindParam(':usuarioId', $usuarioId);
            $stmt->bindParam(':orden', $orden);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al agregar participante: " . $e->getMessage());
            return false;
        }
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

    // Agregar este método a la clase ParticipanteJunta
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

    // Y este método para obtener información de la junta
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
            $query .= " AND (u.Nombre LIKE :usuario OR u.Apellido LIKE :usuario OR u.DNI LIKE :usuario)";
            $params[':usuario'] = "%$usuario%";
        }
        
        if ($estado !== '') {
            $query .= " AND pj.Activo = :estado";
            $params[':estado'] = $estado;
        }
        
        $query .= " ORDER BY pj.ParticipanteID DESC LIMIT :offset, :limit";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$porPagina, PDO::PARAM_INT);
        
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
            $query .= " AND (u.Nombre LIKE :usuario OR u.Apellido LIKE :usuario OR u.DNI LIKE :usuario)";
            $params[':usuario'] = "%$usuario%";
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

    /**
     * Obtiene todas las juntas activas para los filtros
     */
    /**
 * Obtiene todas las juntas activas para los filtros
 */
    public function obtenerJuntasActivas() {
        $query = "SELECT JuntaID, NombreJunta FROM Juntas WHERE Estado = 'Activa' ORDER BY NombreJunta";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}