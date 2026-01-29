<?php
class Database {
    // Cambiamos localhost por 127.0.0.1:3307
    private $host = "127.0.0.1:3307"; 
    private $db_name = "inn10";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this->conn;
        } catch(PDOException $exception) {
            // Muestra el error real en pantalla para saber qué pasa si falla
            echo "Error de conexión: " . $exception->getMessage();
            return null;
        }
    }
}
?>