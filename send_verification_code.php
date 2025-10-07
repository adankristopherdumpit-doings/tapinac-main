<?php
// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $basePath = dirname($_SERVER['PHP_SELF']);
    $redirectPath = rtrim($basePath, '/\\') . '/login.php';
    $redirectPath = str_replace('\\', '/', $redirectPath);
    header("Location: $redirectPath");
    exit;
}

// Start session
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'database/db_connection.php';

// Enable MySQLi error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Validate and retrieve email
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if (empty($email)) {
    $_SESSION['error'] = "Email is required.";
    header("Location: login.php?reset_error=1");
    exit;
}

$user = null;
$user_type = '';

// 1. Try to find user in teachers table
$stmt = $conn->prepare("SELECT id, fname, mname, lname FROM teachers WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $user_type = 'teacher';
} else {
    // 2. Try to find user in users table
    $stmt = $conn->prepare("SELECT id, fname, mname, lname FROM teachers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_type = 'user';
    } else {
        // Email not found in either table
        $_SESSION['error'] = "Email not found.";
        $_SESSION['show_reset_modal'] = true; 
        header("Location: login.php?reset_error=1");
        exit;
    }
}

// 3. Store user info in session
$full_name = trim($user['fname'] . ' ' . ($user['mname'] ?? '') . ' ' . $user['lname']);
$verification_code = random_int(100000, 999999);

$_SESSION['reset_name'] = $full_name;
$_SESSION['reset_email'] = $email;
$_SESSION['user_type'] = $user_type;
$_SESSION['found_user_id'] = $user['id'];
$_SESSION['reset_code'] = $verification_code;
$_SESSION['code_expiry'] = time() + 300; // 5 minutes
$_SESSION['show_otp_modal'] = true;

// 4. Send the code via PHPMailer
$mail = new PHPMailer(true);

try {
    // SMTP settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'kramsetlab99@gmail.com';
    $mail->Password = 'xwpw bbdz tutw cmuh'; // App password
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;

    // Email content
    $mail->setFrom('noreply@email.com', 'Admin');
    $mail->addAddress($email);
    $mail->Subject = 'Your Password Reset Code';
    $mail->Body    = "Hello, $full_name,\n\nYour verification code is: $verification_code\nThis code will expire in 5 minutes.";

    $mail->send();

    $_SESSION['success'] = "Verification code sent.";
    header("Location: login.php");
    exit;

} catch (Exception $e) {
    $_SESSION['error'] = "Email failed to send: {$mail->ErrorInfo}";
    header("Location: login.php?reset_error=1");
    exit;
}
