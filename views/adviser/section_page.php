<?php
// views/adviser/section_page.php

session_start();
include '../../database/db_connection.php';

// Prevent caching (must be BEFORE any output)
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

$role = $_SESSION['role'];
$full_name = $_SESSION['full_name'] ?? '';
$role_label = ucfirst($role);
$header_color = ($role === 'masterteacher') ? '#1a1a1a' : '#44A344';

// collect status message for display inside HTML (avoid output before headers)
$status_html = '';
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    $msg = $_GET['message'] ?? '';
    if ($status === 'success_add') {
        $status_html = "<div class='alert alert-success'>Student added successfully.</div>";
    } else {
        $status_html = "<div class='alert alert-danger'>Error: " . htmlspecialchars($msg) . "</div>";
    }
}

// navigation bar file
$nav_file = "../../layoutnav/{$role}bar.php";
if (!file_exists($nav_file)) {
    $nav_file = "../../layoutnav/defaultbar.php";
}

// check DB connection
if (!isset($conn) || !$conn) {
    die("Database connection not found.");
}

// Determine section context: prefer section_id if provided, otherwise grade+section name
$section_id = null;
$grade = null;
$section_name = null;
$grade_name = null;
$adviser_id = null;

if (isset($_GET['section_id']) && is_numeric($_GET['section_id'])) {
    $section_id = (int) $_GET['section_id'];

    $stmt = $conn->prepare("
        SELECT s.id, s.section_name, s.grade_level_id, s.teacher_id, g.grade_name
        FROM sections s
        INNER JOIN grade_levels g ON s.grade_level_id = g.id
        WHERE s.id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $section_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $grade = (int)$row['grade_level_id'];
        $section_name = $row['section_name'];
        $grade_name = $row['grade_name'];
        $adviser_id = $row['teacher_id'];
    } else {
        // invalid section_id
        $stmt->close();
        die("Section not found.");
    }
    $stmt->close();
} else {
    // fallback to grade + section query params
    $grade = isset($_GET['grade']) && is_numeric($_GET['grade']) ? (int)$_GET['grade'] : null;
    $section_name = isset($_GET['section']) ? trim($_GET['section']) : null;

    if (!$grade || !$section_name) {
        die("Invalid section. Provide section_id or grade & section in the query string.");
    }

    $sec_stmt = $conn->prepare("
        SELECT s.id, s.teacher_id, s.grade_level_id, g.grade_name
        FROM sections s
        INNER JOIN grade_levels g ON s.grade_level_id = g.id
        WHERE s.grade_level_id = ? AND s.section_name = ?
        LIMIT 1
    ");
    $sec_stmt->bind_param("is", $grade, $section_name);
    $sec_stmt->execute();
    $sec_result = $sec_stmt->get_result();
    if ($row = $sec_result->fetch_assoc()) {
        $section_id = (int)$row['id'];
        $adviser_id = $row['teacher_id'];
        $grade = (int)$row['grade_level_id'];
        $grade_name = $row['grade_name'];
    } else {
        $sec_stmt->close();
        die("Section not found for Grade {$grade} and section '" . htmlspecialchars($section_name) . "'.");
    }
    $sec_stmt->close();
}

// --- Get subject assignments for this section (if table exists) ---
// Check table presence first
$subject_assignments = [];
$sa_check = $conn->query("SHOW TABLES LIKE 'subject_assignments'");
if ($sa_check && $sa_check->num_rows > 0) {
    // Prepared statement; if your schema is different adjust the fields/tables
    $stmt = $conn->prepare("
        SELECT sa.teacher_id, t.fname, t.lname, sub.subject_name,
               CONCAT(IFNULL(ay.year_start,''), '-', IFNULL(ay.year_end,'')) AS academic_year
        FROM subject_assignments sa
        INNER JOIN teachers t ON sa.teacher_id = t.id
        INNER JOIN subjects sub ON sa.subject_id = sub.id
        LEFT JOIN academic_years ay ON sa.academic_year_id = ay.id
        WHERE sa.grade_level_id = ? AND sa.section_id = ?
        ORDER BY sub.subject_name, t.lname
    ");
    if ($stmt) {
        $stmt->bind_param("ii", $grade, $section_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $subject_assignments[] = $r;
        }
        $stmt->close();
    } // else leave subject_assignments empty
}

// --- Get students in this section ---
$students = [];
$stud_stmt = $conn->prepare("
    SELECT id, lname, fname, mname, gender
    FROM students
    WHERE grade_level_id = ? AND section_id = ?
    ORDER BY lname ASC
");
$stud_stmt->bind_param("ii", $grade, $section_id);
$stud_stmt->execute();
$stud_res = $stud_stmt->get_result();
while ($s = $stud_res->fetch_assoc()) {
    $students[] = $s;
}
$stud_stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Section <?= htmlspecialchars($section_name ?? 'Unknown') ?> (Grade <?= htmlspecialchars($grade) ?>)</title>
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
      background-color: <?= htmlspecialchars($header_color) ?>;
      color: white;
      padding: 20px;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 70px;
  ">
    <h2 class="m-0" style="position: absolute; left: 50%; transform: translateX(-50%);">
      <?= htmlspecialchars(($grade_name ?? "Grade {$grade}") . ' - ' . ($section_name ?? 'Section')) ?>
    </h2>

    <?php if (!empty($full_name)): ?>
        <span style="position: absolute; right: 20px;">
            Hello <?= htmlspecialchars($role_label . ' ' . $full_name) ?>
        </span>
    <?php endif; ?>
  </div>

  <div class="container mt-4">
    <?= $status_html ?>

    <!-- Back button; adjust if your parent page is elsewhere -->
    <a href="teacher_listpage.php" class="btn btn-secondary mb-3">← Back</a>

    <!-- Subject assignments table -->
    <table class="table table-bordered text-center">
      <thead class="table-info">
        <tr>
          <th>Teacher Name</th>
          <th>Subject</th>
          <th>Academic Year</th>
          <th>Role</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($subject_assignments)): ?>
          <?php foreach ($subject_assignments as $row): ?>
            <tr>
              <td><?= htmlspecialchars(trim($row['fname'] . ' ' . $row['lname'])) ?></td>
              <td><?= htmlspecialchars($row['subject_name']) ?></td>
              <td><?= htmlspecialchars($row['academic_year']) ?></td>
              <td>
                <?php if ($adviser_id !== null && (int)$row['teacher_id'] === (int)$adviser_id): ?>
                  <span class="text-success fw-bold">Adviser</span>
                <?php else: ?>
                  <span class="text-muted">Subject Teacher</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="4">No subject assignment data found for this section.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- Students list and Add Student modal trigger -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4>Student List</h4>
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
        Add Student
      </button>
    </div>

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
        <?php if (!empty($students)): ?>
          <?php foreach ($students as $stud): ?>
            <tr>
              <td><?= htmlspecialchars($stud['id']) ?></td>
              <td><?= htmlspecialchars($stud['lname']) ?></td>
              <td><?= htmlspecialchars($stud['fname']) ?></td>
              <td><?= htmlspecialchars($stud['mname']) ?></td>
              <td><?= htmlspecialchars($stud['gender']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="5">No students enrolled in this section.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="process_add.php" method="POST">
        <input type="hidden" name="grade" value="<?= htmlspecialchars($grade) ?>">
        <input type="hidden" name="section_id" value="<?= htmlspecialchars($section_id) ?>">
        <input type="hidden" name="section_name" value="<?= htmlspecialchars($section_name) ?>">

        <div class="modal-header">
          <h5 class="modal-title" id="addModalLabel">Add Student</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
