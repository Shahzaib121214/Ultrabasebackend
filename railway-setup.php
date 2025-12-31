<?php
// Railway Database Test & Setup Script
// Run this ONCE to create all tables in Railway database

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Railway Database Setup</h1>";
echo "<pre>";

// Railway MySQL Configuration
$host = 'mysql.railway.internal'; // Yahan sahi host daalo
$user = 'root';
$pass = 'dGIXnHczcIeccCrrLjImfaiVjEaLRMip'; // Yahan sahi password daalo (Last wala check karo)
$db   = 'railway';
$port = 3306;


echo "Connecting to Railway MySQL...\n";
echo "Host: $host:$port\n";
echo "Database: $db\n\n";

// Create connection
$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

echo "✅ Connected successfully!\n\n";

$conn->set_charset("utf8mb4");

// Create all tables
$tables = [
    "users" => "CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `email` VARCHAR(100) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "projects" => "CREATE TABLE IF NOT EXISTS `projects` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `api_key` VARCHAR(64) NOT NULL,
        `user_id` INT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "universal_data" => "CREATE TABLE IF NOT EXISTS `universal_data` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `project_id` INT DEFAULT 0,
        `collection` VARCHAR(100) NOT NULL,
        `json_data` LONGTEXT NOT NULL,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (`project_id`, `collection`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "analytics" => "CREATE TABLE IF NOT EXISTS `analytics` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `project_id` INT DEFAULT 0,
        `date` DATE NOT NULL,
        `reads` INT DEFAULT 0,
        `writes` INT DEFAULT 0,
        UNIQUE KEY `unique_project_date` (`project_id`, `date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "api_keys" => "CREATE TABLE IF NOT EXISTS `api_keys` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `project_id` INT NOT NULL,
        `key_name` VARCHAR(100) NOT NULL,
        `api_key` VARCHAR(64) NOT NULL,
        `permissions` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (`project_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "activity_logs" => "CREATE TABLE IF NOT EXISTS `activity_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `project_id` INT NOT NULL,
        `action` VARCHAR(50) NOT NULL,
        `collection` VARCHAR(100),
        `details` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (`project_id`, `created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "backups" => "CREATE TABLE IF NOT EXISTS `backups` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `project_id` INT NOT NULL,
        `backup_name` VARCHAR(100) NOT NULL,
        `backup_data` LONGTEXT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (`project_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "webhooks" => "CREATE TABLE IF NOT EXISTS `webhooks` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `project_id` INT NOT NULL,
        `url` VARCHAR(255) NOT NULL,
        `events` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (`project_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "security_rules" => "CREATE TABLE IF NOT EXISTS `security_rules` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `project_id` INT NOT NULL,
        `collection` VARCHAR(100) NOT NULL,
        `rules` TEXT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (`project_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

echo "Creating tables...\n\n";

foreach ($tables as $name => $sql) {
    try {
        if ($conn->query($sql)) {
            echo "✅ Table '$name' created/verified\n";
        } else {
            echo "❌ Error creating '$name': " . $conn->error . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Exception for '$name': " . $e->getMessage() . "\n";
    }
}

echo "\n\n=== VERIFICATION ===\n";
$result = $conn->query("SHOW TABLES");
echo "Tables in database:\n";
while ($row = $result->fetch_array()) {
    echo "  - " . $row[0] . "\n";
}

echo "\n✅ Setup complete!\n";
echo "\nYou can now use your UltraBase API.\n";
echo "Delete this file after setup for security.\n";

$conn->close();
echo "</pre>";
?>
