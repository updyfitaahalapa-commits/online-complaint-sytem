<?php
session_start();
session_destroy();
header("location: /ocms/login.php");
exit;
?>
