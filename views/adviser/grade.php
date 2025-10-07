<?php
session_start();


// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Redirect if user is not an admin
if ($_SESSION['role'] !== 'adviser') {
    header("Location: ../security/unauthorized.php"); 
    exit();
}

// Check if 'grade' parameter exists and is not empty
if (!isset($_GET['grade']) || empty(trim($_GET['grade']))) {
    // Handle missing grade param: you can redirect or show error message
    die("Error: No grade level specified. Please select a grade.");
}

// Now get the grade from the URL safely
$currentGrade = trim($_GET['grade']);


include '../../database/db_connection.php';

$user_id = $_SESSION['user_id'] ?? null;
$gradeLevelId = null;
$sections = [];

// Get grade_level_id from grade_levels table
$gradeQuery = "SELECT id FROM grade_levels WHERE grade_name = ?";
$gradeStmt = mysqli_prepare($conn, $gradeQuery);
mysqli_stmt_bind_param($gradeStmt, 's', $currentGrade);
mysqli_stmt_execute($gradeStmt);
$gradeResult = mysqli_stmt_get_result($gradeStmt);

if ($row = mysqli_fetch_assoc($gradeResult)) {
    $gradeLevelId = $row['id'];
} else {
    die("Error: Grade level '{$currentGrade}' not found in database.");
}

// If grade_level_id found, fetch teacher's sections for that grade
if ($user_id && $gradeLevelId) {
    $query = "
        SELECT DISTINCT sec.section_name, sec.id
        FROM subject_assignments sa
        JOIN sections sec ON sa.section_id = sec.id
        WHERE sa.teacher_id = ? AND sa.grade_level_id = ?
    ";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $gradeLevelId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $sections[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($currentGrade) ?></title>
    <link rel="icon" type="image/png" href="../../assets/image/logo/logo.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- Navigation Bar -->
<?php include '../../layoutnav/adviserbar.php'; ?>

<div style="margin-left: 250px;">
  <div style="background-color: #44A344; color: white; padding: 20px 20px; display: flex; justify-content: center; align-items: center; position: relative; min-height: 70px;">
    <h2 class="m-0" style="position: absolute; left: 45%; transform: translateX(-50%);"><?= htmlspecialchars($currentGrade) ?></h2>
    <?php if (isset($_SESSION['full_name'])): ?>
    <span style="position: absolute; right: 20px;">
        Hello Adviser <?= htmlspecialchars($_SESSION['full_name']) ?>
    </span>
    <?php endif; ?>
  </div>

  <div class="container mt-4">
    <div class="table-responsive">
        <table class="table table-bordered text-center" style="background-color: #f0f8ff;">
            <thead class="table-info">
                <tr>
                    <th style="display: none;">ID</th>
                    <th>Section</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($sections)): ?>
                    <?php foreach ($sections as $row): ?>
                        <tr>
                            <td style="display: none;"><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['section_name']); ?></td>
                            <td>
                                <button class="btn btn-info btn-sm view-btn" data-id="<?= htmlspecialchars($row['id']) ?>">
                                    View
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">No subject assignments found for <?= htmlspecialchars($currentGrade) ?>.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
  </div>
</div>


<script>
  // Force reload when user clicks back
  window.addEventListener("pageshow", function (event) {
    if (event.persisted) {
      window.location.reload();
    }
  });
</script>

</body>
</html>
