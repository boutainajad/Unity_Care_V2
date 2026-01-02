<?php 
    class Database {
    private $host = "localhost";
    private $dbname = "unity_care_v2";
    private $user = "root";
    private $password = "";
    private $conn;

    public function connect() {
        if ($this->conn === null) {
            try {
                $this->conn = new PDO(
                    "mysql:host={$this->host};dbname={$this->dbname}", 
                    $this->user, 
                    $this->password
                );
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                echo "Connection réussie!";
            } catch (PDOException $e) {
                die("Connection failed: " . $e->getMessage());
            }
        }
        return $this->conn;
    }
}
$db = new Database();
$conn = $db->connect();