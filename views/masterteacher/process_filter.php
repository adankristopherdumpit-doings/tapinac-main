<?php
include "../../db_connection.php";

$grade = $_GET['grade'] ?? '';
$section = $_GET['section'] ?? '';

$sql = "SELECT student_id, name, grade, section FROM students WHERE 1=1";
$params = [];

if ($grade !== '') {
    $sql .= " AND grade = ?";
    $params[] = $grade;
}
if ($section !== '') {
    $sql .= " AND section = ?";
    $params[] = $section;
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table class='table table-bordered'>";
echo "<thead><tr><th>ID</th><th>Name</th><th>Grade</th><th>Section</th></tr></thead><tbody>";
foreach ($students as $s) {
    echo "<tr>
            <td>{$s['student_id']}</td>
            <td>{$s['name']}</td>
            <td>{$s['grade']}</td>
            <td>{$s['section']}</td>
          </tr>";
}
echo "</tbody></table>";
