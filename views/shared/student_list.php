<?php
session_start();

// Force no cache
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Path prefix for processing scripts
$role_path_prefix = '/tapinac/views/shared/multirole_backend/';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Role-based access
$role = $_SESSION['role'];
$full_name = $_SESSION['full_name'] ?? '';
$allowed_roles = ['adviser', 'teacher', 'masterteacher', 'principal'];

if (!in_array($role, $allowed_roles)) {
    header("Location: ../../security/unauthorized.php");
    exit();
}

// Header color per role
$header_color = in_array($role, ['adviser', 'teacher']) ? '#44A344' : '#1a1a1a';
$role_label = ucfirst($role);

// Navigation bar
$nav_file = "../../layoutnav/{$role}bar.php";
if (!file_exists($nav_file)) {
    $nav_file = "../../layoutnav/defaultbar.php";
}

// Masterteacher student filter
$where_clause = "";

// Masterteacher filter (existing)
if ($role === 'masterteacher') {
    if (!isset($_GET['teacher_id'])) {
        header("Location: teacher_listpage.php");
        exit();
    }
    $teacher_id = (int)$_GET['teacher_id'];
    $where_clause = "WHERE sections.teacher_id = $teacher_id";
}

// Adviser or Teacher filter
elseif (in_array($role, ['adviser', 'teacher'])) {
    $teacher_id = $_SESSION['user_id']; // Assuming your teachers/advisers have user_id
    $where_clause = "WHERE sections.teacher_id = $teacher_id";
}

// Admin / Principal sees all (no WHERE needed)


// Determine button color based on role
$btn_color = in_array($role, ['adviser', 'teacher']) ? '#44A344' : '#1a1a1a';
$btn_text_color = '#fff';


// Database connection
require_once '../../database/db_connection.php';


?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" />
<link rel="icon" type="image/png" href="../../assets/image/logo/logoone.png" />
<title>Student List</title>
<link rel="stylesheet" href="../../assets/css/sidebar.css">
<link rel="stylesheet" href="../../assets/css/all_role_style/style.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body>

<!-- Navigation Bar -->
<?php include $nav_file; ?>

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
        <h2 class="m-0" style="position: absolute; left: 50%; transform: translateX(-50%);">Student List</h2>
        <?php if (!empty($full_name)): ?>
            <span style="position: absolute; right: 20px;">
                Hello <?php echo htmlspecialchars($role_label . ' ' . $full_name); ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="container mt-4">
        <?php if ($role === 'masterteacher' && isset($teacher_id)): ?>
            <div class="d-flex justify-content-start mb-3">
                <a href="teacher_listpage.php" class="btn btn-secondary">← Back to Teacher List</a>
            </div>
        <?php endif; ?>

        <div class="card shadow">
            <div class="card-body">
                <!-- Buttons -->
                <div class="mb-3 d-flex flex-wrap gap-2">
                    <button type="button" class="btn" style="background-color: <?= $btn_color ?>; color: <?= $btn_text_color ?>;" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-plus-circle"></i> Add
                    </button>

                    <button type="button" class="btn" id="bulkAssignBtn" style="background-color: <?= $btn_color ?>; color: <?= $btn_text_color ?>;">
                        <i class="bi bi-check-circle"></i> Assign
                    </button>

                    <button type="button" class="btn" id="confirmAssignBtn" style="display:none; background-color: <?= $btn_color ?>; color: <?= $btn_text_color ?>;">
                        Confirm
                    </button>

                    <!-- Grade Filter -->
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" id="gradeFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-star"></i> <span id="gradeFilterLabel">Grade</span>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="gradeFilterBtn" id="gradeFilterMenu">
                            <li><a class="dropdown-item" href="#" data-grade="">All grades</a></li>
                            <li><a class="dropdown-item" href="#" data-grade="1">Grade 1</a></li>
                            <li><a class="dropdown-item" href="#" data-grade="2">Grade 2</a></li>
                            <li><a class="dropdown-item" href="#" data-grade="3">Grade 3</a></li>
                            <li><a class="dropdown-item" href="#" data-grade="4">Grade 4</a></li>
                            <li><a class="dropdown-item" href="#" data-grade="5">Grade 5</a></li>
                            <li><a class="dropdown-item" href="#" data-grade="6">Grade 6</a></li>
                        </ul>
                    </div>

                    <!-- Section Filter -->
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" id="sectionFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-diagram-3"></i> <span id="sectionFilterLabel">Sections</span>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="sectionFilterBtn" id="sectionFilterMenu">
                            <li><a class="dropdown-item" href="#" data-section="">All sections</a></li>
                        </ul>
                    </div>

                    <!-- Search -->
                    <div class="ms-auto">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search" style="width: 200px;">
                    </div>
                </div>

                <!-- Add Student Modal -->
                <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-header bg-primary text-white rounded-top-4">
                            <h5 class="modal-title fw-semibold" id="addModalLabel">
                            <i class="bi bi-person-plus-fill me-2"></i>Add New Student
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                            <form action="/tapinac/views/shared/multirole_backend/process_add.php" method="POST" class="p-4">
                                <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                                    <input type="text" name="fname" class="form-control form-control-sm rounded-3" placeholder="Enter first name" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Middle Name</label>
                                    <input type="text" name="mname" class="form-control form-control-sm rounded-3" placeholder="Enter middle name">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" name="lname" class="form-control form-control-sm rounded-3" placeholder="Enter last name" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Gender <span class="text-danger">*</span></label>
                                    <select name="gender" class="form-select form-select-sm rounded-3" required>
                                    <option value="" disabled selected>Select gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    </select>
                                </div>

                                <?php if (!in_array(strtolower($role), ['teacher', 'adviser'])): ?>
                                <!-- Only visible to admin/principal -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Grade Level <span class="text-danger">*</span></label>
                                    <select name="grade_level_id" class="form-select form-select-sm rounded-3" required>
                                    <option value="" disabled selected>Select grade</option>
                                    <?php
                                        $grades = $conn->query("SELECT * FROM grade_levels");
                                        while ($g = $grades->fetch_assoc()) {
                                        echo "<option value='{$g['id']}'>{$g['grade_name']}</option>";
                                        }
                                    ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Section <span class="text-danger">*</span></label>
                                    <select name="section_id" class="form-select form-select-sm rounded-3" required>
                                    <option value="" disabled selected>Select section</option>
                                    <?php
                                        $sections = $conn->query("SELECT * FROM sections");
                                        while ($s = $sections->fetch_assoc()) {
                                        echo "<option value='{$s['id']}'>{$s['section_name']}</option>";
                                        }
                                    ?>
                                    </select>
                                </div>
                                <?php endif; ?>
                                </div>

                                <div class="mt-4">
                                <button type="submit" class="btn btn-primary w-100 rounded-3 fw-semibold" id="saveBtn">
                                    <span class="spinner-border spinner-border-sm me-2 d-none" id="loadingSpinner" role="status" aria-hidden="true"></span>
                                    Add Student
                                </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Bulk Assign Modal -->
                <div class="modal fade" id="bulkAssignModal" tabindex="-1" aria-labelledby="bulkAssignModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <form action="<?php echo $role_path_prefix; ?>process_bulk_assign.php" method="POST">
                            <input type="hidden" name="student_ids" id="bulk_student_ids">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Assign Selected Students</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="bulk_grade" class="form-label">Grade Level</label>
                                        <select class="form-select" id="bulk_grade" name="grade_level_id" required>
                                            <option value="" disabled selected>Select grade</option>
                                            <?php
                                            $grades = mysqli_query($conn, "SELECT id, grade_name FROM grade_levels ORDER BY id");
                                            while($grade = mysqli_fetch_assoc($grades)) {
                                                echo '<option value="'. $grade['id'] .'">'. htmlspecialchars($grade['grade_name']) .'</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="bulk_section" class="form-label">Section</label>
                                        <select class="form-select" id="bulk_section" name="section_id" required>
                                            <option value="" disabled selected>Select section</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Assign</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Edit Student Modal -->
                <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="<?php echo $role_path_prefix; ?>process_edit.php" method="POST">
                                <input type="hidden" name="id" id="edit_id">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editModalLabel">Edit Student</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="edit_fname" class="form-label">First Name</label>
                                        <input type="text" name="fname" id="edit_fname" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_mname" class="form-label">Middle Name</label>
                                        <input type="text" name="mname" id="edit_mname" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_lname" class="form-label">Last Name</label>
                                        <input type="text" name="lname" id="edit_lname" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_gender" class="form-label">Gender</label>
                                        <select name="gender" id="edit_gender" class="form-select" required>
                                            <option value="" disabled>Select gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Student Table -->
                <?php
                // $teacher_id = $_SESSION['teacher_id']; // or however you get the teacher's ID

                $query = "SELECT DISTINCT
                    s.id,
                    s.fname,
                    s.mname,
                    s.lname,
                    s.gender,
                    s.grade_level_id,
                    s.section_id,
                    CONCAT(s.fname, ' ', COALESCE(s.mname, ''), ' ', s.lname) AS name,
                    COALESCE(gl.grade_name, 'Not assigned') AS grade,
                    COALESCE(sec.section_name, 'Not assigned') AS section,
                    CASE 
                        WHEN sec.teacher_id = ? THEN 'Adviser'
                        WHEN sa.teacher_id = ? THEN 'Subject Teacher'
                        ELSE 'No teacher assigned'
                    END AS teacher_role
                    FROM students s
                    LEFT JOIN grade_levels gl ON s.grade_level_id = gl.id
                    LEFT JOIN sections sec ON s.section_id = sec.id
                    LEFT JOIN subject_assignments sa 
                        ON sa.section_id = s.section_id 
                        AND sa.grade_level_id = s.grade_level_id
                    WHERE sec.teacher_id = ? 
                    OR sa.teacher_id = ?";

                $stmt = $conn->prepare($query);
                $stmt->bind_param("iiii", $teacher_id, $teacher_id, $teacher_id, $teacher_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if (!$result) {
                    echo "<div class='alert alert-danger'>Error fetching student data: " . $conn->error . "</div>";
                }

                ?>

                <div class="table-responsive shadow-sm mt-3">
                    <table id="studentTable" class="table table-hover align-middle text-center mb-0">
                        <thead class="bg-primary text-white">
                        <tr>
                            <th class="select-col" style="display: none;">Select</th>
                            <th class="d-none">ID</th>
                            <th class="d-none">Adviser</th>
                            <th>Name</th>
                            <th>Grade</th>
                            <th>Section</th>
                            <th>Gender</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody id="studentTableBody">
                        <?php while ($student = mysqli_fetch_assoc($result)): ?>
                            <tr
                            data-grade="<?= $student['grade_level_id'] ?? '' ?>"
                            data-section="<?= $student['section_id'] ?? '' ?>"
                            data-name="<?= htmlspecialchars($student['name']) ?>"
                            >
                            <td class="select-col" style="display: none;">
                                <input type="checkbox" class="form-check-input student-checkbox" value="<?= htmlspecialchars($student['id']) ?>">
                            </td>
                            <td class="fw-semibold text-secondary d-none"><?= htmlspecialchars($student['id']) ?></td>
                            <td class="d-none"><?= htmlspecialchars($student['adviser']) ?></td>
                            <td class="text-capitalize"><?= htmlspecialchars($student['name']) ?></td>
                            <td class="<?= $student['grade'] === 'Not assigned' ? 'text-danger fw-semibold' : 'text-dark' ?>">
                                <?= htmlspecialchars($student['grade']) ?>
                            </td>
                            <td class="<?= $student['section'] === 'Not assigned' ? 'text-danger fw-semibold' : 'text-dark' ?>">
                                <?= htmlspecialchars($student['section']) ?>
                            </td>
                            <td><?= htmlspecialchars($student['gender']) ?></td>
                            <td>
                                <div class="d-flex justify-content-center gap-2">
                                <button type="button"
                                        class="btn btn-outline-primary btn-sm px-3 editBtn"
                                        data-id="<?= $student['id'] ?>"
                                        data-fname="<?= htmlspecialchars($student['fname'] ?? '') ?>"
                                        data-mname="<?= htmlspecialchars($student['mname'] ?? '') ?>"
                                        data-lname="<?= htmlspecialchars($student['lname'] ?? '') ?>"
                                        data-gender="<?= htmlspecialchars($student['gender'] ?? '') ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editModal">
                                    Edit
                                </button>
                                <a href="process_drop.php?id=<?= $student['id'] ?>"
                                    onclick="return confirm('Are you sure you want to drop this student?')"
                                    class="btn btn-outline-danger btn-sm px-3">
                                    Drop
                                </a>
                                </div>
                            </td>
                            </tr>
                        <?php endwhile; mysqli_free_result($result); ?>
                        </tbody>
                    </table>
                </div>


                <!-- Import / Easy Monitor Buttons -->
                <div class="text-end mt-3">
                    <button class="btn" style="background-color: <?= $btn_color ?>; color: <?= $btn_text_color ?>;"><i class="bi bi-upload"></i> Import</button>
                    <?php if (in_array($role, ['masterteacher', 'adviser'])): ?>
                        <a href="../<?= $role ?>/teacher_listpage.php" class="btn" style="background-color: <?= $btn_color ?>; color: <?= $btn_text_color ?>;">Easy Monitor Classes</a>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>





<script>
  const form = document.querySelector('form');
  const saveBtn = document.getElementById('saveBtn');
  const spinner = document.getElementById('loadingSpinner');

  form.addEventListener('submit', () => {
    saveBtn.disabled = true;                // Disable button
    spinner.classList.remove('d-none');     // Show spinner
    saveBtn.innerText = 'Saving...';        // Change button text
    saveBtn.prepend(spinner);               // Keep spinner before text
  });
</script>





<!-- JS to dynamically load sections based on grade -->
<script>
const gradeSelect = document.getElementById('grade_level');
const sectionSelect = document.getElementById('section');
const bulkGradeSelect = document.getElementById('bulk_grade');
const bulkSectionSelect = document.getElementById('bulk_section');

function loadSections(gradeId, targetSelect) {
    targetSelect.innerHTML = '<option value="" disabled selected>Loading...</option>';
    fetch('../../views/shared/multirole_backend/get_sections.php?grade_id=' + gradeId)
        .then(res => res.json())
        .then(data => {
            targetSelect.innerHTML = '<option value="" disabled selected>Select section</option>';
            data.forEach(section => {
                const opt = document.createElement('option');
                opt.value = section.id;
                opt.textContent = section.section_name;
                targetSelect.appendChild(opt);
            });
        }).catch(err => {
            console.error(err);
            targetSelect.innerHTML = '<option value="" disabled selected>Error loading</option>';
        });
}

gradeSelect?.addEventListener('change', () => loadSections(gradeSelect.value, sectionSelect));
bulkGradeSelect?.addEventListener('change', () => loadSections(bulkGradeSelect.value, bulkSectionSelect));

// Assign toggle
document.getElementById('bulkAssignBtn').addEventListener('click', () => {
    const rows = document.querySelectorAll('.select-col');
    const confirmBtn = document.getElementById('confirmAssignBtn');
    const assignBtn = document.getElementById('bulkAssignBtn');
    const showing = rows[0].style.display === 'none';
    rows.forEach(r => r.style.display = showing ? '' : 'none');
    confirmBtn.style.display = showing ? 'inline-block' : 'none';
    assignBtn.textContent = showing ? 'Cancel' : 'Assign';
});

// Search filter
document.getElementById('searchInput').addEventListener('input', function() {
    const query = this.value.toLowerCase();
    document.querySelectorAll('#studentTableBody tr').forEach(row => {
        row.style.display = row.dataset.name.toLowerCase().includes(query) ? '' : 'none';
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
