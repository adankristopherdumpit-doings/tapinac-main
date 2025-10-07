<?php
// Start session and block access if user is not logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}


// Block direct browser access (only allow AJAX)
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    header("Location: /tapinac/views/admin/dashboard.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "grading_system");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Pagination setup
$results_per_page = 12;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$start_from = ($page - 1) * $results_per_page;

// Get the search term
$search = isset($_GET['query']) ? $conn->real_escape_string($_GET['query']) : '';

// Build base SQL
$base_sql = "
    FROM users
    JOIN roles ON users.role_id = roles.id
    JOIN teachers ON users.teacher_id = teachers.id
";

$where = "";
if (!empty($search)) {
    $where = " WHERE 
    teachers.lname LIKE '%$search%' OR
    teachers.email LIKE '%$search%' OR
    roles.role_name LIKE '%$search%' OR
    users.username LIKE '%$search%'";

}

// Count total matching results (for pagination)
$count_sql = "SELECT COUNT(*) AS total " . $base_sql . $where;
$count_result = $conn->query($count_sql)->fetch_assoc();
$total_users = $count_result['total'];
$number_of_pages = ceil($total_users / $results_per_page);

// Final paginated query with optional search
$data_sql = "
    SELECT 
        users.id AS user_id,
        CONCAT(teachers.fname, ' ', teachers.mname, ' ', teachers.lname) AS fullname,
        roles.role_name AS role,
        teachers.email
    " . $base_sql . $where . "
    LIMIT $start_from, $results_per_page
";

$result = $conn->query($data_sql);
?>
