<?php
/**
 * Initialization script for Railway deployment
 * Ensures required directories exist and are writable
 */

// Create rez directory if it doesn't exist
$rezDir = __DIR__ . '/rez';
if (!is_dir($rezDir)) {
    mkdir($rezDir, 0755, true);
    // Create .htaccess to protect directory (if using Apache)
    if (file_exists(__DIR__ . '/.htaccess')) {
        file_put_contents($rezDir . '/.htaccess', "Deny from all\n");
    }
}

// Check if database directory is writable
$dbFile = __DIR__ . '/passwords.db';
$dbDir = dirname($dbFile);
if (!is_writable($dbDir)) {
    error_log("Warning: Database directory is not writable: $dbDir");
}

echo "Initialization complete. Directories checked.\n";

