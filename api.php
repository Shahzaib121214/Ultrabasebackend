<?php
// 1. PREVENT CRASHES & HANDLE CORS
session_start(); // Start session for authentication
error_reporting(0);
ini_set('display_errors', 0);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(200); 
    exit(); 
}

function jsonError($msg) {
    echo json_encode(["status" => "error", "message" => $msg]);
    exit();
}

// Catch Fatal Errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE)) {
        if (ob_get_length()) ob_clean();
        jsonError("Server Fatal Error: " . $error['message']);
    }
});

// ==================== RAILWAY DATABASE CONFIGURATION ====================
// FIX: Humne 'Internal' ki jagah 'Public' address use kiya hai taake yeh 
// kahin se bhi connect ho sake (Localhost ya Railway).

$host = 'tramway.proxy.rlwy.net'; // Public Host (Changed from mysql.railway.internal)
$user = 'root';
$pass = 'dGIXnHczcIeccCrrLjImfaiVjEaLRMip'; 
$db   = 'railway';
$port = 43439; // Public Port (Changed from 3306)

// ========================================================================

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
  // Create Connection (with port support for Railway)
  $conn = new mysqli($host, $user, $pass, $db, $port);
  $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    // Agar connect fail ho jaye to clear error dikhao
    jsonError("DB Connect Failed: " . $e->getMessage() . " (Make sure you are using the PUBLIC Railway URL if running locally)");
}

// 3. AUTO-SETUP & REPAIR TABLES
// Yeh check karega ke agar purani table hai to usay naye code ke liye update karde
try {
    // Projects table
    $conn->query("CREATE TABLE IF NOT EXISTS `projects` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `api_key` VARCHAR(64) NOT NULL,
        `user_id` INT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Universal data table
    $conn->query("CREATE TABLE IF NOT EXISTS `universal_data` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `project_id` INT DEFAULT 0,
        `collection` VARCHAR(100) NOT NULL,
        `json_data` LONGTEXT NOT NULL,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (`project_id`, `collection`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Analytics table
    $conn->query("CREATE TABLE IF NOT EXISTS `analytics` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `project_id` INT DEFAULT 0,
        `date` DATE NOT NULL,
        `reads` INT DEFAULT 0,
        `writes` INT DEFAULT 0,
        UNIQUE KEY `unique_project_date` (`project_id`, `date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Users table
    $conn->query("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `email` VARCHAR(100) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // API Keys table
    $conn->query("CREATE TABLE IF NOT EXISTS `api_keys` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `project_id` INT NOT NULL,
        `key_name` VARCHAR(100) NOT NULL,
        `api_key` VARCHAR(64) NOT NULL,
        `permissions` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (`project_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Activity logs table
    $conn->query("CREATE TABLE IF NOT EXISTS `activity_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `project_id` INT NOT NULL,
        `action` VARCHAR(50) NOT NULL,
        `collection` VARCHAR(100),
        `details` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (`project_id`, `created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Backups table
    $conn->query("CREATE TABLE IF NOT EXISTS `backups` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `project_id` INT NOT NULL,
        `backup_name` VARCHAR(100) NOT NULL,
        `backup_data` LONGTEXT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (`project_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Webhooks table
    $conn->query("CREATE TABLE IF NOT EXISTS `webhooks` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `project_id` INT NOT NULL,
        `url` VARCHAR(255) NOT NULL,
        `events` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (`project_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Security rules table
    $conn->query("CREATE TABLE IF NOT EXISTS `security_rules` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `project_id` INT NOT NULL,
        `collection` VARCHAR(100) NOT NULL,
        `rules` TEXT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (`project_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

} catch (Exception $e) {
    // Tables creation failed - log but continue
    error_log("Table creation warning: " . $e->getMessage());
}

// ** IMPORTANT FIX **: Add user_id to projects table if it doesn't exist
try {
    $colCheck = $conn->query("SHOW COLUMNS FROM `projects` LIKE 'user_id'");
    if ($colCheck && $colCheck->num_rows == 0) {
        $conn->query("ALTER TABLE `projects` ADD COLUMN `user_id` INT DEFAULT NULL AFTER `id`");
        $conn->query("ALTER TABLE `projects` ADD INDEX (`user_id`)");
    }
} catch (Exception $e) {
    // Ignore error if already exists
}

// 4. INPUT HANDLING
$inputRaw = file_get_contents("php://input");
$input = json_decode($inputRaw, true);

$action = $_REQUEST['action'] ?? $input['action'] ?? '';
$key = $_REQUEST['key'] ?? $input['key'] ?? '';
$pid = isset($_REQUEST['pid']) ? intval($_REQUEST['pid']) : (isset($input['pid']) ? intval($input['pid']) : 0);

// Allow setup actions without full key check
if ($action === 'check_tables') {
    try {
        $result = $conn->query("SHOW TABLES LIKE 'users'");
        echo json_encode([
            "status" => "success",
            "tables_exist" => ($result && $result->num_rows > 0)
        ]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "tables_exist" => false, "message" => $e->getMessage()]);
    }
    exit;
}

// NEW: Comprehensive Database Health Check
if ($action === 'db_health_check') {
    try {
        $health = [
            "status" => "success",
            "connected" => true,
            "database" => $db,
            "host" => $host,
            "environment" => "Public/Hybrid",
            "tables" => [],
            "all_tables_exist" => true,
            "missing_tables" => []
        ];
        
        // Required tables
        $requiredTables = ['users', 'projects', 'universal_data', 'analytics', 'api_keys', 'activity_logs', 'backups', 'webhooks', 'security_rules'];
        
        // Check each table
        foreach ($requiredTables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            $exists = ($result && $result->num_rows > 0);
            
            $tableInfo = ["name" => $table, "exists" => $exists];
            
            if ($exists) {
                // Get row count
                $countResult = $conn->query("SELECT COUNT(*) as count FROM `$table`");
                $count = $countResult->fetch_assoc()['count'];
                $tableInfo['rows'] = $count;
            } else {
                $health['all_tables_exist'] = false;
                $health['missing_tables'][] = $table;
            }
            
            $health['tables'][] = $tableInfo;
        }
        
        echo json_encode($health);
    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "connected" => false,
            "message" => $e->getMessage()
        ]);
    }
    exit;
}

if ($action === 'setup_tables') {
    try {
        $tables = [];
        
        $conn->query("CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `email` VARCHAR(100) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $tables[] = 'users';
        
        $conn->query("CREATE TABLE IF NOT EXISTS `projects` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `api_key` VARCHAR(64) NOT NULL,
            `user_id` INT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $tables[] = 'projects';
        
        $conn->query("CREATE TABLE IF NOT EXISTS `universal_data` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `project_id` INT DEFAULT 0,
            `collection` VARCHAR(100) NOT NULL,
            `json_data` LONGTEXT NOT NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (`project_id`, `collection`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $tables[] = 'universal_data';
        
        $conn->query("CREATE TABLE IF NOT EXISTS `analytics` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `project_id` INT DEFAULT 0,
            `date` DATE NOT NULL,
            `reads` INT DEFAULT 0,
            `writes` INT DEFAULT 0,
            UNIQUE KEY `unique_project_date` (`project_id`, `date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $tables[] = 'analytics';
        
        $conn->query("CREATE TABLE IF NOT EXISTS `api_keys` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `project_id` INT NOT NULL,
            `key_name` VARCHAR(100) NOT NULL,
            `api_key` VARCHAR(64) NOT NULL,
            `permissions` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (`project_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $tables[] = 'api_keys';
        
        $conn->query("CREATE TABLE IF NOT EXISTS `activity_logs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `project_id` INT NOT NULL,
            `action` VARCHAR(50) NOT NULL,
            `collection` VARCHAR(100),
            `details` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (`project_id`, `created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $tables[] = 'activity_logs';
        
        $conn->query("CREATE TABLE IF NOT EXISTS `backups` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `project_id` INT NOT NULL,
            `backup_name` VARCHAR(100) NOT NULL,
            `backup_data` LONGTEXT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (`project_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $tables[] = 'backups';
        
        $conn->query("CREATE TABLE IF NOT EXISTS `webhooks` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `project_id` INT NOT NULL,
            `url` VARCHAR(255) NOT NULL,
            `events` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (`project_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $tables[] = 'webhooks';
        
        $conn->query("CREATE TABLE IF NOT EXISTS `security_rules` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `project_id` INT NOT NULL,
            `collection` VARCHAR(100) NOT NULL,
            `rules` TEXT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (`project_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $tables[] = 'security_rules';
        
        echo json_encode([
            "status" => "success",
            "message" => "Successfully created " . count($tables) . " tables: " . implode(", ", $tables)
        ]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Setup failed: " . $e->getMessage()]);
    }
    exit;
}

if ($key !== "maxclube_secret") jsonError("Invalid Secret Key");

// --- API ACTIONS ---

// ACTION: Get Projects
if ($action == "get_projects") {
    $userId = $_SESSION['user_id'] ?? null;
    
    if ($userId) {
        // Show only user's projects
        $res = $conn->query("SELECT * FROM `projects` WHERE `user_id` = $userId ORDER BY `id` DESC");
    } else {
        // For backward compatibility, show all projects if not logged in
        $res = $conn->query("SELECT * FROM `projects` WHERE `user_id` IS NULL ORDER BY `id` DESC");
    }
    
    $list = [];
    if($res) {
        while($row = $res->fetch_assoc()) {
            $p_id = $row['id'];
            $stats = $conn->query("SELECT SUM(`reads`) as r, SUM(`writes`) as w FROM `analytics` WHERE `project_id` = $p_id")->fetch_assoc();
            $row['stats'] = $stats ?: ['r'=>0, 'w'=>0];
            $list[] = $row;
        }
    }
    echo json_encode($list);
}

// ACTION: Create Project
elseif ($action == "create_project") {
    $name = $input['name'] ?? 'Untitled App';
    $newKey = bin2hex(random_bytes(16));
    $userId = $_SESSION['user_id'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO `projects` (`user_id`, `name`, `api_key`) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $name, $newKey);
    if($stmt->execute()) echo json_encode(["status"=>"success", "id"=>$conn->insert_id]);
    else jsonError("Create Failed: " . $conn->error);
}

// ACTION: Delete Project
elseif ($action == "delete_project") {
    $id = intval($_REQUEST['id']);
    if ($id > 0) {
        $conn->query("DELETE FROM `projects` WHERE `id`=$id");
        $conn->query("DELETE FROM `universal_data` WHERE `project_id`=$id");
        $conn->query("DELETE FROM `analytics` WHERE `project_id`=$id");
        echo json_encode(["status"=>"success"]);
    } else {
        jsonError("Invalid ID");
    }
}

// ACTION: Get Collections
elseif ($action == "get_collections") {
    $stmt = $conn->prepare("SELECT DISTINCT `collection` FROM `universal_data` WHERE `project_id` = ?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $res = $stmt->get_result();
    $list = [];
    while($row = $res->fetch_assoc()) $list[] = $row['collection'];
    echo json_encode($list);
}

// ACTION: Get Data (Firebase-like response)
elseif ($action == "get") {
    // Analytics
    $d = date('Y-m-d');
    $conn->query("INSERT INTO `analytics` (`project_id`, `date`, `reads`) VALUES ($pid, '$d', 1) ON DUPLICATE KEY UPDATE `reads` = `reads` + 1");

    $coll = $input['collection'] ?? '';
    // More flexible validation - allow letters, numbers, spaces, dots, hyphens, underscores
    if(empty($coll)) jsonError("Collection name is required");
    if(strlen($coll) > 100) jsonError("Collection name too long (max 100 characters)");
    if(!preg_match('/^[a-zA-Z0-9_\-\s.]+$/', $coll)) {
        jsonError("Invalid collection name. Use only letters, numbers, spaces, dots, hyphens, and underscores.");
    }

    $stmt = $conn->prepare("SELECT `id`, `json_data` FROM `universal_data` WHERE `project_id` = ? AND `collection` = ?");
    $stmt->bind_param("is", $pid, $coll);
    $stmt->execute();
    $res = $stmt->get_result();

    $output = [];
    while ($row = $res->fetch_assoc()) {
        // Decode JSON with proper error handling
        $decoded = json_decode($row['json_data'], true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // If JSON decode fails, return raw data
            $decoded = $row['json_data'];
        }
        
        $output[] = [
            "db_id" => $row['id'],
            "data" => $decoded // Can be array, object, or any structure
        ];
    }

    // Firebase-like: Return empty array if no data, not error
    echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// ACTION: Save Data (Firebase-like flexibility)
elseif ($action == "save") {
    // Analytics
    $d = date('Y-m-d');
    $conn->query("INSERT INTO `analytics` (`project_id`, `date`, `writes`) VALUES ($pid, '$d', 1) ON DUPLICATE KEY UPDATE `writes` = `writes` + 1");

    $coll = $input['collection'] ?? '';
    // More flexible validation - allow letters, numbers, spaces, dots, hyphens, underscores
    if(empty($coll)) jsonError("Collection name is required");
    if(strlen($coll) > 100) jsonError("Collection name too long (max 100 characters)");
    if(!preg_match('/^[a-zA-Z0-9_\-\s.]+$/', $coll)) {
        jsonError("Invalid collection name. Use only letters, numbers, spaces, dots, hyphens, and underscores.");
    }

    $data = $input['data'] ?? null;
    if ($data === null) jsonError("No data provided");

    // Firebase-like: Accept ANY type of data
    // - Arrays, Objects, Nested structures, Mixed types - sab kuch!
    // - No restrictions on data structure
    
    // Smart JSON encoding with proper options
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    
    // Check for JSON encoding errors
    if ($json === false) {
        jsonError("Invalid data format: " . json_last_error_msg());
    }
    
    // Check JSON size (increase limit for large data)
    $sizeKB = strlen($json) / 1024;
    if ($sizeKB > 16000) { // 16MB limit (very generous)
        jsonError("Data too large. Maximum 16MB per collection.");
    }

    $conn->begin_transaction();
    try {
        // Step 1: Delete old data for THIS project and THIS collection
        $stmtDel = $conn->prepare("DELETE FROM `universal_data` WHERE `project_id` = ? AND `collection` = ?");
        $stmtDel->bind_param("is", $pid, $coll);
        $stmtDel->execute();
        
        // Step 2: Insert new Data
        $stmt = $conn->prepare("INSERT INTO `universal_data` (`project_id`, `collection`, `json_data`) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $pid, $coll, $json);
        $stmt->execute();
        
        $insertId = $conn->insert_id;
        $conn->commit();
        
        // Log activity
        $details = "Updated collection: $coll (Size: " . number_format($sizeKB, 2) . " KB)";
        logActivity($conn, $pid, 'data_saved', $coll, $details);
        
        // Trigger webhooks
        triggerWebhooks($conn, $pid, 'update', $coll, $data);
        
        echo json_encode([
            "status" => "success", 
            "id" => $insertId, 
            "saved_pid" => $pid,
            "size_kb" => round($sizeKB, 2),
            "message" => "Data saved successfully"
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        jsonError("Save Failed: " . $e->getMessage());
    }
}

// ACTION: Analytics Detail
elseif ($action == "get_analytics_detail") {
    $stmt = $conn->prepare("SELECT * FROM `analytics` WHERE `project_id` = ? ORDER BY `date` DESC LIMIT 7");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $res = $stmt->get_result();
    $stats = [];
    while($row = $res->fetch_assoc()) $stats[] = $row;
    echo json_encode($stats);
}

// ==================== API KEY MANAGEMENT ====================
elseif ($action == "generate_api_key") {
    $keyName = $input['key_name'] ?? 'Default Key';
    $permissions = $input['permissions'] ?? 'read,write';
    $newKey = 'uk_' . bin2hex(random_bytes(24));
    
    $stmt = $conn->prepare("INSERT INTO `api_keys` (`project_id`, `key_name`, `api_key`, `permissions`) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $pid, $keyName, $newKey, $permissions);
    
    if($stmt->execute()) {
        logActivity($conn, $pid, 'api_key_created', null, "Created API key: $keyName");
        echo json_encode(["status"=>"success", "api_key"=>$newKey, "id"=>$conn->insert_id]);
    } else {
        jsonError("Failed to create API key");
    }
}

elseif ($action == "list_api_keys") {
    $stmt = $conn->prepare("SELECT `id`, `key_name`, `api_key`, `permissions`, `is_active`, `created_at` FROM `api_keys` WHERE `project_id` = ? ORDER BY `created_at` DESC");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $res = $stmt->get_result();
    $keys = [];
    while($row = $res->fetch_assoc()) $keys[] = $row;
    echo json_encode($keys);
}

elseif ($action == "revoke_api_key") {
    $keyId = intval($input['key_id']);
    $stmt = $conn->prepare("UPDATE `api_keys` SET `is_active` = 0 WHERE `id` = ? AND `project_id` = ?");
    $stmt->bind_param("ii", $keyId, $pid);
    
    if($stmt->execute()) {
        logActivity($conn, $pid, 'api_key_revoked', null, "Revoked API key ID: $keyId");
        echo json_encode(["status"=>"success"]);
    } else {
        jsonError("Failed to revoke key");
    }
}

elseif ($action == "delete_api_key") {
    $keyId = intval($input['key_id']);
    $stmt = $conn->prepare("DELETE FROM `api_keys` WHERE `id` = ? AND `project_id` = ?");
    $stmt->bind_param("ii", $keyId, $pid);
    
    if($stmt->execute()) {
        logActivity($conn, $pid, 'api_key_deleted', null, "Deleted API key ID: $keyId");
        echo json_encode(["status"=>"success"]);
    } else {
        jsonError("Failed to delete key");
    }
}

// ==================== ACTIVITY LOGS ====================
elseif ($action == "get_activity_logs") {
    $limit = isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : 50;
    $stmt = $conn->prepare("SELECT * FROM `activity_logs` WHERE `project_id` = ? ORDER BY `timestamp` DESC LIMIT ?");
    $stmt->bind_param("ii", $pid, $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    $logs = [];
    while($row = $res->fetch_assoc()) $logs[] = $row;
    echo json_encode($logs);
}

elseif ($action == "clear_activity_logs") {
    $stmt = $conn->prepare("DELETE FROM `activity_logs` WHERE `project_id` = ?");
    $stmt->bind_param("i", $pid);
    if($stmt->execute()) {
        echo json_encode(["status"=>"success", "deleted"=>$stmt->affected_rows]);
    } else {
        jsonError("Failed to clear logs");
    }
}

// ==================== BACKUPS ====================
elseif ($action == "create_backup") {
    $backupName = $input['backup_name'] ?? date('Y-m-d H:i:s');
    
    // Get all collections data for this project
    $stmt = $conn->prepare("SELECT `collection`, `json_data` FROM `universal_data` WHERE `project_id` = ?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $backupData = [];
    while($row = $res->fetch_assoc()) {
        $backupData[$row['collection']] = json_decode($row['json_data']);
    }
    
    $backupJson = json_encode($backupData, JSON_UNESCAPED_UNICODE);
    $sizeKb = strlen($backupJson) / 1024;
    
    $stmt = $conn->prepare("INSERT INTO `backups` (`project_id`, `backup_name`, `backup_data`, `size_kb`) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issd", $pid, $backupName, $backupJson, $sizeKb);
    
    if($stmt->execute()) {
        logActivity($conn, $pid, 'backup_created', null, "Backup: $backupName");
        echo json_encode(["status"=>"success", "id"=>$conn->insert_id, "size_kb"=>round($sizeKb, 2)]);
    } else {
        jsonError("Backup failed");
    }
}

elseif ($action == "list_backups") {
    $stmt = $conn->prepare("SELECT `id`, `backup_name`, `size_kb`, `created_at` FROM `backups` WHERE `project_id` = ? ORDER BY `created_at` DESC");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $res = $stmt->get_result();
    $backups = [];
    while($row = $res->fetch_assoc()) $backups[] = $row;
    echo json_encode($backups);
}

elseif ($action == "restore_backup") {
    $backupId = intval($input['backup_id']);
    
    $stmt = $conn->prepare("SELECT `backup_data` FROM `backups` WHERE `id` = ? AND `project_id` = ?");
    $stmt->bind_param("ii", $backupId, $pid);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if($row = $res->fetch_assoc()) {
        $backupData = json_decode($row['backup_data'], true);
        
        $conn->begin_transaction();
        try {
            // Clear existing data
            $conn->query("DELETE FROM `universal_data` WHERE `project_id` = $pid");
            
            // Restore each collection
            foreach($backupData as $collection => $data) {
                $json = json_encode($data, JSON_UNESCAPED_UNICODE);
                $stmt = $conn->prepare("INSERT INTO `universal_data` (`project_id`, `collection`, `json_data`) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $pid, $collection, $json);
                $stmt->execute();
            }
            
            $conn->commit();
            logActivity($conn, $pid, 'backup_restored', null, "Restored backup ID: $backupId");
            echo json_encode(["status"=>"success"]);
        } catch (Exception $e) {
            $conn->rollback();
            jsonError("Restore failed: " . $e->getMessage());
        }
    } else {
        jsonError("Backup not found");
    }
}

elseif ($action == "delete_backup") {
    $backupId = intval($input['backup_id']);
    $stmt = $conn->prepare("DELETE FROM `backups` WHERE `id` = ? AND `project_id` = ?");
    $stmt->bind_param("ii", $backupId, $pid);
    
    if($stmt->execute()) {
        logActivity($conn, $pid, 'backup_deleted', null, "Deleted backup ID: $backupId");
        echo json_encode(["status"=>"success"]);
    } else {
        jsonError("Failed to delete backup");
    }
}

// ==================== WEBHOOKS ====================
elseif ($action == "add_webhook") {
    $url = $input['webhook_url'] ?? '';
    $events = $input['events'] ?? 'create,update,delete';
    
    if(!filter_var($url, FILTER_VALIDATE_URL)) jsonError("Invalid webhook URL");
    
    $stmt = $conn->prepare("INSERT INTO `webhooks` (`project_id`, `webhook_url`, `events`) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $pid, $url, $events);
    
    if($stmt->execute()) {
        logActivity($conn, $pid, 'webhook_added', null, "Webhook: $url");
        echo json_encode(["status"=>"success", "id"=>$conn->insert_id]);
    } else {
        jsonError("Failed to add webhook");
    }
}

elseif ($action == "list_webhooks") {
    $stmt = $conn->prepare("SELECT * FROM `webhooks` WHERE `project_id` = ? ORDER BY `created_at` DESC");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $res = $stmt->get_result();
    $webhooks = [];
    while($row = $res->fetch_assoc()) $webhooks[] = $row;
    echo json_encode($webhooks);
}

elseif ($action == "delete_webhook") {
    $webhookId = intval($input['webhook_id']);
    $stmt = $conn->prepare("DELETE FROM `webhooks` WHERE `id` = ? AND `project_id` = ?");
    $stmt->bind_param("ii", $webhookId, $pid);
    
    if($stmt->execute()) {
        logActivity($conn, $pid, 'webhook_deleted', null, "Deleted webhook ID: $webhookId");
        echo json_encode(["status"=>"success"]);
    } else {
        jsonError("Failed to delete webhook");
    }
}

elseif ($action == "toggle_webhook") {
    $webhookId = intval($input['webhook_id']);
    $isActive = intval($input['is_active']);
    
    $stmt = $conn->prepare("UPDATE `webhooks` SET `is_active` = ? WHERE `id` = ? AND `project_id` = ?");
    $stmt->bind_param("iii", $isActive, $webhookId, $pid);
    
    if($stmt->execute()) {
        echo json_encode(["status"=>"success"]);
    } else {
        jsonError("Failed to toggle webhook");
    }
}

// ==================== SEARCH ====================
elseif ($action == "search_data") {
    $query = $_REQUEST['query'] ?? '';
    if(strlen($query) < 2) jsonError("Query too short");
    
    $stmt = $conn->prepare("SELECT `collection`, `json_data` FROM `universal_data` WHERE `project_id` = ? AND `json_data` LIKE ?");
    $searchTerm = "%$query%";
    $stmt->bind_param("is", $pid, $searchTerm);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $results = [];
    while($row = $res->fetch_assoc()) {
        $results[] = [
            'collection' => $row['collection'],
            'data' => json_decode($row['json_data'])
        ];
    }
    
    logActivity($conn, $pid, 'search', null, "Searched: $query");
    echo json_encode($results);
}

// ==================== SECURITY RULES ====================
elseif ($action == "save_rules") {
    $rules = $input['rules'] ?? '{}';
    
    $stmt = $conn->prepare("INSERT INTO `security_rules` (`project_id`, `rules_json`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `rules_json` = ?");
    $stmt->bind_param("iss", $pid, $rules, $rules);
    
    if($stmt->execute()) {
        logActivity($conn, $pid, 'rules_updated', null, "Security rules updated");
        echo json_encode(["status"=>"success"]);
    } else {
        jsonError("Failed to save rules");
    }
}

elseif ($action == "get_rules") {
    $stmt = $conn->prepare("SELECT `rules_json` FROM `security_rules` WHERE `project_id` = ?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if($row = $res->fetch_assoc()) {
        echo $row['rules_json'];
    } else {
        echo json_encode(["read" => true, "write" => true]);
    }
}

// ==================== AUTHENTICATION ====================
elseif ($action == "register") {
    $username = $input['username'] ?? '';
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    
    // Validation
    if (strlen($username) < 3) jsonError("Username must be at least 3 characters");
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonError("Invalid email address");
    if (strlen($password) < 6) jsonError("Password must be at least 6 characters");
    
    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT `id` FROM `users` WHERE `username` = ? OR `email` = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        jsonError("Username or email already exists");
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $conn->prepare("INSERT INTO `users` (`username`, `email`, `password`) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashedPassword);
    
    if ($stmt->execute()) {
        $userId = $conn->insert_id;
        
        // Auto-login after registration
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        
        echo json_encode([
            "status" => "success",
            "message" => "Registration successful",
            "user" => [
                "id" => $userId,
                "username" => $username,
                "email" => $email
            ]
        ]);
    } else {
        jsonError("Registration failed: " . $conn->error);
    }
}

elseif ($action == "login") {
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        jsonError("Username and password are required");
    }
    
    // Find user by username or email
    $stmt = $conn->prepare("SELECT `id`, `username`, `email`, `password` FROM `users` WHERE `username` = ? OR `email` = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        jsonError("Invalid username or password");
    }
    
    $user = $result->fetch_assoc();
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        jsonError("Invalid username or password");
    }
    
    // Create session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    
    echo json_encode([
        "status" => "success",
        "message" => "Login successful",
        "user" => [
            "id" => $user['id'],
            "username" => $user['username'],
            "email" => $user['email']
        ]
    ]);
}

elseif ($action == "logout") {
    session_destroy();
    echo json_encode(["status" => "success", "message" => "Logged out successfully"]);
}

elseif ($action == "check_session") {
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            "status" => "success",
            "logged_in" => true,
            "user" => [
                "id" => $_SESSION['user_id'],
                "username" => $_SESSION['username'],
                "email" => $_SESSION['email']
            ]
        ]);
    } else {
        echo json_encode([
            "status" => "success",
            "logged_in" => false
        ]);
    }
}

else {
    jsonError("Unknown Action");
}

// ==================== HELPER FUNCTIONS ====================
function logActivity($conn, $pid, $action, $collection, $details) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $conn->prepare("INSERT INTO `activity_logs` (`project_id`, `action`, `collection`, `details`, `ip_address`) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $pid, $action, $collection, $details, $ip);
    $stmt->execute();
}

function triggerWebhooks($conn, $pid, $event, $collection, $data) {
    $stmt = $conn->prepare("SELECT `webhook_url`, `events` FROM `webhooks` WHERE `project_id` = ? AND `is_active` = 1");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $res = $stmt->get_result();
    
    while($row = $res->fetch_assoc()) {
        $events = explode(',', $row['events']);
        if(in_array($event, $events)) {
            $payload = json_encode([
                'event' => $event,
                'collection' => $collection,
                'data' => $data,
                'timestamp' => time()
            ]);
            
            // Async webhook call (fire and forget)
            $ch = curl_init($row['webhook_url']);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_exec($ch);
            curl_close($ch);
        }
    }
}


$conn->close();
?>