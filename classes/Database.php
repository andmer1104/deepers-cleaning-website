<?php
/**
 * Database Class
 * 
 * Handles all database operations including connection, queries, and data retrieval.
 * Uses PDO for secure database interactions.
 */
class Database
{
    // ===== PROPERTIES =====
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset;
    private $conn;

    // ===== CONSTRUCTOR =====
    public function __construct($host, $dbname, $username, $password, $charset = 'utf8mb4')
    {
        $this->host = $host;
        $this->dbname = $dbname;
        $this->username = $username;
        $this->password = $password;
        $this->charset = $charset;
        $this->conn = null;
    }

    // ===== DATABASE CONNECTION =====
    /**
     * Establishes connection to the database
     */
    public function Connect()
    {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";

            $this->conn = new PDO($dsn, $this->username, $this->password);

            // Configure PDO for error handling and security
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            return true;

        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Closes the database connection
     */
    public function Disconnect()
    {
        $this->conn = null;
    }

    // ===== DATABASE OPERATIONS =====
    /**
     * Executes a prepared SQL statement with parameters
     * Returns the statement object on success, false on failure
     */
    public function Execute($sql, $params = [])
    {
        try {
            if ($this->conn === null) {
                throw new PDOException("No database connection. Call Connect() first.");
            }

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            return $stmt;

        } catch (PDOException $e) {
            error_log("Database execute error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gets the ID of the last inserted record
     */
    public function GetLastInsertId()
    {
        if ($this->conn !== null) {
            return $this->conn->lastInsertId();
        }

        return null;
    }
}
?>