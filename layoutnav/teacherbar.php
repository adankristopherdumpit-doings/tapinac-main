<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/tapinac/security/check_sidebar_access.php'; ?>
<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
<div class="d-flex flex-column flex-shrink-0 text-white bg-green sidebar">
  <a href="dashboard.php" class="d-flex flex-column align-items-center mb-3 text-white text-decoration-none">
    <!-- School Logo -->
    <img src="/tapinac/assets/image/logo/logo.png" alt="School Logo" width="60" height="60" class="rounded-circle mb-2">

    <!-- School Name -->
    <span class="text-center text-white fs-6">Tapinac Elementary School</span>
  </a>
  <ul class="nav nav-pills flex-column mb-auto">

    <!-- Dashboard -->
    <li class="nav-item">
      <a href="dashboard.php"
         class="nav-link <?= $currentPage === 'dashboard.php' ? 'active text-white bg-primary' : 'text-white' ?>">
        <i class="bi bi-speedometer2 me-2"></i> Dashboard
      </a>
    </li>

    <?php
      // Database connection
      try {
          $pdo = new PDO('mysql:host=localhost;dbname=grading_system', 'root', '');
          $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      } catch (PDOException $e) {
          die("Database connection failed: " . $e->getMessage());
      }

      $teacherId = $_SESSION['user_id'] ?? null;
      $grades = ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];

      $assignedGrades = [];
      if ($teacherId) {
          $stmt = $pdo->prepare("
              SELECT DISTINCT gl.grade_name
              FROM subject_assignments sa
              JOIN subjects s ON sa.subject_id = s.id
              JOIN grade_levels gl ON s.grade_level_id = gl.id
              WHERE sa.teacher_id = ?");
          $stmt->execute([$teacherId]);
          $assignedGrades = $stmt->fetchAll(PDO::FETCH_COLUMN);
      }

      $assignedGradesNormalized = array_map('strtolower', array_map('trim', $assignedGrades));
      $currentGradeParam = $_GET['grade'] ?? '';



      // Check assigned grades/sections
      $userId = $_SESSION['user_id'] ?? null;
      $hasAssignedClasses = false;

      if ($userId) {
          $stmt = $pdo->prepare("
              SELECT COUNT(*) 
              FROM subject_assignments 
              WHERE teacher_id = ?
          ");
          $stmt->execute([$userId]);
          $count = $stmt->fetchColumn();
          $hasAssignedClasses = $count > 0;
      }
    ?>

    <!-- Class Dropdown -->
    <li class="nav-item dropdown">
      <a class="nav-link dropdown-toggle text-white <?= $currentPage === 'student_grade.php' ? 'active' : '' ?>"
         href="#"
         role="button"
         data-bs-toggle="dropdown"
         data-bs-auto-close="outside"
         aria-expanded="false">
        <i class="bi bi-people me-2"></i> Class
      </a>
      <ul class="dropdown-menu">
        <?php foreach ($grades as $grade):
          $gradeNormalized = strtolower(trim($grade));
          $isAssigned = in_array($gradeNormalized, $assignedGradesNormalized);
          $isCurrentGrade = strtolower(trim($currentGradeParam)) === $gradeNormalized;
          $linkClass = 'dropdown-item';
          if (!$isAssigned) {
            $linkClass .= ' disabled';
          } elseif ($isCurrentGrade && $currentPage === 'grade.php') {
            $linkClass .= ' active';
          }
        ?>
          <li>
            <a href="<?= $isAssigned ? 'student_grade.php?grade=' . urlencode($grade) : '#' ?>"
               class="<?= $linkClass ?>"
               <?= $isAssigned ? '' : 'tabindex="-1" aria-disabled="true"' ?>>
              <?= $grade ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </li>

    <!-- Student -->
    <li>
      <a href="<?= $hasAssignedClasses ? 'student_page.php' : '#' ?>"
        class="nav-link <?= ($currentPage == 'student_page.php') ? 'active text-white bg-primary' : ($hasAssignedClasses ? 'text-white' : 'text-muted') ?>"
        <?= !$hasAssignedClasses ? 'tabindex="-1" aria-disabled="true" title="No assigned classes yet"' : '' ?>>
        <i class="bi bi-person-lines-fill me-2"></i> Student
      </a>
    </li>



    <!-- Archive -->
    <li>
      <a href="student_archive.php" class="nav-link text-white <?= ($currentPage == 'student_archive.php') ? 'active' : '' ?>">
        <i class="bi bi-archive me-2"></i> Archive
      </a>
    </li>

    <!-- Edit Account -->
    <li>
      <a href="update_info.php" class="nav-link text-white <?= ($currentPage == 'update_info.php') ? 'active' : '' ?>">
        <i class="bi bi-gear me-2"></i> Edit Account
      </a>
    </li>
  </ul>

  <!-- Logout Button -->
  <div class="mt-auto p-3">
    <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#logoutConfirmModal">
      <i class="bi bi-box-arrow-right me-2"></i> Logout
    </button>
  </div>
</div>

<!-- Logout Modal -->
<div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-labelledby="logoutConfirmLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header bg-danger text-white rounded-top-4">
        <h5 class="modal-title fw-semibold" id="logoutConfirmLabel">
          <i class="bi bi-box-arrow-right me-2"></i>Confirm Logout
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body text-center py-4">
        <i class="bi bi-exclamation-triangle-fill text-warning fs-1 mb-3"></i>
        <p class="mb-0 fs-5 text-secondary">Are you sure you want to logout?</p>
      </div>

      <div class="modal-footer border-0 pb-4">
        <a href="../../logout.php" class="btn btn-danger w-100 rounded-3 fw-semibold">
          <i class="bi bi-box-arrow-right me-1"></i>Logout
        </a>
      </div>
    </div>
  </div>
</div>