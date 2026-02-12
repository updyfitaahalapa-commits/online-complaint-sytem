<?php
require_once 'config/db_connect.php';

$sql = "ALTER TABLE users ADD COLUMN phone VARCHAR(20) AFTER email";

if ($conn->query($sql) === TRUE) {
    echo "Column 'phone' added successfully";
} else {
    echo "Error adding column: " . $conn->error;
}

$conn->close();
?>
