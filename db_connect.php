<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "seminar_portal"; // Updated to match your exact database from phpMyAdmin

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}
?>