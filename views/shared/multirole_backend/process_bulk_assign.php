<?php
require_once '../../../database/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grade_level_id = intval($_POST['grade_level_id']);
    $section_name = $_POST['section_id'];
    $student_ids = explode(',', $_POST['student_ids']);

    // Get section ID from section name + grade
    $stmt = $conn->prepare("SELECT id FROM sections WHERE section_name = ? AND grade_level_id = ?");
    $stmt->bind_param("si", $section_name, $grade_level_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result->num_rows) {
        echo "Error: Section not found.";
        exit();
    }

    $section = $result->fetch_assoc();
    $section_id = $section['id'];

    // Update each student
    $updateStmt = $conn->prepare("UPDATE students SET grade_level_id = ?, section_id = ? WHERE id = ?");
    foreach ($student_ids as $student_id) {
        $sid = intval($student_id);
        $updateStmt->bind_param("iii", $grade_level_id, $section_id, $sid);
        $updateStmt->execute();
    }

    header("Location: ../shared/student_list.php?status=success");
    exit();
}
?>
