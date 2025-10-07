<?php
// adviser/reassign_subject_teacher.php
session_start();
include '../../database/db_connection.php';
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'adviser') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$teacher_id = intval($_POST['teacher_id'] ?? 0);
$changes = json_decode($_POST['changes'] ?? '[]', true);

if ($teacher_id <= 0 || !is_array($changes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// get active AY
$ay_res = mysqli_query($conn, "SELECT id FROM academic_years WHERE status='active' LIMIT 1");
if (!$ay_res || mysqli_num_rows($ay_res) === 0) {
    echo json_encode(['success' => false, 'message' => 'No active academic year found']);
    exit;
}
$ay_row = mysqli_fetch_assoc($ay_res);
$academic_year_id = intval($ay_row['id']);

$conn->begin_transaction();
try {
    foreach ($changes as $c) {
        $assignment_id = intval($c['assignment_id'] ?? 0);
        $action = $c['action'] ?? '';

        if ($assignment_id <= 0) {
            throw new Exception("Invalid assignment id");
        }

        if ($action === 'remove') {
            $del = $conn->prepare("DELETE FROM subject_assignments WHERE id = ? AND teacher_id = ? AND academic_year_id = ?");
            $del->bind_param("iii", $assignment_id, $teacher_id, $academic_year_id);
            $del->execute();
            // continue
        } elseif ($action === 'reassign') {
            $new_subject_id = intval($c['new_subject_id'] ?? 0);
            if ($new_subject_id <= 0) throw new Exception("Invalid new subject selected");

            // ensure new_subject is unassigned in this AY
            $chk = $conn->prepare("SELECT COUNT(*) AS cnt FROM subject_assignments WHERE subject_id = ? AND academic_year_id = ?");
            $chk->bind_param("ii", $new_subject_id, $academic_year_id);
            $chk->execute();
            $cnt = $chk->get_result()->fetch_assoc()['cnt'] ?? 0;
            $chk->close();
            if ($cnt > 0) throw new Exception("Selected subject is already assigned to another teacher for this academic year");

            // update the assignment row (ensure same AY and teacher)
            $upd = $conn->prepare("UPDATE subject_assignments SET subject_id = ? WHERE id = ? AND teacher_id = ? AND academic_year_id = ?");
            $upd->bind_param("iiii", $new_subject_id, $assignment_id, $teacher_id, $academic_year_id);
            $upd->execute();
            if ($upd->affected_rows === 0) {
                throw new Exception("Failed to update assignment (maybe mismatch)");
            }
            $upd->close();
        } else {
            throw new Exception("Unknown action");
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Reassignment completed']);
    exit;

} catch (Exception $ex) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $ex->getMessage()]);
    exit;
}
