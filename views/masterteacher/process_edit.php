<?php
require_once '../../database/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = intval($_POST['id']);
    $fname = mysqli_real_escape_string($conn, $_POST['fname']);
    $mname = mysqli_real_escape_string($conn, $_POST['mname']);
    $lname = mysqli_real_escape_string($conn, $_POST['lname']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);

    $sql = "UPDATE students 
            SET fname='$fname', mname='$mname', lname='$lname', gender='$gender'
            WHERE id=$id";

    if (mysqli_query($conn, $sql)) {
        header("Location: ../shared/student_list.php?status=success_edit");
        exit();
    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }
}
?>
