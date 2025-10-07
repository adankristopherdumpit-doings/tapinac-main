<?php
session_start();



header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Allow access to multiple roles
$allowed_roles = ['adviser', 'masterteacher', 'principal', 'teacher'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../security/unauthorized.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];

try {
    $pdo = new PDO('mysql:host=localhost;dbname=grading_system', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : 0;
$currentGrade = isset($_GET['grade']) ? htmlspecialchars($_GET['grade']) : '';

// Fetch subjects only for this teacher and this section
$stmt = $pdo->prepare("
    SELECT subj.subject_name, s.section_name
    FROM subject_assignments sa
    JOIN subjects subj ON sa.subject_id = subj.id
    JOIN sections s ON sa.section_id = s.id
    WHERE sa.teacher_id = ? AND sa.section_id = ?
");
$stmt->execute([$teacher_id, $section_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
$section_name = $subjects[0]['section_name'] ?? 'Unknown Section';

?>

<!DOCTYPE html>
<html>
<head>
    <title>Section <?= htmlspecialchars($section_name) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../assets/image/logo/logo.png" />
    <link rel="stylesheet" href="../../assets/css/teacher.css">
    <!-- Bootstrap 5 CSS & Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <link rel="stylesheet" href="../../assets/css/sidebar.css" />
    <link rel="stylesheet" href="../../assets/css/all_role_style/style.css" />
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
    <div style="background-color: #44A344; color: white; padding: 20px 20px; display: flex; justify-content: center; align-items: center; position: relative; min-height: 70px;">
        <h2 class="m-0" style="position: absolute; left: 50%; transform: translateX(-50%);"> Section <?= htmlspecialchars($section_name) ?></h2>
        <?php if (isset($_SESSION['full_name'])): ?>
            <span style="position: absolute; right: 20px;">
                Hello <?= htmlspecialchars($role_label . ' ' . $full_name) ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="container-fluid py-3">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="container mt-4">
                    <div class="d-flex justify-content-between mb-3">
                        <a href="student_grade.php?grade=<?= urlencode($currentGrade) ?>" class="btn btn-secondary">Back</a>
                    </div>

                    <div class="table-responsive">
                        <?php if (!empty($subjects)): ?>
                            <table class="table table-bordered table-hover w-100 text-center mx-auto">
                                <thead class="table-info">
                                    <tr>
                                        <th>Subject Name</th>
                                        <th style="width: 150px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subjects as $subj): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($subj['subject_name']) ?></td>
                                            <td>
                                                <div class="dropdown position-static">
                                                    <button class="btn btn-info btn-sm dropdown-toggle" type="button" id="dropdownMenu<?= $section_id ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                        View Grades
                                                    </button>
                                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenu<?= $section_id ?>">
                                                        <?php 
                                                            $quarters = ['First', 'Second', 'Third', 'Fourth'];
                                                            foreach ($quarters as $q) {
                                                                echo '<li>
                                                                    <a class="dropdown-item" 
                                                                    href="student_quarter.php?subject=' . urlencode($subj['subject_name']) . '&quarter=' . urlencode($q) . '">' 
                                                                    . $q . ' Quarter
                                                                    </a>
                                                                </li>';
                                                            }
                                                        ?>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="alert alert-warning text-center w-100">No subject assignments found for this section.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


</body>
</html>
