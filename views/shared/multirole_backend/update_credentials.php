<?php
header('Content-Type: application/json');
session_start();
require_once '../../../database/db_connection.php';

function json_response($message, $type = 'danger') {
    echo json_encode(['message' => $message, 'type' => $type]);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    json_response('Unauthorized access. Please log in.');
}

$user_id = $_SESSION['user_id'];
$action  = $_POST['action'] ?? '';

// === 1. CHANGE EMAIL ===
if ($action === 'change_email') {
    $new_email        = trim($_POST['new_email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';

    if (empty($new_email) || empty($current_password)) {
        json_response('All fields are required');
    }

    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        json_response('Invalid email format');
    }

    if (strtolower(substr($new_email, -4)) !== '.com') {
        json_response('Only .com emails are allowed');
    }

    $domain = substr(strrchr($new_email, "@"), 1);
    if (!checkdnsrr($domain, "MX")) {
        json_response('Email domain is not valid or does not exist');
    }

    $stmt = $conn->prepare("SELECT t.email, u.password FROM users u JOIN teachers t ON u.teacher_id = t.id WHERE u.id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $teacher = $stmt->get_result()->fetch_assoc();

    if (!$teacher || !password_verify($current_password, $teacher['password'])) {
        json_response('Incorrect current password');
    }

    if (strtolower($teacher['email']) === strtolower($new_email)) {
        json_response('New email is the same as your current email', 'warning');
    }

    $check = $conn->prepare("SELECT id FROM teachers WHERE email = ? AND id != (SELECT teacher_id FROM users WHERE id = ?)");
    $check->bind_param("si", $new_email, $user_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        json_response('This email is already in use');
    }

    $update = $conn->prepare("UPDATE teachers SET email = ? WHERE id = (SELECT teacher_id FROM users WHERE id = ?)");
    $update->bind_param("si", $new_email, $user_id);
    if ($update->execute()) {
        json_response('Email updated successfully', 'success');
    } else {
        json_response('Failed to update email');
    }
}

// === 2. CHANGE USERNAME ===
elseif ($action === 'change_username') {
    $new_username     = trim($_POST['new_username'] ?? '');
    $current_password = $_POST['current_password'] ?? '';

    if (empty($new_username) || empty($current_password)) {
        json_response('All fields are required');
    }

    $new_username = filter_var($new_username, FILTER_SANITIZE_STRING);

    $stmt = $conn->prepare("SELECT username, password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || !password_verify($current_password, $user['password'])) {
        json_response('Incorrect current password');
    }

    if (strtolower($new_username) === strtolower($user['username'])) {
        json_response('Username is the same', 'warning');
    }

    $check = $conn->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?) AND id != ?");
    $check->bind_param("si", $new_username, $user_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        json_response('This username already exists');
    }

    $update = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
    $update->bind_param("si", $new_username, $user_id);
    if ($update->execute()) {
        json_response('Username updated successfully', 'success');
    } else {
        json_response('Failed to update username');
    }
}

// === 3. CHANGE PASSWORD ===
elseif ($action === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        json_response('All fields are required');
    }

    if ($new_password !== $confirm_password) {
        json_response('Passwords do not match');
    }

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || !password_verify($current_password, $user['password'])) {
        json_response('Incorrect current password');
    }

    $new_hashed = password_hash($new_password, PASSWORD_BCRYPT);
    $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update->bind_param("si", $new_hashed, $user_id);

    if ($update->execute()) {
        json_response('Password updated successfully', 'success');
    } else {
        json_response('Failed to update password');
    }
}

// === 4. CHANGE NAME ===
elseif ($action === 'change_name') {
    $first_name      = trim($_POST['first_name'] ?? '');
    $middle_name     = trim($_POST['middle_name'] ?? '');
    $last_name       = trim($_POST['last_name'] ?? '');
    $current_password = $_POST['current_password'] ?? '';

    if (empty($first_name) || empty($last_name) || empty($current_password)) {
        json_response('First name, last name, and current password are required');
    }

    // Fetch teacher and password
    $stmt = $conn->prepare("SELECT u.password FROM users u WHERE u.id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || !password_verify($current_password, $user['password'])) {
        json_response('Incorrect current password');
    }

    // Update name
    $update = $conn->prepare("
        UPDATE teachers 
        SET fname = ?, mname = ?, lname = ? 
        WHERE id = (SELECT teacher_id FROM users WHERE id = ?)
    ");
    $update->bind_param("sssi", $first_name, $middle_name, $last_name, $user_id);

    if ($update->execute()) {
        json_response('Name updated successfully', 'success');
    } else {
        json_response('Failed to update name');
    }
}





// === 5. Invalid Action ===
else {
    json_response('Invalid action');
}
?>
