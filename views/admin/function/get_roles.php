<?php
require_once '../../../database/db_connection.php';
header('Content-Type: application/json');

$result = $conn->query("SELECT id, role_name FROM roles ORDER BY role_name ASC");

$roles = [];
while ($row = $result->fetch_assoc()) {
    $roles[] = $row;
}

echo json_encode($roles);
?>
