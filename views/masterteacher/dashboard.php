<?php
session_start();

// Force page to expire so browser doesn't cache it
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Redirect if user is not an admin
if ($_SESSION['role'] !== 'masterteacher') {
    header("Location: ../security/unauthorized.php"); 
    exit();
}


// Database connection
try {
    $pdo = new PDO('mysql:host=localhost;dbname=grading_system', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Count total subjects
$totalSubjects = 0;
$stmt = $pdo->query("SELECT COUNT(*) FROM subjects");
if ($stmt) {
    $totalSubjects = $stmt->fetchColumn();
}


include '../../database/db_connection.php';
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
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/all_role_style/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <title>Dashboard</title>
</head>
<body>


<!-- Navigation Bar -->
<?php include '../../layoutnav/masterteacherbar.php'; ?>

<div class="main-content">
    <!-- Header -->
    <div style="background-color: #1a1a1a; color: white; padding: 20px 20px; display: flex; justify-content: center; align-items: center; position: relative; min-height: 70px;">
        <h2 class="m-0" style="position: absolute; left: 45%; transform: translateX(-50%);">Dashboard</h2>
        <?php if (isset($_SESSION['full_name'])): ?>
            <span style="position: absolute; right: 20px;">
                Hello Masterteacher <?php echo htmlspecialchars($_SESSION['full_name']); ?>
            </span>
        <?php endif; ?>
    </div>

    <!-- Main Container -->
    <div class="container-fluid py-5 px-3 px-md-5" style="background-color: rgb(212, 212, 212); min-height: calc(100vh - 70px);">
        <div class="container">
          <div class="row gx-4 gy-4">

            <!-- Action Buttons -->
            <div class="row mb-3">
                <div class="col-auto">
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#assignAdviserModal">
                        <i class="bi bi-plus-circle me-2"></i> Assign Adviser
                    </button>
                </div>
                <div class="col-auto">
                    <button class="btn" style="background-color: #d11525ff; color: white;" data-bs-toggle="modal" data-bs-target="#removeAdviserModal">
                        <i class="bi bi-trash me-2"></i> Remove Assigned Adviser
                    </button>
                </div>
            </div>
            <!-- Statistic Cards: Total Students & Subjects -->
            <div class="row mb-5" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                <div>
                    <div class="card text-white text-center" style="background-color: #3b6ef5;">
                        <div class="card-body p-4">
                            <h6 class="card-title">Total Student</h6>
                            <p class="card-text fs-5">0</p>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="card text-dark text-center" style="background-color: #ffde59;">
                        <div class="card-body p-4">
                            <h6 class="card-title">Total Subject</h6>
                            <p class="card-text fs-5"><?= $totalSubjects ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistic Cards: Total Pass & Fail -->
            <div class="row" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                <div>
                    <div class="card text-white text-center" style="background-color: #29a329;">
                        <div class="card-body p-4">
                            <h6 class="card-title">Total Pass</h6>
                            <p class="card-text fs-5">0</p>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="card text-white text-center" style="background-color: #e63946;">
                        <div class="card-body p-4">
                            <h6 class="card-title">Total Fail</h6>
                            <p class="card-text fs-5">0</p>
                        </div>
                    </div>
                </div>
            </div>
          </div>
        </div>
    </div>
    <!-- Assign Adviser Modal (replace your modal with this) -->
    <div class="modal fade" id="assignAdviserModal" tabindex="-1" aria-labelledby="assignAdviserModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background-color: #f8f9fa;">
            <div class="modal-header border-0 position-relative">
                <h5 class="modal-title w-100 text-center" id="assignAdviserModalLabel"><strong>ASSIGN / RE-ASSIGN ADVISER</strong></h5>
                <button type="button" class="btn-close position-absolute end-0 me-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="assignAdviserForm" action="assign_adviser.php" method="POST">
                <input type="hidden" name="action" id="formAction" value="assign">
                <div class="modal-body text-center">

                <!-- Mode -->
                <div class="mb-3">
                    <div class="btn-group" role="group" aria-label="mode">
                    <input type="radio" class="btn-check" name="mode" id="modeAssign" value="assign" autocomplete="off" checked>
                    <label class="btn btn-outline-success" for="modeAssign">Assign</label>

                    <input type="radio" class="btn-check" name="mode" id="modeReassign" value="reassign" autocomplete="off">
                    <label class="btn btn-outline-warning" for="modeReassign">Re-Assign</label>
                    </div>
                </div>

                <!-- Adviser -->
                <div class="mb-3">
                    <select name="adviser" id="adviserSelect" class="form-control" required>
                    <option value="" disabled selected>Select Adviser</option>
                    <?php
                        $res = mysqli_query($conn, "SELECT * FROM teachers ORDER BY lname, fname");
                        while ($row = mysqli_fetch_assoc($res)) {
                            // If you have a role column you can filter here: WHERE role='adviser'
                            echo "<option value='" . (int)$row['id'] . "'>" .
                                htmlspecialchars($row['lname'] . ', ' . $row['fname'] . (empty($row['mname']) ? '' : ' ' . $row['mname'])) .
                                "</option>";
                        }
                    ?>
                    </select>
                </div>

                <!-- Grade Level (hidden until adviser chosen) -->
                <div class="mb-3" id="gradeWrapper" style="display:none;">
                    <select id="gradeLevelDropdown" name="grade_level" class="form-control" required>
                    <option value="" disabled selected>Select Grade Level</option>
                    <?php
                        $res = mysqli_query($conn, "SELECT * FROM grade_levels ORDER BY id");
                        while ($row = mysqli_fetch_assoc($res)) {
                            echo "<option value='" . (int)$row['id'] . "'>" . htmlspecialchars($row['grade_name']) . "</option>";
                        }
                    ?>
                    </select>
                </div>

                <!-- Section (populated after grade selected) -->
                <div class="mb-3" id="sectionWrapper" style="display:none;">
                    <select id="sectionDropdown" name="section" class="form-control" required>
                    <option value="" disabled selected>Select Section</option>
                    </select>
                </div>

                <!-- Academic Year -->
                <div class="mb-3">
                    <select name="academic_year" id="academicYear" class="form-control" required>
                    <option value="" disabled selected>Select Academic Year</option>
                    <?php
                        $res = mysqli_query($conn, "SELECT * FROM academic_years ORDER BY id DESC");
                        while ($row = mysqli_fetch_assoc($res)) {
                            echo "<option value='" . (int)$row['id'] . "'>" . htmlspecialchars($row['year_start'] . ' - ' . $row['year_end']) . "</option>";
                        }
                    ?>
                    </select>
                </div>

                <!-- Optional Subject -->
                <div class="mb-3" id="subjectWrapper" style="display:none;">
                    <select name="subject" id="subjectDropdown" class="form-control">
                    <option value="">(Optional) Assign Subject</option>
                    <!-- populated by JS -->
                    </select>
                </div>

                </div>

                <div class="modal-footer justify-content-center border-0">
                <button type="submit" id="confirmAssignBtn" class="btn btn-primary px-5">Confirm</button>
                </div>
            </form>
            </div>
        </div>
    </div>
    
    <!-- Remove Adviser Modal -->
    <div class="modal fade" id="removeAdviserModal" tabindex="-1" aria-labelledby="removeAdviserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="removeAdviserForm" method="POST" action="remove_adviser.php">
            <div class="modal-content">
                <div class="modal-header" style="background-color:#d9302e; color:white;">
                <h5 class="modal-title" id="removeAdviserModalLabel">Remove Adviser</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                <!-- Adviser dropdown (only currently-assigned advisers) -->
                <div class="mb-3">
                    <label for="removeAdviserSelect" class="form-label">Select Adviser</label>
                    <select id="removeAdviserSelect" name="adviser_id" class="form-control" required>
                    <option value="" selected disabled>-- Choose Adviser --</option>
                    <?php
                    // Populate only teachers who are currently assigned to a section
                    $res = mysqli_query($conn, "
                        SELECT DISTINCT t.id, t.fname, t.mname, t.lname
                        FROM teachers t
                        JOIN sections s ON s.teacher_id = t.id
                        ORDER BY t.lname, t.fname
                    ");
                    while ($row = mysqli_fetch_assoc($res)) {
                        $name = htmlspecialchars($row['lname'] . ', ' . $row['fname'] . (empty($row['mname']) ? '' : ' ' . $row['mname']));
                        echo "<option value=\"" . (int)$row['id'] . "\">{$name}</option>";
                    }
                    ?>
                    </select>
                </div>

                <!-- Auto-filled details -->
                <div id="removeAdviserDetails" style="display:none;">
                    <div class="mb-2"><strong>Grade:</strong> <span id="removeGradeName"></span></div>
                    <div class="mb-2"><strong>Section:</strong> <span id="removeSectionName"></span></div>
                    <div class="mb-2"><strong>Academic Year:</strong> <span id="removeAcademicYear"></span></div>
                    <div class="form-text text-muted">Removing adviser will only clear their advisory assignment. Subject classes will not be removed.</div>
                </div>

                </div>

                <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" id="removeConfirmBtn" class="btn btn-danger">Confirm Remove</button>
                </div>
            </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const adviserSelect = document.getElementById('adviserSelect');
  const gradeWrapper = document.getElementById('gradeWrapper');
  const gradeDropdown = document.getElementById('gradeLevelDropdown');
  const sectionWrapper = document.getElementById('sectionWrapper');
  const sectionDropdown = document.getElementById('sectionDropdown');
  const subjectWrapper = document.getElementById('subjectWrapper');
  const subjectDropdown = document.getElementById('subjectDropdown');
  const formAction = document.getElementById('formAction');
  const modeRadios = document.getElementsByName('mode');
  const confirmBtn = document.getElementById('confirmAssignBtn');

  // helper: current mode
  function currentMode() {
    return document.getElementById('modeReassign').checked ? 'reassign' : 'assign';
  }

  // show grade when adviser picked
  adviserSelect.addEventListener('change', function () {
    const adviserId = this.value;
    if (!adviserId) {
      gradeWrapper.style.display = 'none';
      sectionWrapper.style.display = 'none';
      subjectWrapper.style.display = 'none';
      return;
    }
    gradeWrapper.style.display = 'block';
    // reset grade and section
    gradeDropdown.value = '';
    sectionWrapper.style.display = 'none';
    sectionDropdown.innerHTML = '<option disabled selected>Select Section</option>';
    subjectWrapper.style.display = 'none';
    subjectDropdown.innerHTML = '<option value="">(Optional) Assign Subject</option>';

    // check adviser assignment
    fetch(`check_adviser.php?adviser_id=${adviserId}`)
      .then(r => r.json())
      .then(data => {
        if (data.assigned) {
          // adviser already assigned somewhere
          if (currentMode() === 'assign') {
            alert('This adviser is already assigned to section: ' + (data.section.section_name ?? 'Unknown') + '. Pick another adviser or use Re-Assign mode.');
            confirmBtn.disabled = true;
          } else {
            // reassign mode: allow proceeding, but still check not assigned elsewhere (we already found an assignment)
            // For safety we'll block reassign if adviser is already assigned (you asked adviser cannot be assigned elsewhere)
            alert('This adviser is already assigned to section: ' + (data.section.section_name ?? 'Unknown') + '. You must unassign them first to assign to another section.');
            confirmBtn.disabled = true;
          }
        } else {
          confirmBtn.disabled = false;
        }
      })
      .catch(err => {
        console.error(err);
      });
  });

  // When mode changes, reset state and update hidden action input
  modeRadios.forEach(r => r.addEventListener('change', function () {
    formAction.value = currentMode();
    // reset selection state
    gradeWrapper.style.display = adviserSelect.value ? 'block' : 'none';
    gradeDropdown.value = '';
    sectionWrapper.style.display = 'none';
    sectionDropdown.innerHTML = '<option disabled selected>Select Section</option>';
    subjectWrapper.style.display = 'none';
    subjectDropdown.innerHTML = '<option value="">(Optional) Assign Subject</option>';
    confirmBtn.disabled = false;
  }));

  // When grade changes, load sections and subjects
  gradeDropdown.addEventListener('change', function () {
    const gradeId = this.value;
    if (!gradeId) return;
    sectionWrapper.style.display = 'block';
    sectionDropdown.innerHTML = '<option disabled selected>Loading...</option>';

    const onlyUnassigned = currentMode() === 'assign' ? '1' : '0';
    fetch(`get_sections.php?grade_level_id=${gradeId}&only_unassigned=${onlyUnassigned}`)
      .then(r => r.json())
      .then(data => {
        sectionDropdown.innerHTML = '<option value="" disabled selected>Select Section</option>';
        if (!Array.isArray(data) || data.length === 0) {
          sectionDropdown.innerHTML = '<option disabled selected>No sections found</option>';
          return;
        }
        data.forEach(s => {
          const opt = document.createElement('option');
          opt.value = s.id;
          opt.textContent = s.section_name;
          sectionDropdown.appendChild(opt);
        });
      })
      .catch(err => {
        console.error(err);
        sectionDropdown.innerHTML = '<option disabled selected>Error loading sections</option>';
      });

    // populate optional subjects for the grade
    subjectWrapper.style.display = 'block';
    subjectDropdown.innerHTML = '<option value="">Loading subjects...</option>';
    fetch(`get_subjects.php?grade_level_id=${gradeId}`)
      .then(r => r.json())
      .then(data => {
        subjectDropdown.innerHTML = '<option value="">(Optional) Assign Subject</option>';
        if (!Array.isArray(data) || data.length === 0) {
          const opt = document.createElement('option');
          opt.value = '';
          opt.textContent = 'No subjects found';
          subjectDropdown.appendChild(opt);
          return;
        }
        data.forEach(sub => {
          const opt = document.createElement('option');
          opt.value = sub.id;
          opt.textContent = sub.subject_name;
          subjectDropdown.appendChild(opt);
        });
      })
      .catch(err => {
        console.error(err);
        subjectDropdown.innerHTML = '<option value="">Error loading subjects</option>';
      });
  });

  // final: before submit, ensure formAction set to current mode
  document.getElementById('assignAdviserForm').addEventListener('submit', function (e) {
    formAction.value = currentMode();
    // small client-side validation: ensure confirm enabled
    if (confirmBtn.disabled) {
      e.preventDefault();
      alert('Cannot submit: selected adviser appears to be already assigned. Please choose another adviser or unassign first.');
    }
  });

});

document.getElementById('adviserSelect').addEventListener('change', function() {
  let adviserId = this.value;
  if (!adviserId) return;

  fetch('get_adviser_details.php?adviser_id=' + adviserId)
    .then(res => res.json())
    .then(data => {
      document.getElementById('adviserDetails').style.display = 'block';
      document.getElementById('gradeLabel').textContent = data.grade;
      document.getElementById('sectionLabel').textContent = data.section;
      document.getElementById('yearLabel').textContent = data.academic_year;

      document.getElementById('gradeInput').value = data.grade_level_id;
      document.getElementById('sectionInput').value = data.section_id;
      document.getElementById('yearInput').value = data.academic_year_id;
    });
});
</script>


<script>
  // If user clicks back, force page to reload from server
  window.addEventListener("pageshow", function (event) {
    if (event.persisted) {
      window.location.reload();
    }
  });

function openAssignModal(sectionId) {
    document.getElementById('modalSectionId').value = sectionId;
    const modal = new bootstrap.Modal(document.getElementById('assignAdviserModal'));
    modal.show();
}
</script>
</body>
</html>