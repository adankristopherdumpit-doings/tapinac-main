<?php
session_start();

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");  // Adjust path if needed
    exit();
}

// Allow only specific roles
$allowed_roles = ['adviser', 'masterteacher', 'principal', 'teacher'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../security/unauthorized.php");  // Adjust path if needed
    exit();
}

$role = $_SESSION['role'];
$full_name = $_SESSION['full_name'] ?? '';
$role_label = ucfirst($role);
$header_color = ($role === 'headmaster') ? '#1a1a1a' : '#44A344';

// Navigation bar path based on role
$nav_file = "../../layoutnav/{$role}bar.php";
if (!file_exists($nav_file)) {
    $nav_file = "../../layoutnav/defaultbar.php";
}

// Directory to save uploaded

// Background uploads directory
$uploadDir = "../../assets/image/certificate/uploads/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Handle uploaded background image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bgImage']) && $_FILES['bgImage']['error'] === UPLOAD_ERR_OK) {
    $tmpName = $_FILES['bgImage']['tmp_name'];
    $fileName = basename($_FILES['bgImage']['name']);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($fileExt, $allowedExt)) {
        $newFileName = uniqid('cert_bg_', true) . '.' . $fileExt;
        $targetPath = $uploadDir . $newFileName;

        if (move_uploaded_file($tmpName, $targetPath)) {
            $_SESSION['certificate_bg'] = $targetPath;
            unset($_SESSION['upload_error']);
        } else {
            $_SESSION['upload_error'] = "Failed to move uploaded file.";
        }
    } else {
        $_SESSION['upload_error'] = "Invalid file type. Allowed: jpg, jpeg, png, gif.";
    }

    // Redirect to avoid resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Use uploaded bg if exists in session else default
$backgroundImage = $_SESSION['certificate_bg'] ?? "../../assets/image/certificate/certificate.png";

// Selected student and performance
$studentName = $_POST['student'] ?? "";
$performance = $_POST['performance'] ?? "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Expires" content="0" />
  <link rel="icon" href="../../assets/image/logo/logo.png" type="image/png" />
  <link rel="stylesheet" href="../../assets/css/sidebar.css" />
  <link rel="stylesheet" href="../../assets/css/all_role_style/style.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
  <title>Certificate</title>
  <style>
    .certificate-page {
      position: relative;
      width: 100%;
      aspect-ratio: 297/210;
      max-width: 1100px;
      margin: auto;
      overflow: hidden;
    }

    .certificate-bg {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      z-index: 0;
    }

    .cert-layer {
      position: absolute;
      left: 50%;
      text-align: center;
      font-family: 'Georgia', serif;
      transform: translateX(-50%);
      z-index: 2;
    }

    .center-block {
      top: 50%;
      transform: translate(-50%, -60%);
    }

    .student-name {
      display: inline-block;
      min-width: 500px;
      font-size: 55px;
      font-weight: bold;
      border-bottom: 3px solid #222;
      color: #222;
      padding-bottom: 10px;
    }

    .performance-block {
      top: 63%;
    }

    .performance {
      font-size: 40px;
      font-weight: bold;
      color: #ed5656ff;
    }

    @media print {
      body, html {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        height: 100% !important;
      }

      body * {
        visibility: hidden !important;
      }

      .certificate-page, .certificate-page * {
        visibility: visible !important;
      }

      .certificate-page {
        position: fixed;
        top: 0.5in;
        left: 0.5in;
        width: calc(100% - 1in) !important;
        height: calc(100% - 1in) !important;
        margin: 0 !important;
        padding: 0 !important;
        page-break-inside: avoid;
      }

      .certificate-bg {
        position: absolute;
        inset: 0;
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
      }

      .no-print, .no-print-inline, .dropdown, .btn, nav, header, footer {
        display: none !important;
      }

      @page {
        size: A4 landscape;
        margin: 0in;
      }
    }
  </style>
</head>
<body>
  <?php include $nav_file; ?>

  <div class="main-content">
    <div style="background-color: <?= $header_color ?>; color: white; padding: 20px; display: flex; justify-content: center; align-items: center; position: relative; min-height: 70px;">
      <h2 class="m-0" style="position: absolute; left: 45%; transform: translateX(-50%);">Dashboard</h2>
      <?php if (!empty($full_name)): ?>
        <span style="position: absolute; right: 20px;">
          Hello <?= htmlspecialchars($role_label . ' ' . $full_name) ?>
        </span>
      <?php endif; ?>
    </div>

    <div class="container-fluid p-5" style="min-height: calc(100vh - 70px);">
      <div style="max-width: 1200px; margin: auto;">
        <?php if (!empty($_SESSION['upload_error'])): ?>
          <div class="alert alert-danger no-print">
            <?= htmlspecialchars($_SESSION['upload_error']) ?>
          </div>
          <?php unset($_SESSION['upload_error']); ?>
        <?php endif; ?>

        <div class="certificate-page">
          <img class="certificate-bg" id="certificateBg" src="<?= htmlspecialchars($backgroundImage) ?>" alt="Certificate Background" />

          <!-- Student Name -->
          <div class="cert-layer center-block d-inline-flex align-items-center gap-2">
            <span id="studentName" class="student-name">
              <?= !empty($studentName) ? htmlspecialchars($studentName) : "&nbsp;" ?>
            </span>
            <div class="dropdown no-print-inline">
              <button class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" type="button" id="studentDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-caret-down-fill"></i>
                <i class="bi bi-list"></i>
              </button>
              <ul class="dropdown-menu" aria-labelledby="studentDropdown">
                <?php
                $students = ["Juan Dela Cruz", "Maria Santos", "Pedro Reyes", "Ana Lopez"];
                foreach ($students as $s) {
                  echo '<li>
                    <form method="POST" style="margin:0;">
                      <button type="submit" name="student" value="' . htmlspecialchars($s) . '" class="dropdown-item ' . (($studentName == $s) ? "active" : "") . '">' . htmlspecialchars($s) . '</button>
                    </form>
                  </li>';
                }
                ?>
              </ul>
            </div>
          </div>

          <!-- Performance -->
          <div class="cert-layer performance-block d-inline-flex align-items-center gap-2">
            <span id="performanceLabel" class="performance">
              <?= !empty($performance) ? htmlspecialchars($performance) : "Select Performance" ?>
            </span>
            <div class="dropdown no-print-inline">
              <button class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" type="button" id="performanceDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-caret-down-fill"></i>
                <i class="bi bi-list"></i>
              </button>
              <ul class="dropdown-menu" aria-labelledby="performanceDropdown">
                <?php
                $performances = ["Excellent", "Very Good", "Good", "Sample"];
                foreach ($performances as $p) {
                  echo '<li>
                    <form method="POST" style="margin:0;">
                      <input type="hidden" name="student" value="' . htmlspecialchars($studentName) . '">
                      <button type="submit" name="performance" value="' . htmlspecialchars($p) . '" class="dropdown-item ' . (($performance == $p) ? "active" : "") . '">' . htmlspecialchars($p) . '</button>
                    </form>
                  </li>';
                }
                ?>
              </ul>
            </div>
          </div>
        </div>

        <div class="mt-4 d-flex justify-content-end gap-3 no-print">
          <!-- Button trigger modal -->
          <button type="button" class="btn btn-primary m-0" data-bs-toggle="modal" data-bs-target="#uploadModal">
            Change Certificate Format
          </button>

          <button class="btn btn-warning shadow-sm m-0" onclick="window.print()">
            Print Certificate
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Upload Modal -->
  <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <form method="POST" enctype="multipart/form-data" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="uploadModalLabel">Upload New Certificate Background</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="file" name="bgImage" accept="image/*" required />
          <small class="text-muted">Allowed file types: jpg, jpeg, png, gif</small>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Upload</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
