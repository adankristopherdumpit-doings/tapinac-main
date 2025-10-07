<?php
// masterteacher/remove_adviser.php
session_start();
include '../../database/db_connection.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'masterteacher') {
    header("Location: ../security/unauthorized.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit();
}

$adviser_id = isset($_POST['adviser_id']) ? intval($_POST['adviser_id']) : 0;

if ($adviser_id <= 0) {
    echo "<script>alert('Invalid adviser selected.'); window.location.href='dashboard.php';</script>";
    exit();
}

// Update sections: set teacher_id = NULL where that teacher is assigned
$stmt = $conn->prepare("UPDATE sections SET teacher_id = NULL WHERE teacher_id = ?");
$stmt->bind_param("i", $adviser_id);

if ($stmt->execute()) {
    echo "<script>alert('Adviser removed from their advisory successfully. Subjects remain intact.'); window.location.href='dashboard.php';</script>";
} else {
    $err = htmlspecialchars($conn->error, ENT_QUOTES);
    echo "<script>alert('Failed to remove adviser: {$err}'); history.back();</script>";
}
$stmt->close();
$conn->close();
