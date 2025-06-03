<?php
class Usuario {
    private $conn;
    private $table = 'Usuarios';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Método para autenticar usuarios
    public function login($nombreUsuario, $contrasena) {
        $query = "SELECT UsuarioID, Nombre, Apellido, NombreUsuario, ContrasenaHash, Salt, RolID 
                  FROM {$this->table} 
                  WHERE NombreUsuario = :nombreUsuario AND Activo = 1 LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombreUsuario', $nombreUsuario);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $usuario = $stmt->fetch();
            
            // Verificar contraseña
            if ($this->verificarContrasena($contrasena, $usuario['Salt'], $usuario['ContrasenaHash'])) {
                $this->actualizarUltimoLogin($usuario['UsuarioID']);
                return $usuario;
            }
        }
        
        return false;
    }

    // Método para obtener un usuario por su ID
    public function obtenerPorId($usuarioID) {
        $query = "SELECT UsuarioID, Nombre, Apellido, NombreUsuario, Email, DNI, RolID 
                  FROM {$this->table} 
                  WHERE UsuarioID = :usuarioID LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuarioID', $usuarioID);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return false;
    }

    // Método para verificar contraseña
    private function verificarContrasena($contrasena, $salt, $hashAlmacenado) {
        $hashCalculado = hash_pbkdf2("sha256", $contrasena, $salt, 10000, 32, true);
        return hash_equals($hashAlmacenado, $hashCalculado);
    }

    // Método para actualizar último login
    private function actualizarUltimoLogin($usuarioID) {
        $query = "UPDATE {$this->table} SET UltimoLogin = NOW() WHERE UsuarioID = :usuarioID";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuarioID', $usuarioID);
        $stmt->execute();
    }

    // Método para verificar si usuario existe (para registro)
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

    // Método para registrar nuevos usuarios
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
}