<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/tapinac/security/check_sidebar_access.php'; ?>

<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar -->
<div class="d-flex flex-column flex-shrink-0 p-3 text-white sidebar" style="width: 250px; height: 100vh; position: fixed; background-color: #1a1a1a;">
  <a href="dashboard.php" class="d-flex flex-column align-items-center mb-3 text-white text-decoration-none">
    <!-- School Logo -->
    <img src="/tapinac/assets/image/logo/logo.png" alt="School Logo" width="60" height="60" class="rounded-circle mb-2">

    <!-- School Name -->
    <span class="text-center text-white fs-6">Tapinac Elementary School</span>
  </a>
  <ul class="nav nav-pills flex-column mb-auto">
    
    <li class="nav-item">
      <a href="/tapinac/views/masterteacher/dashboard.php" class="nav-link text-white <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
        <i class="bi bi-speedometer2 me-2"></i> Dashboard
      </a>
    </li>

    <li class="nav-item dropdown">
      <a class="nav-link dropdown-toggle text-white <?= (basename($_SERVER['PHP_SELF']) == 'grade.php') ? 'active' : '' ?>" 
        href="#" 
        role="button" 
        data-bs-toggle="dropdown" 
        aria-expanded="false">
        <i class="bi bi-people me-2"></i> Class
      </a>
      <ul class="dropdown-menu">
        <?php
          $grades = ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];
          $currentGrade = $_GET['grade'] ?? '';
          foreach ($grades as $grade):
            $isActive = (strtolower($currentGrade) === strtolower($grade)) && basename($_SERVER['PHP_SELF']) === 'grade.php';
        ?>
          <li>
            <a class="dropdown-item <?= $isActive ? 'active' : '' ?>" 
              href="/tapinac/views/shared/grade.php?grade=<?= urlencode($grade) ?>">
              <?= $grade ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </li>


    <li>
      <a href="/tapinac/views/masterteacher/student_page.php" class="nav-link text-white <?= ($currentPage == 'student_page.php') ? 'active' : '' ?>">
        <i class="bi bi-person-lines-fill me-2"></i> Student List
      </a>
    </li>


<li class="nav-item dropdown">
  <a class="nav-link dropdown-toggle text-white <?= ($currentPage == 'view_report.php') ? 'active' : '' ?>" 
     href="#" 
     id="reportDropdown" 
     role="button" 
     data-bs-toggle="dropdown" 
     aria-expanded="false">
    <i class="bi bi-clipboard-data me-2"></i> Report
  </a>
  <ul class="dropdown-menu custom-report-bg" aria-labelledby="reportDropdown">
    <?php
      $quarters = [
        1 => "First Quarter",
        2 => "Second Quarter",
        3 => "Third Quarter",
        4 => "Fourth Quarter"
      ];
      $currentQuarter = $_GET['quarter'] ?? '';
      foreach ($quarters as $num => $label):
        $isActive = ($currentQuarter == $num) && basename($_SERVER['PHP_SELF']) === 'view_report.php';
    ?>
      <li>
        <a class="dropdown-item <?= $isActive ? 'active' : '' ?>" 
           href="view_report.php?quarter=<?= $num ?>">
          <?= $label ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</li>


    <li>
      <a href="../masterteacher/subject_management.php" class="nav-link text-white <?= ($currentPage == 'add_subject.php') ? 'active' : '' ?>">
        <i class="bi bi-book me-2"></i> Subject Management
      </a>
    </li>
    <li>
      <a href="/tapinac/views/masterteacher/student_archive.php" class="nav-link text-white <?= ($currentPage == 'student_archive.php') ? 'active' : '' ?>">
        <i class="bi bi-archive me-2"></i> Archive
      </a>
    </li>
    <li>
      <a href="/tapinac/views/masterteacher/update_info.php" class="nav-link text-white <?= ($currentPage == 'update_info.php') ? 'active' : '' ?>">
        <i class="bi bi-gear me-2"></i> Edit Account
      </a>
    </li>
    <li>
      <a href="/tapinac/views/masterteacher/student_certificate.php" class="nav-link text-white <?= ($currentPage == 'student_certificate.php') ? 'active' : '' ?>">
        <i class="bi bi-patch-check me-2"></i> Certificate
      </a>
    </li>

  </ul>

  <div class="mt-auto">
    <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#logoutConfirmModal">
      <i class="bi bi-box-arrow-right me-2"></i> Logout
    </button>
  </div>


</div>


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



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
