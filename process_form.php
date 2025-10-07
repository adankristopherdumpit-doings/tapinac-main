<?php
// allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $basePath = dirname($_SERVER['PHP_SELF']);
    $redirectPath = rtrim($basePath, '/\\') . '/login.php';
    $redirectPath = str_replace('\\', '/', $redirectPath);
    header("Location: $redirectPath");
    exit;
}

// start session
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once 'database/db_connection.php'; 
header('Content-Type: application/json');

// Function to generate random password
function generatePassword($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = $_POST['fname'] ?? '';
    $mname = $_POST['mname'] ?? '';
    $lname = $_POST['lname'] ?? '';
    $role = $_POST['role'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';

    // Basic validation
    if (empty($fname) || empty($lname) || empty($role) || empty($email) || empty($username)) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled.']);
        exit;
    }




    $checkName = $conn->prepare("SELECT id FROM teachers WHERE fname = ? AND mname = ? AND lname = ?");
    $checkName->bind_param("sss", $fname, $mname, $lname);
    $checkName->execute();
    $checkName->store_result();

    if ($checkName->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'A teacher with the same name already exists.']);
        $checkName->close();
        exit;
    }
    $checkName->close();

    

    // Check if email already exists in `teachers` table
    $checkEmail = $conn->prepare("SELECT id FROM teachers WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();

    if ($checkEmail->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists.']);
        exit;
    }
    $checkEmail->close();

    // Check if username already exists in `users` table
    $checkUsername = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $checkUsername->bind_param("s", $username);
    $checkUsername->execute();
    $checkUsername->store_result();

    if ($checkUsername->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username already exists.']);
        exit;
    }
    $checkUsername->close();

    // Generate and hash password
    $plainPassword = generatePassword();
    $hashedPassword = hash('sha256', $plainPassword);

    // Insert into teachers table
    $stmtTeacher = $conn->prepare("INSERT INTO teachers (fname, mname, lname, email) VALUES (?, ?, ?, ?)");
    if (!$stmtTeacher) {
        echo json_encode(['success' => false, 'message' => 'Teacher insert prepare error: ' . $conn->error]);
        exit;
    }

    $stmtTeacher->bind_param("ssss", $fname, $mname, $lname, $email);
    if (!$stmtTeacher->execute()) {
        echo json_encode(['success' => false, 'message' => 'Teacher insert execute error: ' . $stmtTeacher->error]);
        exit;
    }
    $teacherId = $stmtTeacher->insert_id;

    // Insert into users table
    $stmtUser = $conn->prepare("INSERT INTO users (username, password, role_id, teacher_id) VALUES (?, ?, (SELECT id FROM roles WHERE role_name = ? LIMIT 1), ?)");
    if (!$stmtUser) {
        echo json_encode(['success' => false, 'message' => 'User insert prepare error: ' . $conn->error]);
        exit;
    }

    $stmtUser->bind_param("sssi", $username, $hashedPassword, $role, $teacherId);
    if (!$stmtUser->execute()) {
        echo json_encode(['success' => false, 'message' => 'User insert execute error: ' . $stmtUser->error]);
        exit;
    }
    // Send email with PHPMailer
    $mail = new PHPMailer(false); 

    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'kramsetlab99@gmail.com'; 
    $mail->Password = 'xwpw bbdz tutw cmuh';   
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;

    // Recipients
    $mail->setFrom('noreply0616@gmail.com', 'Admin');
    $mail->addAddress($email, $fname . ' ' . $lname);

    // Content
    $mail->isHTML(false);
    $mail->Subject = 'Your New Account Credentials';
    $mail->Body    = "Hello $fname,\n\nYour account has been created successfully.\n\nUsername: $username\nPassword: $plainPassword\n\nPlease log in and change your password immediately.\n\nThank you.";

    if (!$mail->send()) {
        echo json_encode(['success' => false, 'message' => 'Email could not be sent. Mailer Error: ' . $mail->ErrorInfo]);
        exit;
    }

    // All successful
    echo json_encode(['success' => true]);
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}
?>