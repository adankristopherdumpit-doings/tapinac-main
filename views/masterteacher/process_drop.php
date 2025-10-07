<?php
session_start();
require_once '../../database/db_connection.php';

// Check permission
$allowed_roles = ['adviser', 'masterteacher', 'principal', 'teacher'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../security/unauthorized.php");
    exit();
}

// Validate ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: student_list.php?status=error_invalid");
    exit();
}

$student_id = intval($_GET['id']);

// Start a transaction for atomic operations
$conn->begin_transaction();

try {
    // Get student info
    $sql = "SELECT * FROM students WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Student not found");
    }

    $student = $result->fetch_assoc();
    $stmt->close();

    // Insert into archives
    $sql = "INSERT INTO archives (id, fname, mname, lname, gender, grade_level_id, section_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "issssii",
        $student['id'],
        $student['fname'],
        $student['mname'],
        $student['lname'],
        $student['gender'],
        $student['grade_level_id'],
        $student['section_id']
    );
    if (!$stmt->execute()) {
        throw new Exception("Failed to archive student");
    }
    $stmt->close();

    // Delete from students
    $sql = "DELETE FROM students WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete student");
    }
    $stmt->close();

    // Commit the transaction
    $conn->commit();

    // Redirect back with success status based on role
    $role = $_SESSION['role'];
    switch ($role) {
        case 'masterteacher':
            header("Location: ../masterteacher/student_page.php?status=success_drop");
            break;
        case 'adviser':
            header("Location: ../adviser/student_page.php?status=success_drop");
            break;
        case 'principal':
            header("Location: ../principal/student_page.php?status=success_drop");
            break;
        case 'teacher':
            header("Location: ../teacher/student_page.php?status=success_drop");
            break;
        default:
            header("Location: ../shared/student_list.php?status=success_drop");
    }
} catch (Exception $e) {
    // Rollback transaction if any error occurs
    $conn->rollback();

    // Redirect back with error status
    header("Location: student_list.php?status=error_drop");
}

exit();
?>
