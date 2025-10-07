<?php
// adviser/fetch_teacher_subjects.php
session_start();
include '../../database/db_connection.php';
header('Content-Type: application/json; charset=UTF-8');

// session/role basic check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'adviser') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$teacher_id = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : 0;
if ($teacher_id <= 0) {
    echo json_encode(['assigned' => [], 'available' => [], 'currentSubjectsMap' => []]);
    exit;
}

// active academic year
$ay_res = mysqli_query($conn, "SELECT id FROM academic_years WHERE status='active' LIMIT 1");
if (!$ay_res || mysqli_num_rows($ay_res) === 0) {
    echo json_encode(['error' => 'No active academic year found', 'assigned' => [], 'available' => [], 'currentSubjectsMap' => []]);
    exit;
}
$ay_row = mysqli_fetch_assoc($ay_res);
$academic_year_id = intval($ay_row['id']);

// fetch assigned subjects for this teacher in active AY
$assigned = [];
$map = [];
$assignedStmt = $conn->prepare(
    "SELECT sa.id AS assignment_id, sa.subject_id, s.subject_name, sa.grade_level_id, sa.section_id
     FROM subject_assignments sa
     JOIN subjects s ON s.id = sa.subject_id
     WHERE sa.teacher_id = ? AND sa.academic_year_id = ?"
);
$assignedStmt->bind_param("ii", $teacher_id, $academic_year_id);
$assignedStmt->execute();
$rs = $assignedStmt->get_result();
while ($r = $rs->fetch_assoc()) {
    $assigned[] = $r;
    $map[$r['subject_id']] = $r['subject_name'];
}
$assignedStmt->close();

// For each distinct grade_level present in the teacher's assignments, fetch available subjects
$available = [];
$grade_levels = [];
foreach ($assigned as $a) {
    $grade_levels[intval($a['grade_level_id'])] = intval($a['grade_level_id']);
}

if (empty($grade_levels)) {
    // If teacher has no assignments, optionally we can return all unassigned subjects across grades
    $sqlAvail = "SELECT id, subject_name, grade_level_id FROM subjects
                 WHERE id NOT IN (SELECT subject_id FROM subject_assignments WHERE academic_year_id = ?)
                 ORDER BY subject_name";
    $stmt = $conn->prepare($sqlAvail);
    $stmt->bind_param("i", $academic_year_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $g = intval($r['grade_level_id']);
        if (!isset($available[$g])) $available[$g] = [];
        $available[$g][] = ['id'=>intval($r['id']), 'subject_name'=>$r['subject_name']];
    }
    $stmt->close();
} else {
    // For each grade_level fetch subjects NOT assigned in this AY
    $sql = "SELECT id, subject_name, grade_level_id FROM subjects
            WHERE grade_level_id = ? AND id NOT IN (SELECT subject_id FROM subject_assignments WHERE academic_year_id = ?)
            ORDER BY subject_name";
    $stmt = $conn->prepare($sql);
    foreach ($grade_levels as $gl) {
        $stmt->bind_param("ii", $gl, $academic_year_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $available[$gl] = [];
        while ($r = $res->fetch_assoc()) {
            $available[$gl][] = ['id'=>intval($r['id']), 'subject_name'=>$r['subject_name']];
        }
    }
    $stmt->close();
}

echo json_encode([
    'assigned' => $assigned,
    'available' => $available,
    'currentSubjectsMap' => $map
]);
