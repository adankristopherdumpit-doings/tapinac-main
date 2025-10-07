<?php
// save_grading.php
session_start();
require_once "../../../database/db_connection.php"; // your existing connection file
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("Content-Type: application/json; charset=utf-8");

// enable mysqli exceptions for clearer catch
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// debug log path (inside same folder)
$debugFile = __DIR__ . '/debug_post.log';

function log_debug($msg) {
    global $debugFile;
    file_put_contents($debugFile, date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
}

try {
    // --- AUTH ---
    $allowed_roles = ['adviser', 'masterteacher', 'principal', 'teacher'];
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', $allowed_roles)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
        exit();
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
        exit();
    }

    // Log incoming POST for debugging
    log_debug("POST payload: " . print_r($_POST, true));

    if (!isset($_POST['grades']) || !is_array($_POST['grades'])) {
        echo json_encode(['ok' => false, 'error' => 'No grades submitted.']);
        exit();
    }

    // ensure DB selected (if db_connection doesn't select DB)
    if (isset($conn) && is_object($conn)) {
        // replace 'grading_system' if your DB name is different
        $conn->select_db('grading_system');
    } else {
        throw new Exception('Database connection ($conn) not available.');
    }

    // Resolve teacher_id: prefer session teacher_id, else try lookup
    $session_teacher_id = (int)($_SESSION['teacher_id'] ?? 0);
    if ($session_teacher_id <= 0 && !empty($_SESSION['user_id'])) {
        $tmp = $conn->prepare("SELECT teacher_id FROM users WHERE id = ? LIMIT 1");
        $tmp->bind_param("i", $_SESSION['user_id']);
        $tmp->execute();
        $r = $tmp->get_result()->fetch_assoc();
        $session_teacher_id = (int)($r['teacher_id'] ?? 0);
        $tmp->close();
    }

    // Global context fallback (from top-level hidden inputs)
    $global_context = $_POST['context'] ?? [];
    $global_subject = (int)($global_context['subject_id'] ?? 0);
    $global_quarter = (int)($global_context['quarter_id'] ?? 0);
    $global_acad = (int)($global_context['academic_year_id'] ?? 0);
    $global_teacher = (int)($global_context['teacher_id'] ?? 0);
    if ($global_teacher <= 0) $global_teacher = $session_teacher_id;

    // helper
    function cleanScore($val) {
        return ($val === "" || $val === null) ? null : (float)$val;
    }

    $saved = [];
    $failed = [];

    $conn->begin_transaction();

    // prepare frequently-used checks
    $stmt_check_subject = $conn->prepare("SELECT id FROM subjects WHERE id = ? LIMIT 1");
    $stmt_check_quarter = $conn->prepare("SELECT id FROM quarters WHERE id = ? AND academic_year_id = ? LIMIT 1");
    $stmt_get_first_quarter = $conn->prepare("SELECT id FROM quarters WHERE academic_year_id = ? ORDER BY id ASC LIMIT 1");
    $stmt_get_student_section = $conn->prepare("SELECT section_id FROM students WHERE id = ? LIMIT 1");
    $stmt_get_assignment_subject = $conn->prepare("SELECT subject_id FROM subject_assignments WHERE teacher_id = ? AND academic_year_id = ? AND section_id = ? LIMIT 1");

    foreach ($_POST['grades'] as $studKey => $gradeData) {
            $student_id       = (int)$studKey;
            $subject_id       = (int)($gradeData['context']['subject_id'] ?? 0);
            $quarter_id       = (int)($gradeData['context']['quarter_id'] ?? 0);
            $academic_year_id = (int)($gradeData['context']['academic_year_id'] ?? 0);
            $teacher_id       = (int)($gradeData['context']['teacher_id'] ?? 0);

            // ✅ Apply global fallbacks if missing
            if ($subject_id <= 0) $subject_id = $global_subject;
            if ($quarter_id <= 0) $quarter_id = $global_quarter;
            if ($academic_year_id <= 0) $academic_year_id = $global_acad;
            if ($teacher_id <= 0) $teacher_id = $global_teacher;

            // ✅ "The fix" starts here
            // --- Fix academic_year_id ---
            if ($academic_year_id <= 0) {
                $res = $conn->query("SELECT id FROM academic_years WHERE status='active' LIMIT 1");
                if ($res && $row = $res->fetch_assoc()) {
                    $academic_year_id = (int)$row['id'];
                }
            }

            // --- Fix quarter_id ---
            if ($quarter_id <= 0 && $academic_year_id > 0) {
                $res = $conn->query("SELECT id FROM quarters WHERE academic_year_id=$academic_year_id ORDER BY id ASC LIMIT 1");
                if ($res && $row = $res->fetch_assoc()) {
                    $quarter_id = (int)$row['id'];
                    log_debug("Fallback quarter_id={$quarter_id} for student {$student_id}");
                }
            }

            // --- Fix subject_id ---
            if ($subject_id <= 0 || !($conn->query("SELECT id FROM subjects WHERE id=$subject_id")->num_rows)) {
                $stmt_get_student_section->bind_param("i", $student_id);
                $stmt_get_student_section->execute();
                $srow = $stmt_get_student_section->get_result()->fetch_assoc();
                $section_id = (int)($srow['section_id'] ?? 0);

                if ($section_id > 0 && $teacher_id > 0 && $academic_year_id > 0) {
                    $stmt_get_assignment_subject->bind_param("iii", $teacher_id, $academic_year_id, $section_id);
                    $stmt_get_assignment_subject->execute();
                    $arow = $stmt_get_assignment_subject->get_result()->fetch_assoc();
                    if (!empty($arow['subject_id'])) {
                        $subject_id = (int)$arow['subject_id'];
                        log_debug("Inferred subject_id={$subject_id} for student {$student_id} via subject_assignments");
                    }
                }
            }

            // ✅ Now run the same validations you already had
            if ($student_id <= 0 || $teacher_id <= 0 || $academic_year_id <= 0 || $subject_id <= 0 || $quarter_id <= 0) {
                $failed[] = [
                    'student' => $student_id,
                    'reason'  => "Missing/invalid context (s=$subject_id, q=$quarter_id, ay=$academic_year_id, t=$teacher_id)"
                ];
                continue;
            }

        // Ensure subject exists
        $stmt_check_subject->bind_param("i", $subject_id);
        $stmt_check_subject->execute();
        $sres = $stmt_check_subject->get_result()->fetch_assoc();
        if (empty($sres['id'])) {
            $failed[] = ['student' => $student_id, 'reason' => "Subject id {$subject_id} not found"];
            continue;
        }

        // Ensure quarter exists for this academic year
        $stmt_check_quarter->bind_param("ii", $quarter_id, $academic_year_id);
        $stmt_check_quarter->execute();
        $qres = $stmt_check_quarter->get_result()->fetch_assoc();
        if (empty($qres['id'])) {
            $failed[] = ['student' => $student_id, 'reason' => "Quarter id {$quarter_id} invalid for academic year {$academic_year_id}"];
            continue;
        }

        // Collect scores
        $ww = [];
        $pt = [];
        for ($i=1;$i<=10;$i++) $ww[$i] = cleanScore($gradeData['scores']["ww{$i}"] ?? null);
        for ($i=1;$i<=10;$i++) $pt[$i] = cleanScore($gradeData['scores']["pt{$i}"] ?? null);
        $qa              = cleanScore($gradeData['scores']['qa'] ?? null);
        $final_grade     = cleanScore($gradeData['scores']['final_grade'] ?? null);
        $quarterly_grade = cleanScore($gradeData['scores']['quarterly_grade'] ?? null);
        $ww_total        = cleanScore($gradeData['scores']['ww_total'] ?? null);
        $ww_ps           = cleanScore($gradeData['scores']['ww_ps'] ?? null);
        $ww_ws           = cleanScore($gradeData['scores']['ww_ws'] ?? null);
        $pt_total        = cleanScore($gradeData['scores']['pt_total'] ?? null);
        $pt_ps           = cleanScore($gradeData['scores']['pt_ps'] ?? null);
        $pt_ws           = cleanScore($gradeData['scores']['pt_ws'] ?? null);
        $qa_ps           = cleanScore($gradeData['scores']['qa_ps'] ?? null);
        $qa_ws           = cleanScore($gradeData['scores']['qa_ws'] ?? null);

        // Build INSERT ... ON DUPLICATE KEY UPDATE
        $sql = "INSERT INTO grades (
            student_id, subject_id, academic_year_id, teacher_id, quarter_id,
            ww_total, ww1, ww2, ww3, ww4, ww5, ww6, ww7, ww8, ww9, ww10,
            pt1, pt2, pt3, pt4, pt5, pt6, pt7, pt8, pt9, pt10,
            qa, final_grade, quarterly_grade,
            ww_ps, ww_ws,
            pt_total, pt_ps, pt_ws,
            qa_ps, qa_ws
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?,
            ?, ?, ?,
            ?, ?
        )
        ON DUPLICATE KEY UPDATE
            ww_total=VALUES(ww_total),
            ww1=VALUES(ww1), ww2=VALUES(ww2), ww3=VALUES(ww3), ww4=VALUES(ww4), ww5=VALUES(ww5),
            ww6=VALUES(ww6), ww7=VALUES(ww7), ww8=VALUES(ww8), ww9=VALUES(ww9), ww10=VALUES(ww10),
            pt1=VALUES(pt1), pt2=VALUES(pt2), pt3=VALUES(pt3), pt4=VALUES(pt4), pt5=VALUES(pt5),
            pt6=VALUES(pt6), pt7=VALUES(pt7), pt8=VALUES(pt8), pt9=VALUES(pt9), pt10=VALUES(pt10),
            qa=VALUES(qa),
            final_grade=VALUES(final_grade),
            quarterly_grade=VALUES(quarterly_grade),
            ww_ps=VALUES(ww_ps), ww_ws=VALUES(ww_ws),
            pt_total=VALUES(pt_total), pt_ps=VALUES(pt_ps), pt_ws=VALUES(pt_ws),
            qa_ps=VALUES(qa_ps), qa_ws=VALUES(qa_ws)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $failed[] = ['student' => $student_id, 'reason' => 'Prepare failed: ' . $conn->error];
            continue;
        }

        $params = [
            $student_id, $subject_id, $academic_year_id, $teacher_id, $quarter_id,
            $ww_total, $ww[1], $ww[2], $ww[3], $ww[4], $ww[5], $ww[6], $ww[7], $ww[8], $ww[9], $ww[10],
            $pt[1], $pt[2], $pt[3], $pt[4], $pt[5], $pt[6], $pt[7], $pt[8], $pt[9], $pt[10],
            $qa, $final_grade, $quarterly_grade,
            $ww_ps, $ww_ws,
            $pt_total, $pt_ps, $pt_ws,
            $qa_ps, $qa_ws
        ];

        // types: first five integers, rest numeric (d) - adjust if you have strings
        $types = "iiiii" . str_repeat("d", count($params) - 5);

        // bind params by reference
        $bind_names = [$types];
        foreach ($params as $k => $v) {
            $bind_name = 'p' . $k;
            $$bind_name = $v;
            $bind_names[] = &$$bind_name;
        }

        $bind_ok = call_user_func_array([$stmt, 'bind_param'], $bind_names);
        if (!$bind_ok) {
            $failed[] = ['student' => $student_id, 'reason' => 'Bind failed: ' . $stmt->error];
            $stmt->close();
            continue;
        }

        if (!$stmt->execute()) {
            $failed[] = ['student' => $student_id, 'reason' => 'Execute failed: ' . $stmt->error];
            $stmt->close();
            continue;
        }

        $saved[] = $student_id;
        $stmt->close();
    } // foreach

    $conn->commit();

    $result = ['ok' => true, 'saved' => $saved, 'failed' => $failed];
    log_debug("RESULT: " . print_r($result, true));
    echo json_encode($result);
    exit();

} catch (Exception $ex) {
    // rollback and return the exception text (also log)
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->rollback();
    }
    $err = $ex->getMessage();
    log_debug("EXCEPTION: " . $err);
    echo json_encode(['ok' => false, 'error' => 'Exception: ' . $err]);
    exit();
}
