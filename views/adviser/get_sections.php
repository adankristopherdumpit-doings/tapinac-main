<?php
include '../../database/db_connection.php';

if (isset($_POST['grade_level_id'])) {
    $grade_level_id = intval($_POST['grade_level_id']);

    $sql = "SELECT id, section_name 
            FROM sections 
            WHERE grade_level_id = $grade_level_id 
            ORDER BY section_name";
    $res = mysqli_query($conn, $sql);

    if (!$res) {
        echo "<option disabled>Error: " . mysqli_error($conn) . "</option>";
        exit;
    }

    if (mysqli_num_rows($res) > 0) {
        echo "<option value='' disabled selected>Select Section</option>";
        while ($row = mysqli_fetch_assoc($res)) {
            echo "<option value='{$row['id']}'>{$row['section_name']}</option>";
        }
    } else {
        echo "<option disabled>No sections found for this grade</option>";
    }
}
?>
