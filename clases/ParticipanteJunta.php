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
}