<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../../database/db_connection.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id'] ?? 0);
    $fname = trim($_POST['fname'] ?? '');
    $mname = trim($_POST['mname'] ?? '');
    $lname = trim($_POST['lname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role_id = intval($_POST['role_id'] ?? 0);

    if (!$user_id || !$fname || !$lname || !$username || !$email || !$role_id) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit;
    }

    // === Update users table ===
    $stmt = $conn->prepare("UPDATE users SET username = ?, role_id = ?, updated_at = NOW() WHERE id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed (users): ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("sii", $username, $role_id, $user_id);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Execute failed (users): ' . $stmt->error]);
        exit;
    }
    $stmt->close();

    // === Get teacher_id linked to user ===
    $stmt = $conn->prepare("SELECT teacher_id FROM users WHERE id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed (get teacher_id): ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($teacher_id);
    $stmt->fetch();
    $stmt->close();

    if (!$teacher_id) {
        echo json_encode(['success' => false, 'message' => 'Teacher not found for this user.']);
        exit;
    }

    // === Update teacher info ===
    $stmt = $conn->prepare("UPDATE teachers SET fname = ?, mname = ?, lname = ?, email = ?, updated_at = NOW() WHERE id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed (teachers): ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("ssssi", $fname, $mname, $lname, $email, $teacher_id);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Execute failed (teachers): ' . $stmt->error]);
        exit;
    }
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
    exit;
}

// === GET request handler ===
if (isset($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);

    $query = "SELECT 
                u.id AS user_id,
                u.username,
                t.fname,
                t.mname,
                t.lname,
                t.email,
                r.role_name,
                r.id AS role_id
              FROM users u
              JOIN teachers t ON u.teacher_id = t.id
              JOIN roles r ON u.role_id = r.id
              WHERE u.id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true] + $row);
    } else {
        echo json_encode(['success' => false, 'message' => 'No data found.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Missing user_id.']);
}
