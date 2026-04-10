<?php

namespace App\Facades;

use App\Helpers\EnvHelper;

class DatabaseManager
{
    private static ?DatabaseManager $instance = null;
    private ?DB $db = null;
    
    private function __construct() {}
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection(): DB
    {
        if ($this->db === null) {
            $this->db = new DB();
        }
        return $this->db;
    }
    
    public function closeConnection(): void
    {
        if ($this->db !== null) {
            $this->db->close();
            $this->db = null;
        }
    }
    
    public function __destruct()
    {
        $this->closeConnection();
    }
}