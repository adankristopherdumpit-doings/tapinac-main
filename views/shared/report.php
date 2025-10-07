<?php
// shared/report.php
// Summary quarterly report (matrix): learners rows, subjects columns.
// Requires: mysqli connection file that sets $conn (adjust paths below).

session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// -- Auth guard (redirect to login if not logged) --
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}
$user_id = (int)$_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? '';

// Allowed roles (adjust as needed)
$allowed_roles = ['adviser','masterteacher','principal','teacher'];
if (!in_array($user_role, $allowed_roles)) {
    header("Location: ../security/unauthorized.php");
    exit();
}

// -- DB connection: try sensible paths (edit if yours differs) --
$dbPaths = [
    __DIR__ . '/../database/db_connection.php',
    __DIR__ . '/../../database/db_connection.php',
    __DIR__ . '/database/db_connection.php',
    __DIR__ . '/../db_connection.php'
];
$conn = null;
foreach ($dbPaths as $p) {
    if (file_exists($p)) {
        require_once $p;
        if (isset($conn) && $conn instanceof mysqli) break;
        if (isset($mysqli) && $mysqli instanceof mysqli) { $conn = $mysqli; break; }
    }
}
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection not found - please adjust the \$dbPaths array in shared/report.php");
}

// small helper
function safe_get($k, $d = null) { return isset($_GET[$k]) ? trim($_GET[$k]) : $d; }

// -- Determine section_id (auto-detect by adviser, allow override via GET) --
$section_id = safe_get('section_id', null);
if ($section_id !== null) $section_id = (int)$section_id;

// If no explicit section, try session
if (empty($section_id) && isset($_SESSION['section_id'])) {
    $section_id = (int)$_SESSION['section_id'];
}

// If still no section, try to find the section assigned to the logged-in teacher (adviser)
if (empty($section_id)) {
    $q = "SELECT id FROM sections WHERE teacher_id = ? LIMIT 1";
    if ($stmt = $conn->prepare($q)) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($r = $res->fetch_assoc()) $section_id = (int)$r['id'];
        $stmt->close();
    }
}

// If still empty, allow masterteacher/principal to pass a section via GET (already attempted), otherwise error
if (empty($section_id)) {
    echo "<div class='alert alert-warning'>No section detected. If you're a masterteacher/principal, open this page with ?section_id=&lt;id&gt; or assign yourself a section.</div>";
    return;
}

// -- Detect academic year (session -> active -> latest) --
$academic_year_id = isset($_SESSION['academic_year_id']) ? (int)$_SESSION['academic_year_id'] : null;
if (empty($academic_year_id)) {
    // Try active academic year
    $q = "SELECT id FROM academic_years WHERE status = 'active' LIMIT 1";
    if ($stmt = $conn->prepare($q)) {
        $stmt->execute();
        $res = $stmt->get_result();
        if ($r = $res->fetch_assoc()) $academic_year_id = (int)$r['id'];
        $stmt->close();
    }
}
// Fallback: latest by id
if (empty($academic_year_id)) {
    $q = "SELECT id FROM academic_years ORDER BY id DESC LIMIT 1";
    if ($res = $conn->query($q)) {
        if ($r = $res->fetch_assoc()) $academic_year_id = (int)$r['id'];
    }
}
if (empty($academic_year_id)) {
    echo "<div class='alert alert-warning'>No academic year found in the system. Please add or activate an academic year.</div>";
    return;
}

// -- Determine quarter (use quarters table lookup by id passed via ?quarter=) --
$quarter_id = safe_get('quarter', null);
if ($quarter_id !== null) $quarter_id = (int)$quarter_id;

$quarter_name = '';
if ($quarter_id) {
    $q = "SELECT id, name, academic_year_id FROM quarters WHERE id = ? LIMIT 1";
    if ($stmt = $conn->prepare($q)) {
        $stmt->bind_param('i', $quarter_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($r = $res->fetch_assoc()) {
            $quarter_name = $r['name'];
            // If the quarter belongs to a different year, override academic_year_id with the quarter's year
            if (!empty($r['academic_year_id'])) $academic_year_id = (int)$r['academic_year_id'];
        } else {
            $quarter_id = null; // invalid id, fallback next
        }
        $stmt->close();
    }
}
if (!$quarter_id) {
    // pick the first quarter for the selected academic year (usually Q1)
    $q = "SELECT id, name FROM quarters WHERE academic_year_id = ? ORDER BY id ASC LIMIT 1";
    if ($stmt = $conn->prepare($q)) {
        $stmt->bind_param('i', $academic_year_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($r = $res->fetch_assoc()) {
            $quarter_id = (int)$r['id'];
            $quarter_name = $r['name'];
        }
        $stmt->close();
    }
}

// -- Fetch section info (and grade_level) --
$section = null;
$q = "SELECT id, section_name, grade_level_id, teacher_id" .
     // some schemas have school_id; include if present
     ", IFNULL(school_id, NULL) AS school_id" .
     " FROM sections WHERE id = ? LIMIT 1";
if ($stmt = $conn->prepare($q)) {
    $stmt->bind_param('i', $section_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $section = $res->fetch_assoc();
    $stmt->close();
}
$grade_level_id = $section['grade_level_id'] ?? null;
$section_name = $section['section_name'] ?? '';

// -- Fetch school info: try to use section.school_id if present else pick the single row in school_info --
$school = ['school_name'=>'','school_id'=>'','region'=>'','division'=>'','district'=>''];
if (!empty($section['school_id'])) {
    $q = "SELECT school_name, school_id, region, division, district FROM school_info WHERE id = ? LIMIT 1";
    if ($stmt = $conn->prepare($q)) {
        $stmt->bind_param('i', $section['school_id']);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($r = $res->fetch_assoc()) $school = $r;
        $stmt->close();
    }
}
if (empty(array_filter($school))) {
    $q = "SELECT school_name, school_id, region, division, district FROM school_info LIMIT 1";
    if ($res = $conn->query($q)) {
        if ($r = $res->fetch_assoc()) $school = $r;
    }
}
// -- Fetch subjects for this grade level (ordered) --
$subjects = []; // [subject_id => subject_name]
if ($grade_level_id) {
    $q = "SELECT id, subject_name FROM subjects WHERE grade_level_id = ? ORDER BY subject_name";
    if ($stmt = $conn->prepare($q)) {
        $stmt->bind_param('i', $grade_level_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $subjects[(int)$r['id']] = $r['subject_name'];
        $stmt->close();
    }
}
// fallback: if no subjects found, try subject_assignments for this section
if (empty($subjects)) {
    $q = "SELECT DISTINCT subj.id, subj.subject_name FROM subject_assignments sa JOIN subjects subj ON sa.subject_id = subj.id WHERE sa.section_id = ? ORDER BY subj.subject_name";
    if ($stmt = $conn->prepare($q)) {
        $stmt->bind_param('i', $section_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $subjects[(int)$r['id']] = $r['subject_name'];
        $stmt->close();
    }
}

// -- Fetch students in section, ordered Male then Female then lname --
$students = []; // [student_id => ['id'=>...,'fname'=>'','lname'=>'','gender'=>'']]
$q = "SELECT id, fname, mname, lname, gender FROM students WHERE section_id = ? ORDER BY CASE WHEN gender IN ('Male','M','male','m') THEN 0 ELSE 1 END, lname, fname";
if ($stmt = $conn->prepare($q)) {
    $stmt->bind_param('i', $section_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $students[(int)$r['id']] = $r;
    $stmt->close();
}
if (empty($students)) {
    echo "<div class='alert alert-warning'>No students found for section {$section_name}.</div>";
    return;
}

// -- Fetch grades for the students in this section for the selected academic year & quarter --
$grades_rows = [];
$q = "SELECT g.student_id, g.subject_id, g.quarterly_grade AS grade
      FROM grades g
      JOIN students s ON g.student_id = s.id
      WHERE s.section_id = ? AND g.academic_year_id = ? AND g.quarter_id = ?";
if ($stmt = $conn->prepare($q)) {
    $stmt->bind_param('iii', $section_id, $academic_year_id, $quarter_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $grades_rows[] = $r;
    $stmt->close();
} else {
    // fallback: query without join if prepare fails
    $q2 = "SELECT student_id, subject_id, quarterly_grade AS grade FROM grades WHERE academic_year_id = {$academic_year_id} AND quarter_id = {$quarter_id}";
    if ($res = $conn->query($q2)) {
        while ($r = $res->fetch_assoc()) $grades_rows[] = $r;
    }
}

// Build matrix: matrix[student_id][subject_id] = grade
$matrix = [];
foreach ($grades_rows as $g) {
    $sid = (int)$g['student_id'];
    $subid = (int)$g['subject_id'];
    $matrix[$sid][$subid] = $g['grade'];
}

// Helpers
function compute_average($grades_row, $subjects) {
    $sum = 0.0; $count = 0;
    foreach ($subjects as $subid => $name) {
        if (isset($grades_row[$subid]) && $grades_row[$subid] !== '' && is_numeric($grades_row[$subid])) {
            $sum += (float)$grades_row[$subid];
            $count++;
        }
    }
    if ($count === 0) return null;
    // round to 2 decimal places
    return round($sum / $count, 2);
}

// Determine honors string from average (pass numeric average or null)
function getHonors($avg) {
    if ($avg === null) return '';
    // ensure numeric
    $avg = (float)$avg;
    if ($avg >= 98.0 && $avg <= 100.0) return "Highest Honor";
    if ($avg >= 95.0 && $avg <= 97.99) return "High Honor";
    if ($avg >= 90.0 && $avg <= 94.99) return "Honor";
    return "";
}


// Group students by gender for rendering order
$male_students = []; $female_students = [];
foreach ($students as $sid => $stu) {
    $g = strtolower($stu['gender'] ?? '');
    if ($g === 'male' || $g === 'm') $male_students[$sid] = $stu;
    else $female_students[$sid] = $stu;
}
$role = $_SESSION['role'] ?? '';
$full_name = $_SESSION['full_name'] ?? '';
$role_label = ucfirst($role);

$nav_file = "../../layoutnav/{$role}bar.php";
if (!file_exists($nav_file)) {
    $nav_file = "../../layoutnav/defaultbar.php"; // fallback
}

include $nav_file;
$header_color = ($role === 'masterteacher') ? '#1a1a1a' : '#44A344';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Expires" content="0" />
  <link rel="icon" type="image/png" href="../../assets/image/logo/logo.png" />
  <title>Report</title>
  <link rel="stylesheet" href="../../assets/css/sidebar.css">
  <link rel="stylesheet" href="../../assets/css/all_role_style/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
            /* small print-friendly tweaks */
    @media print {
        body * {
            visibility: hidden; /* hide everything */
        }
        .container-fluid.px-4.mt-3,
        .container-fluid.px-4.mt-3 * {
            visibility: visible; /* show only the report container */
        }
        .container-fluid.px-4.mt-3 {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        button {
            display: none !important;
        }
        }
    </style>
</head>
<body>

<div class="main-content">

  <!-- Sticky Header -->
  <div style="
    position: sticky;
    top: 0;
    z-index: 999;
    background-color: <?php echo $header_color; ?>;
    color: white;
    padding: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 70px;
  ">
    <h2 class="m-0" style="position: absolute; left: 50%; transform: translateX(-50%);">Student Report</h2>
    <?php if (!empty($full_name)): ?>
      <span style="position: absolute; right: 20px;">
        Hello <?php echo htmlspecialchars($role_label . ' ' . $full_name); ?>
      </span>
    <?php endif; ?>
  </div>


<div class="container-fluid px-4 mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="header-logo deped-seal">
            <img src="../../assets/image/logo/Department_of_Education.svg" alt="DepEd Seal" width="100" height="100">
        </div>
        <div class="text-center mb-2">
            <h4 class="fw-bold">Summary Quarterly Grades (<?= htmlspecialchars($quarter_name ?: '(First Quarter)') ?>)</h4>
        </div>
        <div class="header-logo deped-logo">
            <img src="../../assets/image/logo/Department_of_Education_(DepEd).svg" alt="DepEd Logo" width="100" height="100">
        </div>
    </div>
    <div class="table-responsive">
    <div class="row text-white fw-bold text-center mb-2" style="background-color: #0c7c3b;">
         <div class="col-md-2 border-end border-white py-2"> 
            <small>Region</small><br><strong><?php echo htmlspecialchars($school['region'] ?? ''); ?></strong>
        </div>
        <div class="col-md-3 border-end border-white py-2">
            <small>School Name</small><br><strong><?php echo htmlspecialchars($school['school_name'] ?? ''); ?></strong>
        </div>
        <div class="col-md-2 border-end border-white py-2">
            <small>School ID</small><br><strong><?php echo htmlspecialchars($school['school_id'] ?? ''); ?></strong>
        </div>
        <div class="col-md-3 border-end border-white py-2">
            <small>Division</small><br><strong><?php echo htmlspecialchars($school['division'] ?? ''); ?></strong>
        </div>
        <div class="col-md-2 border-end border-white py-2">
            <small>District</small><br><strong><?php echo htmlspecialchars($school['district'] ?? ''); ?></strong>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="table-light text-center">
                        <tr>
                            <th style="width:260px">Learners' Names</th>
                            <?php foreach ($subjects as $sid => $sname): ?>
                                <th><?php echo htmlspecialchars($sname); ?></th>
                            <?php endforeach; ?>
                            <th>Average</th>
                            <th>With Honors</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($male_students)): ?>
                            <tr class="table-secondary"><td colspan="<?php echo count($subjects) + 3; ?>"><strong>Male</strong></td></tr>
                            <?php foreach ($male_students as $sid => $stu): 
                                $rowGrades = $matrix[$sid] ?? [];
                                $avg = compute_average($matrix[$sid] ?? [], $subjects);
                                $avg_display = $avg !== null ? number_format($avg, 2) : '-';
                                $honors = getHonors($avg);
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($stu['lname'] . ', ' . $stu['fname'] . (trim($stu['mname'] ?? '') ? ' ' . $stu['mname'][0] . '.' : '')); ?></td>
                                    <?php foreach ($subjects as $subid => $sname): ?>
                                        <td class="text-center"><?php echo isset($rowGrades[$subid]) ? htmlspecialchars($rowGrades[$subid]) : ''; ?></td>
                                    <?php endforeach; ?>
                                    <td class="text-center"><?php echo ($avg === null ? '' : $avg); ?></td>
                                    <td class="text-center"><?php echo $honors; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (!empty($female_students)): ?>
                            <tr class="table-secondary"><td colspan="<?php echo count($subjects) + 3; ?>"><strong>Female</strong></td></tr>
                            <?php foreach ($female_students as $sid => $stu): 
                                $avg = compute_average($rowGrades, $subjects);
                                $avg_display = $avg !== null ? number_format($avg, 2) : '-';
                                $honors = getHonors($avg);
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($stu['lname'] . ', ' . $stu['fname'] . (trim($stu['mname'] ?? '') ? ' ' . $stu['mname'][0] . '.' : '')); ?></td>
                                    <?php foreach ($subjects as $subid => $sname): ?>
                                        <td class="text-center"><?php echo isset($rowGrades[$subid]) ? htmlspecialchars($rowGrades[$subid]) : ''; ?></td>
                                    <?php endforeach; ?>
                                    <td class="text-center"><?php echo $avg_display; ?></td>
                                    <td class="text-center"><?php echo $honors; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center gap-3 my-4">
              <button class="btn btn-success px-4 fw-bold">All Quarter</button>
              <button class="btn btn-success px-4 fw-bold">Archive</button>
              <button class="btn btn-success px-4 fw-bold" onclick="window.print()">Print Report</button>
            </div>

        </div>
    </div>
</div>
</div>

<style>
  thead th {
  position: sticky;
  top: 0;
  background: #198754; /* Bootstrap green */
  color: white;
  z-index: 1;
}
</style>
<script>
function printReport() {
    var printContents = document.querySelector('.container-fluid.px-4.mt-3').innerHTML;
    var originalContents = document.body.innerHTML;

    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
}
</script>

</body>
</html>