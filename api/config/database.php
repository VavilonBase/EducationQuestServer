<?php
// используем для подключения к базе данных MySQL 
class Database
{

    // учетные данные базы данных 
    private $host = "ec2-52-31-219-113.eu-west-1.compute.amazonaws.com";
    private $db_name = "d2k1albim4eke9";
    private $username = "ejoakkofrykkqe";
    private $password = "59816242e3c8ae5a1c0930e88c891da5f542190249021bbf500c516c08042b8e";
    public $conn;
    // получаем соединение с базой данных 
    public function getConnection()
    {

        $this->conn = null;

        try {
            $this->conn = new PDO("pgsql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
