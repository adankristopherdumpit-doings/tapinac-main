<?php
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$allowed_roles = ['masterteacher'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../security/unauthorized.php");
    exit();
}

include '../../database/db_connection.php';

// Fetch all grade levels with their sections
$query = "
    SELECT gl.grade_name, sec.id AS section_id, sec.section_name
    FROM grade_levels gl
    JOIN sections sec ON sec.grade_level_id = gl.id
    ORDER BY gl.id, sec.section_name
";
$result = mysqli_query($conn, $query);

$sections = [];
while ($row = mysqli_fetch_assoc($result)) {
    $sections[] = $row;
}

$role = $_SESSION['role'];
$full_name = $_SESSION['full_name'] ?? 'Unknown'; 
$role_label = ucfirst($role);
$header_color = '#1a1a1a'; // masterteacher theme
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Monitor Reports</title>
  <link rel="icon" type="image/png" href="../../assets/image/logo/logo.png" />
  <link rel="stylesheet" href="../../assets/css/sidebar.css">
  <link rel="stylesheet" href="../../assets/css/all_role_style/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body>

<?php
$nav_file = "../../layoutnav/{$role}bar.php";
if (!file_exists($nav_file)) {
    $nav_file = "../../layoutnav/defaultbar.php";
}
include $nav_file;
?>

<div class="main-content">
  <div style="background-color: <?= $header_color ?>; color: white; padding: 20px 20px; display: flex; justify-content: center; align-items: center; position: relative; min-height: 70px;">
    <h2 class="m-0" style="position: absolute; left: 50%; transform: translateX(-50%);">Monitor Reports</h2>
    <span style="position: absolute; right: 20px;">
        Hello <?= htmlspecialchars($role_label . ' ' . $full_name) ?>
    </span>
  </div>
  
  <div class="container-fluid py-3">
    <div class="card shadow-sm">
      <div class="card-body p-0">
        <div class="container mt-4">
          <div class="table-responsive">
            <table class="table table-bordered text-center" style="background-color: #f0f8ff;">
              <thead class="table-light">
                <tr>
                  <th>Grade</th>
                  <th>Section</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($sections)): ?>
                  <?php foreach ($sections as $row): ?>
                    <tr>
                      <td><?= htmlspecialchars($row['grade_name']) ?></td>
                      <td><?= htmlspecialchars($row['section_name']) ?></td>
                      <td>
                        <a href="../shared/report.php?grade=<?= urlencode($row['grade_name']) ?>&section_id=<?= urlencode($row['section_id']) ?>" 
                           class="btn btn-primary btn-sm <?= $row['report_passed'] ? '' : 'disabled' ?>">
                          View
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="3">No sections available.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
