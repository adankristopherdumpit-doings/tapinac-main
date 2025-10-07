<?php
// adviser/get_teachers.php
session_start();
include '../../database/db_connection.php';

header('Content-Type: text/html; charset=UTF-8');

$me = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$db = mysqli_real_escape_string($conn, mysqli_get_server_info($conn)); // just to force $conn use

// detect columns
$has_roles = false;
$has_role = false;
$has_status = false;

$q = "SELECT COLUMN_NAME
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'teachers'
        AND COLUMN_NAME IN ('roles','role','status')";
$resc = mysqli_query($conn, $q);
if ($resc) {
    while ($r = mysqli_fetch_assoc($resc)) {
        if ($r['COLUMN_NAME'] === 'roles') $has_roles = true;
        if ($r['COLUMN_NAME'] === 'role') $has_role = true;
        if ($r['COLUMN_NAME'] === 'status') $has_status = true;
    }
}

// Build base SELECT
$selectCols = "id, fname, mname, lname";
if ($has_roles) $selectCols .= ", roles";
if ($has_role)  $selectCols .= ", role";
if ($has_status) $selectCols .= ", status";

$whereParts = [];
// prefer filtering by roles column if exists
if ($has_roles) {
    // roles could be comma-separated; use FIND_IN_SET
    $whereParts[] = "(FIND_IN_SET('teacher', roles) OR FIND_IN_SET('adviser', roles))";
} elseif ($has_role) {
    $whereParts[] = "(role = 'teacher' OR role = 'adviser')";
} else {
    // no role data available - cannot filter reliably; fallback to listing all teachers
    $whereParts[] = "1=1";
}

// prefer active status if available
if ($has_status) {
    $whereParts[] = "(status IS NULL OR status = 'active' OR status = '')";
}

$where = implode(' AND ', $whereParts);

$sql = "SELECT {$selectCols} FROM teachers WHERE {$where} ORDER BY lname, fname";
$res = mysqli_query($conn, $sql);

if (!$res) {
    echo "<option disabled>Error loading teachers</option>";
    exit;
}

if (mysqli_num_rows($res) === 0) {
    echo "<option disabled>No teachers found</option>";
    exit;
}

echo "<option value='' disabled selected>Select Teacher</option>";
while ($row = mysqli_fetch_assoc($res)) {
    $tid = (int)$row['id'];
    $name = htmlspecialchars($row['lname'] . ', ' . $row['fname'] . (empty($row['mname']) ? '' : ' ' . $row['mname']));
    if ($tid === $me) $name .= " (Me)";
    echo "<option value='{$tid}'>{$name}</option>";
}
