<?php
include '../../database/db_connection.php'; 

if (!isset($_GET['grade_level_id'])) {
    http_response_code(400);
    echo json_encode([]);
    exit();
}

$gradeLevelId = intval($_GET['grade_level_id']);
$onlyUnassigned = isset($_GET['only_unassigned']) && $_GET['only_unassigned'] === '1';

if ($onlyUnassigned) {
    $stmt = $conn->prepare("SELECT id, section_name FROM sections WHERE grade_level_id = ? AND (teacher_id IS NULL OR teacher_id = '') ORDER BY section_name");
} else {
    // return sections that have an adviser (for reassign) or all (if you want)
    $stmt = $conn->prepare("SELECT id, section_name FROM sections WHERE grade_level_id = ? AND teacher_id IS NOT NULL ORDER BY section_name");
}

$stmt->bind_param("i", $gradeLevelId);
$stmt->execute();
$result = $stmt->get_result();

$sections = [];
while ($row = $result->fetch_assoc()) {
    $sections[] = $row;
}

header('Content-Type: application/json');
echo json_encode($sections);
?>
