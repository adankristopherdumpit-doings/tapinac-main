<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $teacher_ids = $_POST['teacher_ids'];
    $user_ids = $_POST['user_ids'];
    $fnames = $_POST['fname'];
    $mnames = $_POST['mname'];
    $lnames = $_POST['lname'];
    $emails = $_POST['email'];
    $usernames = $_POST['username'];

    for ($i = 0; $i < count($teacher_ids); $i++) {
        $teacher_id = $teacher_ids[$i];
        $user_id = $user_ids[$i];
        $fname = $fnames[$i];
        $mname = $mnames[$i];
        $lname = $lnames[$i];
        $email = $emails[$i];
        $username = $usernames[$i];

        // Update teachers table
        $stmt = $conn->prepare("UPDATE teachers SET fname=?, mname=?, lname=?, email=? WHERE id=?");
        $stmt->bind_param("ssssi", $fname, $mname, $lname, $email, $teacher_id);
        $stmt->execute();

        // Update users table
        $stmt = $conn->prepare("UPDATE users SET username=? WHERE id=?");
        $stmt->bind_param("si", $username, $user_id);
        $stmt->execute();
    }

    header("Location: ../views/admin/admin_manage_user.php?update=success");
    exit();
}
?>
