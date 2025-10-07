<?php
// views/adviser/teacher_listpage.php

session_start();
include '../../database/db_connection.php';

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Only advisers should use this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'adviser') {
    header("Location: ../../security/unauthorized.php");
    exit();
}

$role       = $_SESSION['role'];
$full_name  = $_SESSION['full_name'] ?? '';
$adviser_id = (int) $_SESSION['user_id'];
// 1. Get adviser's advisory section (if any)
$advisory_section_id = null;
$stmt = $conn->prepare("SELECT id FROM sections WHERE teacher_id = ?");
$stmt->bind_param("i", $adviser_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $advisory_section_id = (int)$row['id'];
}
$stmt->close();

// 2. Get sections where adviser has subject assignments
$assigned_section_ids = [];
$stmt = $conn->prepare("
    SELECT DISTINCT sa.section_id
    FROM subject_assignments sa
    WHERE sa.teacher_id = ?
");
$stmt->bind_param("i", $adviser_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $assigned_section_ids[] = (int)$row['section_id'];
}
$stmt->close();

// Header/nav colors & nav file
$header_color = "#44A344";
$role_label   = ucfirst($role);
$nav_file = "../../layoutnav/{$role}bar.php";
if (!file_exists($nav_file)) {
    $nav_file = "../../layoutnav/defaultbar.php";
}

// ensure $conn exists
if (!isset($conn) || !$conn) {
    die("Database connection not found.");
}

/**
 * Get all section IDs where this teacher is assigned to teach subject(s).
 * This function probes several likely table names and checks column existence,
 * so it can adapt to different DB naming.
 *
 * Returns: array of section ids (integers)
 */
function getSubjectAssignedSectionIds($conn, $teacher_id) {
    $candidate_tables = [
        'teacher_subjects', 'teacher_assignments', 'subject_teachers',
        'class_teachers', 'assignments', 'schedules', 'class_schedule',
        'class_subjects', 'teacher_sections', 'subject_assignments'
    ];

    $found_section_ids = [];

    foreach ($candidate_tables as $table) {
        // check table existence
        $check = $conn->query("SHOW TABLES LIKE '{$table}'");
        if (!$check || $check->num_rows === 0) {
            continue; // table not present
        }

        // get columns
        $colsRes = $conn->query("DESCRIBE `{$table}`");
        if (!$colsRes) continue;
        $cols = [];
        while ($c = $colsRes->fetch_assoc()) {
            $cols[] = $c['Field'];
        }

        // determine likely column names
        $teacher_cols = ['teacher_id', 'instructor_id', 'teacher'];
        $section_cols = ['section_id', 'section', 'class_id', 'class'];
        $grade_cols   = ['grade_level_id', 'grade_id', 'grade_level', 'grade'];

        $teacher_col = null;
        $section_col = null;
        $grade_col   = null;

        foreach ($teacher_cols as $t) if (in_array($t, $cols)) { $teacher_col = $t; break; }
        foreach ($section_cols as $s) if (in_array($s, $cols)) { $section_col = $s; break; }
        foreach ($grade_cols as $g)   if (in_array($g, $cols))   { $grade_col = $g; break; }

        // CASE 1: table directly references section_id and teacher_id
        if ($teacher_col && $section_col) {
            $sql = "SELECT DISTINCT `{$section_col}` AS sid FROM `{$table}` WHERE `{$teacher_col}` = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param('i', $teacher_id);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($r = $res->fetch_assoc()) {
                    if (!empty($r['sid'])) $found_section_ids[] = (int)$r['sid'];
                }
                $stmt->close();
            }
        }

        // CASE 2: table assigns teacher by grade_level_id (or equivalent)
        if ($teacher_col && $grade_col) {
            // join with sections to get section ids for that grade
            $sqlg = "SELECT DISTINCT s.id AS sid 
                     FROM `sections` s
                     JOIN `{$table}` t ON s.grade_level_id = t.`{$grade_col}`
                     WHERE t.`{$teacher_col}` = ?";
            if ($stmt2 = $conn->prepare($sqlg)) {
                $stmt2->bind_param('i', $teacher_id);
                $stmt2->execute();
                $res2 = $stmt2->get_result();
                while ($r2 = $res2->fetch_assoc()) {
                    if (!empty($r2['sid'])) $found_section_ids[] = (int)$r2['sid'];
                }
                $stmt2->close();
            }
        }

        // small additional heuristic: maybe the table contains columns `grade` and `section` (text)
        if (!$section_col && in_array('section_name', $cols) && in_array('grade_level', $cols) && $teacher_col) {
            $sqlh = "SELECT DISTINCT s.id AS sid
                     FROM `sections` s
                     JOIN `{$table}` t ON s.section_name = t.section_name AND s.grade_level_id = t.grade_level
                     WHERE t.`{$teacher_col}` = ?";
            if ($stmt3 = $conn->prepare($sqlh)) {
                $stmt3->bind_param('i', $teacher_id);
                $stmt3->execute();
                $res3 = $stmt3->get_result();
                while ($r3 = $res3->fetch_assoc()) {
                    if (!empty($r3['sid'])) $found_section_ids[] = (int)$r3['sid'];
                }
                $stmt3->close();
            }
        }
    }

    // return unique ids
    $found_section_ids = array_values(array_unique($found_section_ids));
    return $found_section_ids;
}

// Fetch all sections grouped by grade_name
$sql = "SELECT s.id, s.section_name, s.grade_level_id, s.teacher_id, g.grade_name 
        FROM sections s
        JOIN grade_levels g ON s.grade_level_id = g.id
        ORDER BY g.id, s.section_name";

$result = $conn->query($sql);
if (!$result) {
    die("Query failed: " . $conn->error);
}

$sections_by_grade = [];
while ($row = $result->fetch_assoc()) {
    $sections_by_grade[$row['grade_name']][] = $row;
}

// determine which sections this adviser actually teaches subjects in
$subject_section_ids = [];
$sql = "SELECT DISTINCT section_id FROM subject_assignments WHERE teacher_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $adviser_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $subject_section_ids[] = (int)$r['section_id'];
    }
    $stmt->close();
}
// now $subject_section_ids contains only the sections where this adviser has assigned subjects

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="image/png" href="../../assets/image/logo/logoone.png" />
  <title>Monitor All Classes</title>
  <link rel="stylesheet" href="../../assets/css/sidebar.css" />
  <link rel="stylesheet" href="../../assets/css/all_role_style/style.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
  <style>
    .section-btn {
      background-color: #e0e0e0;
      border: 1px solid #bbb;
      padding: 10px 14px;
      margin: 6px;
      border-radius: 6px;
      font-weight: 600;
      color: #000;
      text-decoration: none;
      display: inline-block;
      min-width: 160px;
      text-align: center;
    }
    .section-btn:hover { background-color: #17a2b8; color: white; }
    .section-advisory { background-color: #2e7d32; color: #fff; border-color: #256029; } /* green for advisory */
    .section-subject  { background-color: #28a745; color: #fff; border-color: #207a36; } /* green for subject teacher */
    .section-disabled { background-color: #e0e0e0; color: #6c757d; border-color: #d4d4d4; cursor: default; }
  </style>
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
      Monitor Classes
    </h2>
    <?php if (!empty($full_name)): ?>
        <span style="position: absolute; right: 20px;">
            Hello <?= htmlspecialchars($role_label . ' ' . $full_name) ?>
        </span>
    <?php endif; ?>
  </div>

  <div class="container mt-4">
    <div class="d-flex justify-content-between mb-3">
      <a href="../adviser/dashboard.php" class="btn btn-success">Back</a>
    </div>

    <?php if (empty($sections_by_grade)): ?>
      <div class="alert alert-info">No sections found.</div>
    <?php else: ?>
      <?php foreach ($sections_by_grade as $grade => $secList): ?>
        <h5 class="mt-4"><?= htmlspecialchars($grade) ?></h5>
        <div class="mb-3">
          <?php foreach ($secList as $sec): ?>
            <?php
              $sid = (int)$sec['id'];
              $is_advisory = ($sec['teacher_id'] !== null && (int)$sec['teacher_id'] === $adviser_id);
              $is_subject  = in_array($sid, $subject_section_ids, true);

              // label and class priority: advisory label takes precedence if both true
              $label = htmlspecialchars($sec['section_name']);
              $btn_class = 'section-btn ';

              if ($is_advisory) {
                  $label .= " (Advisory Class)";
                  $btn_class .= 'section-advisory';
              } elseif ($is_subject) {
                  $btn_class .= 'section-subject';
              } else {
                  $btn_class .= 'section-disabled';
              }
            ?>

            <?php if ($is_advisory || $is_subject): ?>
              <!-- clickable green button -->
              <a href="section_page.php?section_id=<?= $sid ?>" class="<?= $btn_class ?>">
                <?= $label ?>
              </a>
            <?php else: ?>
              <!-- gray disabled -->
              <button class="<?= $btn_class ?>" disabled><?= $label ?></button>
            <?php endif; ?>

          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
