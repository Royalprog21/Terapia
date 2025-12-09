<?php
$host = "localhost";   // Database server (leave as localhost)
$user = "root";        // MySQL username (default for XAMPP/WAMP is root)
$pass = "";            // MySQL password (leave empty if none set)
$db   = "terapia";     // 👈 Correct database name

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
