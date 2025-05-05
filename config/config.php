<?php
$host = "db"; // Docker service name
$username = "studyuser"; // Non-root user from docker-compose
$password = "studypassword"; // Matching password
$database = "studyroom";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>