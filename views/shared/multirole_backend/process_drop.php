<?php
session_start();
require_once '../../database/db_connection.php';

$allowed_roles = ['adviser', 'masterteacher', 'principal', 'teacher'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../security/unauthorized.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = intval($_POST['student_id']);

    $stmt = $conn->prepare("UPDATE students SET is_archived = 1 WHERE id = ?");
    $stmt->bind_param("i", $student_id);

    if ($stmt->execute()) {
        header("Location: ../shared/student_list.php?status=archived");
        exit();
    } else {
        header("Location: ../shared/student_list.php?status=error&message=" . urlencode($stmt->error));
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: ../shared/student_list.php?status=error&message=Invalid request");
}
?>
