<?php
// shared/get_adviser_details.php
include '../../database/db_connection.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['adviser_id']) || !is_numeric($_GET['adviser_id'])) {
    echo json_encode(['error' => 'Missing adviser_id']);
    exit;
}

$adviserId = intval($_GET['adviser_id']);

// We assume each adviser is only assigned to one section (per your rules).
$sql = "SELECT s.id AS section_id, s.section_name,
               g.id AS grade_level_id, g.grade_name,
               ay.id AS academic_year_id, ay.year_start, ay.year_end
        FROM sections s
        JOIN grade_levels g ON s.grade_level_id = g.id
        LEFT JOIN academic_years ay ON ay.status = 'active'
        WHERE s.teacher_id = ? LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $adviserId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'section_id' => (int)$row['section_id'],
        'section_name' => $row['section_name'],
        'grade_level_id' => (int)$row['grade_level_id'],
        'grade_name' => $row['grade_name'],
        'academic_year_id' => isset($row['academic_year_id']) ? (int)$row['academic_year_id'] : null,
        'academic_year_display' => isset($row['year_start']) ? ($row['year_start'] . ' - ' . $row['year_end']) : null
    ]);
} else {
    // No assignment found
    echo json_encode(['not_assigned' => true]);
}
$stmt->close();
$conn->close();
