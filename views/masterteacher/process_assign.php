<?php
require_once '../../database/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = intval($_POST['student_id']);
    $grade_level_id = intval($_POST['grade_level_id']);
    $section_id = intval($_POST['section_id']);

    $query = "UPDATE students SET grade_level_id = ?, section_id = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $grade_level_id, $section_id, $student_id);

    if ($stmt->execute()) {
        header("Location: ../shared/student_list.php?status=success");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
