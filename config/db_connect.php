<?php
/* Database credentials. */
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'ocms_db';

/* Attempt to connect to MySQL database using Object-Oriented approach */
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Log the error for admin (optional but good practice)
    error_log("Connection failed: " . $conn->connect_error);
    
    // Display user-friendly error message
    die("<b>Error:</b> Unable to connect to the database. Please try again later.");
}
?>
