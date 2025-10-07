<?php

// Security: Prevent direct access via browser
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(403);
    exit('Access denied.');
}

$servername = "localhost";
$username = "root";
$password = "";
$database = "grading_system";

$conn = new mysqli($servername, $username, $password, $database);

// Optional: Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

