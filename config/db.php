<?php
// config/db.php

$host = "localhost";
$username = "root";      
$password = "";          
$database = "urban_ladder"; 

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

// Set charset to support modern characters
mysqli_set_charset($conn, "utf8mb4");
?>