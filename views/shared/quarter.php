<?php
// Start session
session_start();

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Allow access to certain roles
$allowed_roles = ['adviser', 'masterteacher', 'principal', 'teacher'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../security/unauthorized.php");
    exit();
}

include '../../database/db_connection.php';

// Normalize incoming values
$quarter = $_GET['quarter'] ?? 'N/A';
$subject = $_GET['subject'] ?? 'N/A';

// Try to find the teacher's assignment by subject name
$assignment = [];
$sql = "SELECT g.id AS grade_level_id, g.grade_name, 
               s.id AS section_id, s.section_name,
               sub.id AS subject_id, sub.subject_name
        FROM subject_assignments sa
        JOIN grade_levels g ON sa.grade_level_id = g.id
        JOIN sections s ON sa.section_id = s.id
        JOIN subjects sub ON sa.subject_id = sub.id
        WHERE sub.subject_name = ? AND sa.teacher_id = ?";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("si", $subject, $_SESSION['user_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $assignment = $res ? $res->fetch_assoc() : [];
    $stmt->close();
} else {
    // Log errors for debugging
    error_log("SQL prepare failed (assignment by subject name): " . $conn->error);
}

// If no assignment found, try matching by subject_id (numeric subject)
if (empty($assignment) && ctype_digit((string)$subject)) {
    $subject_int = (int)$subject;
    $sql2 = "SELECT g.id AS grade_level_id, g.grade_name, 
                    s.id AS section_id, s.section_name,
                    sub.id AS subject_id, sub.subject_name
             FROM subject_assignments sa
             JOIN grade_levels g ON sa.grade_level_id = g.id
             JOIN sections s ON sa.section_id = s.id
             JOIN subjects sub ON sa.subject_id = sub.id
             WHERE sub.id = ? AND sa.teacher_id = ?";
    $stmt2 = $conn->prepare($sql2);
    if ($stmt2) {
        $stmt2->bind_param("ii", $subject_int, $_SESSION['user_id']);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        $assignment = $res2 ? $res2->fetch_assoc() : [];
        $stmt2->close();
    } else {
        error_log("SQL prepare failed (assignment by subject ID): " . $conn->error);
    }
}

// Safely extract assignment data, setting defaults
$grade = $assignment['grade_name'] ?? 'N/A';
$section = $assignment['section_name'] ?? 'N/A';
$grade_level_id = $assignment['grade_level_id'] ?? 0;
$section_id = $assignment['section_id'] ?? 0;
$subject_id = $assignment['subject_id'] ?? 0;
$subject = $assignment['subject_name'] ?? $subject; // Keep the subject from GET if not found

// Fetch students if valid grade_level_id and section_id
$students = [];
if ($grade_level_id > 0 && $section_id > 0) {
    $sql = "SELECT id, fname, lname 
            FROM students 
            WHERE grade_level_id = ? AND section_id = ?
            ORDER BY lname, fname";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $grade_level_id, $section_id);
        $stmt->execute();
        $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        error_log("SQL prepare failed (students): " . $conn->error);
    }
} else {
    // No valid assignment found — students array remains empty
}
define('PROD', false);

// 1. Current teacher
$context_teacher_id = $_SESSION['user_id'] ?? null;

// 2. Active academic year
$context_academic_year_id = null;
$res = $conn->query("SELECT id FROM academic_years WHERE status='active' LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    $context_academic_year_id = $row['id'];
}

// 3. Get quarter (you can choose active or default to 1st)
$context_quarter_id = $_GET['quarter_id'] ?? null;
if (!$context_quarter_id) {
    $res = $conn->query("SELECT id FROM quarters WHERE academic_year_id=".(int)$context_academic_year_id." ORDER BY id ASC LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $context_quarter_id = $row['id']; // default = 1st Quarter
    }
}

// 4. Get subject_id from subject_assignments (match teacher + section + academic year)
$context_subject_id = $_GET['subject_id'] ?? null;
if (!$context_subject_id && isset($section_id)) { // if you know section_id from UI
    $res = $conn->query("
        SELECT subject_id 
        FROM subject_assignments 
        WHERE teacher_id=".(int)$context_teacher_id."
          AND section_id=".(int)$section_id."
          AND academic_year_id=".(int)$context_academic_year_id."
        LIMIT 1
    ");
    if ($res && $row = $res->fetch_assoc()) {
        $context_subject_id = $row['subject_id'];
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($quarter) ?> Quarter <?= htmlspecialchars($subject) ?>  </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../assets/image/logo/logo.png" />
    <link rel="stylesheet" href="../teacher/teacherCss/quarterDesign.css">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/all_role_style/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        input.ww-total,
        input.ww-ps,
        input.ww-ws,
        input.pt-total,
        input.pt-ps,
        input.pt-ws,
        input.qa-ps,
        input.qa-ws,
        input.final-grade,
        input.quarterly-grade {
          width: 85px !important;
          text-align: center;
          }

        input.ww-max,
        input.pt-max,
        input.qa-score,
        input.ww-score,
        input.pt-score {
          min-width: 55px; 
          max-width: 70px;
          text-align: center;
        }


      .percent-symbol {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        font-weight: bold;
        color: #555;
      }
      td {
        position: relative;
      }
      input[type="number"]::-webkit-outer-spin-button,
      input[type="number"]::-webkit-inner-spin-button {
      -webkit-appearance: none;
      margin: 0;
      }

      /* Remove spinners in Firefox */
      input[type="number"] {
        -moz-appearance: textfield;
      }
  </style>
</head>
<body>


<!-- Navigation Bar -->
<?php
  $role = $_SESSION['role'];
  $full_name = $_SESSION['full_name'] ?? '';
  $role_label = ucfirst($role);

  $nav_file = "../../layoutnav/{$role}bar.php";
  if (!file_exists($nav_file)) {
      $nav_file = "../../layoutnav/defaultbar.php";
  }
  include $nav_file;
?>


<div class="main-content">
    <!-- Header -->
    <div style="background-color: #44A344; color: white; padding: 20px; display: flex; justify-content: center; align-items: center; position: relative; min-height: 70px;">
        <h2 class="m-0" style="position: absolute; left: 50%; transform: translateX(-50%);">Quarter</h2>
        <?php if (isset($_SESSION['full_name'])): ?>
            <span style="position: absolute; right: 20px;">
                Hello <?= htmlspecialchars($role_label . ' ' . $full_name) ?>
            </span>
        <?php endif; ?>
    </div>



    <?php
    // Kumuha ng school info kasama ang active academic year
    $sql = "SELECT si.*, ay.year_start, ay.year_end 
            FROM school_info si
            LEFT JOIN academic_years ay ON ay.status = 'active'";

    $res = mysqli_query($conn, $sql);
    $school = mysqli_fetch_assoc($res);

    // Format ng school year
    $school_year = isset($school['year_start']) ? $school['year_start'] . ' - ' . $school['year_end'] : '';
    ?>

    <div class="container-fluid p-3">
      <div class="d-flex justify-content-between mb-3">
            <button type="button" class="btn btn-danger"
                onclick="if (history.length > 1) { history.back(); } else { window.location.href='section.php'; }">
                Back
            </button>
        </div>
      <div class="header-top d-flex justify-content-between align-items-center">
          <div class="header-logo deped-seal">
            <img src="../../assets/image/logo/Department_of_Education.svg" alt="DepEd Seal" width="100" height="100">
          </div>
          <div class="header-title text-center flex-grow-1">
              <h1 class="mb-0">Class Record</h1>
              <p class="mb-0 sub-title">
                  <small>(Pursuant to DepEd Order 8, series of 2015)</small>
              </p>
          </div>
          <div class="header-logo deped-logo">
              <img src="../../assets/image/logo/Department_of_Education_(DepEd).svg" alt="DepEd Logo" width="100" height="100">
          </div>
      </div>
    
        <div class="header-info row g-2">
            <div class="col-md-6 col-lg-3">
                <label class="form-label-custom">REGION</label>
                <input type="text" class="form-control form-control-custom" value="<?php echo isset($school['region']) ? $school['region'] : ''; ?>" readonly>
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label-custom">DIVISION</label>
                <input type="text" class="form-control form-control-custom" value="<?php echo isset($school['division']) ? $school['division'] : ''; ?>" readonly>
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label-custom">DISTRICT</label>
                <input type="text" class="form-control form-control-custom" value="<?php echo isset($school['district']) ? $school['district'] : ''; ?>" readonly>
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label-custom">SCHOOL YEAR</label>
                <input type="text" class="form-control form-control-custom" value="<?php echo $school_year; ?>" readonly>
            </div>
            <div class="col-md-6 col-lg-6 mt-3">
                <label class="form-label-custom">SCHOOL NAME</label>
                <input type="text" class="form-control form-control-custom" value="<?php echo isset($school['school_name']) ? $school['school_name'] : ''; ?>" readonly>
            </div>
            <div class="col-md-6 col-lg-3 mt-3">
                <label class="form-label-custom">SCHOOL ID</label>
                <input type="text" class="form-control form-control-custom" value="<?php echo isset($school['school_id']) ? $school['school_id'] : ''; ?>" readonly>
            </div>
        </div>

        <!-- Quarter Info -->
        <div class="row g-2 mb-3">
          <?php
          
          $quarter = $_GET['quarter'] ?? 'N/A';
          $subject = $_GET['subject'] ?? 'N/A';

          $sql = "SELECT g.grade_name, s.section_name
                  FROM subject_assignments sa
                  JOIN grade_levels g ON sa.grade_level_id = g.id
                  JOIN sections s ON sa.section_id = s.id
                  JOIN subjects sub ON sa.subject_id = sub.id
                  WHERE sub.subject_name = ?";
          $stmt = $conn->prepare($sql);
          $stmt->bind_param("s", $subject);
          $stmt->execute();
          $stmt->bind_result($grade, $section);
          $stmt->fetch();
          $stmt->close();
          ?>

          <div class="col-md-3">
              <label class="form-label-custom"><?= strtoupper($quarter) ?> QUARTER</label>
          </div>
          <div class="col-md-3">
              <label class="form-label-custom"><?= strtoupper($grade) ?> - <?= strtoupper($section) ?></label>
          </div>
          <div class="col-md-3">
              <label class="form-label-custom">TEACHER: <?= strtoupper($full_name) ?></label>
          </div>
          <div class="col-md-3">
              <label class="form-label-custom">SUBJECT: <?= strtoupper($subject) ?></label>
          </div>
          <div class="col-md-3">
            <button id="togglePercentBtn" type="button" class="btn btn-outline-primary btn-sm">
                Edit Grading Percentage
            </button>
          </div>
        </div>

        <!-- Table + Form -->
        <form id="gradesForm" method="post" action="/tapinac/views/shared/multirole_backend/save_grading.php">
          <input type="hidden" id="context_quarter_id" value="<?= (int)$context_quarter_id ?>">
          <input type="hidden" id="context_section_id" value="<?= (int)$section_id ?>">
          <input type="hidden" id="context_subject_id" value="<?= (int)$context_subject_id ?>">
          <input type="hidden" id="context_teacher_id" value="<?= (int)$context_teacher_id ?>">
          <input type="hidden" name="context[subject_id]" value="<?= (int)$context_subject_id ?>">
          <input type="hidden" name="context[quarter_id]" value="<?= (int)$context_quarter_id ?>">
          <input type="hidden" name="context[academic_year_id]" value="<?= (int)$context_academic_year_id ?>">
          <input type="hidden" name="context[teacher_id]" value="<?= (int)$context_teacher_id ?>">
          <div style="overflow-x: auto;">
            <table class="table table-bordered table-sm table-condensed text-center align-middle">
                <thead class="bg-lightblue text-center align-middle">
                    <tr>
                        <th rowspan="3">Learner's Name</th>
                        <th colspan="13">
                            Written Works (
                            <input list="percent-list" type="number" class="ww-percent" name="ww_percent"
                                  value="<?= $ww_percent ?>" min="0" max="80" style="width: 50px;"> %)
                        </th>
                        <th colspan="13">
                            Performance Tasks (
                            <input list="percent-list" type="number" class="pt-percent" name="pt_percent"
                                  value="<?= $pt_percent ?>" min="0" max="80" style="width: 50px;"> %)
                        </th>
                        <th colspan="3">
                            Quarterly Assessment (
                            <input type="number" class="qa-percent" name="qa_percent"
                                  value="20" readonly style="width: 50px;"> %)
                        </th>
                        <th rowspan="3">Initial Grade</th>
                        <th rowspan="3">Quarterly Grade</th>
                    </tr>
                    <tr>
                        <!-- WW -->
                        <?php for ($i = 1; $i <= 10; $i++): ?><th><?= $i ?></th><?php endfor; ?>
                        <th>Total</th><th>PS</th><th>WS</th>
                        <!-- PT -->
                        <?php for ($i = 1; $i <= 10; $i++): ?><th><?= $i ?></th><?php endfor; ?>
                        <th>Total</th><th>PS</th><th>WS</th>
                        <!-- QA -->
                        <th>1</th><th>PS</th><th>WS</th>
                    </tr>
                </thead>

                <!-- Highest Possible Score Row -->
                <tbody>
                    <tr class="highest-row">
                        <td>Highest Possible Score</td>
                        <?php for ($i = 0; $i < 10; $i++): ?><td><input type="number" class="form-control ww-max" max="100"></td><?php endfor; ?>
                        <td><input type="number" class="form-control ww-total" readonly></td>
                        <td><input type="number" class="form-control ww-ps" readonly></td>
                        <td><input type="number" class="form-control ww-ws" readonly></td>

                        <?php for ($i = 0; $i < 10; $i++): ?><td><input type="number" class="form-control pt-max" max="100"></td><?php endfor; ?>
                        <td><input type="number" class="form-control pt-total" readonly></td>
                        <td><input type="number" class="form-control pt-ps" readonly></td>
                        <td><input type="number" class="form-control pt-ws" readonly></td>

                        <td><input type="number" class="form-control qa-score" max="100"></td>
                        <td><input type="number" class="form-control qa-ps" readonly></td>
                        <td><input type="text" class="form-control qa-ws" readonly></td>
                        <td></td>
                        <td></td>
                    </tr>

                    <?php
                    $context_subject_id = isset($subject_id) ? (int)$subject_id
                                    : (isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0);

                    $context_quarter_id = isset($quarter_id) ? (int)$quarter_id
                                      : (isset($_GET['quarter_id']) ? (int)$_GET['quarter_id'] : 0);

                    $context_academic_year_id = isset($academic_year_id) ? (int)$academic_year_id
                                                  : (isset($_GET['academic_year_id']) ? (int)$_GET['academic_year_id'] : 0);
                    $context_teacher_id = $_SESSION['teacher_id'] ?? 0;
                    if (empty($context_teacher_id) && !empty($_SESSION['user_id'])) {
                        $tmpStmt = $conn->prepare("SELECT teacher_id FROM users WHERE id = ? LIMIT 1");
                        $tmpStmt->bind_param("i", $_SESSION['user_id']);
                        $tmpStmt->execute();
                        $tmpRes = $tmpStmt->get_result()->fetch_assoc();
                        $context_teacher_id = (int)($tmpRes['teacher_id'] ?? 0);
                        $tmpStmt->close();
                    }

                    if (!defined('PROD') || PROD !== true) {
                      echo "<!-- CONTEXT: subject={$context_subject_id}, quarter={$context_quarter_id}, acad_year={$context_academic_year_id}, teacher={$context_teacher_id} -->\n";
                    }
                    ?>

                    <!-- Student Rows -->
                    <?php if (!empty($students)): ?>
                        <?php foreach ($students as $stud): ?>
                        <tr>
                            <td><?= htmlspecialchars($stud['lname'] . ', ' . $stud['fname']) ?></td>

                            <!-- WW -->
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <td>
                                    <input type="number" 
                                          name="grades[<?= $stud['id'] ?>][scores][ww<?= $i ?>]" 
                                          class="form-control ww-score" max="100">
                                </td>
                            <?php endfor; ?>
                            <td><input type="number" name="grades[<?= $stud['id'] ?>][scores][ww_total]" class="form-control ww-total" readonly></td>
                            <td><input type="number" name="grades[<?= $stud['id'] ?>][scores][ww_ps]" class="form-control ww-ps" readonly></td>
                            <td><input type="number" name="grades[<?= $stud['id'] ?>][scores][ww_ws]" class="form-control ww-ws" readonly></td>

                            <!-- PT -->
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <td>
                                    <input type="number" 
                                          name="grades[<?= $stud['id'] ?>][scores][pt<?= $i ?>]" 
                                          class="form-control pt-score" max="100">
                                </td>
                            <?php endfor; ?>
                            <td><input type="number" name="grades[<?= $stud['id'] ?>][scores][pt_total]" class="form-control pt-total" readonly></td>
                            <td><input type="number" name="grades[<?= $stud['id'] ?>][scores][pt_ps]" class="form-control pt-ps" readonly></td>
                            <td><input type="number" name="grades[<?= $stud['id'] ?>][scores][pt_ws]" class="form-control pt-ws" readonly></td>

                            <!-- QA -->
                            <td><input type="number" name="grades[<?= $stud['id'] ?>][scores][qa]" class="form-control qa-score" max="100"></td>
                            <td><input type="number" name="grades[<?= $stud['id'] ?>][scores][qa_ps]" class="form-control qa-ps" readonly></td>
                            <td><input type="number" step="0.01" name="grades[<?= $stud['id'] ?>][scores][qa_ws]" class="form-control qa-ws" readonly></td>

                            <!-- Final Grade -->
                            <td><input type="number" name="grades[<?= $stud['id'] ?>][scores][final_grade]" class="form-control final-grade" readonly></td>

                            <!-- Quarterly Grade -->
                            <td><input type="number" name="grades[<?= $stud['id'] ?>][scores][quarterly_grade]" class="form-control quarterly-grade"></td>

                            <!-- Hidden context fields -->  
                            <input type="hidden" name="grades[<?= $stud['id'] ?>][context][subject_id]" value="<?= (int)$context_subject_id ?>">
                            <input type="hidden" name="grades[<?= $stud['id'] ?>][context][quarter_id]" value="<?= (int)$context_quarter_id ?>">
                            <input type="hidden" name="grades[<?= $stud['id'] ?>][context][academic_year_id]" value="<?= (int)$context_academic_year_id ?>">
                            <input type="hidden" name="grades[<?= $stud['id'] ?>][context][teacher_id]" value="<?= (int)$context_teacher_id ?>">
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="40">No students found for this section.</td>
                            </tr>
                        <?php endif; ?>
                </tbody>
            </table>

            <!-- Dropdown options 1–100 -->
            <datalist id="percent-list">
                <?php for ($i = 1; $i <= 100; $i++): ?>
                    <option value="<?= $i ?>"></option>
                <?php endfor; ?>
            </datalist>
                      </div>
                      <!-- Save Button -->
                      <div style="margin-top: 15px; text-align: right;">
                        <button type="submit" class="btn btn-primary">Save Grades</button>
                      </div>
                      <div style="margin-top: 15px; text-align: right;">
                        <button type="submit" class="btn btn-secondary">Pass Grades</button>
                      </div>
          </form>
        </div>
      </div>
      <!-- Clear Button -->
      <div class="text-end my-3">
          <button id="clearStorageBtn" class="btn btn-outline-danger btn-sm">
              <i class="bi bi-trash3-fill me-1"></i> Clear Saved Data
          </button>
      </div>
    </div>
  </div>



<!--  STUDENT GRADE CALCULATION & TRANSMUTATION -->
<script>

document.getElementById('gradesForm').addEventListener('submit', function(e) {
  e.preventDefault();

  fetch("/tapinac/views/shared/multirole_backend/save_grading.php", {
    method: "POST",
    body: new FormData(this)
  })

  .then(res => res.json())
  .then(data => {
    if (data.ok) {
      let msg = `Saved: ${data.saved.length} students. Failed: ${data.failed.length}`;
      if (data.failed.length) {
        msg += "\n\nFailures:\n" + data.failed.map(f => `Student ${f.student}: ${f.reason}`).join("\n");
      }
      alert(msg);
    } else {
      alert("Error: " + (data.error || JSON.stringify(data)));
    }
  })
  .catch(err => {
    alert("Request failed: " + err);
  });
});




  const transmutationTable = [
    { min: 98.40, max: 100.00, grade: 99 },
    { min: 96.80, max: 98.39, grade: 98 },
    { min: 95.20, max: 96.79, grade: 97 },
    { min: 93.60, max: 95.19, grade: 96 },
    { min: 92.00, max: 93.59, grade: 95 },
    { min: 90.40, max: 91.99, grade: 94 },
    { min: 88.80, max: 90.39, grade: 93 },
    { min: 87.20, max: 88.79, grade: 92 },
    { min: 85.60, max: 87.19, grade: 91 },
    { min: 84.00, max: 85.59, grade: 90 },
    { min: 82.40, max: 83.99, grade: 89 },
    { min: 80.80, max: 82.39, grade: 88 },
    { min: 79.20, max: 80.79, grade: 87 },
    { min: 77.60, max: 79.19, grade: 86 },
    { min: 76.00, max: 77.59, grade: 85 },
    { min: 74.40, max: 75.99, grade: 84 },
    { min: 72.80, max: 74.39, grade: 83 },
    { min: 71.20, max: 72.79, grade: 82 },
    { min: 69.60, max: 71.19, grade: 81 },
    { min: 68.00, max: 69.59, grade: 80 },
    { min: 66.40, max: 67.99, grade: 79 },
    { min: 64.80, max: 66.39, grade: 78 },
    { min: 63.20, max: 64.79, grade: 77 },
    { min: 61.60, max: 63.19, grade: 76 },
    { min: 60.00, max: 61.59, grade: 75 },
    { min: 56.00, max: 59.99, grade: 74 },
    { min: 52.00, max: 55.99, grade: 73 },
    { min: 48.00, max: 51.99, grade: 72 },
    { min: 44.00, max: 47.99, grade: 71 },
    { min: 40.00, max: 43.99, grade: 70 },
    { min: 36.00, max: 39.99, grade: 69 },
    { min: 32.00, max: 35.99, grade: 68 },
    { min: 28.00, max: 31.99, grade: 67 },
    { min: 24.00, max: 27.99, grade: 66 },
    { min: 20.00, max: 23.99, grade: 65 },
    { min: 16.00, max: 19.99, grade: 64 },
    { min: 12.00, max: 15.99, grade: 63 },
    { min: 8.00, max: 11.99, grade: 62 },
    { min: 4.00, max: 7.99, grade: 61 },
    { min: 0.00, max: 3.99, grade: 60 },
  ];

  // 🛠 Utility Functions
  function getValues(row, selector) {
    return [...row.querySelectorAll(selector)].map(input => parseFloat(input.value) || 0);
  }

  function setValue(row, selector, value, fixed = 2) {
    const el = row.querySelector(selector);
    if (el) el.value = value.toFixed(fixed);
  }

  function calculatePS(sum, max) {
    return (sum / (max || 1)) * 100;
  }

  function calculateWS(ps, weight) {
    return (ps * weight) / 100;
  }

  function getTransmutedGrade(initial) {
    for (const { min, max, grade } of transmutationTable) {
      if (initial >= min && initial <= max) return grade;
    }
    return "";
  }

  const wwInput = document.querySelector(".ww-percent");
  const ptInput = document.querySelector(".pt-percent");
  const qaInput = document.querySelector(".qa-percent");

  let wwLocked = false;
  let ptLocked = false;
  let qaLocked = false;

  function round5(value) {
    return Math.round(value / 5) * 5;
  }

  function rebalance() {
    let ww = parseInt(wwInput.value) || 0;
    let pt = parseInt(ptInput.value) || 0;
    let qa = parseInt(qaInput.value) || 0;

    const total = ww + pt + qa;

    if (total !== 100) {
      if (!wwLocked && ptLocked && qaLocked) {
        ww = 100 - pt - qa;
        wwInput.value = round5(ww);
      } else if (wwLocked && !ptLocked && qaLocked) {
        pt = 100 - ww - qa;
        ptInput.value = round5(pt);
      } else if (wwLocked && ptLocked && !qaLocked) {
        qa = 100 - ww - pt;
        qaInput.value = round5(qa);
      } else if (!wwLocked && !ptLocked && qaLocked) {
        ww = round5((100 - qa) * 0.4);
        pt = 100 - ww - qa;
        wwInput.value = ww;
        ptInput.value = pt;
      } else if (!wwLocked && ptLocked && !qaLocked) {
        ww = round5((100 - pt) * 0.5);
        qa = 100 - ww - pt;
        wwInput.value = ww;
        qaInput.value = qa;
      } else if (wwLocked && !ptLocked && !qaLocked) {
        pt = round5((100 - ww) * 0.6);
        qa = 100 - ww - pt;
        ptInput.value = pt;
        qaInput.value = qa;
      }
    }

    document.querySelectorAll("tbody tr").forEach(computeRow);
    computeHighestRow();
  }

  wwInput.addEventListener("blur", () => { wwLocked = true; rebalance(); });
  ptInput.addEventListener("blur", () => { ptLocked = true; rebalance(); });
  qaInput.addEventListener("blur", () => { qaLocked = true; rebalance(); });

  [wwInput, ptInput, qaInput].forEach(input =>
    input.addEventListener("input", rebalance)
  );

  function computeHighestRow() {
    const row = document.querySelector(".highest-row");

    ['ww', 'pt'].forEach(cat => {
      const scores = getValues(row, `.${cat}-max`);
      const sum = scores.slice(0, 10).reduce((a, b) => a + b, 0);
      const weight = parseFloat(document.querySelector(`.${cat}-percent`).value) || 0;
      setValue(row, `.${cat}-total`, sum, 0);
      setValue(row, `.${cat}-ps`, 100, 0);
      setValue(row, `.${cat}-ws`, weight);
    });

    const weight = parseFloat(qaInput.value) || 0;
    setValue(row, ".qa-ps", 100, 0);
    setValue(row, ".qa-ws", weight);
  }

  function computeRow(row) {
    if (row.classList.contains("highest-row")) return;

    const highestRow = document.querySelector(".highest-row");
    const weights = {
      ww: parseFloat(wwInput.value) || 0,
      pt: parseFloat(ptInput.value) || 0,
      qa: parseFloat(qaInput.value) || 0,
    };

    ['ww', 'pt'].forEach(cat => {
      const maxValues = getValues(highestRow, `.${cat}-max`);
      const scoreInputs = row.querySelectorAll(`.${cat}-score`);

      scoreInputs.forEach((input, index) => {
        const max = maxValues[index] || 0;
        let val = parseFloat(input.value) || 0;
        if (val > max) {
          input.value = max; // clamp
          val = max;
        }
      });

      const scores = getValues(row, `.${cat}-score`);
      const sum = scores.reduce((a, b) => a + b, 0);
      const maxTotal = parseFloat(highestRow.querySelector(`.${cat}-total`).value) || 1;
      const ps = calculatePS(sum, maxTotal);
      const ws = calculateWS(ps, weights[cat]);

      setValue(row, `.${cat}-total`, sum, 0);
      setValue(row, `.${cat}-ps`, ps, 0);
      setValue(row, `.${cat}-ws`, ws);
    });

    // QA clamping
    const qaInputEl = row.querySelector(".qa-score");
    const qaMax = parseFloat(highestRow.querySelector(".qa-score").value) || 1;
    let qaScore = parseFloat(qaInputEl.value) || 0;
    if (qaScore > qaMax) {
      qaScore = qaMax;
      qaInputEl.value = qaScore;
    }

    const qaPS = calculatePS(qaScore, qaMax);
    const qaWS = calculateWS(qaPS, weights.qa);

    setValue(row, ".qa-ps", qaPS, 0);
    setValue(row, ".qa-ws", qaWS);

    const final =
      parseFloat(row.querySelector(".ww-ws").value) +
      parseFloat(row.querySelector(".pt-ws").value) +
      parseFloat(row.querySelector(".qa-ws").value);

    row.querySelector(".final-grade").value = final.toFixed(2);
    row.querySelector(".quarterly-grade").value = getTransmutedGrade(final);
  }

  document.addEventListener("DOMContentLoaded", () => {
    loadFromLocalStorage();
    computeHighestRow();

    document.querySelectorAll(".highest-row input").forEach(input => {
      input.addEventListener("input", () => {
        computeHighestRow();
        document.querySelectorAll("tbody tr").forEach(computeRow);
      });
    });

    document.querySelectorAll("tbody tr").forEach(row => {
      if (!row.classList.contains("highest-row")) {
        row.querySelectorAll("input").forEach(input =>
          input.addEventListener("input", () => {
            computeRow(row);
            saveToLocalStorage();
          })
        );
        computeRow(row);
      }
    });
  });

  function saveToLocalStorage() {
    const inputs = document.querySelectorAll("input");
    inputs.forEach((input, index) => {
      localStorage.setItem(`grade-input-${index}`, input.value);
    });
  }

  function loadFromLocalStorage() {
    const inputs = document.querySelectorAll("input");
    inputs.forEach((input, index) => {
      const saved = localStorage.getItem(`grade-input-${index}`);
      if (saved !== null) input.value = saved;
    });
  }

  document.getElementById("clearStorageBtn").addEventListener("click", () => {
    if (confirm("Are you sure you want to clear all saved data?!!")) {
      localStorage.clear();
      location.reload();
    }
  });

let isEditing = false;
const btn = document.getElementById("togglePercentBtn");

btn.addEventListener("click", function() {
  const ww = document.querySelector(".ww-percent");
  const pt = document.querySelector(".pt-percent");
  const qa = document.querySelector(".qa-percent");

  if (!isEditing) {
    // Enable editing
    ww.removeAttribute("readonly");
    pt.removeAttribute("readonly");
    qa.removeAttribute("readonly");
    btn.textContent = "Save Percentage";
    isEditing = true;
  } else {
    // Validate total = 100
    const total = parseInt(ww.value || 0) + parseInt(pt.value || 0) + parseInt(qa.value || 0);
    if (total < 100) { alert("Component percentage didn't reach 100%"); return; }
    if (total > 100) { alert("Component percentage overreach 100%"); return; }

    // Save to backend
    const formData = new FormData();
formData.append("academic_year_id", document.getElementById("context_academic_year_id").value);
formData.append("quarter_id", document.getElementById("context_quarter_id").value);
formData.append("section_id", document.getElementById("context_section_id").value);
formData.append("subject_id", document.getElementById("context_subject_id").value);
formData.append("teacher_id", document.getElementById("context_teacher_id").value);

    fetch("/tapinac/views/shared/multirole_backend/save_percentages.php", {
      method: "POST",
      body: formData
    })
    .then(res => res.text())
    .then(msg => alert(msg));

    // Lock again
    ww.setAttribute("readonly", true);
    pt.setAttribute("readonly", true);
    qa.setAttribute("readonly", true);
    btn.textContent = "Edit Grading Percentage";
    isEditing = false;
  }
});

</script>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcSanrsyFwI4zQFRO+LzY2K3pL" crossorigin="anonymous"></script>
<script>
  // Force reload when user clicks back
  window.addEventListener("pageshow", function (event) {
    if (event.persisted) {
      window.location.reload();
    }
  });
const ww = document.querySelector(".ww-percent");
const pt = document.querySelector(".pt-percent");
const qa = document.querySelector(".qa-percent");

// QA fixed at 20
qa.value = 20;

// When WW changes → adjust PT
ww.addEventListener("input", function () {
  let wwVal = parseInt(ww.value) || 0;
  if (wwVal > 80) wwVal = 80; // max 80
  ww.value = wwVal;
  pt.value = 80 - wwVal; // PT auto adjusts
});

// When PT changes → adjust WW
pt.addEventListener("input", function () {
  let ptVal = parseInt(pt.value) || 0;
  if (ptVal > 80) ptVal = 80; // max 80
  pt.value = ptVal;
  ww.value = 80 - ptVal; // WW auto adjusts
});
  wwInput.value = 50;
  ptInput.value = 30;
  qaInput.value = fixedQA;
</script>

</body>
</html>
