<?php
/*
 * General Helper Functions
 */

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function check_auth($role) {
    if(!isset($_SESSION['role']) || $_SESSION['role'] !== $role){
        header("location: /ocms/login.php");
        exit;
    }
}
?>
