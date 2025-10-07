<?php
session_start();
require '../../database/db_connection.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'masterteacher') {
    header("Location: ../security/unauthorized.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit();
}

$action = $_POST['action'] ?? 'assign'; // 'assign' or 'reassign'
$adviser_id = intval($_POST['adviser'] ?? 0);
$grade_level_id = intval($_POST['grade_level'] ?? 0);
$section_id = intval($_POST['section'] ?? 0);
$academic_year_id = intval($_POST['academic_year'] ?? 0);
$subject_id = isset($_POST['subject']) && $_POST['subject'] !== '' ? intval($_POST['subject']) : null;

if ($adviser_id <= 0 || $grade_level_id <= 0 || $section_id <= 0 || $academic_year_id <= 0) {
    echo "<script>alert('Please complete all required fields.'); history.back();</script>";
    exit();
}

$conn->begin_transaction();

try {
    // 1) ensure adviser exists
    $stmt = $conn->prepare("SELECT id FROM teachers WHERE id = ?");
    $stmt->bind_param("i", $adviser_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        throw new Exception("Selected adviser not found.");
    }
    $stmt->close();

    // 2) ensure section exists and fetch current teacher_id
    $stmt = $conn->prepare("SELECT teacher_id FROM sections WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $section_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        throw new Exception("Section not found.");
    }
    $row = $res->fetch_assoc();
    $current_teacher = $row['teacher_id'] === null ? null : intval($row['teacher_id']);
    $stmt->close();

    if ($action === 'assign') {
        // Check section is unassigned
        if ($current_teacher !== null) {
            throw new Exception("This section already has an adviser. Use Re-Assign mode to replace.");
        }

        // Check adviser is not already assigned to any section
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM sections WHERE teacher_id = ?");
        $stmt->bind_param("i", $adviser_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (intval($result['cnt']) > 0) {
            throw new Exception("Selected adviser is already assigned to a section. Unassign them first or choose another adviser.");
        }

    } elseif ($action === 'reassign') {
        // Reassign: section must currently have an adviser
        if ($current_teacher === null) {
            throw new Exception("This section currently has no adviser. Use Assign mode instead.");
        }

        // Ensure selected adviser is not already assigned elsewhere
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM sections WHERE teacher_id = ?");
        $stmt->bind_param("i", $adviser_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (intval($result['cnt']) > 0) {
            throw new Exception("Selected adviser is already assigned to another section. Unassign them first or choose another adviser.");
        }
    } else {
        throw new Exception("Invalid action.");
    }

    // 3) Perform the section update
    $upd = $conn->prepare("UPDATE sections SET teacher_id = ? WHERE id = ?");
    $upd->bind_param("ii", $adviser_id, $section_id);
    if (!$upd->execute()) {
        throw new Exception("Failed to assign adviser: " . $upd->error);
    }
    $upd->close();

    // 4) Optional subject assignment (if provided)
    if ($subject_id !== null) {
        // Prevent duplicate subject assignment for the same teacher/subject/section/ay
        $chk = $conn->prepare("SELECT id FROM subject_assignments 
            WHERE teacher_id = ? AND subject_id = ? AND section_id = ? AND academic_year_id = ?");
        $chk->bind_param("iiii", $adviser_id, $subject_id, $section_id, $academic_year_id);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows === 0) {
            $ins = $conn->prepare("INSERT INTO subject_assignments 
                (teacher_id, subject_id, grade_level_id, section_id, academic_year_id) VALUES (?, ?, ?, ?, ?)");
            $ins->bind_param("iiiii", $adviser_id, $subject_id, $grade_level_id, $section_id, $academic_year_id);
            if (!$ins->execute()) {
                throw new Exception("Failed to save subject assignment: " . $ins->error);
            }
            $ins->close();
        }
        $chk->close();
    }

    $conn->commit();
    echo "<script>alert('Adviser assigned successfully.'); window.location.href='dashboard.php';</script>";
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES);
    echo "<script>alert('Error: {$msg}'); history.back();</script>";
    exit();
}
?>
