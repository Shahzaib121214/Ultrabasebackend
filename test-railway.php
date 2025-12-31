<?php
// Simple test to check if Railway credentials work
header("Content-Type: application/json");

$host = 'mysql.railway.internal'; // Yahan sahi host daalo
$user = 'root';
$pass = 'dGIXnHczcIeccCrrLjImfaiVjEaLRMip'; // Yahan sahi password daalo (Last wala check karo)
$db   = 'railway';
$port = 3306;


try {
    $conn = new mysqli($host, $user, $pass, $db, $port);
    
    if ($conn->connect_error) {
        echo json_encode([
            "status" => "error",
            "message" => "Connection failed: " . $conn->connect_error,
            "host" => $host,
            "port" => $port,
            "database" => $db
        ]);
        exit;
    }
    
    echo json_encode([
        "status" => "success",
        "message" => "Connected successfully!",
        "host" => $host,
        "port" => $port,
        "database" => $db,
        "server_info" => $conn->server_info
    ]);
    
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage(),
        "host" => $host,
        "port" => $port,
        "database" => $db
    ]);
}
?>
