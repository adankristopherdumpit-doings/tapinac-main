<?php
include '../../database/db_connection.php';

if (!isset($_GET['adviser_id'])) {
    http_response_code(400);
    echo json_encode(['assigned'=>false]);
    exit();
}

$adviserId = intval($_GET['adviser_id']);

$stmt = $conn->prepare("SELECT s.id, s.section_name, s.grade_level_id FROM sections s WHERE s.teacher_id = ?");
$stmt->bind_param("i", $adviserId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['assigned' => true, 'section' => $row]);
} else {
    echo json_encode(['assigned' => false]);
}
?>
