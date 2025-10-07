<?php
session_start();

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// --- Authentication / Authorization ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'masterteacher') {
    header('Location: ../security/unauthorized.php');
    exit;
}

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = function_exists('random_bytes')
        ? bin2hex(random_bytes(32))
        : bin2hex(openssl_random_pseudo_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Include DB connection
include_once __DIR__ . '/../../database/db_connection.php';

$successMessage = '';
$errorMessage   = '';

// --- Handle Add Subject ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $grade_level = intval($_POST['grade_level'] ?? 0);
    $subject_name = trim($_POST['subject_name'] ?? '');
    $posted_csrf = $_POST['csrf_token'] ?? '';

    if (!hash_equals($csrf_token, $posted_csrf)) {
        $errorMessage = 'Invalid CSRF token.';
    } elseif ($grade_level <= 0 || $subject_name === '') {
        $errorMessage = 'Please provide a grade level and subject name.';
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO subjects (subject_name, grade_level_id) VALUES (?, ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'si', $subject_name, $grade_level);
            if (mysqli_stmt_execute($stmt)) {
                $successMessage = 'Subject added successfully.';
            } else {
                $errorMessage = 'Error adding subject: ' . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// --- Handle Edit Subject ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_subject'])) {
    $subject_id   = intval($_POST['subject_id'] ?? 0);
    $subject_name = trim($_POST['subject_name'] ?? '');
    $grade_level  = intval($_POST['grade_level'] ?? 0);
    $posted_csrf  = $_POST['csrf_token'] ?? '';

    if (!hash_equals($csrf_token, $posted_csrf)) {
        $errorMessage = 'Invalid CSRF token.';
    } elseif ($subject_id <= 0 || $grade_level <= 0 || $subject_name === '') {
        $errorMessage = 'Please provide valid subject information.';
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE subjects SET subject_name = ?, grade_level_id = ? WHERE id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'sii', $subject_name, $grade_level, $subject_id);
            if (mysqli_stmt_execute($stmt)) {
                $successMessage = 'Subject updated successfully.';
            } else {
                $errorMessage = 'Error updating subject: ' . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// --- Handle Delete Subject ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_subject'])) {
    $subject_id  = intval($_POST['subject_id'] ?? 0);
    $posted_csrf = $_POST['csrf_token'] ?? '';

    if (!hash_equals($csrf_token, $posted_csrf)) {
        $errorMessage = 'Invalid CSRF token.';
    } elseif ($subject_id <= 0) {
        $errorMessage = 'Invalid subject selected for deletion.';
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM subjects WHERE id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $subject_id);
            if (mysqli_stmt_execute($stmt)) {
                $successMessage = 'Subject deleted successfully.';
            } else {
                $errorMessage = 'Error deleting subject: ' . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// --- Fetch grade levels ---
$gradeLevels = [];
$sql = "SELECT id, grade_name FROM grade_levels ORDER BY id";
$res = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($res)) {
    $gradeLevels[$row['id']] = $row['grade_name'];
}
mysqli_free_result($res);

// --- Fetch subjects grouped by grade ---
$subjectsByGrade = [];
$sql = "SELECT id, subject_name, grade_level_id FROM subjects ORDER BY grade_level_id, subject_name";
$res = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($res)) {
    $gid = (int)$row['grade_level_id'];
    if (!isset($subjectsByGrade[$gid])) $subjectsByGrade[$gid] = [];
    $subjectsByGrade[$gid][] = $row;
}
mysqli_free_result($res);

// --- Fetch teachers for preference form ---
$teachers = [];
$tres = mysqli_query($conn, "SELECT id, fname, mname, lname FROM teachers ORDER BY lname");
while ($row = mysqli_fetch_assoc($tres)) {
    $teachers[] = $row;
}

// --- Get logged-in user info ---
$role_label = '';
$full_name  = '';
$user_id = $_SESSION['user_id'];
$userQuery = "
    SELECT u.id, u.username, r.role_name, t.fname, t.mname, t.lname
    FROM users u
    JOIN roles r ON u.role_id = r.id
    LEFT JOIN teachers t ON u.teacher_id = t.id
    WHERE u.id = ?
";
$stmt = mysqli_prepare($conn, $userQuery);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($user = mysqli_fetch_assoc($result)) {
    $role_label = $user['role_name'];
    $full_name  = $user['fname'].' '.($user['mname'] ? substr($user['mname'],0,1).'. ' : '').$user['lname'];
}
mysqli_stmt_close($stmt);

$header_color = '#1a1a1a'; // theme
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Subject Management</title>
  <link rel="icon" href="../../assets/image/logo/logo.png" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/sidebar.css">
  <link rel="stylesheet" href="../../assets/css/all_role_style/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

</head>
<body>
<?php
$nav_file = "../../layoutnav/{$_SESSION['role']}bar.php";
if (!file_exists($nav_file)) $nav_file = "../../layoutnav/defaultbar.php";
include $nav_file;
?>

<div style="background-color:<?php echo $header_color; ?>;color:white;padding:20px;display:flex;justify-content:center;align-items:center;position:relative;min-height:70px;">
  <h2 class="m-0" style="position:absolute;left:50%;transform:translateX(-50%);">Subject Management</h2>
  <span style="position:absolute;right:20px;">Hello <?= htmlspecialchars($role_label.' '.$full_name) ?></span>
</div>

<div class="container mt-4">
  <?php if ($successMessage): ?><div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div><?php endif; ?>
  <?php if ($errorMessage): ?><div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div><?php endif; ?>

  <!-- Add Subject Form -->
  <div class="card mb-4">
    <div class="card-header bg-dark text-white">Add New Subject</div>
    <div class="card-body">
      <form method="post" class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Grade Level</label>
          <select name="grade_level" class="form-select" required>
            <option value="">Select Grade Level</option>
            <?php foreach ($gradeLevels as $gid=>$gname): ?>
              <option value="<?= $gid ?>"><?= htmlspecialchars($gname) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-5">
          <label class="form-label">Subject Name</label>
          <input type="text" name="subject_name" class="form-control" required>
        </div>
        <div class="col-md-3 text-end">
          <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
          <button type="submit" name="add_subject" class="btn btn-primary">Add Subject</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Teacher Preference Form -->
  <div class="card mb-4">
    <div class="card-header bg-dark text-white">Assign Teacher’s Major/Preference</div>
    <div class="card-body">
      <form method="POST" action="assign_major.php" class="row g-2 align-items-end">
        <div class="col-md-5">
          <label class="form-label">Select Teacher</label>
          <select name="teacher_id" class="form-select" required>
            <option value="">Select Teacher</option>
            <?php foreach ($teachers as $t): ?>
              <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['lname'].', '.$t['fname']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-5">
          <label class="form-label">Select Subject</label>
          <select name="subject_id" class="form-select" required>
            <option value="">Select Subject</option>
            <?php foreach ($subjectsByGrade as $gid=>$subs): ?>
              <?php foreach ($subs as $s): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['subject_name']) ?> (<?= htmlspecialchars($gradeLevels[$gid]) ?>)</option>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-success w-100">Confirm</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Subjects Table -->
  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover">
          <thead class="table-light">
            <tr>
              <th style="width:25%">Grade Level</th>
              <th style="width:50%">Subject</th>
              <th style="width:25%">Action</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($gradeLevels as $gid=>$gname):
            $subs = $subjectsByGrade[$gid] ?? [];
            if ($subs):
              $count = count($subs);
              foreach ($subs as $i=>$s): ?>
                <tr>
                  <?php if ($i===0): ?><td rowspan="<?= $count ?>"><?= htmlspecialchars($gname) ?></td><?php endif; ?>
                  <td><?= htmlspecialchars($s['subject_name']) ?></td>
                  <td>
                    <button type="button" class="btn btn-primary btn-sm edit-btn"
                      data-id="<?= $s['id'] ?>"
                      data-name="<?= htmlspecialchars($s['subject_name'],ENT_QUOTES) ?>"
                      data-grade="<?= $gid ?>">Edit</button>
                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this subject?');">
                      <input type="hidden" name="subject_id" value="<?= $s['id'] ?>">
                      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                      <button type="submit" name="delete_subject" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach;
            else: ?>
              <tr>
                <td><?= htmlspecialchars($gname) ?></td>
                <td colspan="2"><em>No subjects yet.</em></td>
              </tr>
            <?php endif; endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title">Edit Subject</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="subject_id" id="edit_subject_id">
          <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
          <div class="mb-3">
            <label class="form-label">Subject Name</label>
            <input type="text" name="subject_name" id="edit_subject_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Grade Level</label>
            <select name="grade_level" id="edit_grade_level" class="form-select" required>
              <?php foreach ($gradeLevels as $gid=>$gname): ?>
                <option value="<?= $gid ?>"><?= htmlspecialchars($gname) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="edit_subject" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('.edit-btn').forEach(btn=>{
    btn.addEventListener('click',()=>{
      document.getElementById('edit_subject_id').value=btn.dataset.id;
      document.getElementById('edit_subject_name').value=btn.dataset.name;
      document.getElementById('edit_grade_level').value=btn.dataset.grade;
      new bootstrap.Modal(document.getElementById('editModal')).show();
    });
  });
});
</script>
</body>
</html>
