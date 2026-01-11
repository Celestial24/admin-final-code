<?php
// This script fixes the database by adding missing columns to the contracts table
// Run this once in your browser: domain.com/admin/Modules/fix_db.php

class Database
{
    // Use the same credentials as your legalmanagement.php
    private $host = "127.0.0.1"; // Update this if your live server uses 'localhost' or different host
    private $db_name = "admin_new";
    private $username = "admin_new";
    private $password = "123";
    public $conn;

    public function getConnection()
    {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8mb4");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
            die();
        }
        return $this->conn;
    }
}

$database = new Database();
$db = $database->getConnection();

echo "<h2>Database Migration Tool</h2>";

// List of columns to check/add
$columns = [
    'file_path' => "ADD COLUMN file_path VARCHAR(255) AFTER case_id",
    'description' => "ADD COLUMN description TEXT AFTER case_id",
    'risk_level' => "ADD COLUMN risk_level VARCHAR(50) DEFAULT 'Low'",
    'risk_score' => "ADD COLUMN risk_score INT DEFAULT 0",
    'risk_factors' => "ADD COLUMN risk_factors TEXT",
    'recommendations' => "ADD COLUMN recommendations TEXT",
    'analysis_summary' => "ADD COLUMN analysis_summary TEXT"
];

foreach ($columns as $col => $sql) {
    echo "Checking column '$col'... ";
    try {
        // Try to select the column to see if it exists
        $db->query("SELECT $col FROM contracts LIMIT 1");
        echo "<span style='color:green'>Exists.</span><br>";
    } catch (PDOException $e) {
        // If error (column not found), add it
        echo "<span style='color:orange'>Missing. Adding...</span> ";
        try {
            $db->exec("ALTER TABLE contracts $sql");
            echo "<span style='color:green'>Success!</span><br>";
        } catch (PDOException $ex) {
            echo "<span style='color:red'>Failed: " . $ex->getMessage() . "</span><br>";
        }
    }
}

echo "<hr><p>Done. You can delete this file now.</p>";
?>