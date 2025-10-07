<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../../database/db_connection.php';

header('Content-Type: application/json');

if (isset($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);

    $query = "SELECT 
                u.id AS user_id,
                CONCAT(t.fname, ' ', COALESCE(t.mname, ''), ' ', t.lname) AS fullname,
                u.username,
                t.email,
                r.role_name AS role
              FROM users u
              JOIN teachers t ON u.teacher_id = t.id
              JOIN roles r ON u.role_id = r.id
              WHERE u.id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'user_id' => $user_id,
            'fullname' => $row['fullname'],
            'username' => $row['username'],
            'role' => $row['role'],
            'email' => $row['email']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No data found.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
}
