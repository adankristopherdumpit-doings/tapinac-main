<?php
session_start();
require_once '../../../database/db_connection.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info (role + teacher link)
$user_sql = "SELECT u.role_id, r.role_name, u.teacher_id 
             FROM users u 
             JOIN roles r ON u.role_id = r.id 
             WHERE u.id = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();
$stmt->close();

$role_name = strtolower($user_data['role_name']);
$teacher_id = $user_data['teacher_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = trim($_POST['fname'] ?? '');
    $mname = trim($_POST['mname'] ?? '');
    $lname = trim($_POST['lname'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $grade_level_id = $_POST['grade_level_id'] ?? null;
    $section_id = $_POST['section_id'] ?? null;

    // ✅ Auto-assign for adviser/teacher (they don’t choose section manually)
    if (in_array($role_name, ['adviser', 'teacher'])) {
        $stmt = $conn->prepare("SELECT id AS section_id, grade_level_id FROM sections WHERE teacher_id = ?");
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $grade_level_id = $row['grade_level_id'];
            $section_id = $row['section_id'];
        } else {
            die('⚠️ No assigned section found for your account. Please contact the administrator.');
        }
        $stmt->close();
    }

    // ✅ Basic validation
    if (empty($fname) || empty($lname) || empty($gender) || !$grade_level_id || !$section_id) {
        die('⚠️ All required fields must be filled.');
    }

    // ✅ 1. Insert student
    $stmt = $conn->prepare("
        INSERT INTO students (fname, mname, lname, gender, grade_level_id, section_id, teacher_id)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssssiii", $fname, $mname, $lname, $gender, $grade_level_id, $section_id, $teacher_id);
    if (!$stmt->execute()) {
        die('Database insert error: ' . $stmt->error);
    }
    $student_id = $stmt->insert_id;
    $stmt->close();

    // ✅ 2. Auto-assign all subjects for that grade
    $stmt = $conn->prepare("SELECT id FROM subjects WHERE grade_level_id = ?");
    $stmt->bind_param("i", $grade_level_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $subject_ids = [];
    while ($row = $result->fetch_assoc()) {
        $subject_ids[] = $row['id'];
    }
    $stmt->close();

    if (!empty($subject_ids)) {
        $stmt = $conn->prepare("INSERT INTO student_subjects (student_id, subject_id) VALUES (?, ?)");
        foreach ($subject_ids as $sub_id) {
            $stmt->bind_param("ii", $student_id, $sub_id);
            $stmt->execute();
        }
        $stmt->close();
    }

    // ✅ Redirect with success message
    header("Location: ../student_list.php?status=success_add");
    exit();
}
?>
