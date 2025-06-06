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
}