<?php
include '../../database/db_connection.php'; 

if (isset($_GET['grade_level_id'])) {
    $gradeLevelId = intval($_GET['grade_level_id']);

    $stmt = $conn->prepare("SELECT id, subject_name FROM subjects WHERE grade_level_id = ?");
    $stmt->bind_param("i", $gradeLevelId);
    $stmt->execute();
    $result = $stmt->get_result();

    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }

    header('Content-Type: application/json'); 
    echo json_encode($subjects);
}
?>
