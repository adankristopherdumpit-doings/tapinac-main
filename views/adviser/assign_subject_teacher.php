<?php
// adviser/assign_subject_teacher.php
session_start();
include '../../database/db_connection.php';

header('Content-Type: application/json; charset=UTF-8');

// Basic session/role checks
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'adviser') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$adviser_id = intval($_SESSION['user_id']);
$subject_id = intval($_POST['subject_id'] ?? 0);
$teacher_id = intval($_POST['teacher_id'] ?? 0);

// Validate inputs
if ($subject_id <= 0 || $teacher_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please choose a subject and a teacher.']);
    exit;
}

// Find the section this adviser is assigned to (must exist and match session)
$stmt = $conn->prepare("SELECT id, grade_level_id, section_name FROM sections WHERE teacher_id = ?");
$stmt->bind_param("i", $adviser_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'You are not assigned to any section. Contact masterteacher.']);
    exit;
}
$section = $res->fetch_assoc();
$section_id = (int)$section['id'];
$grade_level_id = (int)$section['grade_level_id'];
$stmt->close();

// Find active academic year
$ay_res = mysqli_query($conn, "SELECT id FROM academic_years WHERE status='active' LIMIT 1");
if (!$ay_res || mysqli_num_rows($ay_res) === 0) {
    echo json_encode(['success' => false, 'message' => 'No active academic year found.']);
    exit;
}
$ay_row = mysqli_fetch_assoc($ay_res);
$academic_year_id = (int)$ay_row['id'];

// Check if the subject is already assigned for this section & active AY
$chk = $conn->prepare("SELECT sa.id, t.fname, t.lname FROM subject_assignments sa JOIN teachers t ON t.id = sa.teacher_id WHERE sa.subject_id = ? AND sa.section_id = ? AND sa.academic_year_id = ? LIMIT 1");
$chk->bind_param("iii", $subject_id, $section_id, $academic_year_id);
$chk->execute();
$chk_res = $chk->get_result();
if ($chk_res && $chk_res->num_rows > 0) {
    $row = $chk_res->fetch_assoc();
    $existing_teacher_name = htmlspecialchars($row['lname'] . ', ' . $row['fname']);
    echo json_encode(['success' => false, 'message' => "Subject is already taken by {$existing_teacher_name}"]);
    exit;
}
$chk->close();

// Insert new assignment
$ins = $conn->prepare("INSERT INTO subject_assignments (teacher_id, subject_id, grade_level_id, section_id, academic_year_id) VALUES (?, ?, ?, ?, ?)");
$ins->bind_param("iiiii", $teacher_id, $subject_id, $grade_level_id, $section_id, $academic_year_id);
if ($ins->execute()) {

    // **Assign subject to all students in the section**
    $student_res = $conn->prepare("SELECT id FROM students WHERE section_id = ?");
    $student_res->bind_param("i", $section_id);
    $student_res->execute();
    $students = $student_res->get_result();
    
    $insert_stmt = $conn->prepare("INSERT INTO student_subjects (student_id, subject_id) VALUES (?, ?)");
    while ($student = $students->fetch_assoc()) {
        $insert_stmt->bind_param("ii", $student['id'], $subject_id);
        $insert_stmt->execute();
    }
    $insert_stmt->close();
    $student_res->close();

    echo json_encode(['success' => true, 'message' => 'Subject teacher successfully assigned']);
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save assignment: ' . $ins->error]);
    exit;
}

