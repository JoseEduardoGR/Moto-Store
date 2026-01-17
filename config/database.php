<?php
class Database {
    private $host = '189.203.143.177';
    private $db_name = 'moto_store';
    private $username = 'root';
    private $password = 'vertrigo'; // Cambiar según tu configuración de MAMP
    private $port = '3306'; // Puerto por defecto de MAMP
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>
