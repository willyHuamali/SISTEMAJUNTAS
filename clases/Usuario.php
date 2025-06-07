<?php
class Usuario {
    private $conn;
    private $table = 'Usuarios';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Método para autenticar usuarios (versión actualizada)
    public function login($nombreUsuario, $contrasena) {
        $query = "SELECT u.*, r.NombreRol 
                  FROM {$this->table} u
                  JOIN Roles r ON u.RolID = r.RolID
                  WHERE u.NombreUsuario = :nombreUsuario AND u.Activo = 1 LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombreUsuario', $nombreUsuario);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificar contraseña
            if ($this->verificarContrasena($contrasena, $usuario['Salt'], $usuario['ContrasenaHash'])) {
                $this->actualizarUltimoLogin($usuario['UsuarioID']);
                return $usuario;
            }
        }
        
        return false;
    }

    // Método para obtener un usuario por su ID (versión actualizada)
    public function obtenerPorId($usuarioID) {
        $query = "SELECT u.*, r.NombreRol, u.PuntosCredito 
                  FROM {$this->table} u
                  JOIN Roles r ON u.RolID = r.RolID
                  WHERE u.UsuarioID = :usuarioID LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuarioID', $usuarioID);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return false;
    }

    // Método para verificar contraseña (sin cambios)
    private function verificarContrasena($contrasena, $salt, $hashAlmacenado) {
        $hashCalculado = hash_pbkdf2("sha256", $contrasena, $salt, 10000, 32, true);
        return hash_equals($hashAlmacenado, $hashCalculado);
    }

    // Método para actualizar último login (sin cambios)
    private function actualizarUltimoLogin($usuarioID) {
        $query = "UPDATE {$this->table} SET UltimoLogin = NOW() WHERE UsuarioID = :usuarioID";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuarioID', $usuarioID);
        $stmt->execute();
    }

    // Método para verificar si usuario existe (sin cambios)
    public function existeUsuario($nombreUsuario, $email, $dni) {
        $query = "SELECT UsuarioID FROM {$this->table} 
                  WHERE NombreUsuario = :nombreUsuario OR Email = :email OR DNI = :dni 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombreUsuario', $nombreUsuario);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':dni', $dni);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    // Método para registrar nuevos usuarios (sin cambios)
    public function registrar($datos) {
        // Validar datos
        if (empty($datos['nombre']) || empty($datos['apellido']) || empty($datos['dni']) || 
            empty($datos['email']) || empty($datos['nombreUsuario']) || empty($datos['contrasena']) ||
            empty($datos['rolID'])) {
            throw new Exception("Todos los campos son obligatorios.");
        }

        // Verificar si el rol existe
        $query = "SELECT RolID FROM Roles WHERE RolID = :rolID AND Activo = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':rolID', $datos['rolID']);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            throw new Exception("El rol seleccionado no es válido.");
        }

        // Verificar si el usuario ya existe
        if ($this->existeUsuario($datos['nombreUsuario'], $datos['email'], $datos['dni'])) {
            throw new Exception("El nombre de usuario, email o DNI ya están registrados.");
        }

        // Hash de la contraseña
        $salt = random_bytes(16);
        $contrasenaHash = hash_pbkdf2("sha256", $datos['contrasena'], $salt, 10000, 32, true);

        // Insertar usuario
        $query = "INSERT INTO {$this->table} (Nombre, Apellido, DNI, Email, NombreUsuario, ContrasenaHash, Salt, RolID) 
                  VALUES (:nombre, :apellido, :dni, :email, :nombreUsuario, :contrasenaHash, :salt, :rolID)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre', $datos['nombre']);
        $stmt->bindParam(':apellido', $datos['apellido']);
        $stmt->bindParam(':dni', $datos['dni']);
        $stmt->bindParam(':email', $datos['email']);
        $stmt->bindParam(':nombreUsuario', $datos['nombreUsuario']);
        $stmt->bindParam(':contrasenaHash', $contrasenaHash);
        $stmt->bindParam(':salt', $salt);
        $stmt->bindParam(':rolID', $datos['rolID'], PDO::PARAM_INT);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        } else {
            throw new Exception("Error al registrar el usuario.");
        }
    }

    // Resto de los métodos (sin cambios)
    public function obtenerCuentasBancarias($usuarioId) {
        $query = "SELECT * FROM CuentasBancarias WHERE UsuarioID = ? ORDER BY EsPrincipal DESC, FechaRegistro DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerGarantias($usuarioId) {
        $query = "SELECT * FROM Garantias WHERE UsuarioID = ? ORDER BY Estado, FechaRegistro DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerActividadReciente($usuarioId, $limite = 5) {
        $query = "SELECT 
                    'Pago' AS Titulo,
                    CONCAT('Pago de S/', p.MontoTotal, ' para la junta ', j.NombreJunta) AS Descripcion,
                    p.FechaPago AS Fecha,
                    p.MontoTotal AS Monto,
                    p.Estado
                FROM Pagos p
                JOIN ParticipantesJuntas pj ON p.ParticipanteID = pj.ParticipanteID
                JOIN Juntas j ON pj.JuntaID = j.JuntaID
                WHERE pj.UsuarioID = ?
                
                UNION ALL
                
                SELECT 
                    'Desembolso' AS Titulo,
                    CONCAT('Desembolso de S/', d.Monto, ' recibido de la junta ', j.NombreJunta) AS Descripcion,
                    d.FechaDesembolso AS Fecha,
                    d.Monto AS Monto,
                    d.Estado
                FROM Desembolsos d
                JOIN ParticipantesJuntas pj ON d.ParticipanteID = pj.ParticipanteID
                JOIN Juntas j ON pj.JuntaID = j.JuntaID
                WHERE pj.UsuarioID = ?
                
                UNION ALL
                
                SELECT 
                    'Notificación' AS Titulo,
                    n.Mensaje AS Descripcion,
                    n.FechaCreacion AS Fecha,
                    NULL AS Monto,
                    NULL AS Estado
                FROM Notificaciones n
                WHERE n.UsuarioID = ?
                
                ORDER BY Fecha DESC
                LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$usuarioId, $usuarioId, $usuarioId, $limite]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarPerfil($usuarioId, $datos) {
        $query = "UPDATE Usuarios SET 
                    Nombre = :nombre,
                    Apellido = :apellido,
                    Telefono = :telefono,
                    Direccion = :direccion
                WHERE UsuarioID = :usuarioId";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':nombre' => $datos['Nombre'],
            ':apellido' => $datos['Apellido'],
            ':telefono' => $datos['Telefono'],
            ':direccion' => $datos['Direccion'],
            ':usuarioId' => $usuarioId
        ]);
    }

    public function verificarPassword($usuarioId, $password) {
        // Obtener el hash y salt de la base de datos
        $query = "SELECT ContrasenaHash, Salt FROM Usuarios WHERE UsuarioID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$usuarioId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$resultado) {
            return false;
        }
        
        // Verificar la contraseña
        $hashIngresado = hash_pbkdf2("sha256", $password, $resultado['Salt'], 10000, 32, true);
        return hash_equals($resultado['ContrasenaHash'], $hashIngresado);
    }

    public function cambiarPassword($usuarioId, $nuevoPassword) {
        // Generar un nuevo salt
        $nuevoSalt = random_bytes(16);
        
        // Crear el hash de la nueva contraseña
        $nuevoHash = hash_pbkdf2("sha256", $nuevoPassword, $nuevoSalt, 10000, 32, true);
        
        // Actualizar en la base de datos
        $query = "UPDATE Usuarios SET 
                    ContrasenaHash = :hash,
                    Salt = :salt
                WHERE UsuarioID = :usuarioId";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':hash' => $nuevoHash,
            ':salt' => $nuevoSalt,
            ':usuarioId' => $usuarioId
        ]);
    }

    public function obtenerUsuariosDisponiblesParaJunta($juntaId) {
    $query = "SELECT u.UsuarioID, u.Nombre, u.Apellido, u.Email 
              FROM Usuarios u
              WHERE u.Activo = 1 AND u.UsuarioID NOT IN (
                  SELECT pj.UsuarioID FROM ParticipantesJuntas pj 
                  WHERE pj.JuntaID = :juntaId AND pj.Activo = 1
              )";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':juntaId', $juntaId);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}