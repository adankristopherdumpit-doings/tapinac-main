<?php

// Prevent direct access to sidebar files
if (!isset($_SESSION['role'])) {
    header("Location: /tapinac/security/unauthorized.php");
    exit();
}

// Optional: allow only specific roles (e.g. admin, teacher, etc.)
$allowed_roles = ['admin', 'teacher', 'masterteacher', 'adviser', 'principal'];

if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: /tapinac/security/unauthorized.php");
    exit();
}

?>