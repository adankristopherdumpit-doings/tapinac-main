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





if (isset($_SESSION['reset_code']) && $code === strval($_SESSION['reset_code'])) {
    $_SESSION['otp_verified'] = true;

    // Move the ID from found_user_id to reset_user_id
    if (isset($_SESSION['found_user_id'])) {
        $_SESSION['reset_user_id'] = $_SESSION['found_user_id'];
    }

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid verification code.']);
}


?>
