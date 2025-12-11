<?php
// database.php
class Database {
    private $db;
    
    public function __construct($path) {
        $this->db = new SQLite3($path);
        $this->initializeTables();
    }
    
    private function initializeTables() {
        // Check if tables exist, create if not
        $tables = [
            'donors' => "CREATE TABLE IF NOT EXISTS donors (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                phone TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            'donations' => "CREATE TABLE IF NOT EXISTS donations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                donor_id INTEGER,
                amount REAL NOT NULL,
                transaction_id TEXT,
                status TEXT DEFAULT 'pending',
                phone TEXT,
                reference TEXT,
                frequency TEXT DEFAULT 'one_time',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (donor_id) REFERENCES donors(id)
            )"
        ];
        
        foreach ($tables as $sql) {
            $this->db->exec($sql);
        }
    }
    
    public function getConnection() {
        return $this->db;
    }
}
?>