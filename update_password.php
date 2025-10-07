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
require_once 'database/db_connection.php';
header('Content-Type: application/json');

// Check if temp session exists
if (!isset($_SESSION['temp_user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
    exit;
}

// Get new password inputs
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Validate password length
if (strlen($newPassword) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
    exit;
}

// Check if passwords match
if ($newPassword !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit;
}

// Use bcrypt to hash password
$hashed = password_hash($newPassword, PASSWORD_BCRYPT);

// Update the password in the database
$stmt = $conn->prepare("UPDATE users SET password = ?, force_password_reset = 0 WHERE id = ?");
$stmt->bind_param("si", $hashed, $_SESSION['temp_user_id']);

if ($stmt->execute()) {
    // Promote temp session to full session
    $_SESSION['user_id'] = $_SESSION['temp_user_id'];
    $_SESSION['username'] = $_SESSION['temp_username'];
    $_SESSION['role'] = $_SESSION['temp_role'];
    $_SESSION['full_name'] = $_SESSION['temp_full_name'];
    
    // Remove temp session variables
    unset($_SESSION['temp_user_id'], $_SESSION['temp_username'], $_SESSION['temp_role'], $_SESSION['temp_full_name']);

    echo json_encode(['success' => true, 'role' => $_SESSION['role']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed.']);
}

?>
