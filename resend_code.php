<?php
session_start();
header('Content-Type: application/json');

// Check if user is set
if (!isset($_SESSION['found_user_id']) || !isset($_SESSION['reset_email'])) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}

// Check if previous code is still valid (5-minute cooldown)
if (isset($_SESSION['last_code_sent']) && time() - $_SESSION['last_code_sent'] < 60) {
    echo json_encode(['success' => false, 'message' => 'Please wait before resending the code.']);
    exit;
}

// Generate new 6-digit code
$verification_code = random_int(100000, 999999);
$_SESSION['reset_code'] = $verification_code;
$_SESSION['code_expiry'] = time() + 300; // 5 minutes
$_SESSION['last_code_sent'] = time();

$email = $_SESSION['reset_email'];
$full_name = $_SESSION['reset_name'] ?? 'User';

// PHPMailer
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'kramsetlab99@gmail.com';
    $mail->Password = 'xwpw bbdz tutw cmuh'; // App password
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;

    $mail->setFrom('noreply@email.com', 'Admin');
    $mail->addAddress($email);
    $mail->Subject = 'Your Password Reset Code';
    $mail->Body    = "Hello, $full_name,\n\nYour verification code is: $verification_code\nThis code will expire in 5 minutes.";

    $mail->send();

    echo json_encode(['success' => true, 'message' => 'Code resent successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => "Email failed to send: {$mail->ErrorInfo}"]);
}
