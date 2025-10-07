<?php
// allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

// start session
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once 'database/db_connection.php';
header('Content-Type: application/json');

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// sanitize inputs
$username = trim($username);
$inputPassword = trim($password);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

// FORCE CASE-SENSITIVE username comparison with BINARY
$stmt = $conn->prepare("
    SELECT users.id, users.username, users.password, users.force_password_reset,
           roles.role_name,
           teachers.fname, teachers.mname, teachers.lname
    FROM users
    JOIN roles ON users.role_id = roles.id
    LEFT JOIN teachers ON users.teacher_id = teachers.id
    WHERE BINARY users.username = ?
");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    $storedHash = $user['password'];
    $isPasswordValid = false;

    // Try BCRYPT first
    if (password_verify($inputPassword, $storedHash)) {
        $isPasswordValid = true;
    }
    // Fallback to SHA-256 (legacy) - upgrade to BCRYPT if matched
    elseif (hash('sha256', $inputPassword) === $storedHash) {
        $isPasswordValid = true;

        // Upgrade password to BCRYPT
        $newHash = password_hash($inputPassword, PASSWORD_BCRYPT);
        $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->bind_param("si", $newHash, $user['id']);
        $update->execute();
    }

    if ($isPasswordValid) {
        // build full name properly
        $names = array_filter([$user['fname'], $user['mname'], $user['lname']]);
        $full_name = implode(' ', $names);

        if ($user['force_password_reset'] == 1) {
            // Temporary session only
            $_SESSION['temp_user_id']    = $user['id'];
            $_SESSION['temp_username']  = $user['username'];
            $_SESSION['temp_role']      = $user['role_name'];
            $_SESSION['temp_full_name'] = $full_name;

            echo json_encode([
                'success' => true,
                'force_reset' => true,
                'role' => $user['role_name']
            ]);
        } else {
            // Fully logged in
            session_regenerate_id(true); // prevent session fixation
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = $user['role_name'];
            $_SESSION['full_name'] = $full_name;

            echo json_encode([
                'success' => true,
                'force_reset' => false,
                'role' => $user['role_name']
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No account found with that username.']);
}

$conn->close();
