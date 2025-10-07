<?php
require_once '../../../database/db_connection.php';

if (isset($_GET['grade_id'])) {
    $grade_id = intval($_GET['grade_id']);
    $query = "SELECT id, section_name FROM sections WHERE grade_level_id = $grade_id";
    $result = mysqli_query($conn, $query);
    $sections = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $sections[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($sections);
}
?>
