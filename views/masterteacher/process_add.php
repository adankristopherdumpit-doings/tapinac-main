<?php
session_start();
require_once '../../database/db_connection.php'; // adjust path if different

// Allowed roles
$allowed_roles = ['adviser', 'masterteacher', 'principal', 'teacher'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../security/unauthorized.php");
    exit();
}

// Helper: safe redirect and exit
function safe_redirect($url) {
    header("Location: $url");
    exit();
}

// Build a fallback redirect (used if referer is missing or not safe)
function fallback_section_redirect($grade, $section_name, $status = null, $message = null) {
    $base = '../masterteacher/section_page.php';
    $params = [];
    if ($grade !== null && $grade !== '') $params['grade'] = $grade;
    if ($section_name !== null && $section_name !== '') $params['section'] = $section_name;
    if ($status !== null) $params['status'] = $status;
    if ($message !== null) $params['message'] = $message;
    $url = $base . (count($params) ? ('?' . http_build_query($params)) : '');
    safe_redirect($url);
}

// Use referer when safe (same host)
$referer = $_SERVER['HTTP_REFERER'] ?? null;
$safe_referer = null;
if ($referer) {
    $r = parse_url($referer);
    if (!isset($r['host']) || $r['host'] === $_SERVER['HTTP_HOST']) {
        $safe_referer = $referer;
    }
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($safe_referer) safe_redirect($safe_referer);
    safe_redirect('student_list.php');
}

// Collect & trim
$fname        = trim($_POST['fname'] ?? '');
$mname        = trim($_POST['mname'] ?? '');
$lname        = trim($_POST['lname'] ?? '');
$gender       = trim($_POST['gender'] ?? '');
$grade        = isset($_POST['grade']) ? trim($_POST['grade']) : null;
$section_id   = isset($_POST['section_id']) ? trim($_POST['section_id']) : null;
$section_name = isset($_POST['section_name']) ? trim($_POST['section_name']) : null;

// Basic required validation
if ($fname === '' || $lname === '' || $gender === '') {
    $err = "First name, last name and gender are required";
    if ($safe_referer) safe_redirect($safe_referer . (strpos($safe_referer, '?') === false ? '?' : '&') . 'status=error&message=' . urlencode($err));
    fallback_section_redirect($grade, $section_name, 'error', $err);
}

// Normalize numeric values
$gradeInt = null;
$sectionInt = null;
if ($grade !== null && $grade !== '') {
    $gradeInt = filter_var($grade, FILTER_VALIDATE_INT);
    if ($gradeInt === false) $gradeInt = null;
}
if ($section_id !== null && $section_id !== '') {
    $sectionInt = filter_var($section_id, FILTER_VALIDATE_INT);
    if ($sectionInt === false) $sectionInt = null;
}

// If section_id missing but grade + section_name provided, try to lookup section_id
if ($sectionInt === null && $gradeInt !== null && $section_name) {
    $lookup = $conn->prepare("SELECT id FROM sections WHERE grade_level_id = ? AND section_name = ? LIMIT 1");
    if ($lookup) {
        $lookup->bind_param("is", $gradeInt, $section_name);
        if ($lookup->execute()) {
            $res = $lookup->get_result();
            if ($r = $res->fetch_assoc()) {
                $sectionInt = (int)$r['id'];
            }
            $res->free();
        }
        $lookup->close();
    }
}

// --- Derive grade_level_id from section_id when possible (ensures consistency)
if (!empty($sectionInt)) {
    $q = $conn->prepare("SELECT grade_level_id FROM sections WHERE id = ? LIMIT 1");
    if ($q) {
        $q->bind_param("i", $sectionInt);
        if ($q->execute()) {
            $res = $q->get_result();
            if ($row = $res->fetch_assoc()) {
                $derivedGrade = (int)$row['grade_level_id'];
                if (empty($gradeInt) || $gradeInt !== $derivedGrade) {
                    $gradeInt = $derivedGrade;
                }
            }
            $res->free();
        }
        $q->close();
    }
}

$stmt = null;

// Prefer inserting BOTH grade_level_id and section_id when available
if (!empty($gradeInt) && !empty($sectionInt)) {
    $sql = "INSERT INTO students (fname, mname, lname, gender, grade_level_id, section_id)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) $stmt->bind_param("ssssii", $fname, $mname, $lname, $gender, $gradeInt, $sectionInt);
}
// Only section_id available
elseif (!empty($sectionInt)) {
    $sql = "INSERT INTO students (fname, mname, lname, gender, section_id)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) $stmt->bind_param("ssssi", $fname, $mname, $lname, $gender, $sectionInt);
}
// Only grade_level_id available
elseif (!empty($gradeInt)) {
    $sql = "INSERT INTO students (fname, mname, lname, gender, grade_level_id)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) $stmt->bind_param("ssssi", $fname, $mname, $lname, $gender, $gradeInt);
}
// Neither provided (insert basic record only)
else {
    $sql = "INSERT INTO students (fname, mname, lname, gender)
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) $stmt->bind_param("ssss", $fname, $mname, $lname, $gender);
}

if (!$stmt) {
    $err = "DB prepare failed: " . $conn->error;
    if ($safe_referer) {
        safe_redirect($safe_referer . (strpos($safe_referer, '?') === false ? '?' : '&') .
                      'status=error&message=' . urlencode($err));
    }
    fallback_section_redirect($grade, $section_name, 'error', $err);
}

// Execute
if ($stmt->execute()) {
    if ($safe_referer) {
        $sep = (strpos($safe_referer, '?') === false) ? '?' : '&';
        safe_redirect($safe_referer . $sep . 'status=success_add');
    }
    if ($gradeInt !== null && $section_name) {
        fallback_section_redirect($gradeInt, $section_name, 'success_add', 'Student added');
    }
    safe_redirect('student_list.php?status=success_add');
} else {
    $err = $stmt->error ?: 'Insert failed';
    if ($safe_referer) safe_redirect($safe_referer . (strpos($safe_referer, '?') === false ? '?' : '&') . 'status=error&message=' . urlencode($err));
    fallback_section_redirect($grade, $section_name, 'error', $err);
}

// cleanup
$stmt->close();
$conn->close();
