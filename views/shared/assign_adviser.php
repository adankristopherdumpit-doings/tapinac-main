<?php
session_start();
include '../../database/db_connection.php';

if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'masterteacher') {
    header("Location: ../security/unauthorized.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['adviser_id'], $_POST['section_id']) || empty($_POST['adviser_id']) || empty($_POST['section_id'])) {
        die("Error: Missing required parameters.");
    }

    $adviser_id = intval($_POST['adviser_id']);
    $section_id = intval($_POST['section_id']);
    $grade = htmlspecialchars($_POST['grade'] ?? '', ENT_QUOTES, 'UTF-8');

    // Prevent reassignment of already assigned advisers (defensive)
    $checkQuery = "SELECT COUNT(*) AS count FROM sections WHERE teacher_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("i", $adviser_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $data = $checkResult->fetch_assoc();

    if ($data['count'] > 0) {
        die("Error: This adviser is already assigned to a section.");
    }

    // Update adviser
    $update = $conn->prepare("UPDATE sections SET teacher_id = ? WHERE id = ?");
    $update->bind_param("ii", $adviser_id, $section_id);

    if ($update->execute()) {
        header("Location: grade.php?grade=" . urlencode($grade) . "&success=1");
        exit();
    } else {
        echo "Error: " . $update->error;
    }
}
?>
