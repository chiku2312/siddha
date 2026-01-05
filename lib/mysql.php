<?php
require_once 'config.php';

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $this->connection = mysqli_connect(
            MYSQL_HOST,
            MYSQL_USER,
            MYSQL_PASSWORD,
            MYSQL_DBNAME
        );
        
        if (!$this->connection) {
            error_log("KYC Database Error: " . mysqli_connect_error());
            die("Database connection failed");
        }
        
        mysqli_set_charset($this->connection, 'utf8mb4');
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql) {
        $result = mysqli_query($this->connection, $sql);
        if (!$result) {
            error_log("KYC Query Error: " . mysqli_error($this->connection));
        }
        return $result;
    }
    
    public function escape($string) {
        return mysqli_real_escape_string($this->connection, $string);
    }
    
    public function __destruct() {
        if ($this->connection) {
            mysqli_close($this->connection);
        }
    }
}
