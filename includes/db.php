<?php
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $charset = DB_CHARSET;
    private $conn;

    public function getConnection() {
        if ($this->conn === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false
                ];
                
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
                
                // Test connection
                $this->conn->query("SELECT 1");
            } catch(PDOException $e) {
                error_log("Database error: " . $e->getMessage());
                throw new Exception("Error de conexión con la base de datos");
            }
        }

        return $this->conn;
    }
    
    // Opcional: método para cerrar conexión
    public function closeConnection() {
        $this->conn = null;
    }
}