<?php
session_start();
include '../../database/db_connection.php';
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    $msg = $_GET['message'] ?? '';
    if ($status === 'success_add') {
        echo "<div class='alert alert-success'>Student added successfully.</div>";
    } else {
        $escaped = htmlspecialchars($msg);
        echo "<div class='alert alert-danger'>Error: {$escaped}</div>";
    }
}


// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$allowed_roles = ['adviser', 'masterteacher', 'principal', 'teacher'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../../security/unauthorized.php");
    exit();
}

// Get grade & section from query params
$grade = $_GET['grade'] ?? null;
$section_name = $_GET['section'] ?? null;

if (!$grade || !$section_name) {
    die("Invalid section.");
}

$role = $_SESSION['role'];
$full_name = $_SESSION['full_name'] ?? '';
$role_label = ucfirst($role);
$header_color = ($role === 'masterteacher') ? '#1a1a1a' : '#44A344';

// Navigation bar file
$nav_file = "../../layoutnav/{$role}bar.php";
if (!file_exists($nav_file)) {
    $nav_file = "../../layoutnav/defaultbar.php"; // fallback
}

// ✅ Step 1: Get section_id and adviser_id
$sec_stmt = $conn->prepare("
    SELECT s.id, s.teacher_id, g.grade_name 
    FROM sections s
    INNER JOIN grade_levels g ON s.grade_level_id = g.id
    WHERE s.grade_level_id = ? AND s.section_name = ?
");
$sec_stmt->bind_param("is", $grade, $section_name);
$sec_stmt->execute();
$sec_result = $sec_stmt->get_result();
$section_id = null;
$adviser_id = null;

if ($row = $sec_result->fetch_assoc()) {
    $section_id = $row['id'];
    $adviser_id = $row['teacher_id']; // adviser assigned from assign_adviser.php
    $grade_name = $row['grade_name']; // actual grade label
}

// ✅ Step 2: If section found, get subject assignments
$result = null;
if ($section_id) {
    $stmt = $conn->prepare("
    SELECT sa.teacher_id, t.fname, t.lname, s.subject_name,
           CONCAT(ay.year_start, '-', ay.year_end) AS academic_year
    FROM subject_assignments sa
    INNER JOIN teachers t ON sa.teacher_id = t.id
    INNER JOIN subjects s ON sa.subject_id = s.id
    INNER JOIN academic_years ay ON sa.academic_year_id = ay.id
    WHERE sa.grade_level_id = ? AND sa.section_id = ?
    ");
    $stmt->bind_param("ii", $grade, $section_id);
    $stmt->execute();
    $result = $stmt->get_result();
}

$stud_stmt = $conn->prepare("
    SELECT id, lname, fname, mname, gender 
    FROM students 
    WHERE grade_level_id = ? AND section_id = ?
    ORDER BY lname ASC
");
$stud_stmt->bind_param("ii", $grade, $section_id);
$stud_stmt->execute();
$students = $stud_stmt->get_result();


$gradeParam = $_GET['grade'];

// If the grade is just a number, prepend "Grade "
if (is_numeric($gradeParam)) {
    $gradeParam = "Grade " . $gradeParam;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Section <?= htmlspecialchars($section_name) ?> (Grade <?= htmlspecialchars($grade) ?>)</title>
  <link rel="icon" type="image/png" href="../../assets/image/logo/logoone.png" />
  <link rel="stylesheet" href="../../assets/css/sidebar.css" />
  <link rel="stylesheet" href="../../assets/css/all_role_style/style.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
</head>
<body>

<!-- Navigation -->
<?php include $nav_file; ?>

<div class="main-content">
  <!-- Sticky Header -->
  <div style="
      position: sticky;
      top: 0;
      z-index: 999;
      background-color: <?= $header_color ?>;
      color: white;
      padding: 20px;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 70px;
  ">
    <h2 class="m-0" style="position: absolute; left: 50%; transform: translateX(-50%);">
      <?= htmlspecialchars("$grade_name - $section_name") ?>
    </h2>

    <?php if (!empty($full_name)): ?>
        <span style="position: absolute; right: 20px;">
            Hello <?= htmlspecialchars($role_label . ' ' . $full_name) ?>
        </span>
    <?php endif; ?>
  </div>

  <div class="container mt-4">
    <a href="../shared/grade.php?grade=<?= urlencode($gradeParam) ?>" class="btn btn-secondary mb-3">Back</a>
    <table class="table table-bordered text-center">
      <thead class="table-info">
        <tr>
          <th>Teacher Name</th>
          <th>Subject</th>
          <th>Role</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['fname'] . ' ' . $row['lname']) ?></td>
              <td><?= htmlspecialchars($row['subject_name']) ?></td>
              <td>
                <?php if ($adviser_id && $row['teacher_id'] == $adviser_id): ?>
                  <span class="text-success fw-bold">Adviser</span>
                <?php else: ?>
                  <span class="text-muted">Subject Teacher</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="4">No data found for this section.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="d-flex justify-content-between align-items-center mb-3">
  <div class="container mt-5">
  <h4>Student List</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
      Add Student
    </button>
  <table class="table table-bordered text-center">
    <thead class="table-warning">
      <tr>
        <th>ID</th>
        <th>Last Name</th>
        <th>First Name</th>
        <th>Middle Name</th>
        <th>Gender</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($students && $students->num_rows > 0): ?>
        <?php while ($stud = $students->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($stud['id']) ?></td>
            <td><?= htmlspecialchars($stud['lname']) ?></td>
            <td><?= htmlspecialchars($stud['fname']) ?></td>
            <td><?= htmlspecialchars($stud['mname']) ?></td>
            <td><?= htmlspecialchars($stud['gender']) ?></td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="5">No students enrolled in this section.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Add Student Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addModalLabel">Add Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="process_add.php" method="POST">
      <input type="hidden" name="grade" value="<?= htmlspecialchars($grade) ?>">
      <input type="hidden" name="section_id" value="<?= htmlspecialchars($section_id) ?>">
      <input type="hidden" name="section_name" value="<?= htmlspecialchars($section_name) ?>">

        <div class="modal-body">
          <div class="mb-3">
            <label for="fname" class="form-label">First Name</label>
            <input type="text" name="fname" id="fname" class="form-control" required>
          </div>
          <div class="mb-3">
            <label for="mname" class="form-label">Middle Name</label>
            <input type="text" name="mname" id="mname" class="form-control">
          </div>
          <div class="mb-3">
            <label for="lname" class="form-label">Last Name</label>
            <input type="text" name="lname" id="lname" class="form-control" required>
          </div>
          <div class="mb-3">
            <label for="gender" class="form-label">Gender</label>
            <select name="gender" id="gender" class="form-select" required>
              <option value="" disabled selected>Select gender</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

</body>
</html>
