<?php
include '../../database/db_connection.php'; 
session_start();

if (!isset($_SESSION['teacher_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$teacherId = intval($_SESSION['teacher_id']);

// Find adviser's grade level
$stmt = $conn->prepare("
    SELECT grade_level_id 
    FROM sections 
    WHERE teacher_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $teacherId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $gradeLevelId = $row['grade_level_id'];

    $stmtSubjects = $conn->prepare("SELECT id, subject_name FROM subjects WHERE grade_level_id = ?");
    $stmtSubjects->bind_param("i", $gradeLevelId);
    $stmtSubjects->execute();
    $resultSubjects = $stmtSubjects->get_result();

    $subjects = [];
    while ($sub = $resultSubjects->fetch_assoc()) {
        $subjects[] = $sub;
    }

    header('Content-Type: application/json');
    echo json_encode($subjects);
} else {
    echo json_encode([]);
}
?>
