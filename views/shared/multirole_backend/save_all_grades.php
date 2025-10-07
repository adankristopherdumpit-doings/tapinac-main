<?php
require '../../../database/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = !empty($_POST['subject_id']) ? intval($_POST['subject_id']) : null;
    $teacher_id = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : null;
    $quarter_id = !empty($_POST['quarter_id']) ? intval($_POST['quarter_id']) : null;
    $academic_year_id = isset($_POST['academic_year_id']) ? intval($_POST['academic_year_id']) : 1; // default to 1 if missing

    if (!empty($_POST['grades']) && is_array($_POST['grades'])) {
        foreach ($_POST['grades'] as $student_id => $gradeData) {
            // sanitize values (null if empty)
            $ww1 = !empty($gradeData['ww1']) ? floatval($gradeData['ww1']) : null;
            $pt1 = !empty($gradeData['pt1']) ? floatval($gradeData['pt1']) : null;
            $qa  = !empty($gradeData['qa']) ? floatval($gradeData['qa']) : null;

            // prepare INSERT (basic example: only ww1, pt1, qa for now)
            $stmt = $conn->prepare("
                INSERT INTO grades 
                (student_id, subject_id, academic_year_id, teacher_id, quarter_id, ww1, pt1, qa) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    ww1 = VALUES(ww1), 
                    pt1 = VALUES(pt1),
                    qa  = VALUES(qa)
            ");

            $stmt->bind_param(
                "iiiiiddd", 
                $student_id,
                $subject_id,
                $academic_year_id,
                $teacher_id,
                $quarter_id,
                $ww1,
                $pt1,
                $qa
            );

            $stmt->execute();
            $stmt->close();
        }
    }

    echo "✅ Grades saved successfully.";
}
?>
