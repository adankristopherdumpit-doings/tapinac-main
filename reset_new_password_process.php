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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate passwords
    if (empty($newPassword) || empty($confirmPassword)) {
        echo json_encode(['success' => false, 'message' => 'Password fields cannot be empty.']);
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
        exit;
    }

    // Check session ID
    if (!isset($_SESSION['reset_user_id'])) {
    // Fallback (optional)
        if (isset($_SESSION['found_user_id'])) {
            $_SESSION['reset_user_id'] = $_SESSION['found_user_id'];
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid or expired session.']);
            exit;
        }
    }


    $userId = $_SESSION['reset_user_id'];
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $success = false;

    // Try updating in users table
    $stmt = $conn->prepare("UPDATE users SET password = ?, force_password_reset = 0 WHERE id = ?");
    $stmt->bind_param("si", $hashedPassword, $userId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $success = true;
    }

    $stmt->close();

    // If not successful in users table, try teachers table
    if (!$success) {
        $stmt = $conn->prepare("UPDATE teachers SET password = ?, force_password_reset = 0 WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $userId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $success = true;
        }

        $stmt->close();
    }

    $conn->close();

    // At the end of reset_new_password_process.php
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Password reset successfully.']);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update password. Please try again.']);
        exit;
    }

}
?>
