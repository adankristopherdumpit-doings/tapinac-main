<?php
require_once "../../../database/db_connection.php";
session_start();

$ww = (int)($_POST['ww_percent'] ?? 0);
$pt = (int)($_POST['pt_percent'] ?? 0);
$qa = (int)($_POST['qa_percent'] ?? 0);
$ay = (int)($_POST['academic_year_id'] ?? 0);
$q  = (int)($_POST['quarter_id'] ?? 0);
$s  = (int)($_POST['section_id'] ?? 0);
$sub= (int)($_POST['subject_id'] ?? 0);
$t  = (int)($_POST['teacher_id'] ?? 0);

if ($ww + $pt + $qa !== 100) {
    die("Percentages must total 100%");
}

$sql = "INSERT INTO grading_components 
        (academic_year_id, quarter_id, section_id, subject_id, teacher_id, ww_percent, pt_percent, qa_percent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        ww_percent=VALUES(ww_percent),
        pt_percent=VALUES(pt_percent),
        qa_percent=VALUES(qa_percent)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiiiiii", $ay, $q, $s, $sub, $t, $ww, $pt, $qa);

if ($stmt->execute()) {
    echo "Grading percentages saved successfully.";
} else {
    echo "Error: " . $stmt->error;
}
$stmt->close();
$conn->close();
?>
