<?php
// D:\php\htdocs\Barcode\config\db.php

// Custom error handler
set_error_handler(function($severity, $message, $file, $line) {
    error_log("[$severity] $message in $file:$line");
    if (in_array($severity, [E_ERROR, E_WARNING, E_PARSE])) {
        die('An error occurred. Please try again later.');
    }
    return true;
});

// Database connection with reconnection logic
define('BASE_URL', 'http://localhost/Barcode');
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'pos_barcode_db';

function connect_db() {
    global $host, $user, $pass, $db;
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die('Database connection failed.');
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

$conn = connect_db();

// Function to reconnect if connection is lost
function reconnect_if_needed($conn) {
    if (!$conn->ping()) {
        $conn->close();
        return connect_db();
    }
    return $conn;
}