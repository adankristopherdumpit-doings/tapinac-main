<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$code = trim($_POST['code'] ?? '');

if (!isset($_SESSION['reset_code'])) {
    echo json_encode(['success' => false, 'message' => 'No verification code set.']);
    exit;
}

// Check expiry
if (isset($_SESSION['code_expiry']) && time() > $_SESSION['code_expiry']) {
    echo json_encode(['success' => false, 'message' => 'Verification code expired.']);
    exit;
}

if ($code === trim($_SESSION['reset_code'])) {
    $_SESSION['otp_verified'] = true;
    if (isset($_SESSION['found_user_id'])) {
        $_SESSION['reset_user_id'] = $_SESSION['found_user_id'];
    }
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid verification code.']);
}
