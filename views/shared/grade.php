<?php
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Allow access to multiple roles
$allowed_roles = ['adviser', 'masterteacher', 'principal', 'teacher'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../security/unauthorized.php");
    exit();
}

// Check if 'grade' parameter exists and is not empty
if (!isset($_GET['grade']) || empty(trim($_GET['grade']))) {
    die("Error: No grade level specified. Please select a grade.");
}

$currentGrade = trim($_GET['grade']);

include '../../database/db_connection.php';

$user_id = $_SESSION['user_id'] ?? null;
$gradeLevelId = null;
$sections = [];

$gradeQuery = "SELECT id FROM grade_levels WHERE grade_name = ?";
$gradeStmt = mysqli_prepare($conn, $gradeQuery);
if (!$gradeStmt) {
    die("Query Error: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($gradeStmt, 's', $currentGrade);
mysqli_stmt_execute($gradeStmt);
$gradeResult = mysqli_stmt_get_result($gradeStmt);

if ($row = mysqli_fetch_assoc($gradeResult)) {
    $gradeLevelId = $row['id'];
} else {
    die("Error: Grade level '{$currentGrade}' not found in database.");
}

$role = $_SESSION['role'];

if ($role === 'masterteacher') {
    // masterteacher can view all sections for the selected grade
    $query = "
        SELECT DISTINCT sec.section_name, sec.id
        FROM sections sec
        JOIN subject_assignments sa ON sa.section_id = sec.id
        WHERE sa.grade_level_id = ?
    ";
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        die("Query Error: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, 'i', $gradeLevelId);
} else {
    // Other roles (adviser, teacher) can view only their assigned sections
    $query = "
        SELECT DISTINCT sec.section_name, sec.id
        FROM subject_assignments sa
        JOIN sections sec ON sa.section_id = sec.id
        WHERE sa.teacher_id = ? AND sa.grade_level_id = ?
    ";
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        die("Query Error: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $gradeLevelId);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Populate sections array with adviser info
$sections = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Fetch adviser's name
    $adviserQuery = "
      SELECT CONCAT(teachers.fname, ' ', teachers.lname) AS adviser
      FROM sections
      LEFT JOIN teachers ON sections.teacher_id = teachers.id
      WHERE sections.id = ?
    ";
    $advStmt = mysqli_prepare($conn, $adviserQuery);
    if (!$advStmt) {
        die("Query Error: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($advStmt, 'i', $row['id']);
    mysqli_stmt_execute($advStmt);
    $advResult = mysqli_stmt_get_result($advStmt);

    $adviserRow = mysqli_fetch_assoc($advResult);
    $row['adviser'] = $adviserRow['adviser'] ?? null;

    $sections[] = $row;
}

$header_color = ($role === 'masterteacher' || $role === 'principal' || $role === 'admin') ? '#1a1a1a' : '#44A344';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($currentGrade) ?></title>
    <link rel="icon" type="image/png" href="../../assets/image/logo/logo.png" />
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/all_role_style/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body>

<?php
$role = $_SESSION['role'];
$full_name = $_SESSION['full_name'] ?? 'Unknown'; 
$role_label = ucfirst($role);

$nav_file = "../../layoutnav/{$role}bar.php";
if (!file_exists($nav_file)) {
    $nav_file = "../../layoutnav/defaultbar.php";
}
include $nav_file;
?>

<div class="main-content">
  <div style="background-color: <?php echo $header_color; ?>; color: white; padding: 20px 20px; display: flex; justify-content: center; align-items: center; position: relative; min-height: 70px;">
    <h2 class="m-0" style="position: absolute; left: 50%; transform: translateX(-50%);"><?= htmlspecialchars($currentGrade) ?></h2>
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
                  <th>Section Name</th>
                  <th>Action</th>
                </tr>
              </thead>

              <tbody>
                <?php if (!empty($sections)): ?>
                  <?php foreach ($sections as $row): ?>
                    <tr>
                      <td><?= htmlspecialchars($row['section_name']) ?></td>
                      <td>
                        <!-- View Section - Visible only to the Adviser -->
                        <?php if ($role === 'adviser'): ?>
                          <a href="section.php?section_id=<?= urlencode($row['id']) ?>&grade=<?= urlencode($currentGrade) ?>" 
                            class="btn btn-info btn-sm">View Section</a>
                        <?php endif; ?>

                        <!-- Monitor Classes - Visible only to the masterteacher -->
                        <?php if ($role === 'masterteacher'): ?>
                          <a href="../masterteacher/section_page.php?grade=<?= urlencode(str_replace('Grade ', '', $currentGrade)) ?>&section=<?= urlencode(ucfirst(strtolower($row['section_name']))) ?>" 
                            class="btn btn-secondary btn-sm">Monitor Classes</a>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="3">No sections found for <?= htmlspecialchars($currentGrade) ?>.</td>
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
