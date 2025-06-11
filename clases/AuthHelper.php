<?php
namespace Clases;

class AuthHelper {
    private \PDO $db;
    
    public function __construct(\PDO $db) {
        $this->db = $db;
    }
    
    /**
     * Verifica si un rol tiene un permiso específico
     * @param string $codigoPermiso Código del permiso a verificar
     * @param int|null $rolID ID del rol (null para invitados/no autenticados)
     * @return bool True si tiene permiso, False si no
     */
    public function tienePermiso(string $codigoPermiso, ?int $rolID): bool {
        if (!$rolID) return false;
        
        try {
            $query = "SELECT COUNT(*) as tiene 
                      FROM Roles_Permisos rp
                      JOIN Permisos p ON rp.PermisoID = p.PermisoID
                      WHERE rp.RolID = :rolID AND p.Codigo = :codigo";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':rolID', $rolID, \PDO::PARAM_INT);
            $stmt->bindParam(':codigo', $codigoPermiso, \PDO::PARAM_STR);
            $stmt->execute();
            
            $resultado = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return (bool)$resultado['tiene'];
        } catch (\PDOException $e) {
            error_log("Error al verificar permiso: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra una acción en la bitácora del sistema
     * 
     * @param int $usuarioId ID del usuario que realiza la acción
     * @param string $accion Descripción breve de la acción
     * @param string $detalles Detalles adicionales (opcional)
     * @return bool True si se registró correctamente, false si hubo error
     */
    public function registrarAccion($usuarioId, $accion, $detalles = '')
    {
        try {
            $query = "INSERT INTO bitacora (usuario_id, accion, detalles, fecha) 
                     VALUES (:usuario_id, :accion, :detalles, NOW())";
            $stmt = $this->db->prepare($query);
            
            $stmt->bindParam(':usuario_id', $usuarioId, \PDO::PARAM_INT);
            $stmt->bindParam(':accion', $accion, \PDO::PARAM_STR);
            $stmt->bindParam(':detalles', $detalles, \PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Error al registrar acción en bitácora: " . $e->getMessage());
            return false;
        }
    }

    public function tieneAlgunPermiso(array $permisos, $rolId) {
        if (empty($permisos)) {
            return false;
        }
        
        $placeholders = implode(',', array_fill(0, count($permisos), '?'));
        $query = "SELECT COUNT(*) FROM Roles_Permisos rp
                JOIN Permisos p ON rp.PermisoID = p.PermisoID
                WHERE rp.RolID = ? AND p.Codigo IN ($placeholders)";
        
        $stmt = $this->db->prepare($query);  // Cambiado de $this->conn a $this->db
        $params = array_merge([$rolId], $permisos);
        $stmt->execute($params);
        
        return $stmt->fetchColumn() > 0;
    }

    public function obtenerPermisosPorRol(int $rolID): array {
        try {
            $query = "SELECT p.Codigo 
                    FROM Roles_Permisos rp
                    JOIN Permisos p ON rp.PermisoID = p.PermisoID
                    WHERE rp.RolID = :rolID";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':rolID', $rolID, \PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\PDOException $e) {
            error_log("Error al obtener permisos por rol: " . $e->getMessage());
            return [];
        }
    }

    
}