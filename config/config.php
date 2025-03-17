<?php
$host = "db"; // Use 'db' (service name in docker-compose.yml)
$username = "root"; // MySQL username
$password = "rootpassword"; // MySQL password
$database = "studyroom"; // Database name

// Create database connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>