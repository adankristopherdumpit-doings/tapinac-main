<?php
session_start();

// If user has a role, redirect them directly
if (isset($_SESSION['role'])) {
    $role = strtolower(str_replace(' ', '', $_SESSION['role']));
    $redirect = "/tapinac/views/$role/dashboard.php";
    header("Location: $redirect");
    exit(); 
}



if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
?>
